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
        $parentCode = fn (string $table, string $foreign = 'product_id') =>
            "(SELECT COALESCE(parent.code, sku.code) FROM products sku LEFT JOIN products parent ON parent.id = sku.parent_product_id WHERE sku.id = {$table}.{$foreign})";
        $skuCode = fn (string $table, string $foreign = 'product_id') =>
            "(SELECT sku.code FROM products sku WHERE sku.id = {$table}.{$foreign} AND sku.parent_product_id IS NOT NULL)";
        $document = fn (string $column, string $table, string $foreign, string $source) =>
            "(SELECT {$source}.{$column} FROM {$source} WHERE {$source}.id = {$table}.{$foreign} LIMIT 1)";
        if (in_array($report, ['material-mutation', 'finished-goods-mutation'], true)) {
            $parentCode = 'COALESCE((SELECT parent.code FROM products parent WHERE parent.id = products.parent_product_id), products.code)';
            $variantColumns = $report === 'finished-goods-mutation' ? ['products.color', 'products.size'] : [null, null];
            return [null, $parentCode, 'products.code', 'products.name', ...$variantColumns, null, null, 'opening_balance', null, null, null, null, 'counted_quantity', 'count_difference', 'count_number'];
        }
        if (in_array($report, ['reject-scrap-mutation', 'capital-goods-mutation'], true)) {
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
                null, null, 'COALESCE((SELECT parent.code FROM products sku JOIN products parent ON parent.id = sku.parent_product_id WHERE sku.id = goods_receipt_items.product_id), '.$product('code', 'goods_receipt_items').')',
                $product('code', 'goods_receipt_items'), $product('name', 'goods_receipt_items'), null, null, null,
                'goods_receipt_items.quantity', 'goods_receipt_items.unit_price', 'goods_receipt_items.line_total',
                $document('currency', 'goods_receipt_items', 'goods_receipt_id', 'goods_receipts')],
            'outgoing' => [null, null, null, null, 'number', 'document_date', 'recipient', null, 'parent_code', 'sku_code', 'item_name', 'color', 'size', 'unit', 'quantity', 'unit_price', 'total_amount'],
            'wip-position' => [null, 'mo_number', 'style_code', "CONCAT(code, '/', position)", 'name', 'color', 'size', 'planned_quantity', 'symbol', 'quantity', null],
            'production-plan' => [null, 'number', 'po_date', $product('code', 'production_orders', 'output_product_id'), $product('name', 'production_orders', 'output_product_id'), $product('color', 'production_orders', 'output_product_id'), $product('size', 'production_orders', 'output_product_id'), 'planned_quantity', 'report_rejected', 'report_produced', 'report_shipped'],
            'daily-production-status' => [
                $document('name', 'production_orders', 'customer_id', 'customers'), 'number', 'buyer_po_number',
                'COALESCE(production_orders.style_code, '.$product('style_code', 'production_orders', 'output_product_id').')',
                $parentCode('production_orders', 'output_product_id'), $skuCode('production_orders', 'output_product_id'), $product('name', 'production_orders', 'output_product_id'),
                $product('color', 'production_orders', 'output_product_id'), $product('size', 'production_orders', 'output_product_id'), 'cutting_quantity', 'sewing_quantity', 'packing_quantity', 'finishing_quantity',
            ],
            'production-result' => [null,
                $document('number', 'production_order_stage_entries', 'production_order_id', 'production_orders'),
                $document('buyer_po_number', 'production_order_stage_entries', 'production_order_id', 'production_orders'),
                'packing_number', 'processed_at', null, null, null, null, null, null, 'input_quantity', 'good_quantity', null],
            'supplier-return' => [null,
                $document('number', 'purchase_return_items', 'purchase_return_id', 'purchase_returns'),
                $document('returned_at', 'purchase_return_items', 'purchase_return_id', 'purchase_returns'),
                $parentCode('purchase_return_items'), $skuCode('purchase_return_items'), $product('name', 'purchase_return_items'), null, null, null, 'purchase_return_items.quantity'],
            'warehouse-return' => [null,
                $document('number', 'production_return_items', 'production_return_document_id', 'production_return_documents'),
                $document('return_date', 'production_return_items', 'production_return_document_id', 'production_return_documents'),
                $parentCode('production_return_items'), $skuCode('production_return_items'), $product('name', 'production_return_items'), null, null, null, 'production_return_items.quantity'],
            'finished-goods-return' => [null, null, 'stock_movements.created_at', $parentCode('stock_movements'), $skuCode('stock_movements'), $product('name', 'stock_movements'), null, null, null, 'stock_movements.quantity'],
        };
    }

    public function response(Request $request, array $filters, string $report, callable $builder)
    {
        return app(\App\Support\ServerSideTable::class)->response(
            $request, $filters, $builder, self::columns($report), 'super.reports.partials.rows.'.$report
        );
    }
}
