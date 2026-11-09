<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Accounting\Queries\CashBankQuery;
use App\Domain\Accounting\Services\CashBankService;
use App\Http\Controllers\Controller;
use App\Models\CashBankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CashBankController extends Controller
{
    public function index(CashBankQuery $query): View
    {
        return view('super.cash-bank.index', [
            'accounts' => $query->activeAccounts(),
            'cashBankChartAccounts' => $query->activeCashBankChartAccounts(),
            'chartAccounts' => $query->activeTransactionChartAccounts(),
        ]);
    }

    public function accountsDt(CashBankQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->accounts())
            ->addColumn('type_label', fn(CashBankAccount $account) => $account->type === 'cash' ? 'Kas' : 'Bank')
            ->addColumn('account_info', fn(CashBankAccount $account) => $account->type === 'bank'
                ? trim(($account->bank_name ?: 'Bank') . ' · ' . ($account->account_number ?: 'No. rekening belum diisi'), ' ·')
                : 'Kas tunai')
            ->addColumn('chart_account_label', fn(CashBankAccount $account) => $account->chartOfAccount ? $account->chartOfAccount->code . ' · ' . $account->chartOfAccount->name : 'Belum dipetakan')
            ->addColumn('balance', fn(CashBankAccount $account) => round((float) $account->opening_balance + (float) ($account->incoming_total ?? 0) - (float) ($account->outgoing_total ?? 0), 2))
            ->addColumn('status_label', fn(CashBankAccount $account) => $account->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>')
            ->rawColumns(['status_label'])->toJson();
    }

    public function transactionsDt(Request $request, CashBankQuery $query): JsonResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
            }],
        ]);

        return DataTables::eloquent($query->transactions($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('account_name', fn($transaction) => $transaction->account?->name ?? 'Akun dihapus')
            ->addColumn('counter_account_name', fn($transaction) => $transaction->pos_sale_void_id ? 'Void Penjualan POS' : ($transaction->customer_return_id ? 'Retur Penjualan' : ($transaction->sales_invoice_payment_id ? 'Piutang Usaha' : ($transaction->supplier_payment_id ? 'Utang Supplier' : ($transaction->pos_session_id ? 'POS' : ($transaction->counterAccount ? $transaction->counterAccount->code . ' · ' . $transaction->counterAccount->name : '—'))))))
            ->addColumn('source_number', function ($transaction) {
                if ($transaction->posSaleVoid?->number) return 'Void POS ' . $transaction->posSaleVoid->number;
                if ($transaction->customerReturn?->number) return 'CRT ' . $transaction->customerReturn->number;
                if ($transaction->payment?->invoice?->number) return $transaction->payment->invoice->number;
                if ($transaction->supplierPayment?->goodsReceipt?->number) return 'GR ' . $transaction->supplierPayment->goodsReceipt->number;
                if ($transaction->posSale?->number) return 'POS ' . $transaction->posSale->number;
                if ($transaction->posSession?->number) return 'POS ' . $transaction->posSession->number;
                return 'Manual';
            })
            ->addColumn('direction_label', fn($transaction) => $transaction->direction === 'in'
                ? '<span class="badge bg-success">Masuk</span>'
                : '<span class="badge bg-danger">Keluar</span>')
            ->addColumn('amount_signed', fn($transaction) => ($transaction->direction === 'out' ? -1 : 1) * (float) $transaction->amount)
            ->addColumn('creator_name', fn($transaction) => $transaction->creator?->name ?? '—')
            ->addColumn('actions', function ($transaction) {
                if ($transaction->sales_invoice_payment_id || $transaction->supplier_payment_id || $transaction->customer_return_id || $transaction->pos_sale_void_id || $transaction->pos_session_id || $transaction->pos_sale_id) {
                    $title = $transaction->pos_sale_void_id ? 'Dikelola melalui POS Void' : ($transaction->customer_return_id ? 'Dikelola melalui Customer Return' : ($transaction->sales_invoice_payment_id ? 'Dikelola melalui Account Receivable' : ($transaction->supplier_payment_id ? 'Dikelola melalui Account Payable' : 'Dikelola melalui POS')));
                    return '<span class="text-muted" title="' . e($title) . '"><i data-feather="link-2"></i></span>';
                }

                $buttons = '';
                if (auth()->user()->can('cash-bank.update')) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-cash-transaction"'
                        . ' data-url="' . e(route('super.cash-bank.transactions.update', $transaction)) . '"'
                        . ' data-account="' . (int) $transaction->cash_bank_account_id . '"'
                        . ' data-counter-account="' . (int) ($transaction->counter_chart_account_id ?? 0) . '"'
                        . ' data-date="' . e($transaction->transaction_date?->toDateString()) . '"'
                        . ' data-direction="' . e($transaction->direction) . '"'
                        . ' data-amount="' . e($transaction->amount) . '"'
                        . ' data-description="' . e($transaction->description) . '"'
                        . ' data-reference="' . e($transaction->reference_number) . '" title="Edit"><i data-feather="edit-2"></i></button> ';
                }
                if (auth()->user()->can('cash-bank.delete')) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-cash-transaction" data-url="' . e(route('super.cash-bank.transactions.destroy', $transaction)) . '" title="Hapus"><i data-feather="trash-2"></i></button>';
                }
                return $buttons ?: '—';
            })
            ->rawColumns(['direction_label', 'actions'])->toJson();
    }

    public function storeAccount(Request $request, CashBankService $service): JsonResponse
    {
        $companyId = app(\App\Domain\Companies\Services\CompanyContext::class)->id();
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('cash_bank_accounts', 'code')->where('company_id', $companyId)],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:cash,bank'],
            'chart_of_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'bank_name' => ['required_if:type,bank', 'nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'account_name' => ['nullable', 'string', 'max:150'],
            'opening_balance' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);
        $account = $service->createAccount($data);
        return response()->json(['message' => "Akun {$account->name} berhasil dibuat."], 201);
    }

    public function storeTransaction(Request $request, CashBankService $service): JsonResponse
    {
        $data = $request->validate([
            'cash_bank_account_id' => ['required', 'integer', 'exists:cash_bank_accounts,id'],
            'counter_chart_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'direction' => ['required', 'in:in,out'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'description' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:150'],
        ]);
        $transaction = $service->recordTransaction($data, (int) $request->user()->id);
        return response()->json(['message' => 'Mutasi kas/bank berhasil dicatat.', 'transaction_id' => $transaction->id], 201);
    }

    public function updateTransaction(Request $request, \App\Models\CashBankTransaction $cashBankTransaction, CashBankService $service): JsonResponse
    {
        $data = $request->validate($this->transactionRules());
        $service->updateTransaction($cashBankTransaction, $data, (int) $request->user()->id);
        return response()->json(['message' => 'Mutasi kas/bank berhasil diperbarui.']);
    }

    public function deleteTransaction(Request $request, \App\Models\CashBankTransaction $cashBankTransaction, CashBankService $service): JsonResponse
    {
        $service->deleteTransaction($cashBankTransaction, (int) $request->user()->id);
        return response()->json(['message' => 'Mutasi kas/bank berhasil dihapus.']);
    }

    private function transactionRules(): array
    {
        return [
            'cash_bank_account_id' => ['required', 'integer', 'exists:cash_bank_accounts,id'],
            'counter_chart_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'direction' => ['required', 'in:in,out'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'description' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:150'],
        ];
    }
}
