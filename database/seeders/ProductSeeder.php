<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $pcs = Unit::where('code', 'PCS')->first();
        $box = Unit::where('code', 'BOX')->first();
        $pack = Unit::where('code', 'PACK')->first();
        $kg = Unit::where('code', 'KG')->first();
        $liter = Unit::where('code', 'LTR')->first();

        $food = Category::where('code', 'CAT-FOOD')->first();
        $beverage = Category::where('code', 'CAT-BEV')->first();
        $grocery = Category::where('code', 'CAT-GROCERY')->first();
        $office = Category::where('code', 'CAT-OFFICE')->first();

        $products = [
            [
                'code' => 'PRD-0001',
                'barcode' => '899100100001',
                'sku' => 'SKU-AIR-MINERAL-600',
                'name' => 'Air Mineral 600ml',
                'short_name' => 'Air Mineral 600',
                'category_id' => $beverage?->id,
                'unit_id' => $pcs?->id,
                'product_type' => 'stock',
                'purchase_price' => 2500,
                'sales_price' => 4000,
                'min_stock' => 20,
                'max_stock' => 200,
                'reorder_point' => 30,
                'track_stock' => true,
                'taxable' => false,
                'tax_rate' => 0,
                'allow_discount' => true,
                'allow_purchase' => true,
                'allow_sales' => true,
                'description' => 'Air mineral kemasan 600ml.',
                'is_active' => true,
            ],

            [
                'code' => 'PRD-0002',
                'barcode' => '899100100002',
                'sku' => 'SKU-TEH-BOTOL',
                'name' => 'Teh Botol 350ml',
                'short_name' => 'Teh Botol',
                'category_id' => $beverage?->id,
                'unit_id' => $pcs?->id,
                'product_type' => 'stock',
                'purchase_price' => 3500,
                'sales_price' => 5000,
                'min_stock' => 20,
                'max_stock' => 150,
                'reorder_point' => 30,
                'track_stock' => true,
                'taxable' => false,
                'tax_rate' => 0,
                'allow_discount' => true,
                'allow_purchase' => true,
                'allow_sales' => true,
                'description' => 'Minuman teh siap minum kemasan botol.',
                'is_active' => true,
            ],

            [
                'code' => 'PRD-0003',
                'barcode' => '899100100003',
                'sku' => 'SKU-NASI-GORENG',
                'name' => 'Nasi Goreng',
                'short_name' => 'Nasi Goreng',
                'category_id' => $food?->id,
                'unit_id' => $pcs?->id,
                'product_type' => 'stock',
                'purchase_price' => 12000,
                'sales_price' => 20000,
                'min_stock' => 5,
                'max_stock' => 50,
                'reorder_point' => 10,
                'track_stock' => true,
                'taxable' => true,
                'tax_rate' => 11,
                'allow_discount' => true,
                'allow_purchase' => true,
                'allow_sales' => true,
                'description' => 'Produk makanan siap jual.',
                'is_active' => true,
            ],

            [
                'code' => 'PRD-0004',
                'barcode' => '899100100004',
                'sku' => 'SKU-MIE-INSTAN',
                'name' => 'Mie Instan',
                'short_name' => 'Mie Instan',
                'category_id' => $grocery?->id,
                'unit_id' => $pack?->id,
                'product_type' => 'stock',
                'purchase_price' => 2500,
                'sales_price' => 3500,
                'min_stock' => 10,
                'max_stock' => 100,
                'reorder_point' => 20,
                'track_stock' => true,
                'taxable' => false,
                'tax_rate' => 0,
                'allow_discount' => true,
                'allow_purchase' => true,
                'allow_sales' => true,
                'description' => 'Mie instan kemasan.',
                'is_active' => true,
            ],

            [
                'code' => 'PRD-0005',
                'barcode' => '899100100005',
                'sku' => 'SKU-GULA-1KG',
                'name' => 'Gula Pasir 1 Kg',
                'short_name' => 'Gula 1Kg',
                'category_id' => $grocery?->id,
                'unit_id' => $kg?->id,
                'product_type' => 'stock',
                'purchase_price' => 15000,
                'sales_price' => 18000,
                'min_stock' => 10,
                'max_stock' => 100,
                'reorder_point' => 20,
                'track_stock' => true,
                'taxable' => false,
                'tax_rate' => 0,
                'allow_discount' => true,
                'allow_purchase' => true,
                'allow_sales' => true,
                'description' => 'Gula pasir kemasan 1 kilogram.',
                'is_active' => true,
            ],

            [
                'code' => 'PRD-0006',
                'barcode' => '899100100006',
                'sku' => 'SKU-MINYAK-1L',
                'name' => 'Minyak Goreng 1 Liter',
                'short_name' => 'Minyak 1L',
                'category_id' => $grocery?->id,
                'unit_id' => $liter?->id,
                'product_type' => 'stock',
                'purchase_price' => 16000,
                'sales_price' => 19000,
                'min_stock' => 10,
                'max_stock' => 100,
                'reorder_point' => 20,
                'track_stock' => true,
                'taxable' => false,
                'tax_rate' => 0,
                'allow_discount' => true,
                'allow_purchase' => true,
                'allow_sales' => true,
                'description' => 'Minyak goreng kemasan 1 liter.',
                'is_active' => true,
            ],

            [
                'code' => 'PRD-0007',
                'barcode' => '899100100007',
                'sku' => 'SKU-KERTAS-A4',
                'name' => 'Kertas A4 80gsm',
                'short_name' => 'Kertas A4',
                'category_id' => $office?->id,
                'unit_id' => $box?->id,
                'product_type' => 'stock',
                'purchase_price' => 45000,
                'sales_price' => 55000,
                'min_stock' => 2,
                'max_stock' => 20,
                'reorder_point' => 5,
                'track_stock' => true,
                'taxable' => true,
                'tax_rate' => 11,
                'allow_discount' => true,
                'allow_purchase' => true,
                'allow_sales' => true,
                'description' => 'Kertas A4 80gsm satu box.',
                'is_active' => true,
            ],

            [
                'code' => 'PRD-0008',
                'barcode' => null,
                'sku' => 'SKU-JASA-MAINTENANCE',
                'name' => 'Jasa Maintenance',
                'short_name' => 'Maintenance',
                'category_id' => $office?->id,
                'unit_id' => $pcs?->id,
                'product_type' => 'service',
                'purchase_price' => 0,
                'sales_price' => 250000,
                'min_stock' => 0,
                'max_stock' => 0,
                'reorder_point' => 0,
                'track_stock' => false,
                'taxable' => true,
                'tax_rate' => 11,
                'allow_discount' => true,
                'allow_purchase' => false,
                'allow_sales' => true,
                'description' => 'Jasa maintenance sistem dan perangkat.',
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                [
                    'code' => $product['code'],
                ],
                $product
            );
        }
    }
}
