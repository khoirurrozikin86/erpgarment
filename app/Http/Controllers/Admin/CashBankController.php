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
            ->addColumn('type_label', fn(CashBankAccount $account) => $account->type === 'cash' ? __('ui.modules.cash_account') : __('ui.modules.bank_account'))
            ->addColumn('account_info', fn(CashBankAccount $account) => $account->type === 'bank'
                ? trim(($account->bank_name ?: __('ui.modules.bank_default')) . ' · ' . ($account->account_number ?: __('ui.modules.account_number_missing_label')), ' ·')
                : __('ui.modules.cash_account'))
            ->addColumn('chart_account_label', fn(CashBankAccount $account) => $account->chartOfAccount ? $account->chartOfAccount->code . ' · ' . $account->chartOfAccount->name : __('ui.modules.not_mapped'))
            ->addColumn('balance', fn(CashBankAccount $account) => round((float) $account->opening_balance + (float) ($account->incoming_total ?? 0) - (float) ($account->outgoing_total ?? 0), 2))
            ->addColumn('status_label', fn(CashBankAccount $account) => $account->is_active ? '<span class="badge bg-success">' . e(__('ui.modules.active_status')) . '</span>' : '<span class="badge bg-secondary">' . e(__('ui.modules.inactive_status')) . '</span>')
            ->rawColumns(['status_label'])->toJson();
    }

    public function transactionsDt(Request $request, CashBankQuery $query): JsonResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail(__('ui.modules.date_end_invalid'));
            }],
        ]);

        return DataTables::eloquent($query->transactions($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('account_name', fn($transaction) => $transaction->account?->name ?? __('ui.modules.account_deleted'))
            ->addColumn('counter_account_name', fn($transaction) => $transaction->pos_sale_void_id ? __('ui.modules.pos_void_source') : ($transaction->customer_return_id ? __('ui.modules.sales_return_source') : ($transaction->sales_invoice_payment_id ? __('ui.modules.receivable_source') : ($transaction->supplier_payment_id ? __('ui.modules.payable_source') : ($transaction->pos_session_id ? __('ui.modules.pos_source') : ($transaction->counterAccount ? $transaction->counterAccount->code . ' · ' . $transaction->counterAccount->name : '—'))))))
            ->addColumn('source_number', function ($transaction) {
                if ($transaction->posSaleVoid?->number) return __('ui.modules.pos_void_number', ['number' => $transaction->posSaleVoid->number]);
                if ($transaction->customerReturn?->number) return __('ui.modules.customer_return_number', ['number' => $transaction->customerReturn->number]);
                if ($transaction->payment?->invoice?->number) return $transaction->payment->invoice->number;
                if ($transaction->supplierPayment?->goodsReceipt?->number) return __('ui.modules.receipt_number_prefix', ['number' => $transaction->supplierPayment->goodsReceipt->number]);
                if ($transaction->posSale?->number) return __('ui.modules.pos_number_prefix', ['number' => $transaction->posSale->number]);
                if ($transaction->posSession?->number) return __('ui.modules.pos_number_prefix', ['number' => $transaction->posSession->number]);
                return __('ui.modules.manual_source');
            })
            ->addColumn('direction_label', fn($transaction) => $transaction->direction === 'in'
                ? '<span class="badge bg-success">' . e(__('ui.modules.cash_in')) . '</span>'
                : '<span class="badge bg-danger">' . e(__('ui.modules.cash_out')) . '</span>')
            ->addColumn('amount_signed', fn($transaction) => ($transaction->direction === 'out' ? -1 : 1) * (float) $transaction->amount)
            ->addColumn('creator_name', fn($transaction) => $transaction->creator?->name ?? '—')
            ->addColumn('actions', function ($transaction) {
                if ($transaction->sales_invoice_payment_id || $transaction->supplier_payment_id || $transaction->customer_return_id || $transaction->pos_sale_void_id || $transaction->pos_session_id || $transaction->pos_sale_id) {
                    $title = $transaction->pos_sale_void_id ? __('ui.modules.managed_pos_void') : ($transaction->customer_return_id ? __('ui.modules.managed_customer_return') : ($transaction->sales_invoice_payment_id ? __('ui.modules.managed_receivable') : ($transaction->supplier_payment_id ? __('ui.modules.managed_payable') : __('ui.modules.managed_pos'))));
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
                        . ' data-reference="' . e($transaction->reference_number) . '" title="' . e(__('ui.modules.edit')) . '"><i data-feather="edit-2"></i></button> ';
                }
                if (auth()->user()->can('cash-bank.delete')) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-cash-transaction" data-url="' . e(route('super.cash-bank.transactions.destroy', $transaction)) . '" title="' . e(__('ui.modules.delete')) . '"><i data-feather="trash-2"></i></button>';
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
        return response()->json(['message' => __('ui.modules.cash_account_created', ['name' => $account->name])], 201);
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
        return response()->json(['message' => __('ui.modules.cash_transaction_saved'), 'transaction_id' => $transaction->id], 201);
    }

    public function updateTransaction(Request $request, \App\Models\CashBankTransaction $cashBankTransaction, CashBankService $service): JsonResponse
    {
        $data = $request->validate($this->transactionRules());
        $service->updateTransaction($cashBankTransaction, $data, (int) $request->user()->id);
        return response()->json(['message' => __('ui.modules.cash_transaction_updated')]);
    }

    public function deleteTransaction(Request $request, \App\Models\CashBankTransaction $cashBankTransaction, CashBankService $service): JsonResponse
    {
        $service->deleteTransaction($cashBankTransaction, (int) $request->user()->id);
        return response()->json(['message' => __('ui.modules.cash_transaction_deleted')]);
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
