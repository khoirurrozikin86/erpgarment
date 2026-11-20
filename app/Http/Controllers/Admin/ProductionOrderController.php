<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Exports\MaterialOrdersExport;
use App\Domain\Manufacturing\Actions\CreateProductionOrderAction;
use App\Domain\Manufacturing\Actions\UpdateMaterialOrderAction;
use App\Domain\Manufacturing\Actions\RecordProductionStageAction;
use App\Domain\Manufacturing\Services\EnsureProductionMaterialsIssued;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderStageEntry;
use App\Models\Customer;
use App\Models\VariantDimension;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ProductionOrderController extends Controller
{
    public function index(CompanyContext $company)
    {
        $orders = ProductionOrder::query()->where('company_id', $company->id())
            ->with(['outputProduct.unit', 'outputProduct.primaryImage', 'outputProduct.parentProduct.primaryImage', 'buyer'])
            ->orderByDesc('created_at')->orderByDesc('id')->get();
        return view('super.production-orders.index', compact('orders'));
    }

    public function create(CompanyContext $company)
    {
        return view('super.production-orders.create', [
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'currency' => $company->get()->currency ?: 'USD',
            'colors' => VariantDimension::options('color')->get(['code', 'name']),
            'sizes' => VariantDimension::options('size')->get(['code', 'name']),
        ]);
    }

    public function edit(ProductionOrder $productionOrder, CompanyContext $company)
    {
        $order = $this->findForCompany($productionOrder, $company->id());
        $order->load(['outputProduct.unit', 'outputProduct.primaryImage', 'outputProduct.parentProduct.primaryImage', 'buyer']);

        return $this->form($company, $order);
    }

    private function form(CompanyContext $company, ?ProductionOrder $order = null)
    {
        $products = Product::query()->where('is_active', true)->where('product_type', 'stock')
            ->whereNull('parent_product_id')
            ->whereHas('category', fn ($query) => $query->where('code', 'FG')->orWhereRaw('LOWER(name) = ?', ['finished goods']))
            ->with(['unit:id,name,symbol', 'category:id,name', 'primaryImage'])
            ->orderBy('name')->get(['id', 'code', 'name', 'style_code', 'unit_id', 'category_id', 'parent_product_id']);
        $customers = Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $currencies = array_unique(array_filter(['USD', $company->get()->currency, $order?->currency]));
        $formValues = $order ? $this->formValues($order) : null;

        $colors = VariantDimension::options('color')->pluck('code');
        $sizes = VariantDimension::options('size')->pluck('code');

        return view('super.production-orders.form', compact('order', 'products', 'customers', 'currencies', 'formValues', 'colors', 'sizes'));
    }

    public function export(Request $request, CompanyContext $company)
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) {
                    $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
                }
            }],
        ]);
        $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? now()->toDateString();

        return Excel::download(
            new MaterialOrdersExport($company->id(), $fromDate, $toDate),
            'material-orders-' . ($fromDate ?: 'all') . '-to-' . ($toDate ?: 'all') . '.xlsx'
        );
    }

    public function fullImportTemplate()
    {
        return Excel::download(new \App\Exports\MaterialOrdersTemplate(), 'template-mo-complete.xlsx');
    }

    public function fullImport(Request $request, CompanyContext $company, CreateProductionOrderAction $create): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:2048'],
            'confirm' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $rows = (new \App\Imports\MaterialOrdersImport())->read($request->file('file')->getRealPath());
        $numbers = collect($rows)->pluck('number')->unique();
        if (ProductionOrder::where('company_id', $company->id())->whereIn('number', $numbers)->exists()) {
            throw ValidationException::withMessages(['file' => __('variants.import_existing')]);
        }
        if (!$request->boolean('confirm')) return response()->json(['rows' => $rows, 'count' => count($rows)]);
        $imagePath = $request->hasFile('image') ? $request->file('image')->store('material-orders', 'public') : null;
        if ($request->hasFile('image') && !$imagePath) throw ValidationException::withMessages(['image' => __('variants.upload_failed')]);
        try {
            DB::transaction(function () use ($rows, $request, $create, $imagePath, $company, $numbers) {
                \App\Models\Company::whereKey($company->id())->lockForUpdate()->firstOrFail();
                if (ProductionOrder::where('company_id', $company->id())->whereIn('number', $numbers)->exists()) {
                    throw ValidationException::withMessages(['file' => __('variants.import_existing')]);
                }
                foreach ($rows as $row) {
                    if ($imagePath) $row['image_path'] = $imagePath;
                    $create($row, (int) $request->user()->id);
                }
            });
        } catch (\Throwable $exception) {
            if ($imagePath) Storage::disk('public')->delete($imagePath);
            throw $exception;
        }
        return response()->json(['message' => __('variants.import_done', ['count' => count($rows)])]);
    }

    public function importTemplate()
    {
        return Excel::download(new \App\Exports\MaterialOrderLinesTemplate(), 'template-mo-color-size.xlsx');
    }

    public function importLines(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:2048']]);
        $import = new \App\Imports\MaterialOrderLinesImport();
        Excel::import($import, $request->file('file'));
        $data = validator(['variant_rows' => $import->rows], [
            'variant_rows' => ['required', 'array', 'min:1', 'max:100'],
            'variant_rows.*.color' => ['required', 'string', 'max:80'],
            'variant_rows.*.size' => ['required', 'string', 'max:40'],
            'variant_rows.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
        ])->validate();
        foreach ($data['variant_rows'] as $index => &$row) {
            foreach (['color', 'size'] as $kind) {
                $row[$kind] = VariantDimension::resolve($kind, $row[$kind], 'file', $index + 2);
            }
        }
        unset($row);
        $keys = collect($data['variant_rows'])->map(fn ($row) => mb_strtolower(trim($row['color'])).'|'.mb_strtolower(trim($row['size'])));
        if ($keys->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['file' => __('variants.duplicate_pair')]);
        }
        return response()->json(['rows' => $data['variant_rows']]);
    }

    public function store(Request $request, CreateProductionOrderAction $create): JsonResponse
    {
        if ($request->has('draft_lines')) {
            return $this->storeDraft($request, $create, app(CompanyContext::class));
        }

        $data = $request->validate([
            'number' => ['required', 'string', 'max:100'],
            'buyer_po_number' => ['nullable', 'string', 'max:100'],
            'no_cbd' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'destination' => ['nullable', 'string', 'max:150'],
            'po_date' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['required', 'date_format:Y-m-d'],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'output_product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)->where('product_type', 'stock')],
            'bill_of_material_id' => ['nullable', 'integer', 'exists:bill_of_materials,id'],
            'planned_quantity' => ['required_without:variant_rows', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'price_per_unit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.9999'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'style_code' => ['nullable', 'string', 'max:80'],
            'order_color' => ['nullable', 'string', 'max:80'],
            'order_size' => ['nullable', 'string', 'max:40'],
            'variant_rows' => ['sometimes', 'array', 'min:1', 'max:100'],
            'variant_rows.*.output_product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('is_active', true)->where('product_type', 'stock')],
            'variant_rows.*.color' => ['nullable', 'string', 'max:80'],
            'variant_rows.*.size' => ['required', 'string', 'max:40'],
            'variant_rows.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
        ]);
        $this->assertFinishedGood((int) $data['output_product_id']);
        $order = $this->withMoImage($request, function (?string $imagePath) use ($data, $create, $request) {
            if ($imagePath) $data['image_path'] = $imagePath;
            $variants = app(\App\Domain\Manufacturing\Services\MaterialOrderVariantService::class);
            if (!empty($data['variant_rows'])) {
                $keys = collect($data['variant_rows'])->map(fn ($row) => ($row['output_product_id'] ?? $data['output_product_id']).'|'.mb_strtolower(trim($row['color'] ?? '') ?: trim($data['order_color'] ?? '')).'|'.mb_strtolower(trim($row['size'])));
                if ($keys->duplicates()->isNotEmpty()) {
                    throw ValidationException::withMessages(['variant_rows' => __('variants.duplicate_pair')]);
                }
                $first = null;
                foreach ($data['variant_rows'] as $row) {
                    $color = trim($row['color'] ?? '') ?: trim($data['order_color'] ?? '');
                    if (!$color) {
                        throw ValidationException::withMessages(['order_color' => __('variants.color_required')]);
                    }
                    $productId = (int) ($row['output_product_id'] ?? $data['output_product_id']);
                    $this->assertFinishedGood($productId);
                    $product = $variants->resolve($productId, $color, $row['size']);
                    $line = $create(array_replace($data, ['output_product_id' => $product->id, 'planned_quantity' => $row['quantity']]), (int) $request->user()->id);
                    $first ??= $line;
                }
                return $first;
            }
            if (!empty($data['order_color']) || !empty($data['order_size'])) {
                $product = $variants->resolve((int) $data['output_product_id'], $data['order_color'] ?? '', $data['order_size'] ?? null);
                $data['output_product_id'] = $product->id;
            }
            return $create($data, (int) $request->user()->id);
        });

        return response()->json(['message' => __('ui.workflow.material_order_created'), 'id' => $order->id], 201);
    }

    private function storeDraft(Request $request, CreateProductionOrderAction $create, CompanyContext $company): JsonResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:100'],
            'buyer_po_number' => ['nullable', 'string', 'max:100'],
            'no_cbd' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'destination' => ['nullable', 'string', 'max:150'],
            'po_date' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:po_date'],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'draft_lines' => ['required', 'array', 'min:1', 'max:100'],
            'draft_lines.*' => ['required', 'array:style_code,color,size,planned_quantity,price_per_unit,description'],
            'draft_lines.*.style_code' => ['required', 'string', 'max:80'],
            'draft_lines.*.color' => ['required', 'string', 'max:80'],
            'draft_lines.*.size' => ['required', 'string', 'max:40'],
            'draft_lines.*.planned_quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'draft_lines.*.price_per_unit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.9999'],
            'draft_lines.*.description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $lines = collect($data['draft_lines'])->values()->map(function (array $line, int $index) {
            foreach (['color', 'size'] as $kind) {
                $line[$kind] = VariantDimension::resolve($kind, $line[$kind], 'draft_lines', $index + 1);
            }
            return $line;
        });
        $lineKey = fn (?string ...$fields) => json_encode(array_map(
            fn (?string $value) => mb_strtolower(trim($value ?? '')), $fields
        ));
        $keys = $lines->map(fn (array $line) => $lineKey($line['style_code'], $line['color'], $line['size']));
        if ($keys->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['draft_lines' => __('variants.duplicate_pair')]);
        }
        unset($data['draft_lines'], $data['image']);

        $order = $this->withMoImage($request, function (?string $imagePath) use ($company, $create, $data, $lines, $request, $keys, $lineKey) {
            \App\Models\Company::whereKey($company->id())->lockForUpdate()->firstOrFail();
            $existingKeys = ProductionOrder::where('company_id', $company->id())->where('number', $data['number'])
                ->with('outputProduct')->get()
                ->map(fn (ProductionOrder $line) => $lineKey($line->model_style, $line->line_color, $line->line_size));
            if ($keys->intersect($existingKeys)->isNotEmpty()) {
                throw ValidationException::withMessages(['draft_lines' => __('variants.duplicate_pair')]);
            }

            return $lines->map(fn (array $line) => $create(
                [...$data, ...$line, 'image_path' => $imagePath], (int) $request->user()->id
            ))->first();
        });

        return response()->json(['message' => __('ui.workflow.material_order_created'), 'id' => $order->id], 201);
    }

    public function update(Request $request, ProductionOrder $productionOrder, CompanyContext $company, UpdateMaterialOrderAction $update): JsonResponse
    {
        $productionOrder = $this->findForCompany($productionOrder, $company->id());
        if ($request->boolean('image_only')) {
            $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
            $updated = $this->withMoImage($request, fn (string $imagePath) => $update->updateImage($productionOrder, $imagePath));
            return response()->json(['message' => __('ui.workflow.material_order_updated'), 'id' => $updated->id]);
        }
        $data = $request->validate([
            'number' => ['required', 'string', 'max:100'],
            'buyer_po_number' => ['nullable', 'string', 'max:100'],
            'no_cbd' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'destination' => ['nullable', 'string', 'max:150'],
            'po_date' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['required', 'date_format:Y-m-d'],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'output_product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)->where('product_type', 'stock')],
            'bill_of_material_id' => ['nullable', 'integer', 'exists:bill_of_materials,id'],
            'planned_quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'price_per_unit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.9999'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'style_code' => ['nullable', 'string', 'max:80'],
            'order_color' => ['nullable', 'string', 'max:80'],
            'order_size' => ['nullable', 'string', 'max:40'],
        ]);
        $this->assertFinishedGood((int) $data['output_product_id']);
        $updated = $this->withMoImage($request, function (?string $imagePath) use ($productionOrder, $data, $request, $update) {
            Company::whereKey($productionOrder->company_id)->lockForUpdate()->firstOrFail();
            if ($imagePath) $data['image_path'] = $imagePath;
            $data['order_color'] ??= $productionOrder->order_color;
            $data['order_size'] ??= $productionOrder->order_size;
            foreach (['color', 'size'] as $kind) {
                $key = 'order_'.$kind;
                $value = $data[$key];
                if ($value !== null && $value !== '' && $value !== $productionOrder->{'line_'.$kind}) {
                    $data[$key] = VariantDimension::resolve($kind, $value, $key, 1);
                }
            }
            if (!empty($data['order_color']) || !empty($data['order_size'])) {
                $product = app(\App\Domain\Manufacturing\Services\MaterialOrderVariantService::class)
                    ->resolve((int) $data['output_product_id'], $data['order_color'] ?? '', $data['order_size'] ?? null);
                $data['output_product_id'] = $product->id;
            }
            return $update($productionOrder, $data, (int) $request->user()->id);
        });

        return response()->json(['message' => __('ui.workflow.material_order_updated'), 'id' => $updated->id]);
    }

    public function destroy(ProductionOrder $productionOrder, CompanyContext $company, AuditLogService $audit): JsonResponse
    {
        $orderId = $this->findForCompany($productionOrder, $company->id())->id;
        DB::transaction(function () use ($orderId, $company, $audit) {
            $order = ProductionOrder::query()->where('company_id', $company->id())->lockForUpdate()->findOrFail($orderId);
            if ($order->materialRequests()->exists() || $order->hasOrderMaterialRequests() || $order->stageEntries()->exists()) {
                throw ValidationException::withMessages(['order' => __('ui.workflow.material_order_locked')]);
            }
            $old = $order->load('outputProduct', 'buyer', 'materials.product')->toArray();
            $header = $order->header;
            $order->materials()->delete();
            $order->delete();
            if ($header && !$header->details()->exists()) $header->delete();
            $audit->log(action: 'DELETE', module: 'MATERIAL_ORDER', description: __('ui.workflow.material_order_deleted_audit', ['number' => $order->number]), model: $order, oldValues: $old, newValues: null);
        });
        return response()->json(['message' => __('ui.workflow.material_order_deleted')]);
    }

    private function withMoImage(Request $request, callable $callback): ProductionOrder
    {
        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('material-orders', 'public');
            if (!$path) throw ValidationException::withMessages(['image' => __('variants.upload_failed')]);
        }
        try {
            return DB::transaction(fn () => $callback($path));
        } catch (\Throwable $exception) {
            if ($path) Storage::disk('public')->delete($path);
            throw $exception;
        }
    }

    private function assertFinishedGood(int $productId): void
    {
        $product = Product::query()->whereKey($productId)->where('is_active', true)
            ->where('product_type', 'stock')->whereHas('category', fn ($query) => $query->where('code', 'FG')->orWhereRaw('LOWER(name) = ?', ['finished goods']))->exists();
        if (!$product) throw ValidationException::withMessages(['output_product_id' => __('ui.workflow.material_order_finished_good_required')]);
    }

    private function formValues(ProductionOrder $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'buyer_po_number' => $order->buyer_po_number,
            'no_cbd' => $order->no_cbd,
            'currency' => $order->currency,
            'destination' => $order->destination,
            'po_date' => $order->po_date?->format('Y-m-d'),
            'due_date' => $order->due_date?->format('Y-m-d'),
            'customer_id' => $order->customer_id,
            'output_product_id' => $order->output_product_id,
            'parent_product_id' => $order->outputProduct?->parent_product_id,
            'bill_of_material_id' => $order->bill_of_material_id,
            'planned_quantity' => (float) $order->planned_quantity,
            'price_per_unit' => (float) $order->price_per_unit,
            'description' => $order->description,
            'product' => $order->outputProduct?->variant_display_name ?? '—',
            'product_code' => $order->outputProduct?->parentProduct?->code ?: ($order->outputProduct?->code ?? '—'),
            'style' => $order->model_style,
            'image_url' => $order->image_url,
            'is_locked' => $order->materialRequests()->exists() || $order->hasOrderMaterialRequests() || $order->stageEntries()->exists(),
            'color' => $order->line_color,
            'size' => $order->line_size,
            'unit' => $order->outputProduct?->unit?->symbol ?: $order->outputProduct?->unit?->name ?: '—',
        ];
    }

    public function show(ProductionOrder $productionOrder, CompanyContext $company): JsonResponse
    {
        $order = $this->findForCompany($productionOrder, $company->id())->load(['outputProduct.unit', 'outputProduct.primaryImage', 'outputProduct.parentProduct.primaryImage', 'buyer', 'materials.product.unit', 'creator', 'releaser']);

        return response()->json([
            ...$this->formValues($order),
            'stage_url' => route('super.material-orders.stages.store', $order),
            'bom_version' => $order->bom_version,
            'planned_start_date' => $order->planned_start_date?->format('d/m/Y') ?? '—',
            'planned_end_date' => $order->planned_end_date?->format('d/m/Y') ?? '—',
            'notes' => $order->notes ?: '—',
            'status' => $order->status,
            'materials' => $order->materials->map(fn ($item) => [
                'code' => $item->product?->code ?? '—',
                'product' => $item->product?->variant_display_name ?? '—',
                'operation' => $item->operation ?: '—',
                'required_quantity' => number_format((float) $item->required_quantity, 4, ',', '.'),
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
            ]),
            'stages' => $this->stageSummary($order),
            'stage_entries' => $order->stageEntries()->latest('processed_at')->limit(20)->get()->map(fn ($entry) => [
                'stage' => $entry->stage,
                'stage_label' => __('ui.workflow.stage_' . $entry->stage),
                'input_quantity' => number_format((float) $entry->input_quantity, 4, ',', '.'),
                'good_quantity' => number_format((float) $entry->good_quantity, 4, ',', '.'),
                'rejected_quantity' => number_format((float) $entry->rejected_quantity, 4, ',', '.'),
                'waste_quantity' => number_format((float) $entry->waste_quantity, 4, ',', '.'),
                'processed_at' => $entry->processed_at?->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function recordStage(Request $request, ProductionOrder $productionOrder, CompanyContext $company, RecordProductionStageAction $record): JsonResponse
    {
        $order = $this->findForCompany($productionOrder, $company->id());
        $data = $request->validate([
            'stage' => ['required', 'in:cutting,sewing,finishing,packing'],
            'input_quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'good_quantity' => ['required', 'numeric', 'min:0', 'max:999999999999.9999'],
            'rejected_quantity' => ['required', 'numeric', 'min:0', 'max:999999999999.9999'],
            'waste_quantity' => ['required', 'numeric', 'min:0', 'max:999999999999.9999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if (in_array($data['stage'], ['cutting', 'sewing'], true)) {
            throw ValidationException::withMessages(['stage' => __('variants.use_stage_module')]);
        }
        if ($data['stage'] === 'packing') {
            throw ValidationException::withMessages(['stage' => __('ui.packing.use_module')]);
        }
        if ($data['stage'] === 'finishing') {
            throw ValidationException::withMessages(['stage' => __('ui.finished_goods.use_module')]);
        }
        $record($order, $data, (int) $request->user()->id);

        return response()->json(['message' => __('ui.workflow.production_stage_recorded')]);
    }

    private function stageSummary(ProductionOrder $order): array
    {
        $stageNames = \App\Domain\Manufacturing\Services\ProductionStageBalance::STAGES;
        $totals = $order->stageEntries()->selectRaw('stage, SUM(input_quantity) as input_quantity, SUM(good_quantity) as good_quantity, SUM(rejected_quantity) as rejected_quantity, SUM(waste_quantity) as waste_quantity')
            ->groupBy('stage')->get()->keyBy('stage');

        return collect($stageNames)->values()->map(function ($stage, $index) use ($stageNames, $totals, $order) {
            $total = $totals->get($stage);
            $processedInput = $stage === 'cutting'
                ? (float) ($total?->good_quantity ?? 0) + (float) ($total?->rejected_quantity ?? 0)
                : (float) ($total?->input_quantity ?? 0);
            $expected = $index === 0 ? (float) $order->planned_quantity : (float) ($totals->get($stageNames[$index - 1])?->good_quantity ?? 0);

            return [
                'stage' => $stage,
                'label' => __('ui.workflow.stage_' . $stage),
                'input_quantity' => number_format($processedInput, 4, ',', '.'),
                'good_quantity' => number_format((float) ($total?->good_quantity ?? 0), 4, ',', '.'),
                'rejected_quantity' => number_format((float) ($total?->rejected_quantity ?? 0), 4, ',', '.'),
                'waste_quantity' => number_format((float) ($total?->waste_quantity ?? 0), 4, ',', '.'),
                'available_quantity' => round(max(0, $expected - $processedInput), 4),
                'is_recordable' => $order->status === 'in_progress' && max(0, $expected - $processedInput) > 0,
            ];
        })->all();
    }

    public function transition(Request $request, ProductionOrder $productionOrder, CompanyContext $company, AuditLogService $audit, EnsureProductionMaterialsIssued $ensureMaterials): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:released,in_progress,cancelled']]);
        $order = $this->findForCompany($productionOrder, $company->id());

        DB::transaction(function () use ($order, $data, $request, $audit, $ensureMaterials) {
            $order = ProductionOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $transitions = [
                'draft' => ['released', 'cancelled'],
                'released' => ['in_progress', 'cancelled'],
                'in_progress' => [],
                'completed' => [],
                'cancelled' => [],
            ];
            if (!in_array($data['status'], $transitions[$order->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => __('ui.workflow.production_order_invalid_transition')]);
            }
            if ($data['status'] === 'released' && !$order->output_product_id) {
                throw ValidationException::withMessages(['output_product_id' => __('variants.link_finished_good')]);
            }
            if ($data['status'] === 'in_progress') {
                if (!$order->materials()->exists()) {
                    throw ValidationException::withMessages(['status' => __('variants.mo_material_request_required')]);
                }
                $ensureMaterials($order);
            }
            $previous = $order->status;
            $order->update([
                'status' => $data['status'],
                'released_by' => $data['status'] === 'released' ? $request->user()->id : $order->released_by,
                'released_at' => $data['status'] === 'released' ? now() : $order->released_at,
            ]);
            $audit->log(
                action: strtoupper($data['status']), module: 'PRODUCTION_ORDER',
                description: __('ui.workflow.production_order_status_audit', ['number' => $order->number, 'status' => $data['status']]),
                model: $order, oldValues: ['status' => $previous], newValues: ['status' => $data['status']],
            );
        });

        return response()->json(['message' => __('ui.workflow.production_order_status_updated')]);
    }

    private function findForCompany(ProductionOrder $order, int $companyId): ProductionOrder
    {
        return ProductionOrder::query()->where('company_id', $companyId)->findOrFail($order->id);
    }
}
