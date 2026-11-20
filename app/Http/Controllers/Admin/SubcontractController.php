<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Subcontract\Actions\{SaveSubcontractAction, SaveSubcontractContractAction};
use App\Domain\Subcontract\Queries\SubcontractTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Http\Controllers\Controller;
use App\Models\{Customer, SubcontractContract, Subcontractor};
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SubcontractController extends Controller
{
    public function index(): View
    {
        return view('super.subcontracts.index');
    }

    public function createContract(DocumentNumberService $numbers): View
    {
        return $this->contractForm(null, $numbers->preview('subcontract_contract'));
    }

    public function editContract(SubcontractContract $contract, CompanyContext $company): View
    {
        abort_unless((int) $contract->company_id === $company->id(), 404);

        return $this->contractForm($contract, $contract->contract_number);
    }

    private function contractForm(?SubcontractContract $contract, string $number): View
    {
        $customer = old('customer_id')
            ? Customer::query()->where('is_active', true)->find(old('customer_id'))
            : $contract?->customer;

        return view('super.subcontracts.contract-form', compact('contract', 'number', 'customer'));
    }

    public function customersDt(): JsonResponse
    {
        return DataTables::eloquent(Customer::query()->where('is_active', true)->select(['id', 'code', 'name']))
            ->escapeColumns([])->toJson();
    }

    public function subcontractorsDt(CompanyContext $company, SubcontractTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->subcontractors($company->id()))
            ->addColumn('actions', function (Subcontractor $row) {
                $id = (int) $row->id;
                $actions = '';
                if (auth()->user()->can('subcontractors.update')) $actions .= '<button class="btn btn-sm btn-outline-primary me-1 js-edit-subcontractor" data-id="'.$id.'" title="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i></button>';
                if (auth()->user()->can('subcontractors.delete')) $actions .= '<button class="btn btn-sm btn-outline-danger js-delete-subcontractor" data-id="'.$id.'" title="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i></button>';
                return $actions ?: '—';
            })
            ->addColumn('status_label', fn (Subcontractor $row) => $row->is_active ? __('ui.common.active') : __('ui.common.inactive'))
            ->rawColumns(['actions'])->toJson();
    }

    public function contractsDt(Request $request, CompanyContext $company, SubcontractTableQuery $query): JsonResponse
    {
        $filters = $request->validate(['from_date' => ['nullable', 'date'], 'to_date' => ['nullable', 'date', 'after_or_equal:from_date']]);
        return DataTables::eloquent($query->contracts($company->id(), $filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()))
            ->addColumn('subcontractor_name', fn (SubcontractContract $row) => $row->customer?->name ?? $row->subcontractor?->name ?? '—')
            ->addColumn('starts_label', fn (SubcontractContract $row) => $row->starts_on?->format('d-m-Y'))
            ->addColumn('ends_label', fn (SubcontractContract $row) => $row->ends_on?->format('d-m-Y'))
            ->addColumn('quota_label', fn (SubcontractContract $row) => $row->quota === null ? '—' : \App\Support\DisplayNumber::format((float) $row->quota, 4))
            ->addColumn('actions', function (SubcontractContract $row) {
                $id = (int) $row->id;
                $actions = '<div class="d-flex justify-content-end gap-1">';
                if (auth()->user()->can('subcontract-contracts.update')) $actions .= '<a class="btn btn-sm btn-outline-primary" href="'.e(route('super.subcontract.contracts.edit', $row)).'" title="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i></a>';
                if (auth()->user()->can('subcontract-contracts.delete')) $actions .= '<button class="btn btn-sm btn-outline-danger js-delete-contract" data-id="'.$id.'" title="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i></button>';
                return $actions.'</div>';
            })
            ->addColumn('outbound_label', fn (SubcontractContract $row) => $this->contractDetailLink($row, 'issue', $filters))
            ->addColumn('inbound_label', fn (SubcontractContract $row) => $this->contractDetailLink($row, 'receipt', $filters))
            ->rawColumns(['actions', 'outbound_label', 'inbound_label'])->toJson();
    }

    private function contractDetailLink(SubcontractContract $contract, string $direction, array $filters): string
    {
        $url = route('super.subcontract.goods-monitoring.index', [
            ...$filters,
            'contract_id' => $contract->id,
            'direction' => $direction,
            'from_contracts' => 1,
        ]);

        return '<a class="btn btn-sm btn-outline-primary text-nowrap" href="'.e($url).'">'
            .e(__('subcontract_goods_monitoring.'.$direction)).'</a>';
    }

    public function showSubcontractor(Subcontractor $subcontractor, CompanyContext $company): JsonResponse
    {
        abort_unless((int) $subcontractor->company_id === $company->id(), 404);
        return response()->json($subcontractor);
    }

    public function storeSubcontractor(Request $request, CompanyContext $company): JsonResponse
    {
        return $this->saveSubcontractor($request, $company, null);
    }

    public function updateSubcontractor(Request $request, Subcontractor $subcontractor, CompanyContext $company): JsonResponse
    {
        abort_unless((int) $subcontractor->company_id === $company->id(), 404);
        return $this->saveSubcontractor($request, $company, $subcontractor);
    }

    private function saveSubcontractor(Request $request, CompanyContext $company, ?Subcontractor $subcontractor): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160', Rule::unique('subcontractors', 'name')->where('company_id', $company->id())->ignore($subcontractor?->id)],
            'is_active' => ['required', 'boolean'],
        ]);
        $saved = $subcontractor
            ? app(SaveSubcontractAction::class)->update($subcontractor, $data, (int) $request->user()->id)
            : app(SaveSubcontractAction::class)->create($data, $company->id(), (int) $request->user()->id);
        return response()->json(['message' => __('ui.subcontract.master_saved'), 'id' => $saved->id]);
    }

    public function destroySubcontractor(Request $request, Subcontractor $subcontractor, CompanyContext $company): JsonResponse
    {
        abort_unless((int) $subcontractor->company_id === $company->id(), 404);
        if ($subcontractor->contracts()->withTrashed()->exists()) return response()->json(['message' => __('ui.subcontract.master_in_use')], 422);
        app(SaveSubcontractAction::class)->delete($subcontractor, (int) $request->user()->id);
        return response()->json(['message' => __('ui.subcontract.master_deleted')]);
    }

    public function showContract(SubcontractContract $contract, CompanyContext $company): JsonResponse
    {
        abort_unless((int) $contract->company_id === $company->id(), 404);
        return response()->json($contract->load('subcontractor:id,name'));
    }

    public function storeContract(Request $request, CompanyContext $company): JsonResponse|RedirectResponse
    {
        return $this->saveContract($request, $company, null);
    }

    public function updateContract(Request $request, SubcontractContract $contract, CompanyContext $company): JsonResponse|RedirectResponse
    {
        abort_unless((int) $contract->company_id === $company->id(), 404);
        return $this->saveContract($request, $company, $contract);
    }

    private function saveContract(Request $request, CompanyContext $company, ?SubcontractContract $contract): JsonResponse|RedirectResponse
    {
        $action = app(SaveSubcontractContractAction::class);
        $data = $request->validate([
            'customer_id' => [$contract || $request->expectsJson() ? 'nullable' : 'required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'subcontractor_id' => ['nullable', Rule::requiredIf(!$contract && !$request->filled('customer_id')), 'integer', function ($attribute, $value, $fail) use ($action, $company) { if (! $action->subcontractorBelongsToCompany((int) $value, $company->id())) $fail(__('ui.subcontract.invalid_subcontractor')); }],
            'bc_decree_number' => ['nullable', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:5000'],
            'starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'quota' => ['nullable', 'numeric', 'gte:0', 'max:9999999999999999.9999'],
        ]);
        $saved = $contract ? $action->update($contract, $data, (int) $request->user()->id) : $action->create($data, $company->id(), (int) $request->user()->id);
        if ($request->expectsJson()) {
            return response()->json(['message' => __('ui.subcontract.contract_saved'), 'number' => $saved->contract_number]);
        }

        return redirect()->route('super.subcontract.index', [
            'from_date' => min(now()->startOfMonth()->toDateString(), $saved->starts_on->toDateString()),
            'to_date' => max(now()->toDateString(), $saved->starts_on->toDateString()),
        ])->with('contract_success', __('ui.subcontract.contract_saved').' ('.$saved->contract_number.')');
    }

    public function destroyContract(Request $request, SubcontractContract $contract, CompanyContext $company): JsonResponse
    {
        abort_unless((int) $contract->company_id === $company->id(), 404);
        app(SaveSubcontractContractAction::class)->delete($contract, (int) $request->user()->id);
        return response()->json(['message' => __('ui.subcontract.contract_deleted')]);
    }

    public function numberPreview(DocumentNumberService $numbers): JsonResponse { return response()->json(['number' => $numbers->preview('subcontract_contract')]); }
}
