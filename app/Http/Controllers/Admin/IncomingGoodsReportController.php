<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Purchasing\Queries\IncomingGoodsReportQuery;
use App\Exports\IncomingGoodsReportExport;
use App\Http\Controllers\Controller;
use App\Models\{GoodsReceiptItem, GoodsSentNoteItem, MaterialIssueItem, Product, ProductionOrder, ProductionOrderMaterial, ProductionOrderStageEntry};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class IncomingGoodsReportController extends Controller
{
    private function filters(Request $request): array
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
        $filters['from_date'] ??= now()->startOfMonth()->toDateString();
        $filters['to_date'] ??= now()->toDateString();
        if ($filters['to_date'] < $filters['from_date']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['to_date' => __('bc_reports.the_end_date_must_be_on_or_after_the_start_date')]);
        }
        return $filters;
    }

    public function index(Request $request, IncomingGoodsReportQuery $query)
    {
        $filters = $this->filters(new Request($request->except(['search'])));
        if ($request->ajax()) {
            return app(\App\Domain\Customs\Queries\BcReportDataTable::class)->response(
                $request, $filters, 'incoming', fn (array $filters) => $query->builder($filters)
            );
        }
        return view('super.reports.incoming.index', compact('filters'));
    }

    public function export(Request $request)
    {
        return Excel::download(new IncomingGoodsReportExport($this->filters($request)), 'laporan-pemasukan-barang.xlsx');
    }

    public function trace(Request $request, Product $product, CompanyContext $company)
    {
        $filters = $request->validate(['mo' => ['nullable', 'integer', 'min:1'], 'receipt_item' => ['nullable', 'integer', 'min:1'], 'export' => ['nullable', 'boolean']]);
        $product->load(['unit', 'parentProduct:id,code']);
        $selectedReceiptItem = isset($filters['receipt_item'])
            ? GoodsReceiptItem::with(['goodsReceipt', 'purchaseOrderItem.purchaseRequestItem.materialRequestItem.request'])
                ->where('product_id', $product->id)
                ->whereHas('goodsReceipt', fn ($query) => $query->where('status', 'approved'))
                ->findOrFail($filters['receipt_item'])
            : null;
        $color = __('ui.workflow.color');
        $size = __('ui.workflow.size');
        $selectedReceiptItem?->setRelation('product', $product);
        $source = $selectedReceiptItem?->purchaseOrderItem?->purchaseRequestItem?->materialRequestItem?->request;
        $pickedColor = mb_strtolower(trim($selectedReceiptItem?->received_color ?? $product->color ?? ''));
        $pickedSize = mb_strtolower(trim($selectedReceiptItem?->received_size ?? $product->size ?? ''));
        $matchingMaterial = function (Builder $materials) use ($product, $pickedColor, $pickedSize, $source) {
            $materials->where(fn (Builder $codes) => $codes->where('product_id', $product->id)->when(
                $product->parent_product_id,
                fn (Builder $variants) => $variants->orWhere(fn (Builder $base) => $base
                    ->where('product_id', $product->parent_product_id)
                    ->whereRaw("LOWER(TRIM(COALESCE(color, ''))) = ?", [$pickedColor])
                    ->whereRaw("LOWER(TRIM(COALESCE(size, ''))) = ?", [$pickedSize]))
            ));
            if ($source?->bill_of_material_id) {
                $bomId = $source->bill_of_material_id;
                $materials->where(fn (Builder $bom) => $bom->where('bill_of_material_id', $bomId)
                    ->orWhere(fn (Builder $legacy) => $legacy->whereNull('bill_of_material_id')
                        ->whereHas('productionOrder', fn (Builder $order) => $order->where('bill_of_material_id', $bomId))));
            }
        };
        $ordersQuery = ProductionOrder::query()->where('company_id', $company->id())
            ->whereHas('materials', $matchingMaterial)
            ->when($source?->production_order_header_id, fn (Builder $orders, $id) => $orders->where('production_order_header_id', $id))
            ->when($source?->production_order_id && !$source->production_order_header_id,
                fn (Builder $orders) => $orders->whereKey($source->production_order_id));
        $orderLines = (clone $ordersQuery)->orderBy('number')->orderBy('id')->get(['id', 'number']);
        $ordersByNumber = $orderLines->groupBy('number');
        $orders = $ordersByNumber->map(fn ($lines) => $lines->first())->values();
        $selectedLine = isset($filters['mo']) ? $orderLines->firstWhere('id', (int) $filters['mo']) : null;
        abort_if(isset($filters['mo']) && !$selectedLine, 404);
        $selectedLines = $selectedLine ? $ordersByNumber->get($selectedLine->number) : $orderLines;
        $selected = $selectedLine ? $selectedLines->first() : null;
        $ids = $selectedLines->pluck('id');
        $materials = ProductionOrderMaterial::with(['productionOrder.outputProduct', 'productionOrder.billOfMaterial', 'billOfMaterial', 'product'])
            ->whereIn('production_order_id', $ids)->where($matchingMaterial)->get();
        $issues = MaterialIssueItem::with(['product', 'document.request.productionOrder', 'document.rawMaterialOrder', 'requestItem:id,color,size', 'rawMaterialOrderItem.sourceMaterial:id,color,size'])
            ->where('product_id', $product->id)->whereHas('document', function ($q) use ($orders, $selected, $company) {
                $numbers = $selected ? [$selected->number] : $orders->pluck('number')->all();
                $q->where('company_id', $company->id())->where(function ($q) use ($numbers, $company) {
                    $q->whereHas('request.productionOrder', fn ($q) => $q->where('company_id', $company->id())->whereIn('number', $numbers))
                        ->orWhereHas('rawMaterialOrder', fn ($q) => $q->whereIn('production_order_number', $numbers));
                });
            })->get();
        $receipts = GoodsReceiptItem::with(['goodsReceipt', 'purchaseOrderItem.purchaseRequestItem.materialRequestItem', 'product'])->where('product_id', $product->id)
            ->whereHas('goodsReceipt', fn ($q) => $q->where('status', 'approved'))
            ->where(function (Builder $sources) use ($selectedReceiptItem, $issues) {
                $sources->whereHas('inventoryLot.allocations.stockMovement', fn (Builder $movements) =>
                    $movements->where('movement_type', 'material_issue')->whereIn('material_issue_item_id', $issues->pluck('id')));
                if ($selectedReceiptItem) {
                    $sources->orWhere('goods_receipt_items.id', $selectedReceiptItem->id);
                }
            })->orderBy('goods_receipt_id')->orderBy('id')->get();
        $finished = ProductionOrderStageEntry::with('productionOrder.outputProduct.unit')->whereIn('production_order_id', $ids)->where('stage', 'finishing')->orderBy('processed_at')->get();
        $deliveries = GoodsSentNoteItem::with(['note', 'productionOrder', 'product.unit'])->whereIn('production_order_id', $ids)
            ->whereHas('note', fn ($q) => $q->where('company_id', $company->id())->whereNotNull('approved_at'))->get();
        $unit = $product->unit?->symbol ?: $product->unit?->name;
        $sections = [
            ['title' => __('bc_reports.1_production_planning_mo_and_bom'), 'headers' => [__('bc_reports.mo_no'), __('bc_reports.buyer_po'), __('bc_reports.order_date'), __('variants.bom_number'), __('bc_reports.product_code'), __('variants.stock_sku'), __('bc_reports.material_name'), $color, $size, __('bc_reports.consumption'), __('bc_reports.unit')],
                'rows' => $materials->map(fn ($m) => [$m->productionOrder?->number, $m->productionOrder?->buyer_po_number, $m->productionOrder?->po_date?->format('d-m-Y'), $m->billOfMaterial?->number ?? $m->productionOrder?->billOfMaterial?->number, $m->productionOrder?->outputProduct?->code, $product->code, $m->product?->name, $m->color ?: $product->color, $m->size ?: $product->size, $m->required_quantity, $unit])],
            ['title' => __('bc_reports.2_raw_material_receipts'), 'headers' => [__('bc_reports.receipt_no_2'), __('bc_reports.receipt_date_2'), __('bc_reports.submission_no'), __('bc_reports.customs_document'), __('bc_reports.customs_document_no'), __('bc_reports.customs_document_date'), __('bc_reports.item_code'), __('bc_reports.item_name'), $color, $size, __('bc_reports.received_quantity'), __('bc_reports.unit')],
                'rows' => $receipts->map(fn ($i) => [$i->goodsReceipt?->number, $i->goodsReceipt?->received_at?->format('d-m-Y'), null, $i->goodsReceipt?->customs_document_code, $i->goodsReceipt?->customs_document_number, $i->goodsReceipt?->customs_document_date?->format('d-m-Y'), $i->product?->code, $i->product?->name, $i->received_color, $i->received_size, $i->quantity, $unit]),
                'note' => __('bc_reports.receipts_are_traced_through_transfer_lot_allocations_received_quantity_is_t')],
            ['title' => __('bc_reports.3_warehouse_transfers_to_production'), 'headers' => [__('bc_reports.mo_no'), __('bc_reports.mis_no_material_order'), __('bc_reports.transfer_no'), __('bc_reports.material_code'), __('bc_reports.material_name'), $color, $size, __('bc_reports.unit'), __('bc_reports.transfer_date'), __('bc_reports.transferred_quantity')],
                'rows' => $issues->map(fn ($i) => [$i->document?->request?->productionOrder?->number ?? $i->document?->rawMaterialOrder?->production_order_number, $i->document?->rawMaterialOrder?->number ?? $i->document?->request?->number, $i->document?->number, $i->product?->code, $i->product?->name,
                    $i->rawMaterialOrderItem?->color ?: $i->rawMaterialOrderItem?->sourceMaterial?->color ?: $i->requestItem?->color ?: $product->color,
                    $i->rawMaterialOrderItem?->size ?: $i->rawMaterialOrderItem?->sourceMaterial?->size ?: $i->requestItem?->size ?: $product->size,
                    $unit, $i->document?->issue_date?->format('d-m-Y'), $i->quantity])],
            ['title' => __('bc_reports.4_raw_material_usage_in_production_wip_out'), 'headers' => [__('bc_reports.mo_no'), __('bc_reports.production_no'), __('bc_reports.production_date'), __('bc_reports.used_quantity'), __('bc_reports.product_code'), 'Nama Produk', __('bc_reports.unit')], 'rows' => collect(), 'note' => __('bc_reports.needs_confirmation_wip_out_documents_have_no_mo_relationship_yet_transactio')],
            ['title' => __('bc_reports.5_finished_goods_completion'), 'headers' => [__('bc_reports.mo_no'), __('bc_reports.production_no'), __('bc_reports.production_date'), __('bc_reports.product_code'), 'Nama Produk', $color, $size, __('bc_reports.unit'), __('bc_reports.produced_quantity_2')],
                'rows' => $finished->map(fn ($e) => [$e->productionOrder?->number, $e->finished_goods_number, $e->processed_at?->format('d-m-Y'), $e->productionOrder?->outputProduct?->code, $e->productionOrder?->outputProduct?->name, $e->productionOrder?->line_color, $e->productionOrder?->line_size, $e->productionOrder?->outputProduct?->unit?->symbol, $e->good_quantity])],
            ['title' => __('bc_reports.6_delivery_notes'), 'headers' => [__('bc_reports.mo_no'), __('bc_reports.issue_no_2'), __('bc_reports.issue_date_2'), __('bc_reports.submission_no'), __('bc_reports.customs_document'), __('bc_reports.customs_document_no'), __('bc_reports.customs_document_date'), __('bc_reports.product_code'), 'Nama Produk', $color, $size, __('bc_reports.unit'), __('bc_reports.issued_quantity')],
                'rows' => $deliveries->map(fn ($i) => [$i->productionOrder?->number, $i->note?->number, $i->note?->sent_date?->format('d-m-Y'), null, null, null, null, $i->product?->code, $i->product?->name, $i->product?->color, $i->product?->size, $i->product?->unit?->symbol, $i->quantity]), 'note' => __('bc_reports.submission_numbers_and_outgoing_customs_documents_are_not_yet_available_on_')],
        ];
        $requiredQuantity = $materials->sum('required_quantity');
        $data = compact('product', 'orders', 'selected', 'selectedReceiptItem', 'sections', 'requiredQuantity');
        if ($request->boolean('export')) return Excel::download(new \App\Exports\IncomingGoodsTraceExport($data), 'lacak-pemasukan-bahan.xlsx');
        return view('super.reports.incoming.trace', $data);
    }
}
