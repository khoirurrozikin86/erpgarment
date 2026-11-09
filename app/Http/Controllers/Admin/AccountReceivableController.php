<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Accounting\Queries\AccountsReceivableQuery;
use App\Domain\Accounting\Services\AccountsReceivableService;
use App\Domain\Accounting\Queries\CashBankQuery;
use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AccountReceivableController extends Controller
{
    public function index(CashBankQuery $cashBank): View
    {
        return view('super.account-receivable.index', ['cashBankAccounts' => $cashBank->activeAccounts()]);
    }

    public function dt(Request $request, AccountsReceivableQuery $query): JsonResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) {
                    $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
                }
            }],
        ]);

        return DataTables::eloquent($query->invoices($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('delivery_number', fn (SalesInvoice $invoice) => $invoice->delivery?->number ?? '—')
            ->addColumn('customer_name', fn (SalesInvoice $invoice) => $invoice->customer?->name ?? 'Customer dihapus')
            ->addColumn('paid_amount', fn (SalesInvoice $invoice) => round((float) ($invoice->paid_amount ?? 0), 2))
            ->addColumn('outstanding_amount', fn (SalesInvoice $invoice) => max(0, round((float) $invoice->total_amount - (float) ($invoice->paid_amount ?? 0), 2)))
            ->addColumn('payment_status', function (SalesInvoice $invoice) {
                $paid = (float) ($invoice->paid_amount ?? 0);
                $outstanding = max(0, round((float) $invoice->total_amount - $paid, 2));
                if ($outstanding <= 0) return '<span class="badge bg-success">Lunas</span>';
                if ($paid > 0) return '<span class="badge bg-info text-dark">Sebagian</span>';
                if ($invoice->due_date && $invoice->due_date->toDateString() < today()->toDateString()) return '<span class="badge bg-warning text-dark">Jatuh Tempo</span>';
                return '<span class="badge bg-secondary">Belum Bayar</span>';
            })
            ->addColumn('actions', function (SalesInvoice $invoice) {
                $buttons = '';
                $outstanding = max(0, round((float) $invoice->total_amount - (float) ($invoice->paid_amount ?? 0), 2));
                if ($outstanding > 0 && auth()->user()->can('account-receivable.receive')) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-outline-success btn-receive-payment" data-url="' . e(route('super.account-receivable.payments.store', $invoice)) . '" data-number="' . e($invoice->number) . '" data-outstanding="' . $outstanding . '" title="Catat Pembayaran"><i data-feather="dollar-sign"></i></button> ';
                }
                $buttons .= '<button type="button" class="btn btn-sm btn-outline-secondary btn-payment-history" data-url="' . e(route('super.account-receivable.payments.history', $invoice)) . '" data-number="' . e($invoice->number) . '" title="Riwayat Pembayaran"><i data-feather="clock"></i></button>';
                return $buttons;
            })
            ->rawColumns(['payment_status', 'actions'])
            ->toJson();
    }

    public function receivePayment(Request $request, SalesInvoice $salesInvoice, AccountsReceivableService $service): JsonResponse
    {
        $data = $request->validate([
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'cash_bank_account_id' => ['required', 'integer', 'exists:cash_bank_accounts,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'payment_method' => ['required', 'in:cash,bank_transfer,card,other'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payment = $service->receivePayment($salesInvoice, $data, (int) $request->user()->id);
        return response()->json(['message' => "Pembayaran {$payment->number} berhasil dicatat.", 'payment_number' => $payment->number], 201);
    }

    public function paymentHistory(SalesInvoice $salesInvoice): JsonResponse
    {
        abort_unless($salesInvoice->status === 'issued', 404);
        $salesInvoice->load(['payments.receiver:id,name', 'customer:id,name']);
        $paid = (float) $salesInvoice->payments->sum('amount');

        return response()->json([
            'number' => $salesInvoice->number,
            'customer' => $salesInvoice->customer?->name ?? 'Customer dihapus',
            'total_amount' => (float) $salesInvoice->total_amount,
            'paid_amount' => round($paid, 2),
            'outstanding_amount' => max(0, round((float) $salesInvoice->total_amount - $paid, 2)),
            'payments' => $salesInvoice->payments->map(fn ($payment) => [
                'number' => $payment->number,
                'payment_date' => $payment->payment_date?->format('d/m/Y'),
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number ?: '—',
                'notes' => $payment->notes ?: '—',
                'receiver' => $payment->receiver?->name ?? 'User dihapus',
            ]),
        ]);
    }
}
