<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Pos\Queries\PosSalesReportQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosSalesReportController extends Controller
{
    private const PAYMENT_METHODS = [
        'cash' => 'Tunai',
        'bank_transfer' => 'Transfer',
        'card' => 'Kartu',
    ];

    public function index(Request $request, PosSalesReportQuery $query): View
    {
        $filters = $this->filters($request);
        $sales = $query->sales($filters)->get();
        $summary = $this->summary($sales);
        $cashiers = $query->cashiers();
        $sessions = $query->sessions();
        $paymentMethods = self::PAYMENT_METHODS;

        return view('super.reports.pos-sales', compact('sales', 'summary', 'filters', 'cashiers', 'sessions', 'paymentMethods'));
    }

    public function export(Request $request, PosSalesReportQuery $query)
    {
        $filters = $this->filters($request);
        $sales = $query->sales($filters)->get();
        $filename = "laporan-penjualan-pos-{$filters['from_date']}-{$filters['to_date']}.csv";
        $paymentMethods = self::PAYMENT_METHODS;

        return response()->streamDownload(function () use ($sales, $paymentMethods) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'No Transaksi',
                'Tanggal',
                'Kasir',
                'Sesi POS',
                'Kode Customer',
                'Customer',
                'Jumlah Item',
                'Status',
                'Alasan Void',
                'Subtotal',
                'Diskon',
                'Pajak',
                'Total',
                'Dibayar',
                'Kembalian',
                'Metode Pembayaran',
            ]);
            foreach ($sales as $sale) {
                fputcsv($output, [
                    $sale->number,
                    $sale->sold_at?->format('Y-m-d H:i:s'),
                    $sale->cashier?->name,
                    $sale->session?->number,
                    $sale->customer?->code,
                    $sale->customer?->name ?? 'Walk-in Customer',
                    $sale->items->sum('quantity'),
                    $sale->status,
                    $sale->void_reason,
                    $sale->subtotal,
                    $sale->discount_amount,
                    $sale->tax_amount,
                    $sale->total_amount,
                    $sale->paid_amount,
                    $sale->change_amount,
                    $paymentMethods[$sale->payment_method] ?? $sale->payment_method,
                ]);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
            'cashier_id' => ['nullable', 'integer', 'exists:users,id'],
            'pos_session_id' => ['nullable', 'integer', 'exists:pos_sessions,id'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,card'],
        ]);
        $filters['from_date'] = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $filters['to_date'] = $filters['to_date'] ?? now()->toDateString();

        if ($filters['from_date'] > $filters['to_date']) {
            throw ValidationException::withMessages(['to_date' => 'Tanggal akhir harus sama atau setelah tanggal awal.']);
        }

        return $filters;
    }

    private function summary(Collection $sales): array
    {
        $completed = $sales->where('status', 'completed');
        $voided = $sales->where('status', 'voided');

        return [
            'transaction_count' => $completed->count(),
            'subtotal' => round($completed->sum('subtotal'), 2),
            'discount' => round($completed->sum('discount_amount'), 2),
            'tax' => round($completed->sum('tax_amount'), 2),
            'total' => round($completed->sum('total_amount'), 2),
            'paid' => round($completed->sum('paid_amount'), 2),
            'change' => round($completed->sum('change_amount'), 2),
            'voided_count' => $voided->count(),
            'voided_total' => round($voided->sum('total_amount'), 2),
        ];
    }
}
