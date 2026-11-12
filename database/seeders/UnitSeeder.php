<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            'DRUM', 'BG', 'BL', 'BOX', 'BTL', 'CAN', 'CASE', 'CJ', 'CM', 'CNS',
            'CR', 'CT', 'DPR', 'DZN', 'EA', 'GRM', 'GRO', 'ITEM', 'KARUNG', 'KdSat',
            'KG', 'KGM', 'KGS', 'KIT', 'KRJG', 'LBR', 'LM', 'LTR', 'MTR', 'MTS',
            'NIU', 'PACK', 'PAIL', 'PAIRS', 'PCE', 'PCS', 'PCS/SET', 'PK', 'RIM',
            'RO', 'ROLL', 'SET', 'TNE', 'TRUCK', 'UNIT', 'YDS', 'YRD',
        ];

        foreach ($codes as $code) {
            Unit::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $code,
                    'symbol' => null,
                    'description' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
