<?php

namespace Database\Seeders;

use App\Models\{BillOfMaterial, Category, Company, Product, Style, Unit, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Masters and BOM only: subsequent transactions are entered manually. */
class PoloThreeCombinationSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('is_active', true)->where('code', 'SD001')->first()
            ?? Company::where('is_active', true)->orderBy('id')->first();
        if (!$company) throw new RuntimeException('Perusahaan aktif belum tersedia.');
        DB::transaction(function () use ($company) {
            $style = Style::firstOrCreate(['code' => 'SDI-POLO-001'], ['name' => 'Sample Polo']);
            foreach (['FAB' => 'Fabric', 'ACC' => 'Accessories', 'FG' => 'Finished Goods', 'WIP' => 'Work in Process', 'WASTE' => 'Scrap / Reject', 'MACHINE' => 'Machine', 'EQUIPMENT' => 'Equipment', 'PACK' => 'Packaging'] as $code => $name) {
                Category::firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
            }
            foreach (['MTR' => 'Meter', 'PCS' => 'Pieces', 'KG' => 'Kilogram', 'UNIT' => 'Unit', 'CNS' => 'Cone'] as $code => $name) {
                Unit::firstOrCreate(['code' => $code], ['name' => $name, 'symbol' => $code, 'is_active' => true]);
            }
            $items = [];
            foreach ([
                ['SDI-POLO', 'Polo Shirt', 'FG', 'PCS', 'finished_good', $style->code],
                ['SDI-FABRIC', 'Fabric Polo', 'FAB', 'MTR', 'fabric', null],
                ['SDI-LABEL', 'Label Polo', 'ACC', 'PCS', 'accessory', null],
                ['SDI-THREAD', 'Benang Jahit Polo', 'ACC', 'CNS', 'accessory', null],
                ['SDI-BUTTON', 'Kancing Polo', 'ACC', 'PCS', 'accessory', null],
                ['SDI-POLYBAG', 'Polybag Polo', 'PACK', 'PCS', 'packaging', null],
                ['SDI-CARTON', 'Carton Box', 'PACK', 'PCS', 'packaging', null],
                ['SDI-POLO-CUT', 'Panel Polo Cutting', 'WIP', 'PCS', 'work_in_process', $style->code],
                ['SDI-POLO-SEW', 'Polo Sewing', 'WIP', 'PCS', 'work_in_process', $style->code],
                ['SDI-POLO-PKG', 'Polo Packing', 'WIP', 'PCS', 'work_in_process', $style->code],
                ['SDI-SCRAP-IMPORT', 'Scrap Kain Import', 'WASTE', 'KG', 'other', null],
                ['SDI-SCRAP-LOKAL', 'Scrap Kain Lokal', 'WASTE', 'KG', 'other', null],
                ['SDI-SCRAP-WIP', 'Scrap WIP', 'WASTE', 'KG', 'other', null],
                ['SDI-REJECT', 'Polo Reject', 'WASTE', 'PCS', 'other', $style->code],
                ['SDI-MACHINE-SEW', 'Mesin Jahit Single Needle', 'MACHINE', 'UNIT', 'other', null],
                ['SDI-MACHINE-OVERLOCK', 'Mesin Obras', 'MACHINE', 'UNIT', 'other', null],
                ['SDI-MACHINE-CUT', 'Mesin Potong Kain', 'MACHINE', 'UNIT', 'other', null],
                ['SDI-EQUIP-SCISSORS', 'Gunting Kain', 'EQUIPMENT', 'UNIT', 'other', null],
                ['SDI-EQUIP-IRON', 'Setrika Uap', 'EQUIPMENT', 'UNIT', 'other', null],
            ] as [$code, $name, $category, $unit, $type, $styleCode]) {
                $items[$code] = Product::firstOrCreate(['code' => $code], [
                    'name' => $name, 'category_id' => Category::where('code', $category)->value('id'),
                    'unit_id' => Unit::where('code', $unit)->value('id'), 'product_type' => 'stock',
                    'garment_item_type' => $type, 'style_code' => $styleCode,
                    'color' => null, 'size' => null, 'track_stock' => true,
                    'purchase_price' => 0, 'min_stock' => 0, 'reorder_point' => 0,
                    'is_active' => true, 'is_available' => true,
                ]);
            }
            $bom = BillOfMaterial::firstOrCreate([
                'company_id' => $company->id, 'style_code' => 'SDI-POLO-001', 'name' => 'Sample Polo — 3 kombinasi',
            ], [
                'version' => '1', 'status' => 'active', 'output_quantity' => 1,
                'created_by' => User::orderBy('id')->value('id'),
                'notes' => 'Sample manual: BLACK/S 100 PCS; NAVY/M 150 PCS; WHITE/L 200 PCS. Consumption contoh, bukan tech pack produksi.',
            ]);
            foreach ([['BLACK', 'S'], ['NAVY', 'M'], ['WHITE', 'L']] as [$color, $size]) {
                $bom->items()->firstOrCreate([
                    'product_id' => $items['SDI-FABRIC']->id, 'operation' => 'Cutting',
                    'applies_to_color' => $color, 'applies_to_size' => null,
                ], ['quantity' => 1.2, 'wastage_percent' => 0, 'notes' => 'Fabric tanpa ukuran.']);
                $bom->items()->firstOrCreate([
                    'product_id' => $items['SDI-LABEL']->id, 'operation' => 'Sewing',
                    'applies_to_color' => $color, 'applies_to_size' => $size,
                ], ['quantity' => 1, 'wastage_percent' => 0, 'notes' => 'Satu kode label, warna/size pada rincian kebutuhan.']);
            }
            $this->command?->info('Style '.$style->code.'; BOM '.$bom->number.'; '.count($items).' item global; 3 kombinasi BLACK/S, NAVY/M, WHITE/L. MO/MR/PR/PO dan stok tetap kosong.');
        });
    }
}
