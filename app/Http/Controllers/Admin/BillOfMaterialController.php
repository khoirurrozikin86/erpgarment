<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\Product;
use App\Models\MaterialRequest;
use App\Models\ProductionOrder;
use App\Models\BillOfMaterialItem;
use App\Exports\BillOfMaterialsExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class BillOfMaterialController extends Controller
{
    public function index(CompanyContext $company): View
    {
        $products = $this->finishedGoods($company->id());

        return view('super.boms.index', compact('products'));
    }

    public function export(CompanyContext $company)
    {
        return Excel::download(
            new BillOfMaterialsExport($company->id()),
            'bill-of-materials-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    public function create(): View
    {
        return view('super.boms.form', ['bom' => new BillOfMaterial(), 'products' => $this->products(), 'finishedGoods' => $this->finishedGoods(0), 'components' => collect()]);
    }

    public function input(Product $product, CompanyContext $company): View|RedirectResponse
    {
        $product = $this->findFinishedGood($product);
        $bom = BillOfMaterial::query()->where('company_id', $company->id())
            ->where('product_id', $product->id)->orderByDesc('version')->first();

        if ($bom) {
            return redirect()->route('super.boms.edit', $bom);
        }

        return view('super.boms.form', [
            'bom' => new BillOfMaterial(['product_id' => $product->id]),
            'products' => $this->products(),
            'finishedGoods' => collect([$product]),
            'components' => collect(),
        ]);
    }

    public function detail(Product $product, CompanyContext $company): View
    {
        $product = $this->findFinishedGood($product);
        $bills = BillOfMaterial::query()->where('company_id', $company->id())
            ->where('product_id', $product->id)->with('items.product.unit', 'creator')
            ->orderByDesc('version')->get();

        return view('super.boms.detail', compact('product', 'bills'));
    }

    public function store(Request $request, CompanyContext $company): RedirectResponse
    {
        $data = $this->validated($request, $company->id());
        $this->findFinishedGood(Product::findOrFail($data['product_id']));
        $this->ensureNoSelfComponent($data);
        $this->ensureUniqueComponents($data);

        DB::transaction(function () use ($data, $company, $request) {
            $bom = BillOfMaterial::create([
                'company_id' => $company->id(), 'product_id' => $data['product_id'],
                'version' => '1', 'name' => null,
                'status' => 'active', 'output_quantity' => 1,
                'effective_from' => null, 'effective_until' => null,
                'notes' => null, 'created_by' => $request->user()->id,
            ]);
            $bom->items()->createMany($data['items']);
        });

        return redirect()->route('super.boms.index')->with('success', __('ui.workflow.bom_saved'));
    }

    public function edit(BillOfMaterial $bom, CompanyContext $company): View
    {
        $bom = $this->findForCompany($bom, $company->id())->load('items');
        return view('super.boms.form', ['bom' => $bom, 'products' => $this->products(), 'finishedGoods' => collect([$bom->product]), 'components' => $bom->items]);
    }

    public function update(Request $request, BillOfMaterial $bom, CompanyContext $company): RedirectResponse
    {
        $bom = $this->findForCompany($bom, $company->id());
        $data = $this->validated($request, $company->id(), $bom);
        $this->findFinishedGood(Product::findOrFail($data['product_id']));
        $this->ensureNoSelfComponent($data);
        $this->ensureUniqueComponents($data);

        DB::transaction(function () use ($data, $bom) {
            $bom->items()->delete();
            $bom->items()->createMany($data['items']);
        });

        return redirect()->route('super.boms.index')->with('success', __('ui.workflow.bom_updated'));
    }

    public function destroy(BillOfMaterial $bom, CompanyContext $company): RedirectResponse
    {
        $bom = $this->findForCompany($bom, $company->id());
        abort_if(
            ProductionOrder::query()->where('bill_of_material_id', $bom->id)->exists()
            || MaterialRequest::query()->where('bill_of_material_id', $bom->id)->exists(),
            422,
            'BOM tidak dapat dihapus karena sudah digunakan pada transaksi produksi atau permintaan material.'
        );
        $bom->delete();

        return redirect()->route('super.boms.index')->with('success', __('ui.workflow.bom_deleted'));
    }

    public function destroyItem(BillOfMaterial $bom, BillOfMaterialItem $item, CompanyContext $company): RedirectResponse
    {
        $bom = $this->findForCompany($bom, $company->id());
        abort_unless($item->bill_of_material_id === $bom->id, 404);
        $item->delete();

        return redirect()->route('super.boms.detail', $bom->product_id)->with('success', 'Rincian material BOM berhasil dihapus.');
    }

    private function validated(Request $request, int $companyId, ?BillOfMaterial $bom = null): array
    {
        return $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)->where('product_type', 'stock')],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)->where('product_type', 'stock')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'items.*.span' => ['nullable', 'numeric', 'min:0', 'max:999999999999.9999'],
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
        return Product::query()->where('is_active', true)->where('product_type', 'stock')
            ->with('unit:id,name,symbol')->orderBy('name')->get(['id', 'code', 'name', 'unit_id', 'garment_item_type']);
    }

    private function finishedGoods(int $companyId)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('style_code')
            ->whereHas('category', fn ($query) => $query->where('code', 'FG')->orWhereRaw('LOWER(name) = ?', ['finished goods']))
            ->with('unit:id,name,symbol');

        if ($companyId > 0) {
            $query->with(['billsOfMaterial' => fn ($query) => $query->where('company_id', $companyId)->withCount('items')->orderByDesc('version')]);
        }

        return $query->orderBy('style_code')->orderBy('code')->get();
    }

    private function findFinishedGood(Product $product): Product
    {
        abort_unless($product->is_active && $product->style_code && $product->category()
            ->where(fn ($query) => $query->where('code', 'FG')->orWhereRaw('LOWER(name) = ?', ['finished goods']))->exists(), 404);

        return $product->load('unit');
    }
}
