<?php

namespace Tests\Unit;

use App\Domain\Purchasing\Services\MaterialRequestLineAggregator;
use Tests\TestCase;

class MaterialRequestLineAggregatorTest extends TestCase
{
    public function test_it_combines_global_lines_of_the_same_product_without_merging_variants(): void
    {
        $result = app(MaterialRequestLineAggregator::class)->forStorage([
            ['product_id' => 10, 'color' => '', 'size' => '', 'quantity' => 2, 'note' => ''],
            ['product_id' => 10, 'color' => null, 'size' => null, 'quantity' => 3, 'note' => ''],
            ['product_id' => 10, 'color' => 'Black', 'size' => 'S', 'quantity' => 4, 'note' => ''],
            ['product_id' => 11, 'color' => null, 'size' => null, 'quantity' => 5, 'note' => ''],
        ]);

        $this->assertCount(3, $result);
        $this->assertSame(5.0, $result[0]['quantity']);
        $this->assertSame(4.0, $result[1]['quantity']);
        $this->assertSame(5.0, $result[2]['quantity']);
    }
}
