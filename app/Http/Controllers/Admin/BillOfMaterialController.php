<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\Product;
use App\Models\MaterialRequest;
use App\Models\ProductionOrder;
use App\Models\Style;
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
    public function index(Request $request, CompanyContext $company): View
    {
        $filters = $request->validate(['style_code' => ['nullable', 'string', 'max:80']]);
        $boms = BillOfMaterial::where('company_id', $company->id())
            ->when($filters['style_code'] ?? null, fn ($query, $code) => $query->where(fn ($scope) => $scope
                ->whereRaw('LOWER(style_code) = ?', [mb_strtolower($code)])
                ->orWhere(fn ($legacy) => $legacy->whereNull('style_code')->whereHas(
                    'product', fn ($product) => $product->whereRaw('LOWER(style_code) = ?', [mb_strtolower($code)])
                ))))
            ->with(['product', 'items.product.unit', 'items.product.parentProduct:id,code', 'items.product.category'])->latest('id')->get();

        return view('super.boms.index', compact('boms'));
    }

    public function styles(CompanyContext $company): View
    {
        $orders = ProductionOrder::where('company_id', $company->id())
            ->with(['outputProduct.parentProduct', 'outputProduct.unit'])->get()
            ->filter(fn (ProductionOrder $order) => filled($order->model_style))
            ->groupBy(fn (ProductionOrder $order) => mb_strtolower($order->model_style));

        $boms = BillOfMaterial::where('company_id', $company->id())->with('product')->get()
            ->groupBy(fn (BillOfMaterial $bom) => mb_strtolower($bom->style_code ?: ($bom->product?->style_code ?? '')));

        $styles = $this->orderStyles($company->id())->map(function ($style) use ($orders, $boms) {
            $key = mb_strtolower($style->code);
            $products = $orders->get($key, collect())->pluck('outputProduct')->filter()
                ->map(fn (Product $product) => $product->parentProduct ?? $product)->unique('id');
            $styleBoms = $boms->get($key, collect());

            return [
                'id' => $style instanceof Style ? $style->id : null,
                'code' => $style->code,
                'products' => $products->map(fn (Product $product) => $product->code.' — '.$product->name)->implode(', '),
                'bom_count' => $styleBoms->count(),
                'has_active_bom' => $styleBoms->contains('status', 'active'),
            ];
        });

        return view('super.boms.styles', compact('styles'));
    }

    public function destroyStyle(Style $style): RedirectResponse
    {
        DB::transaction(function () use ($style) {
            $locked = Style::lockForUpdate()->findOrFail($style->id);
            $code = mb_strtolower($locked->code);
            $usedBy = fn ($relation) => fn ($query) => $query->whereRaw('LOWER(style_code) = ?', [$code])
                ->orWhere(fn ($legacy) => $legacy->whereNull('style_code')->whereHas(
                    $relation,
                    fn ($product) => $product->whereRaw('LOWER(style_code) = ?', [$code])
                ));

            if (ProductionOrder::where($usedBy('outputProduct'))->exists() || BillOfMaterial::where($usedBy('product'))->exists()) {
                throw ValidationException::withMessages(['style' => __('variants.style_in_use')]);
            }

            $before = $locked->toArray();
            $locked->delete();
            app(\App\Domain\Audit\Services\AuditLogService::class)->log(
                action: 'DELETE', module: 'STYLE', description: 'Delete Style '.$locked->code,
                model: $locked, oldValues: $before, newValues: null,
            );
        });

        return redirect()->route('super.boms.styles')->with('success', __('variants.style_deleted'));
    }

    public function export(CompanyContext $company)
    {
        return Excel::download(
            new BillOfMaterialsExport($company->id()),
            'bill-of-materials-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    public function create(Request $request, CompanyContext $company): View
    {
        $data = $request->validate(['style_code' => ['nullable', 'string', 'max:80']]);
        $styles = $this->orderStyles($company->id());
        $style = $styles->firstWhere('code', $data['style_code'] ?? null);

        return view('super.boms.form', [
            'bom' => new BillOfMaterial(['style_code' => $style?->code]),
            'products' => $this->products(),
            'finishedGoods' => collect(),
            'styles' => $styles,
            'components' => collect(),
        ]);
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
            'finishedGoods' => collect([$product]), 'styles' => $this->orderStyles($company->id()),
            'components' => collect(),
        ]);
    }

    public function detail(Product $product, CompanyContext $company): View
    {
        $product = $this->findFinishedGood($product);
        $bills = BillOfMaterial::query()->where('company_id', $company->id())
            ->where(fn ($q) => $q->where('product_id', $product->id)->when(filled($product->style_code), fn ($query) => $query->orWhere(fn ($style) => $style->whereNull('product_id')->where('style_code', $product->style_code))))->with('items.product.unit', 'items.product.parentProduct:id,code', 'creator')
            ->orderByDesc('version')->get();

        return view('super.boms.detail', compact('product', 'bills'));
    }

    public function store(Request $request, CompanyContext $company): RedirectResponse
    {
        $data = $this->validated($request, $company->id());
        $this->ensureNoSelfComponent($data);
        $this->ensureUniqueComponents($data);

        DB::transaction(function () use ($data, $company, $request) {
            \App\Models\Company::whereKey($company->id())->lockForUpdate()->firstOrFail();
            $bom = BillOfMaterial::create([
                'company_id' => $company->id(), 'product_id' => null, 'style_code' => $data['style_code'],
                'bom_type' => $data['bom_type'],
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
        $bom = $this->findForCompany($bom, $company->id())->load('items.product.unit', 'items.product.parentProduct:id,code');
        return view('super.boms.form', ['bom' => $bom, 'products' => $this->products(), 'finishedGoods' => collect([$bom->product])->filter(), 'styles' => $this->orderStyles($company->id()), 'components' => $bom->items]);
    }

    public function update(Request $request, BillOfMaterial $bom, CompanyContext $company): RedirectResponse
    {
        $bom = $this->findForCompany($bom, $company->id());
        $data = $this->validated($request, $company->id(), $bom);
        $this->ensureNoSelfComponent($data + ['product_id' => $bom->product_id]);
        $this->ensureUniqueComponents($data);

        DB::transaction(function () use ($data, $bom, $company) {
            \App\Models\Company::whereKey($company->id())->lockForUpdate()->firstOrFail();
            if ($bom->style_code !== $data['style_code'] && (ProductionOrder::where('bill_of_material_id', $bom->id)->exists() || MaterialRequest::where('bill_of_material_id', $bom->id)->exists())) {
                throw ValidationException::withMessages(['style_code' => __('variants.bom_style_locked')]);
            }
            if ($bom->product_id) {
                // Legacy BOMs remain bound to their original output SKU.
                if ($data['style_code'] !== $bom->product?->style_code) {
                    throw ValidationException::withMessages(['style_code' => __('variants.bom_style_locked')]);
                }
            }
            $bom->update(['style_code' => $data['style_code'], 'bom_type' => $data['bom_type']]);
            $bom->items()->delete();
            $bom->items()->createMany($data['items']);
        });

        return redirect()->route('super.boms.index')->with('success', __('ui.workflow.bom_updated'));
    }

    public function destroy(BillOfMaterial $bom, CompanyContext $company): RedirectResponse
    {
        $bom = $this->findForCompany($bom, $company->id());
        DB::transaction(function () use ($bom) {
            $locked = BillOfMaterial::whereKey($bom->id)->lockForUpdate()->firstOrFail();
            $before = $locked->toArray();
            $locked->delete();
            app(\App\Domain\Audit\Services\AuditLogService::class)->log(
                action: 'DELETE', module: 'BILL_OF_MATERIAL',
                description: 'Delete BOM '.$locked->number,
                model: $locked, oldValues: $before, newValues: $locked->toArray(),
            );
        });

        return redirect()->route('super.boms.index')->with('success', __('ui.workflow.bom_deleted'));
    }

    public function destroyItem(BillOfMaterial $bom, BillOfMaterialItem $item, CompanyContext $company): RedirectResponse
    {
        $bom = $this->findForCompany($bom, $company->id());
        abort_unless($item->bill_of_material_id === $bom->id, 404);
        $item->delete();

        return redirect()->route('super.boms.edit', $bom)->with('success', __('variants.bom_item_deleted'));
    }

    private function validated(Request $request, int $companyId, ?BillOfMaterial $bom = null): array
    {
        $data = $request->validate([
            'style_code' => ['required', 'string', Rule::in($this->orderStyles($companyId)->pluck('code')->all())],
            'bom_type' => ['required', Rule::in(['fabric', 'accessory', 'general'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)->where('product_type', 'stock')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'items.*.span' => ['nullable', 'numeric', 'min:0', 'max:999999999999.9999'],
            'items.*.wastage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.operation' => ['nullable', 'string', 'max:40'],
            'items.*.applies_to_color' => ['nullable', 'string', 'max:80'],
            'items.*.applies_to_size' => ['nullable', 'string', 'max:40'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $submitted = collect($data['items'])->pluck('product_id')->map(fn ($id) => (int) $id)->unique();
        $existing = $bom?->items()->pluck('product_id') ?? collect();
        $added = $submitted->diff($existing);
        if ($added->isNotEmpty() && $this->materialProducts()->whereKey($added->all())->count() !== $added->count()) {
            throw ValidationException::withMessages(['items' => __('variants.bom_finished_good_component')]);
        }

        return $data;
    }

    private function ensureUniqueComponents(array $data): void
    {
        $keys = collect($data['items'])->map(fn ($item) => $item['product_id'] . '|' . mb_strtolower(trim($item['operation'] ?? '')) . '|' . mb_strtolower(trim($item['applies_to_color'] ?? '')) . '|' . mb_strtolower(trim($item['applies_to_size'] ?? '')));
        if ($keys->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => __('ui.workflow.bom_duplicate_component_error')]);
        }
    }

    private function ensureNoSelfComponent(array $data): void
    {
        abort_if(collect($data['items'])->contains(fn ($item) => (int) $item['product_id'] === (int) ($data['product_id'] ?? 0)), 422, __('ui.workflow.bom_self_component_error'));
    }

    private function findForCompany(BillOfMaterial $bom, int $companyId): BillOfMaterial
    {
        return BillOfMaterial::query()->where('company_id', $companyId)->findOrFail($bom->id);
    }

    private function orderStyles(int $companyId)
    {
        return Style::query()->get(['id', 'code', 'name'])->toBase()
            ->concat(ProductionOrder::where('company_id', $companyId)->with('outputProduct')->get()
                ->map(fn ($order) => (object) ['code' => $order->model_style, 'name' => $order->outputProduct?->name]))
            ->concat(BillOfMaterial::where('company_id', $companyId)->with('product')->get()
                ->map(fn ($bom) => (object) ['code' => $bom->style_code ?: $bom->product?->style_code, 'name' => $bom->name]))
            ->filter(fn ($style) => filled($style->code))
            ->unique(fn ($style) => mb_strtolower($style->code))->sortBy('code')->values();
    }

    private function products()
    {
        return $this->materialProducts()
            ->with(['unit:id,name,symbol', 'category:id,name', 'parentProduct:id,code'])
            ->orderBy('name')->get(['id', 'code', 'name', 'unit_id', 'category_id', 'garment_item_type', 'color', 'size', 'parent_product_id']);
    }

    private function materialProducts(): \Illuminate\Database\Eloquent\Builder
    {
        return Product::query()->where('is_active', true)->where('product_type', 'stock')
            ->where(fn ($query) => $query->whereNull('garment_item_type')->orWhere('garment_item_type', '<>', 'finished_good'))
            ->whereDoesntHave('category', fn ($category) => $category->where(fn ($query) =>
                $query->where('code', 'FG')->orWhereRaw('LOWER(name) = ?', ['finished goods'])
            ));
    }

    private function finishedGoods(int $companyId)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->where('product_type', 'stock')
            ->whereHas('category', fn ($query) => $query->where('code', 'FG')->orWhereRaw('LOWER(name) = ?', ['finished goods']))
            ->with('unit:id,name,symbol');

        if ($companyId > 0) {
            $query->with(['billsOfMaterial' => fn ($query) => $query->where('company_id', $companyId)->withCount('items')->with('items.product.unit')->orderByDesc('version')]);
        }

        return $query->orderBy('style_code')->orderBy('code')->get();
    }

    private function findFinishedGood(Product $product): Product
    {
        abort_unless($product->is_active && $product->product_type === 'stock' && $product->category()
            ->where(fn ($query) => $query->where('code', 'FG')->orWhereRaw('LOWER(name) = ?', ['finished goods']))->exists(), 404);

        return $product->load('unit');
    }
}
