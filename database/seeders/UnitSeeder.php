<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            [
                'code' => 'PCS',
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'description' => 'Satuan per buah.',
                'is_active' => true,
            ],
            [
                'code' => 'SET',
                'name' => 'Set',
                'symbol' => 'set',
                'description' => 'Satuan per set.',
                'is_active' => true,
            ],
            [
                'code' => 'BOX',
                'name' => 'Box',
                'symbol' => 'box',
                'description' => 'Satuan per box.',
                'is_active' => true,
            ],
            [
                'code' => 'PACK',
                'name' => 'Pack',
                'symbol' => 'pack',
                'description' => 'Satuan per pack.',
                'is_active' => true,
            ],
            [
                'code' => 'KG',
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'description' => 'Satuan berat kilogram.',
                'is_active' => true,
            ],
            [
                'code' => 'GRAM',
                'name' => 'Gram',
                'symbol' => 'g',
                'description' => 'Satuan berat gram.',
                'is_active' => true,
            ],
            [
                'code' => 'LTR',
                'name' => 'Liter',
                'symbol' => 'L',
                'description' => 'Satuan volume liter.',
                'is_active' => true,
            ],
            [
                'code' => 'MTR',
                'name' => 'Meter',
                'symbol' => 'm',
                'description' => 'Satuan panjang meter.',
                'is_active' => true,
            ],
            [
                'code' => 'CM',
                'name' => 'Centimeter',
                'symbol' => 'cm',
                'description' => 'Satuan panjang centimeter.',
                'is_active' => true,
            ],
            [
                'code' => 'ROLL',
                'name' => 'Roll',
                'symbol' => 'roll',
                'description' => 'Satuan per roll.',
                'is_active' => true,
            ],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                [
                    'code' => $unit['code'],
                ],
                $unit
            );
        }
    }
}
