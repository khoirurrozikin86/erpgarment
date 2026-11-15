<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Queries\CapitalGoodsMutationReportQuery;
use App\Exports\CapitalGoodsMutationReportExport;
use App\Http\Controllers\Controller;
use App\Domain\Companies\Services\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class CapitalGoodsMutationReportController extends Controller
{
    private function filters(Request $request): array
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'], 'to_date' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'], 'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
        $filters['from_date'] ??= now()->startOfMonth()->toDateString();
        $filters['to_date'] ??= now()->toDateString();
        if ($filters['from_date'] > $filters['to_date']) throw ValidationException::withMessages(['to_date' => 'Tanggal akhir harus sama atau setelah tanggal awal.']);
        $filters['_company_id'] = app(CompanyContext::class)->id();
        return $filters;
    }

    public function index(Request $request, CapitalGoodsMutationReportQuery $query)
    {
        $filters = $this->filters($request);
        $items = $query->builder($filters)->paginate($filters['per_page'] ?? 10)->withQueryString();
        return view('super.reports.capital-goods-mutation', compact('filters', 'items'));
    }

    public function export(Request $request)
    {
        return Excel::download(new CapitalGoodsMutationReportExport($this->filters($request)), 'laporan-mutasi-barang-modal.xlsx');
    }
}
