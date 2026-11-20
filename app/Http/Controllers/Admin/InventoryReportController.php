<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Queries\InventoryReportQuery;
use App\Domain\Inventory\Queries\StockFamilyPage;
use App\Exports\InventoryReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockFamilyTableRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class InventoryReportController extends Controller
{
    public function index(Request $request, InventoryReportQuery $query): View
    {
        [$fromDate, $toDate] = $this->period($request);
        $summary = $query->summary($fromDate, $toDate);

        return view('super.reports.inventory', compact('summary', 'fromDate', 'toDate'));
    }

    public function dt(StockFamilyTableRequest $request, InventoryReportQuery $query, StockFamilyPage $families): JsonResponse
    {
        [$fromDate, $toDate] = $this->period($request);
        $inputs = $request->validated();
        $page = $families->get($inputs['start'], $inputs['length'], trim($inputs['search']['value'] ?? ''));
        $ids = $page['ids'];
        $groups = $query->groups($fromDate, $toDate, $ids->all())->keyBy('id');
        $rows = $ids->map(fn ($id) => $groups->get($id))->filter()->map(function (array $group) {
            $group['detail_html'] = view('super.reports.partials.inventory-sku-details', ['group' => $group])->render();
            unset($group['details']);
            return $group;
        })->values();

        return response()->json([
            'draw' => (int) $inputs['draw'],
            'recordsTotal' => $page['total'],
            'recordsFiltered' => $page['filtered'],
            'data' => $rows,
        ]);
    }

    public function export(Request $request)
    {
        [$fromDate, $toDate] = $this->period($request);
        return Excel::download(new InventoryReportExport($fromDate, $toDate), "laporan-inventory-{$fromDate}-{$toDate}.xlsx");
    }

    private function period(Request $request): array
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? now()->toDateString();
        if ($fromDate > $toDate) {
            throw ValidationException::withMessages(['to_date' => 'Tanggal akhir harus sama atau setelah tanggal awal.']);
        }
        return [$fromDate, $toDate];
    }
}
