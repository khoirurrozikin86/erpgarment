<?php

namespace Database\Seeders;

use App\Models\PriceList;
use App\Models\Product;
use Illuminate\Database\Seeder;

class PriceListSeeder extends Seeder
{
    public function run(): void
    {
        $lists = [
            [
                'code' => 'PL-SALES-DEFAULT',
                'name' => 'Harga Jual Umum',
                'type' => 'sales',
                'price_column' => 'sales_price',
                'allow_column' => 'allow_sales',
            ],
            [
                'code' => 'PL-PURCHASE-DEFAULT',
                'name' => 'Harga Beli Umum',
                'type' => 'purchase',
                'price_column' => 'purchase_price',
                'allow_column' => 'allow_purchase',
            ],
        ];

        foreach ($lists as $definition) {
            $priceList = PriceList::firstOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'type' => $definition['type'],
                    'is_active' => true,
                    'notes' => 'Pricelist awal yang dibuat otomatis dari harga master barang.',
                ]
            );

            Product::query()
                ->where($definition['allow_column'], true)
                ->orderBy('id')
                ->get(['id', $definition['price_column']])
                ->each(function (Product $product) use ($priceList, $definition) {
                    $priceList->items()->firstOrCreate(
                        ['product_id' => $product->id],
                        ['price' => $product->{$definition['price_column']}]
                    );
                });
        }
    }
}
