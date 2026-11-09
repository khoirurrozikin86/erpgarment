<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Queries\InventoryReportQuery;
use App\Exports\InventoryReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class InventoryReportController extends Controller
{
    public function index(Request $request, InventoryReportQuery $query): View
    {
        [$fromDate, $toDate] = $this->period($request);
        $products = $query->builder($fromDate, $toDate)->get()->map(function ($product) {
            $opening = (float) ($product->opening_balance ?? 0);
            $incoming = (float) ($product->quantity_in ?? 0);
            $outgoing = abs((float) ($product->quantity_out ?? 0));
            $product->report_opening = $opening;
            $product->report_incoming = $incoming;
            $product->report_outgoing = $outgoing;
            $product->report_closing = $opening + $incoming - $outgoing;
            $threshold = (float) ($product->reorder_point > 0 ? $product->reorder_point : $product->min_stock);
            $product->report_status = $product->report_closing <= 0 && $threshold > 0
                ? 'Habis'
                : ($threshold > 0 && $product->report_closing <= $threshold ? 'Stok Rendah' : 'Aman');
            return $product;
        });
        $summary = [
            'products' => $products->count(),
            'out_of_stock' => $products->where('report_status', 'Habis')->count(),
            'low_stock' => $products->where('report_status', 'Stok Rendah')->count(),
            'safe_stock' => $products->where('report_status', 'Aman')->count(),
            'moved_products' => $products->filter(fn ($product) => $product->report_incoming > 0 || $product->report_outgoing > 0)->count(),
        ];

        return view('super.reports.inventory', compact('products', 'summary', 'fromDate', 'toDate'));
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
