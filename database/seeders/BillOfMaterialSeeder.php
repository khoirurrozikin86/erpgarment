<?php

namespace Database\Seeders;

use App\Models\BillOfMaterial;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillOfMaterialSeeder extends Seeder
{
    /**
     * Seed a reviewable sample BOM and its sample garment/material masters.
     * Quantities are demonstration values and must be replaced with an approved tech pack.
     */
    public function run(): void
    {
        $company = Company::query()->where('is_active', true)->orderBy('id')->first();

        if (!$company) {
            $this->command?->warn('BOM sample dilewati: belum ada perusahaan aktif. Jalankan CompanySeeder terlebih dahulu.');
            return;
        }

        DB::transaction(function () use ($company) {
            $categories = [
                'FAB' => ['name' => 'Fabric', 'description' => 'Demo garment fabric material.'],
                'ACC' => ['name' => 'Accessories', 'description' => 'Demo garment accessories.'],
                'PACK' => ['name' => 'Packaging', 'description' => 'Demo garment packaging materials.'],
                'FG' => ['name' => 'Finished Goods', 'description' => 'Demo finished garment items.'],
            ];

            foreach ($categories as $code => $data) {
                Category::firstOrCreate(['code' => $code], $data + ['is_active' => true]);
            }

            $units = [
                'PCS' => ['name' => 'Pieces', 'symbol' => 'pcs'],
                'MTR' => ['name' => 'Meter', 'symbol' => 'm'],
            ];
            foreach ($units as $code => $data) {
                Unit::firstOrCreate(['code' => $code], $data + ['is_active' => true]);
            }

            $products = [
                'DEMO-ANG-PANT-M' => [
                    'name' => 'Angkle Pants — Anchor Grey — M', 'category' => 'FG', 'unit' => 'PCS',
                    'garment_item_type' => 'finished_good', 'style_code' => 'DEMO-ANG-PANT',
                    'color' => 'Anchor Grey', 'size' => 'M',
                ],
                'DEMO-FAB-MAIN' => [
                    'name' => 'Demo Main Fabric', 'category' => 'FAB', 'unit' => 'MTR',
                    'garment_item_type' => 'fabric', 'style_code' => null, 'color' => null, 'size' => null,
                ],
                'DEMO-FAB-POCKET' => [
                    'name' => 'Demo Pocketing Fabric', 'category' => 'FAB', 'unit' => 'MTR',
                    'garment_item_type' => 'fabric', 'style_code' => null, 'color' => null, 'size' => null,
                ],
                'DEMO-ACC-ZIP' => [
                    'name' => 'Demo Trouser Zipper', 'category' => 'ACC', 'unit' => 'PCS',
                    'garment_item_type' => 'accessory', 'style_code' => null, 'color' => null, 'size' => null,
                ],
                'DEMO-ACC-THREAD' => [
                    'name' => 'Demo Sewing Thread', 'category' => 'ACC', 'unit' => 'MTR',
                    'garment_item_type' => 'accessory', 'style_code' => null, 'color' => null, 'size' => null,
                ],
                'DEMO-ACC-LABEL' => [
                    'name' => 'Demo Garment Label', 'category' => 'ACC', 'unit' => 'PCS',
                    'garment_item_type' => 'accessory', 'style_code' => null, 'color' => null, 'size' => null,
                ],
                'DEMO-PACK-BAG' => [
                    'name' => 'Demo Polybag', 'category' => 'PACK', 'unit' => 'PCS',
                    'garment_item_type' => 'packaging', 'style_code' => null, 'color' => null, 'size' => null,
                ],
            ];

            $createdProducts = [];
            foreach ($products as $code => $data) {
                $category = Category::query()->where('code', $data['category'])->firstOrFail();
                $unit = Unit::query()->where('code', $data['unit'])->firstOrFail();

                $createdProducts[$code] = Product::firstOrCreate(
                    ['code' => $code],
                    [
                        'name' => $data['name'],
                        'category_id' => $category->id,
                        'unit_id' => $unit->id,
                        'product_type' => 'stock',
                        'garment_item_type' => $data['garment_item_type'],
                        'style_code' => $data['style_code'],
                        'color' => $data['color'],
                        'size' => $data['size'],
                        'purchase_price' => 0,
                        'min_stock' => 0,
                        'max_stock' => 0,
                        'reorder_point' => 0,
                        'track_stock' => true,
                        'is_active' => true,
                    ],
                );
            }

            $output = $createdProducts['DEMO-ANG-PANT-M'];
            $bom = BillOfMaterial::firstOrCreate(
                ['company_id' => $company->id, 'product_id' => $output->id, 'version' => 'DEMO-1'],
                [
                    'name' => 'Angkle Pants — Anchor Grey — M (Demo)',
                    'status' => 'draft',
                    'output_quantity' => 1,
                    'notes' => 'DEMO ONLY. Replace quantities with the approved style/size tech pack before activation or production use.',
                ],
            );

            $components = [
                ['code' => 'DEMO-FAB-MAIN', 'quantity' => 1.45, 'wastage_percent' => 5, 'operation' => 'Cutting'],
                ['code' => 'DEMO-FAB-POCKET', 'quantity' => 0.35, 'wastage_percent' => 5, 'operation' => 'Cutting'],
                ['code' => 'DEMO-ACC-ZIP', 'quantity' => 1, 'wastage_percent' => 0, 'operation' => 'Sewing'],
                ['code' => 'DEMO-ACC-THREAD', 'quantity' => 12, 'wastage_percent' => 3, 'operation' => 'Sewing'],
                ['code' => 'DEMO-ACC-LABEL', 'quantity' => 2, 'wastage_percent' => 0, 'operation' => 'Finishing'],
                ['code' => 'DEMO-PACK-BAG', 'quantity' => 1, 'wastage_percent' => 0, 'operation' => 'Packing'],
            ];

            foreach ($components as $component) {
                $bom->items()->firstOrCreate(
                    ['product_id' => $createdProducts[$component['code']]->id, 'operation' => $component['operation']],
                    [
                        'quantity' => $component['quantity'],
                        'wastage_percent' => $component['wastage_percent'],
                        'notes' => 'Demo quantity; confirm against the approved tech pack.',
                    ],
                );
            }
        });

        $this->command?->info("BOM demo berstatus draft disiapkan untuk {$company->name}. Pastikan kuantitas diperiksa sebelum digunakan.");
    }
}
