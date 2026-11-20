<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScrapAndCapitalItemsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $categories = [];
            foreach ([
                'WASTE' => 'WASTE',
                'REJECT' => 'REJECT',
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
            foreach (['KG', 'PCS', 'UNIT', 'SET'] as $code) {
                $units[$code] = Unit::firstOrCreate(['code' => $code], [
                    'name' => $code,
                    'symbol' => $code,
                    'is_active' => true,
                ])->id;
            }

            foreach ([
                ['SCRAP-KAIN-LOKAL', 'Scrap Potongan Kain Lokal', 'WASTE', 'KG'],
                ['SCRAP-KAIN-IMPOR', 'Scrap Potongan Kain Impor', 'WASTE', 'KG'],
                ['SCRAP-PANEL', 'Scrap Panel Cutting', 'WASTE', 'KG'],
                ['SCRAP-BENANG', 'Scrap Benang Jahit', 'WASTE', 'KG'],
                ['SCRAP-KARTON', 'Scrap Karton Packing', 'WASTE', 'KG'],
                ['SCRAP-PLASTIK', 'Scrap Plastik Packing', 'WASTE', 'KG'],
                ['REJECT-CUT', 'Panel Reject Cutting', 'REJECT', 'PCS'],
                ['REJECT-SEW', 'Garment Reject Sewing', 'REJECT', 'PCS'],
                ['REJECT-PACK', 'Garment Reject Packing', 'REJECT', 'PCS'],
                ['REJECT-FG', 'Barang Jadi Reject QC', 'REJECT', 'PCS'],
                ['MESIN-JAHIT', 'Mesin Jahit Jarum Satu', 'MACHINE', 'UNIT'],
                ['MESIN-OBRAS', 'Mesin Obras', 'MACHINE', 'UNIT'],
                ['MESIN-OVERDECK', 'Mesin Overdeck', 'MACHINE', 'UNIT'],
                ['MESIN-POTONG', 'Mesin Potong Kain Pisau Lurus', 'MACHINE', 'UNIT'],
                ['MESIN-LUBANG-KANCING', 'Mesin Lubang Kancing', 'MACHINE', 'UNIT'],
                ['MESIN-PRESS', 'Mesin Heat Press Garment', 'MACHINE', 'UNIT'],
                ['ALAT-GUNTING', 'Gunting Kain Produksi', 'EQUIPMENT', 'UNIT'],
                ['ALAT-SETRIKA', 'Setrika Uap Produksi', 'EQUIPMENT', 'UNIT'],
                ['ALAT-TIMBANG', 'Timbangan Digital Gudang', 'EQUIPMENT', 'UNIT'],
                ['ALAT-TROLI', 'Troli Barang Gudang', 'EQUIPMENT', 'UNIT'],
                ['ALAT-RAK', 'Rak Penyimpanan Gudang', 'EQUIPMENT', 'SET'],
                ['ALAT-HAND-PALLET', 'Hand Pallet Gudang', 'EQUIPMENT', 'UNIT'],
                ['KANTOR-SCANNER', 'Scanner Barcode Gudang', 'OFFICE', 'UNIT'],
                ['KANTOR-PRINTER', 'Printer Label Barcode', 'OFFICE', 'UNIT'],
                ['KANTOR-KOMPUTER', 'Komputer Administrasi Produksi', 'OFFICE', 'SET'],
            ] as [$code, $name, $category, $unit]) {
                Product::firstOrCreate(['code' => 'DEMO-OPR-'.$code], [
                    'name' => $name,
                    'category_id' => $categories[$category],
                    'unit_id' => $units[$unit],
                    'product_type' => 'stock',
                    'garment_item_type' => 'general',
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
