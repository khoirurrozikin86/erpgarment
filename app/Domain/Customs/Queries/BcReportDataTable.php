<?php

namespace App\Domain\Customs\Queries;

use Illuminate\Http\Request;

class BcReportDataTable
{
    /** SQL expressions are application-owned; never accept column names from the request. */
    public static function columns(string $report): array
    {
        $product = fn (string $column, string $table, string $foreign = 'product_id') =>
            "(SELECT products.{$column} FROM products WHERE products.id = {$table}.{$foreign} LIMIT 1)";
        $document = fn (string $column, string $table, string $foreign, string $source) =>
            "(SELECT {$source}.{$column} FROM {$source} WHERE {$source}.id = {$table}.{$foreign} LIMIT 1)";
        if (in_array($report, ['material-mutation', 'finished-goods-mutation', 'reject-scrap-mutation', 'capital-goods-mutation'], true)) {
            // Formula-derived balances remain display-only, using the original report values().
            return [null, 'products.code', 'products.name', null, null, 'opening_balance', null, null, null, null, 'counted_quantity', 'count_difference', 'count_number'];
        }
        return match ($report) {
            'incoming' => [null,
                $document('customs_document_code', 'goods_receipt_items', 'goods_receipt_id', 'goods_receipts'),
                $document('customs_document_number', 'goods_receipt_items', 'goods_receipt_id', 'goods_receipts'),
                $document('customs_document_date', 'goods_receipt_items', 'goods_receipt_id', 'goods_receipts'),
                $document('number', 'goods_receipt_items', 'goods_receipt_id', 'goods_receipts'),
                $document('received_at', 'goods_receipt_items', 'goods_receipt_id', 'goods_receipts'),
                null, null, $product('code', 'goods_receipt_items'), $product('name', 'goods_receipt_items'), null,
                'goods_receipt_items.quantity', 'goods_receipt_items.unit_price', 'goods_receipt_items.line_total',
                $document('currency', 'goods_receipt_items', 'goods_receipt_id', 'goods_receipts')],
            'outgoing' => [null, null, null, null, 'number', 'document_date', 'recipient', null, 'item_code', 'item_name', 'unit', 'quantity', null, null],
            'wip-position' => [null, 'mo_number', 'style_code', "CONCAT(code, '/', position)", 'name', 'color', 'size', 'planned_quantity', 'symbol', 'quantity', null],
            'production-plan' => [null, 'number', 'po_date', $product('code', 'production_orders', 'output_product_id'), $product('name', 'production_orders', 'output_product_id'), $product('color', 'production_orders', 'output_product_id'), $product('size', 'production_orders', 'output_product_id'), 'planned_quantity', 'report_rejected', 'report_produced', null],
            'daily-production-status' => [
                $document('name', 'production_orders', 'customer_id', 'customers'), 'number', 'buyer_po_number',
                $product('style_code', 'production_orders', 'output_product_id'), $product('code', 'production_orders', 'output_product_id'), $product('name', 'production_orders', 'output_product_id'),
                $product('color', 'production_orders', 'output_product_id'), $product('size', 'production_orders', 'output_product_id'), 'planned_quantity', 'cutting_today', 'cutting_total', null, 'sewing_today', 'sewing_total', null, 'packing_today', 'packing_total', 'finishing_today', 'finishing_total', null, 'delivery_today', 'delivery_total'],
            'production-result' => [null,
                $document('number', 'production_order_stage_entries', 'production_order_id', 'production_orders'),
                $document('buyer_po_number', 'production_order_stage_entries', 'production_order_id', 'production_orders'),
                'packing_number', 'processed_at', null, null, null, null, null, 'input_quantity', 'good_quantity', null],
            'supplier-return' => [null,
                $document('number', 'purchase_return_items', 'purchase_return_id', 'purchase_returns'),
                $document('returned_at', 'purchase_return_items', 'purchase_return_id', 'purchase_returns'),
                $product('code', 'purchase_return_items'), $product('name', 'purchase_return_items'), null, 'purchase_return_items.quantity'],
            'warehouse-return' => [null,
                $document('number', 'production_return_items', 'production_return_document_id', 'production_return_documents'),
                $document('return_date', 'production_return_items', 'production_return_document_id', 'production_return_documents'),
                $product('code', 'production_return_items'), $product('name', 'production_return_items'), null, 'production_return_items.quantity'],
            'finished-goods-return' => [null, null, 'stock_movements.created_at', $product('code', 'stock_movements'), $product('name', 'stock_movements'), null, 'stock_movements.quantity'],
        };
    }

    public function response(Request $request, array $filters, string $report, callable $builder)
    {
        return app(\App\Support\ServerSideTable::class)->response(
            $request, $filters, $builder, self::columns($report), 'super.reports.partials.rows.'.$report
        );
    }
}
