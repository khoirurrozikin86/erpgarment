<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Accounting\Queries\ChartOfAccountQuery;
use App\Domain\Accounting\Services\ChartOfAccountService;
use App\Domain\Companies\Services\CompanyContext;
use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ChartOfAccountController extends Controller
{
    public function index(ChartOfAccountQuery $query): View
    {
        return view('super.chart-of-accounts.index', ['parents' => $query->parentOptions()]);
    }

    public function dt(ChartOfAccountQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->builder())
            ->addColumn('parent_label', fn (ChartOfAccount $account) => $account->parent ? $account->parent->code . ' · ' . $account->parent->name : '—')
            ->addColumn('type_label', fn (ChartOfAccount $account) => $this->typeLabel($account->account_type))
            ->addColumn('balance_label', fn (ChartOfAccount $account) => $account->normal_balance === 'debit' ? __('ui.modules.debit_balance') : __('ui.modules.credit_balance'))
            ->addColumn('status_label', fn (ChartOfAccount $account) => $account->is_active ? '<span class="badge bg-success">' . e(__('ui.modules.active_status')) . '</span>' : '<span class="badge bg-secondary">' . e(__('ui.modules.inactive_status')) . '</span>')
            ->addColumn('system_label', fn (ChartOfAccount $account) => $account->is_system ? '<span class="badge bg-light text-dark">' . e(__('ui.modules.standard')) . '</span>' : '<span class="badge bg-info text-dark">' . e(__('ui.modules.custom')) . '</span>')
            ->addColumn('actions', function (ChartOfAccount $account) {
                if (!auth()->user()->can('chart-of-accounts.update')) return '—';
                return '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-chart-account" data-id="' . (int) $account->id . '"'
                    . ' data-code="' . e($account->code) . '" data-name="' . e($account->name) . '"'
                    . ' data-type="' . e($account->account_type) . '" data-normal="' . e($account->normal_balance) . '"'
                    . ' data-parent="' . (int) ($account->parent_id ?? 0) . '" data-group="' . (int) $account->is_group . '"'
                    . ' data-active="' . (int) $account->is_active . '" data-description="' . e($account->description) . '"'
                    . ' data-url="' . e(route('super.chart-of-accounts.update', $account)) . '" title="' . e(__('ui.modules.edit')) . '"><i data-feather="edit-2"></i></button>';
            })
            ->rawColumns(['status_label', 'system_label', 'actions'])->toJson();
    }

    public function store(Request $request, ChartOfAccountService $service, CompanyContext $company): JsonResponse
    {
        $data = $request->validate($this->rules($request, $company));
        $account = $service->create($data);
        return response()->json(['message' => __('ui.modules.account_created', ['code' => $account->code])], 201);
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount, ChartOfAccountService $service, CompanyContext $company): JsonResponse
    {
        $data = $request->validate($this->rules($request, $company, $chartOfAccount));
        $account = $service->update($chartOfAccount, $data);
        return response()->json(['message' => __('ui.modules.account_updated', ['code' => $account->code])]);
    }

    private function rules(Request $request, CompanyContext $company, ?ChartOfAccount $account = null): array
    {
        $uniqueCode = Rule::unique('chart_of_accounts', 'code')->where('company_id', $company->id());
        if ($account) $uniqueCode->ignore($account->id);

        return [
            'code' => ['required', 'string', 'max:50', $uniqueCode],
            'name' => ['required', 'string', 'max:150'],
            'account_type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'normal_balance' => ['required', 'in:debit,credit'],
            'parent_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
            'is_group' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function typeLabel(string $type): string
    {
        return ['asset' => __('ui.modules.assets'), 'liability' => __('ui.modules.liabilities'), 'equity' => __('ui.modules.equity'), 'revenue' => __('ui.modules.revenue'), 'expense' => __('ui.modules.expenses')][$type] ?? $type;
    }
}
