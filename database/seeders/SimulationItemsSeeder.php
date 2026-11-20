<?php

namespace Database\Seeders;

use App\Domain\Products\Services\ProductVariantService;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SimulationItemsSeeder extends Seeder
{
    public function run(ProductVariantService $variants): void
    {
        DB::transaction(function () use ($variants) {
            $categories = [];
            foreach ([
                'RAW' => 'RAW MATERIAL',
                'ACC' => 'ACCESSORIES',
                'PACK' => 'PACKAGING',
                'WIP' => 'BARANG SETENGAH JADI',
                'FG' => 'FINISHED GOODS',
                'WASTE' => 'WASTE',
                'MACHINE' => 'MACHINE',
                'EQUIPMENT' => 'EQUIPMENT',
                'OFFICE' => 'OFFICE EQUIPMENT',
            ] as $code => $name) {
                $categories[$code] = Category::firstOrCreate(['code' => $code], [
                    'name' => $name,
                    'is_active' => true,
                ])->id;
            }

            $units = [];
            foreach (['MTR', 'PCS', 'CNS', 'BOX', 'KG', 'UNIT', 'ROLL'] as $code) {
                $units[$code] = Unit::firstOrCreate(['code' => $code], [
                    'name' => $code,
                    'is_active' => true,
                ])->id;
            }

            $items = [];
            foreach ([
                ['FAB-PIQUE', 'Kain CVC Pique Polo', 'RAW', 'MTR', 'fabric'],
                ['FAB-JERSEY', 'Kain Cotton Jersey Kaos', 'RAW', 'MTR', 'fabric'],
                ['FAB-RIB', 'Kain Rib Kerah', 'RAW', 'MTR', 'fabric'],
                ['FAB-TWILL', 'Kain Cotton Twill', 'RAW', 'MTR', 'fabric'],
                ['FAB-LINING', 'Kain Furing Polyester', 'RAW', 'MTR', 'fabric'],
                ['ACC-THREAD', 'Benang Jahit Polyester', 'ACC', 'CNS', 'accessory'],
                ['ACC-BUTTON', 'Kancing Polo 4 Lubang', 'ACC', 'PCS', 'accessory'],
                ['ACC-ZIPPER', 'Ritsleting Nylon 20 cm', 'ACC', 'PCS', 'accessory'],
                ['ACC-LABEL-SIZE', 'Label Ukuran Garment', 'ACC', 'PCS', 'accessory'],
                ['ACC-LABEL-CARE', 'Label Perawatan Garment', 'ACC', 'PCS', 'accessory'],
                ['ACC-HANGTAG', 'Hangtag Garment', 'ACC', 'PCS', 'accessory'],
                ['ACC-ELASTIC', 'Karet Pinggang 25 mm', 'ACC', 'MTR', 'accessory'],
                ['PACK-POLYBAG', 'Polybag Garment', 'PACK', 'PCS', 'packaging'],
                ['PACK-CARTON', 'Karton Ekspor Garment', 'PACK', 'BOX', 'packaging'],
                ['PACK-TAPE', 'Lakban Karton', 'PACK', 'ROLL', 'packaging'],
                ['PACK-STICKER', 'Stiker Ukuran Garment', 'PACK', 'PCS', 'packaging'],
                ['WIP-CUT', 'Panel Garment Setelah Cutting', 'WIP', 'PCS', 'work_in_process'],
                ['WIP-SEW', 'Garment Setelah Sewing', 'WIP', 'PCS', 'work_in_process'],
                ['WIP-PACK', 'Garment Siap Packing', 'WIP', 'PCS', 'work_in_process'],
                ['FG-POLO', 'Polo Shirt', 'FG', 'PCS', 'finished_good'],
                ['FG-TEE', 'Kaos Basic', 'FG', 'PCS', 'finished_good'],
                ['FG-SHIRT', 'Kemeja Twill', 'FG', 'PCS', 'finished_good'],
                ['WASTE-IMPORT', 'Scrap Kain Impor', 'WASTE', 'KG', 'general'],
                ['WASTE-LOCAL', 'Scrap Kain Lokal', 'WASTE', 'KG', 'general'],
                ['WASTE-WIP', 'Scrap Panel Produksi', 'WASTE', 'KG', 'general'],
                ['WASTE-REJECT', 'Garment Reject', 'WASTE', 'PCS', 'general'],
                ['MACHINE-SEW', 'Mesin Jahit Single Needle', 'MACHINE', 'UNIT', 'general'],
                ['MACHINE-OVERLOCK', 'Mesin Obras', 'MACHINE', 'UNIT', 'general'],
                ['MACHINE-CUT', 'Mesin Potong Kain', 'MACHINE', 'UNIT', 'general'],
                ['EQUIP-SCISSORS', 'Gunting Kain', 'EQUIPMENT', 'UNIT', 'general'],
                ['EQUIP-IRON', 'Setrika Uap', 'EQUIPMENT', 'UNIT', 'general'],
                ['OFFICE-SCANNER', 'Scanner Barcode Gudang', 'OFFICE', 'UNIT', 'general'],
            ] as [$code, $name, $category, $unit, $type]) {
                $items[$code] = Product::firstOrCreate(['code' => 'DEMO-'.$code], [
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

            foreach ([
                'FG-POLO' => [['BLACK', 'S'], ['NAVY', 'M'], ['WHITE', 'L']],
                'FG-TEE' => [['BLACK', 'M'], ['WHITE', 'L']],
                'FAB-PIQUE' => [['BLACK', null], ['NAVY', null], ['WHITE', null]],
                'FAB-JERSEY' => [['BLACK', null], ['WHITE', null]],
                'ACC-LABEL-SIZE' => [['', 'S'], ['', 'M'], ['', 'L']],
                'ACC-BUTTON' => [['BLACK', null], ['WHITE', null]],
            ] as $parentCode => $combinations) {
                foreach ($combinations as [$color, $size]) {
                    $variants->resolve($items[$parentCode]->id, $color, $size);
                }
            }
        });
    }
}
