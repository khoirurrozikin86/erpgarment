<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Accounting\Queries\AccountsPayableQuery;
use App\Domain\Accounting\Queries\CashBankQuery;
use App\Domain\Accounting\Services\AccountsPayableService;
use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AccountPayableController extends Controller
{
    public function index(CashBankQuery $cashBank): View
    {
        return view('super.account-payable.index', ['cashBankAccounts' => $cashBank->activeAccounts()]);
    }

    public function dt(Request $request, AccountsPayableQuery $query): JsonResponse
    {
        $filters = $this->dateFilters($request);
        return DataTables::eloquent($query->receipts($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('supplier_name', fn (GoodsReceipt $receipt) => $receipt->supplier?->name ?? 'Supplier dihapus')
            ->addColumn('purchase_order_number', fn (GoodsReceipt $receipt) => $receipt->purchaseOrder?->number ?? '—')
            ->addColumn('receipt_amount_value', fn (GoodsReceipt $receipt) => round((float) ($receipt->receipt_amount ?? 0), 2))
            ->addColumn('returned_amount_value', fn (GoodsReceipt $receipt) => round((float) ($receipt->returned_amount ?? 0), 2))
            ->addColumn('paid_amount_value', fn (GoodsReceipt $receipt) => round((float) ($receipt->paid_amount ?? 0), 2))
            ->addColumn('outstanding_amount', fn (GoodsReceipt $receipt) => max(0, round((float) ($receipt->receipt_amount ?? 0) - (float) ($receipt->returned_amount ?? 0) - (float) ($receipt->paid_amount ?? 0), 2)))
            ->addColumn('payment_status', function (GoodsReceipt $receipt) {
                $paid = (float) ($receipt->paid_amount ?? 0);
                $due = max(0, round((float) ($receipt->receipt_amount ?? 0) - (float) ($receipt->returned_amount ?? 0)));
                if ($due <= $paid) return '<span class="badge bg-success">Lunas</span>';
                return $paid > 0 ? '<span class="badge bg-info text-dark">Sebagian</span>' : '<span class="badge bg-warning text-dark">Belum Bayar</span>';
            })
            ->addColumn('actions', function (GoodsReceipt $receipt) {
                $due = max(0, round((float) ($receipt->receipt_amount ?? 0) - (float) ($receipt->returned_amount ?? 0) - (float) ($receipt->paid_amount ?? 0), 2));
                $buttons = '';
                if ($due > 0 && auth()->user()->can('account-payable.pay')) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-outline-success btn-pay-supplier" data-url="' . e(route('super.account-payable.payments.store', $receipt)) . '" data-number="' . e($receipt->number) . '" data-outstanding="' . $due . '" title="Catat Pembayaran"><i data-feather="dollar-sign"></i></button> ';
                }
                $buttons .= '<button type="button" class="btn btn-sm btn-outline-secondary btn-supplier-payment-history" data-url="' . e(route('super.account-payable.payments.history', $receipt)) . '" data-number="' . e($receipt->number) . '" title="Riwayat Pembayaran"><i data-feather="clock"></i></button>';
                return $buttons;
            })
            ->rawColumns(['payment_status', 'actions'])->toJson();
    }

    public function pay(Request $request, GoodsReceipt $goodsReceipt, AccountsPayableService $service): JsonResponse
    {
        $data = $request->validate([
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'cash_bank_account_id' => ['required', 'integer', 'exists:cash_bank_accounts,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $payment = $service->pay($goodsReceipt, $data, (int) $request->user()->id);
        return response()->json(['message' => "Pembayaran {$payment->number} berhasil dicatat.", 'payment_number' => $payment->number], 201);
    }

    public function history(GoodsReceipt $goodsReceipt, AccountsPayableQuery $query): JsonResponse
    {
        $receipt = $query->find($goodsReceipt->id);
        $gross = (float) $receipt->items()->sum('line_total');
        $returned = (float) $receipt->purchaseReturns()->sum('total_amount');
        $paid = (float) $receipt->supplierPayments->sum('amount');
        return response()->json([
            'number' => $receipt->number, 'supplier' => $receipt->supplier?->name ?? 'Supplier dihapus',
            'total_amount' => round($gross, 2), 'returned_amount' => round($returned, 2),
            'paid_amount' => round($paid, 2), 'outstanding_amount' => max(0, round($gross - $returned - $paid, 2)),
            'payments' => $receipt->supplierPayments->map(fn ($payment) => [
                'number' => $payment->number, 'payment_date' => $payment->payment_date?->format('d/m/Y'),
                'amount' => (float) $payment->amount, 'account' => $payment->cashBankAccount?->name ?? 'Akun dihapus',
                'reference_number' => $payment->reference_number ?: '—', 'notes' => $payment->notes ?: '—',
                'payer' => $payment->payer?->name ?? 'User dihapus',
            ]),
        ]);
    }

    private function dateFilters(Request $request): array
    {
        return $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
            }],
        ]);
    }
}
