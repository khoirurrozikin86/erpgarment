<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Sales\Queries\SalesReportQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalesReportController extends Controller
{
    public function index(Request $request, SalesReportQuery $query): View
    {
        [$fromDate, $toDate] = $this->period($request);
        $invoices = $query->builder($fromDate, $toDate)->get();
        $summary = [
            'subtotal' => round($invoices->sum('subtotal'), 2),
            'tax' => round($invoices->sum('tax_amount'), 2),
            'total' => round($invoices->sum('total_amount'), 2),
            'paid' => round($invoices->sum(fn ($invoice) => (float) $invoice->paid_total), 2),
            'outstanding' => round($invoices->sum(fn ($invoice) => max(0, (float) $invoice->total_amount - (float) $invoice->paid_total)), 2),
        ];

        return view('super.reports.sales', compact('invoices', 'summary', 'fromDate', 'toDate'));
    }

    public function export(Request $request, SalesReportQuery $query)
    {
        [$fromDate, $toDate] = $this->period($request);
        $invoices = $query->builder($fromDate, $toDate)->get();
        $filename = "laporan-penjualan-{$fromDate}-{$toDate}.csv";

        return response()->streamDownload(function () use ($invoices) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['No Invoice', 'Tanggal', 'Kode Customer', 'Customer', 'Subtotal', 'Pajak', 'Total', 'Dibayar', 'Sisa Piutang']);
            foreach ($invoices as $invoice) {
                $paid = (float) $invoice->paid_total;
                fputcsv($output, [
                    $invoice->number, $invoice->invoice_date?->format('Y-m-d'), $invoice->customer?->code,
                    $invoice->customer?->name, $invoice->subtotal, $invoice->tax_amount,
                    $invoice->total_amount, $paid, max(0, (float) $invoice->total_amount - $paid),
                ]);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
