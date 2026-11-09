<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'RAW',
                'name' => 'Raw Material',
                'description' => 'Bahan baku utama yang digunakan dalam proses produksi.',
                'is_active' => true,
            ],
            [
                'code' => 'FAB',
                'name' => 'Fabric',
                'description' => 'Material kain atau fabric untuk kebutuhan produksi.',
                'is_active' => true,
            ],
            [
                'code' => 'ACC',
                'name' => 'Accessories',
                'description' => 'Aksesoris atau komponen pendukung produk.',
                'is_active' => true,
            ],
            [
                'code' => 'WIP',
                'name' => 'Work In Progress',
                'description' => 'Barang yang masih dalam proses produksi.',
                'is_active' => true,
            ],
            [
                'code' => 'FG',
                'name' => 'Finished Goods',
                'description' => 'Barang jadi yang siap dijual atau dikirim.',
                'is_active' => true,
            ],
            [
                'code' => 'PACK',
                'name' => 'Packaging',
                'description' => 'Material yang digunakan untuk pengemasan produk.',
                'is_active' => true,
            ],
            [
                'code' => 'SPARE',
                'name' => 'Spare Part',
                'description' => 'Suku cadang untuk kebutuhan maintenance.',
                'is_active' => true,
            ],
            [
                'code' => 'CON',
                'name' => 'Consumable',
                'description' => 'Barang habis pakai untuk operasional.',
                'is_active' => true,
            ],
            [
                'code' => 'SERVICE',
                'name' => 'Service',
                'description' => 'Produk atau layanan jasa.',
                'is_active' => true,
            ],
            [
                'code' => 'OTHER',
                'name' => 'Other',
                'description' => 'Kategori barang lainnya.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                [
                    'code' => $category['code'],
                ],
                $category
            );
        }
    }
}
