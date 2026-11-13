<?php

namespace Tests\Feature\Purchasing;

use App\Exports\GoodsReceiptsExport;
use Carbon\Carbon;
use Tests\TestCase;

class GoodsReceiptsExportTest extends TestCase
{
    public function test_export_filters_by_receipt_date(): void
    {
        $query = (new GoodsReceiptsExport('2026-11-01', '2026-11-13'))->query();

        $this->assertStringContainsString('goods_receipt_id', $query->toSql());
        $this->assertCount(2, $query->getBindings());
    }

    public function test_export_maps_each_receipt_item_as_its_own_row(): void
    {
        $export = new GoodsReceiptsExport();
        $item = (object) [
            'goodsReceipt' => (object) [
                'number' => 'GRN-001',
                'received_at' => Carbon::parse('2026-11-12'),
                'invoice_number' => 'INV-10',
                'invoice_date' => Carbon::parse('2026-11-11'),
                'supplier' => (object) ['name' => 'Supplier A'],
                'currency' => 'USD',
                'status' => 'approved',
                'notes' => 'Receipt note',
                'purchaseOrder' => (object) [
                    'number' => 'PO-001',
                    'purchaseRequest' => (object) [
                        'materialRequest' => (object) [
                            'number' => 'MR-001',
                            'productionOrder' => (object) [
                                'number' => 'MO-001',
                                'outputProduct' => (object) ['style_code' => 'STYLE-1'],
                            ],
                            'outputProduct' => null,
                        ],
                    ],
                ],
            ],
            'product' => (object) [
                'code' => 'MAT-01', 'name' => 'Fabric', 'style_code' => null,
                'unit' => (object) ['symbol' => 'MTR', 'name' => 'Meter'],
            ],
            'quantity' => '2.5000', 'unit_price' => '3.1400', 'line_total' => '7.85',
        ];

        $row = $export->map($item);

        $this->assertSame('GRN-001', $row[1]);
        $this->assertSame('STYLE-1', $row[6]);
        $this->assertSame('MR-001', $row[7]);
        $this->assertSame('approved', $row[9]);
        $this->assertSame('MAT-01', $row[10]);
        $this->assertSame(2.5, $row[13]);
        $this->assertSame(7.85, $row[16]);
        $this->assertCount(18, $row);
    }
}
