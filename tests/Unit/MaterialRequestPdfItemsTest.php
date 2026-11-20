<?php

namespace Tests\Unit;

use App\Domain\Purchasing\Services\MaterialRequestPdfItems;
use Tests\TestCase;

class MaterialRequestPdfItemsTest extends TestCase
{
    public function test_it_combines_dimensionless_rows_and_sorts_variant_details_by_color_then_size(): void
    {
        $items = collect([
            $this->item('FAB-01', 'Fabric', null, null, 2, 'Meter'),
            $this->item('FAB-01', 'Fabric', null, null, 3, 'Meter'),
            $this->item('FAB-01', 'Fabric', 'White', 'M', 6, 'Meter'),
            $this->item('FAB-01', 'Fabric', 'Black', 'M', 5, 'Meter'),
            $this->item('FAB-01', 'Fabric', 'Black', 'S', 4, 'Meter'),
        ]);

        $result = app(MaterialRequestPdfItems::class)->forPdf($items);

        $this->assertCount(4, $result);
        $this->assertSame(5.0, $result[0]['quantity']);
        $this->assertSame([null, 'Black', 'Black', 'White'], $result->pluck('color')->all());
        $this->assertSame(['S', 'M'], $result->slice(1, 2)->pluck('size')->all());
    }

    private function item(string $code, string $name, ?string $color, ?string $size, float $quantity, string $unit): object
    {
        return (object) [
            'id' => 1,
            'product' => (object) [
                'code' => $code,
                'name' => $name,
                'color' => $color,
                'size' => $size,
                'unit' => (object) ['symbol' => $unit, 'name' => $unit],
            ],
            'color' => null,
            'size' => null,
            'quantity' => $quantity,
            'note' => null,
        ];
    }
}
