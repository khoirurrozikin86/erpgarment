<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Purchasing\Queries\IncomingGoodsReportQuery;
use App\Exports\IncomingGoodsReportExport;
use App\Http\Controllers\Controller;
use App\Models\{GoodsReceiptItem, GoodsSentNoteItem, MaterialIssueItem, Product, ProductionOrder, ProductionOrderMaterial, ProductionOrderStageEntry};
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
        $filters = $request->validate(['mo' => ['nullable', 'integer', 'min:1'], 'export' => ['nullable', 'boolean']]);
        $product->load('unit');
        $ordersQuery = ProductionOrder::query()->where('company_id', $company->id())
            ->whereHas('materials', fn ($q) => $q->where('product_id', $product->id));
        $orders = (clone $ordersQuery)->orderBy('number')->get(['id', 'number']);
        $selected = !empty($filters['mo']) ? (clone $ordersQuery)->findOrFail($filters['mo']) : null;
        $ids = $selected ? collect([$selected->id]) : $orders->pluck('id');
        $materials = ProductionOrderMaterial::with('productionOrder.outputProduct')->where('product_id', $product->id)->whereIn('production_order_id', $ids)->get();
        $issues = MaterialIssueItem::with(['document.request.productionOrder', 'document.rawMaterialOrder'])
            ->where('product_id', $product->id)->whereHas('document', function ($q) use ($ids, $orders, $selected, $company) {
                $numbers = $selected ? [$selected->number] : $orders->pluck('number')->all();
                $q->where('company_id', $company->id())->where(function ($q) use ($ids, $numbers) {
                    $q->whereHas('request', fn ($q) => $q->whereIn('production_order_id', $ids))
                        ->orWhereHas('rawMaterialOrder', fn ($q) => $q->whereIn('production_order_number', $numbers));
                });
            })->get();
        // Only receipt lots explicitly allocated to these material issues establish provenance.
        $receipts = GoodsReceiptItem::with('goodsReceipt')->where('product_id', $product->id)
            ->whereHas('goodsReceipt', fn ($q) => $q->where('status', 'approved'))
            ->whereHas('inventoryLot.allocations.stockMovement', fn ($q) => $q->where('movement_type', 'material_issue')->whereIn('material_issue_item_id', $issues->pluck('id')))->get();
        $finished = ProductionOrderStageEntry::with('productionOrder.outputProduct.unit')->whereIn('production_order_id', $ids)->where('stage', 'finishing')->orderBy('processed_at')->get();
        $deliveries = GoodsSentNoteItem::with(['note', 'productionOrder', 'product.unit'])->whereIn('production_order_id', $ids)
            ->whereHas('note', fn ($q) => $q->where('company_id', $company->id())->whereNotNull('approved_at'))->get();
        $unit = $product->unit?->symbol ?: $product->unit?->name;
        $sections = [
            ['title' => __('bc_reports.1_production_planning_mo_and_bom'), 'headers' => [__('bc_reports.mo_no'), __('bc_reports.buyer_po'), __('bc_reports.order_date'), __('bc_reports.product_code'), __('bc_reports.material_code'), __('bc_reports.material_name'), __('bc_reports.consumption'), __('bc_reports.unit')],
                'rows' => $materials->map(fn ($m) => [$m->productionOrder?->number, $m->productionOrder?->buyer_po_number, $m->productionOrder?->po_date?->format('d-m-Y'), $m->productionOrder?->outputProduct?->code, $product->code, $product->name, $m->required_quantity, $unit])],
            ['title' => __('bc_reports.2_raw_material_receipts'), 'headers' => [__('bc_reports.receipt_no_2'), __('bc_reports.receipt_date_2'), __('bc_reports.submission_no'), __('bc_reports.customs_document'), __('bc_reports.customs_document_no'), __('bc_reports.customs_document_date'), __('bc_reports.item_code'), __('bc_reports.item_name'), __('bc_reports.received_quantity'), __('bc_reports.unit')],
                'rows' => $receipts->map(fn ($i) => [$i->goodsReceipt?->number, $i->goodsReceipt?->received_at?->format('d-m-Y'), null, $i->goodsReceipt?->customs_document_code, $i->goodsReceipt?->customs_document_number, $i->goodsReceipt?->customs_document_date?->format('d-m-Y'), $product->code, $product->name, $i->quantity, $unit]),
                'note' => __('bc_reports.receipts_are_traced_through_transfer_lot_allocations_received_quantity_is_t')],
            ['title' => __('bc_reports.3_warehouse_transfers_to_production'), 'headers' => [__('bc_reports.mo_no'), __('bc_reports.mis_no_material_order'), __('bc_reports.transfer_no'), __('bc_reports.material_code'), __('bc_reports.material_name'), __('bc_reports.unit'), __('bc_reports.transfer_date'), __('bc_reports.transferred_quantity')],
                'rows' => $issues->map(fn ($i) => [$i->document?->request?->productionOrder?->number ?? $i->document?->rawMaterialOrder?->production_order_number, $i->document?->rawMaterialOrder?->number ?? $i->document?->request?->number, $i->document?->number, $product->code, $product->name, $unit, $i->document?->issue_date?->format('d-m-Y'), $i->quantity])],
            ['title' => __('bc_reports.4_raw_material_usage_in_production_wip_out'), 'headers' => [__('bc_reports.mo_no'), __('bc_reports.production_no'), __('bc_reports.production_date'), __('bc_reports.used_quantity'), __('bc_reports.product_code'), 'Nama Produk', __('bc_reports.unit')], 'rows' => collect(), 'note' => __('bc_reports.needs_confirmation_wip_out_documents_have_no_mo_relationship_yet_transactio')],
            ['title' => __('bc_reports.5_finished_goods_completion'), 'headers' => [__('bc_reports.mo_no'), __('bc_reports.production_no'), __('bc_reports.production_date'), __('bc_reports.product_code'), 'Nama Produk', __('bc_reports.unit'), __('bc_reports.produced_quantity_2')],
                'rows' => $finished->map(fn ($e) => [$e->productionOrder?->number, $e->finished_goods_number, $e->processed_at?->format('d-m-Y'), $e->productionOrder?->outputProduct?->code, $e->productionOrder?->outputProduct?->name, $e->productionOrder?->outputProduct?->unit?->symbol, $e->good_quantity])],
            ['title' => __('bc_reports.6_delivery_notes'), 'headers' => [__('bc_reports.mo_no'), __('bc_reports.issue_no_2'), __('bc_reports.issue_date_2'), __('bc_reports.submission_no'), __('bc_reports.customs_document'), __('bc_reports.customs_document_no'), __('bc_reports.customs_document_date'), __('bc_reports.product_code'), 'Nama Produk', __('bc_reports.unit'), __('bc_reports.issued_quantity')],
                'rows' => $deliveries->map(fn ($i) => [$i->productionOrder?->number, $i->note?->number, $i->note?->sent_date?->format('d-m-Y'), null, null, null, null, $i->product?->code, $i->product?->name, $i->product?->unit?->symbol, $i->quantity]), 'note' => __('bc_reports.submission_numbers_and_outgoing_customs_documents_are_not_yet_available_on_')],
        ];
        $requiredQuantity = $materials->sum('required_quantity');
        $data = compact('product', 'orders', 'selected', 'sections', 'requiredQuantity');
        if ($request->boolean('export')) return Excel::download(new \App\Exports\IncomingGoodsTraceExport($data), 'lacak-pemasukan-bahan.xlsx');
        return view('super.reports.incoming.trace', $data);
    }
}
