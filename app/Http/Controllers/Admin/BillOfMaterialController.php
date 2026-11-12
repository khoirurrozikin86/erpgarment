<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BillOfMaterialController extends Controller
{
    public function index(CompanyContext $company): View
    {
        $bills = BillOfMaterial::query()
            ->where('company_id', $company->id())
            ->with('product.unit')
            ->withCount('items')
            ->orderBy('product_id')->orderByDesc('version')->get();

        return view('super.boms.index', compact('bills'));
    }

    public function create(): View
    {
        return view('super.boms.form', ['bom' => new BillOfMaterial(), 'products' => $this->products(), 'components' => collect()]);
    }

    public function store(Request $request, CompanyContext $company): RedirectResponse
    {
        $data = $this->validated($request, $company->id());
        $this->ensureNoSelfComponent($data);
        $this->ensureUniqueComponents($data);
        $this->ensureSingleActive($data, $company->id());

        DB::transaction(function () use ($data, $company, $request) {
            $bom = BillOfMaterial::create([
                'company_id' => $company->id(), 'product_id' => $data['product_id'],
                'version' => $data['version'], 'name' => $data['name'] ?? null,
                'status' => $data['status'], 'output_quantity' => $data['output_quantity'],
                'effective_from' => $data['effective_from'] ?? null, 'effective_until' => $data['effective_until'] ?? null,
                'notes' => $data['notes'] ?? null, 'created_by' => $request->user()->id,
            ]);
            $bom->items()->createMany($data['items']);
        });

        return redirect()->route('super.boms.index')->with('success', __('ui.workflow.bom_saved'));
    }

    public function edit(BillOfMaterial $bom, CompanyContext $company): View
    {
        $bom = $this->findForCompany($bom, $company->id())->load('items');
        return view('super.boms.form', ['bom' => $bom, 'products' => $this->products(), 'components' => $bom->items]);
    }

    public function update(Request $request, BillOfMaterial $bom, CompanyContext $company): RedirectResponse
    {
        $bom = $this->findForCompany($bom, $company->id());
        $data = $this->validated($request, $company->id(), $bom);
        $this->ensureNoSelfComponent($data);
        $this->ensureUniqueComponents($data);
        $this->ensureSingleActive($data, $company->id(), $bom->id);

        DB::transaction(function () use ($data, $bom) {
            $bom->update([
                'product_id' => $data['product_id'], 'version' => $data['version'], 'name' => $data['name'] ?? null,
                'status' => $data['status'], 'output_quantity' => $data['output_quantity'],
                'effective_from' => $data['effective_from'] ?? null, 'effective_until' => $data['effective_until'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $bom->items()->delete();
            $bom->items()->createMany($data['items']);
        });

        return redirect()->route('super.boms.index')->with('success', __('ui.workflow.bom_updated'));
    }

    public function destroy(BillOfMaterial $bom, CompanyContext $company): RedirectResponse
    {
        $bom = $this->findForCompany($bom, $company->id());
        abort_if($bom->status === 'active', 422, __('ui.workflow.bom_active_delete_error'));
        $bom->delete();

        return redirect()->route('super.boms.index')->with('success', __('ui.workflow.bom_deleted'));
    }

    private function validated(Request $request, int $companyId, ?BillOfMaterial $bom = null): array
    {
        return $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)->where('product_type', 'stock')],
            'version' => ['required', 'string', 'max:30', Rule::unique('bill_of_materials', 'version')->where('company_id', $companyId)->where('product_id', $request->input('product_id'))->ignore($bom?->id)],
            'name' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'output_quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'effective_from' => ['nullable', 'date_format:Y-m-d'],
            'effective_until' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('effective_from') && $value < $request->input('effective_from')) $fail(__('ui.workflow.bom_effective_date_error'));
            }],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)->where('product_type', 'stock')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'items.*.wastage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.operation' => ['nullable', 'string', 'max:40'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function ensureUniqueComponents(array $data): void
    {
        $keys = collect($data['items'])->map(fn ($item) => $item['product_id'] . '|' . mb_strtolower(trim($item['operation'] ?? '')));
        if ($keys->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => __('ui.workflow.bom_duplicate_component_error')]);
        }
    }

    private function ensureNoSelfComponent(array $data): void
    {
        abort_if(collect($data['items'])->contains(fn ($item) => (int) $item['product_id'] === (int) $data['product_id']), 422, __('ui.workflow.bom_self_component_error'));
    }

    private function ensureSingleActive(array $data, int $companyId, ?int $exceptId = null): void
    {
        if ($data['status'] !== 'active') return;
        $exists = BillOfMaterial::query()->where('company_id', $companyId)->where('product_id', $data['product_id'])->where('status', 'active')->when($exceptId, fn ($query) => $query->where('id', '<>', $exceptId))->exists();
        abort_if($exists, 422, __('ui.workflow.bom_one_active_error'));
    }

    private function findForCompany(BillOfMaterial $bom, int $companyId): BillOfMaterial
    {
        return BillOfMaterial::query()->where('company_id', $companyId)->findOrFail($bom->id);
    }

    private function products()
    {
        return Product::query()->where('is_active', true)->where('product_type', 'stock')->with('unit:id,name,symbol')->orderBy('name')->get(['id', 'code', 'name', 'unit_id', 'garment_item_type']);
    }
}
