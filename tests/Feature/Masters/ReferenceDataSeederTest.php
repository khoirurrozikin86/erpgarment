<?php

namespace Tests\Feature\Masters;

use App\Models\Category;
use App\Models\Unit;
use Database\Seeders\CategorySeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_and_unit_seeders_add_the_reference_values_idempotently(): void
    {
        $this->seed([CategorySeeder::class, UnitSeeder::class]);
        $this->seed([CategorySeeder::class, UnitSeeder::class]);

        $this->assertSame([
            'ACCESSORIES', 'BARANG SETENGAH JADI', 'EQUIPMENT', 'FINISHED GOODS',
            'MACHINE', 'OFFICE EQUIPMENT', 'RAW MATERIAL', 'WASTE',
        ], Category::query()->orderBy('name')->pluck('name')->all());

        $expectedUnits = [
            'DRUM', 'BG', 'BL', 'BOX', 'BTL', 'CAN', 'CASE', 'CJ', 'CM', 'CNS', 'CR', 'CT',
            'DPR', 'DZN', 'EA', 'GRM', 'GRO', 'ITEM', 'KARUNG', 'KdSat', 'KG', 'KGM', 'KGS',
            'KIT', 'KRJG', 'LBR', 'LM', 'LTR', 'MTR', 'MTS', 'NIU', 'PACK', 'PAIL', 'PAIRS',
            'PCE', 'PCS', 'PCS/SET', 'PK', 'RIM', 'RO', 'ROLL', 'SET', 'TNE', 'TRUCK', 'UNIT', 'YDS', 'YRD',
        ];
        $actualUnits = Unit::query()->pluck('code')->all();
        sort($expectedUnits, SORT_STRING);
        sort($actualUnits, SORT_STRING);
        $this->assertSame($expectedUnits, $actualUnits);
        $this->assertDatabaseHas('units', ['code' => 'KdSat', 'name' => 'KdSat', 'is_active' => true]);
        $this->assertDatabaseHas('units', ['code' => 'PCS/SET', 'name' => 'PCS/SET', 'is_active' => true]);
    }
}
