<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SimpleTshirtItemsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $categories = [];
            foreach ([
                'RAW' => 'RAW MATERIAL',
                'ACC' => 'ACCESSORIES',
                'PACK' => 'PACKAGING',
                'FG' => 'FINISHED GOODS',
            ] as $code => $name) {
                $categories[$code] = Category::firstOrCreate(['code' => $code], [
                    'name' => $name,
                    'is_active' => true,
                ])->id;
            }

            $units = [];
            foreach (['MTR', 'CNS', 'PCS', 'BOX'] as $code) {
                $units[$code] = Unit::firstOrCreate(['code' => $code], [
                    'name' => $code,
                    'symbol' => $code,
                    'is_active' => true,
                ])->id;
            }

            foreach ([
                ['KAOS-FAB-JERSEY', 'Kain Cotton Jersey Kaos Basic', 'RAW', 'MTR', 'fabric'],
                ['KAOS-FAB-RIB', 'Kain Rib Kerah Kaos Basic', 'RAW', 'MTR', 'fabric'],
                ['KAOS-ACC-THREAD', 'Benang Jahit Kaos Basic', 'ACC', 'CNS', 'accessory'],
                ['KAOS-ACC-LABEL-MAIN', 'Label Merek Kaos Basic', 'ACC', 'PCS', 'accessory'],
                ['KAOS-ACC-LABEL-SIZE', 'Label Ukuran Kaos Basic', 'ACC', 'PCS', 'accessory'],
                ['KAOS-ACC-LABEL-CARE', 'Care Label Kaos Basic', 'ACC', 'PCS', 'accessory'],
                ['KAOS-PACK-POLYBAG', 'Polybag Kaos Basic', 'PACK', 'PCS', 'packaging'],
                ['KAOS-PACK-CARTON', 'Karton Packing Kaos Basic', 'PACK', 'BOX', 'packaging'],
                ['KAOS-FG-BASIC', 'Kaos Basic Lengan Pendek', 'FG', 'PCS', 'finished_good'],
            ] as [$code, $name, $category, $unit, $type]) {
                Product::firstOrCreate(['code' => $code], [
                    'name' => $name,
                    'category_id' => $categories[$category],
                    'unit_id' => $units[$unit],
                    'product_type' => 'stock',
                    'garment_item_type' => $type,
                    'track_stock' => true,
                    'is_active' => true,
                    'is_available' => true,
                    'purchase_price' => 0,
                    'min_stock' => 0,
                    'reorder_point' => 0,
                ]);
            }
        });
    }
}
