<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'ACC', 'name' => 'ACCESSORIES'],
            ['code' => 'WIP', 'name' => 'BARANG SETENGAH JADI'],
            ['code' => 'EQUIP', 'name' => 'EQUIPMENT'],
            ['code' => 'FG', 'name' => 'FINISHED GOODS'],
            ['code' => 'MACHINE', 'name' => 'MACHINE'],
            ['code' => 'OFFICE', 'name' => 'OFFICE EQUIPMENT'],
            ['code' => 'RAW', 'name' => 'RAW MATERIAL'],
            ['code' => 'WASTE', 'name' => 'WASTE'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['code' => $category['code']],
                $category + ['is_active' => true],
            );
        }
    }
}
