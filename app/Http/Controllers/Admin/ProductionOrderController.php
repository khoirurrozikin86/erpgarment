<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\CreateProductionOrderAction;
use App\Domain\Manufacturing\Actions\UpdateMaterialOrderAction;
use App\Domain\Manufacturing\Actions\RecordProductionStageAction;
use App\Domain\Manufacturing\Services\EnsureProductionMaterialsIssued;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderStageEntry;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductionOrderController extends Controller
{
    public function index(CompanyContext $company)
    {
        $orders = ProductionOrder::query()->where('company_id', $company->id())
            ->with(['outputProduct.unit', 'buyer'])
            ->orderByDesc('created_at')->get();
        $products = Product::query()->where('is_active', true)->whereNotNull('style_code')
            ->whereHas('category', fn ($query) => $query->where('code', 'FG')->orWhereRaw('LOWER(name) = ?', ['finished goods']))
            ->with('unit:id,name,symbol')->orderBy('name')->get(['id', 'code', 'name', 'style_code', 'unit_id']);
        $customers = Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $defaultCurrency = $company->get()->currency ?: 'USD';

        return view('super.production-orders.index', compact('orders', 'products', 'customers', 'defaultCurrency'));
    }

    public function store(Request $request, CreateProductionOrderAction $create): JsonResponse
    {
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
            'planned_quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'price_per_unit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.9999'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $this->assertFinishedGood((int) $data['output_product_id']);
        $order = $create($data, (int) $request->user()->id);

        return response()->json(['message' => __('ui.workflow.material_order_created'), 'id' => $order->id], 201);
    }

    public function update(Request $request, ProductionOrder $productionOrder, CompanyContext $company, UpdateMaterialOrderAction $update): JsonResponse
    {
        $this->findForCompany($productionOrder, $company->id());
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
            'planned_quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'price_per_unit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.9999'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $this->assertFinishedGood((int) $data['output_product_id']);
        $updated = $update($productionOrder, $data, (int) $request->user()->id);

        return response()->json(['message' => __('ui.workflow.material_order_updated'), 'id' => $updated->id]);
    }

    public function destroy(ProductionOrder $productionOrder, CompanyContext $company, AuditLogService $audit): JsonResponse
    {
        $orderId = $this->findForCompany($productionOrder, $company->id())->id;
        DB::transaction(function () use ($orderId, $company, $audit) {
            $order = ProductionOrder::query()->where('company_id', $company->id())->lockForUpdate()->findOrFail($orderId);
            if ($order->materialRequests()->exists() || $order->stageEntries()->exists()) {
                throw ValidationException::withMessages(['order' => __('ui.workflow.material_order_locked')]);
            }
            $old = $order->load('outputProduct', 'buyer', 'materials.product')->toArray();
            $order->materials()->delete();
            $order->delete();
            $audit->log(action: 'DELETE', module: 'MATERIAL_ORDER', description: __('ui.workflow.material_order_deleted_audit', ['number' => $order->number]), model: $order, oldValues: $old, newValues: null);
        });
        return response()->json(['message' => __('ui.workflow.material_order_deleted')]);
    }

    private function assertFinishedGood(int $productId): void
    {
        $product = Product::query()->whereKey($productId)->where('is_active', true)
            ->whereNotNull('style_code')->whereHas('category', fn ($query) => $query->where('code', 'FG')->orWhereRaw('LOWER(name) = ?', ['finished goods']))->exists();
        if (!$product) throw ValidationException::withMessages(['output_product_id' => __('ui.workflow.material_order_finished_good_required')]);
    }

    public function show(ProductionOrder $productionOrder, CompanyContext $company): JsonResponse
    {
        $order = $this->findForCompany($productionOrder, $company->id())->load(['outputProduct.unit', 'buyer', 'materials.product.unit', 'creator', 'releaser']);

        return response()->json([
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
            'planned_quantity' => (float) $order->planned_quantity,
            'price_per_unit' => (float) $order->price_per_unit,
            'description' => $order->description,
            'stage_url' => route('super.material-orders.stages.store', $order),
            'number' => $order->number,
            'product' => $order->outputProduct?->name ?? '—',
            'product_code' => $order->outputProduct?->code ?? '—',
            'style' => $order->outputProduct?->style_code ?? '—',
            'planned_quantity' => number_format((float) $order->planned_quantity, 4, ',', '.'),
            'unit' => $order->outputProduct?->unit?->symbol ?: $order->outputProduct?->unit?->name ?: '—',
            'bom_version' => $order->bom_version,
            'planned_start_date' => $order->planned_start_date?->format('d/m/Y') ?? '—',
            'planned_end_date' => $order->planned_end_date?->format('d/m/Y') ?? '—',
            'notes' => $order->notes ?: '—',
            'status' => $order->status,
            'materials' => $order->materials->map(fn ($item) => [
                'code' => $item->product?->code ?? '—',
                'product' => $item->product?->name ?? '—',
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
        $record($order, $data, (int) $request->user()->id);

        return response()->json(['message' => __('ui.workflow.production_stage_recorded')]);
    }

    private function stageSummary(ProductionOrder $order): array
    {
        $stageNames = ['cutting', 'sewing', 'finishing', 'packing'];
        $totals = $order->stageEntries()->selectRaw('stage, SUM(input_quantity) as input_quantity, SUM(good_quantity) as good_quantity, SUM(rejected_quantity) as rejected_quantity, SUM(waste_quantity) as waste_quantity')
            ->groupBy('stage')->get()->keyBy('stage');

        return collect($stageNames)->values()->map(function ($stage, $index) use ($stageNames, $totals, $order) {
            $total = $totals->get($stage);
            $processedInput = (float) ($total?->input_quantity ?? 0);
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
            if ($data['status'] === 'in_progress') {
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
