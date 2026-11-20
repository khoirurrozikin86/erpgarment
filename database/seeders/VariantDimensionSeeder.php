<?php

namespace Database\Seeders;

use App\Models\VariantDimension;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VariantDimensionSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['BLACK', 'Hitam', ['HITAM']],
            ['WHITE', 'Putih', ['PUTIH']],
            ['NAVY', 'Biru Dongker', ['BIRU DONGKER']],
            ['LIGHT GREY', 'Abu-abu Muda', ['LIGHT GRAY', 'ABU-ABU MUDA']],
            ['GREY', 'Abu-abu', ['GRAY', 'ABU-ABU']],
            ['RED', 'Merah', ['MERAH']],
            ['BLUE', 'Biru', ['BIRU']],
            ['GREEN', 'Hijau', ['HIJAU']],
            ['YELLOW', 'Kuning', ['KUNING']],
        ];
        $sizes = array_map(fn ($code) => [$code, $code, []], [
            'XXS', 'XS', 'S', 'M', 'L', 'XL', '2XL', 'XXL', '3XL', 'XXXL', '4XL', '5XL', 'ONE SIZE',
        ]);

        DB::transaction(function () use ($colors, $sizes) {
            foreach (['color' => $colors, 'size' => $sizes] as $kind => $entries) {
                foreach ($entries as $index => [$code, $name, $aliases]) {
                    $entry = VariantDimension::firstOrCreate(['kind' => $kind, 'code' => $code], [
                        'name' => $name, 'sort_order' => ($index + 1) * 10,
                    ]);
                    if ($entry->canonical_id !== null) {
                        continue;
                    }
                    foreach ($aliases as $alias) {
                        VariantDimension::firstOrCreate(['kind' => $kind, 'code' => $alias], [
                            'canonical_id' => $entry->id,
                        ]);
                    }
                }
            }
        });
    }
}
