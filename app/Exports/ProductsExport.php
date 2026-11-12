<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Product::query()->with(['category', 'unit'])->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'code', 'barcode', 'sku', 'name', 'short_name', 'category_name', 'unit_name',
            'product_type', 'garment_item_type', 'style_code', 'color', 'size', 'hs_code',
            'composition', 'fabric_width_cm', 'fabric_weight_gsm', 'country_of_origin',
            'purchase_price', 'sales_price', 'min_stock', 'max_stock',
            'reorder_point', 'track_stock', 'taxable', 'tax_rate', 'allow_discount',
            'allow_purchase', 'allow_sales', 'description', 'is_active',
        ];
    }

    public function map($product): array
    {
        return [
            $product->code, $product->barcode, $product->sku, $product->name,
            $product->short_name, $product->category?->name, $product->unit?->name,
            $product->product_type, $product->garment_item_type, $product->style_code, $product->color, $product->size, $product->hs_code,
            $product->composition, $product->fabric_width_cm, $product->fabric_weight_gsm, $product->country_of_origin,
            $product->purchase_price, $product->sales_price,
            $product->min_stock, $product->max_stock, $product->reorder_point,
            (int) $product->track_stock, (int) $product->taxable, $product->tax_rate,
            (int) $product->allow_discount, (int) $product->allow_purchase,
            (int) $product->allow_sales, $product->description, (int) $product->is_active,
        ];
    }
}
