<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Purchasing\Queries\PurchaseReportQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseReportController extends Controller
{
    public function index(Request $request, PurchaseReportQuery $query): View
    {
        [$fromDate, $toDate] = $this->period($request);
        $orders = $query->orders($fromDate, $toDate)->get();
        $receipts = $query->receipts($fromDate, $toDate)->get();
        $returns = $query->returns($fromDate, $toDate)->get();
        $summary = [
            'order_count' => $orders->count(),
            'ordered_value' => round($orders->sum('total_amount'), 2),
            'receipt_count' => $receipts->count(),
            'received_value' => round($receipts->sum('received_value'), 2),
            'return_value' => round($returns->sum('total_amount'), 2),
            'net_received_value' => round($receipts->sum('received_value') - $returns->sum('total_amount'), 2),
        ];

        return view('super.reports.purchase', compact('orders', 'receipts', 'returns', 'summary', 'fromDate', 'toDate'));
    }

    public function export(Request $request, PurchaseReportQuery $query)
    {
        [$fromDate, $toDate] = $this->period($request);
        $receipts = $query->receipts($fromDate, $toDate)->get();
        $returns = $query->returns($fromDate, $toDate)->get();
        $filename = "laporan-pembelian-{$fromDate}-{$toDate}.csv";

        return response()->streamDownload(function () use ($receipts, $returns) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Jenis', 'Nomor Dokumen', 'Tanggal', 'No Purchase Order', 'Kode Supplier', 'Supplier', 'Nilai']);
            foreach ($receipts as $receipt) {
                fputcsv($output, [
                    'Penerimaan', $receipt->number, $receipt->received_at?->format('Y-m-d'), $receipt->purchaseOrder?->number,
                    $receipt->supplier?->code, $receipt->supplier?->name, $receipt->received_value,
                ]);
            }
            foreach ($returns as $return) {
                fputcsv($output, [
                    'Retur', $return->number, $return->returned_at?->format('Y-m-d'), $return->goodsReceipt?->purchaseOrder?->number,
                    $return->supplier?->code, $return->supplier?->name, -1 * (float) $return->total_amount,
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
