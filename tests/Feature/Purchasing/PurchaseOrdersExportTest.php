<?php

namespace Tests\Feature\Purchasing;

use App\Exports\PurchaseOrdersExport;
use Tests\TestCase;

class PurchaseOrdersExportTest extends TestCase
{
    public function test_export_uses_color_and_size_from_the_linked_material_request_item(): void
    {
        $export = new PurchaseOrdersExport();
        $order = (object) [
            'number' => 'PO-001',
            'purchaseRequest' => (object) [
                'number' => 'PR-001',
                'materialRequest' => (object) [
                    'productionOrder' => null,
                    'outputProduct' => null,
                    'production_quantity' => null,
                ],
            ],
            'supplier' => (object) ['name' => 'Supplier One'],
            'order_date' => null,
            'expected_delivery_at' => null,
            'total_amount' => 100,
            'status' => 'draft',
            'items' => collect([
                (object) [
                    'product' => (object) [
                        'code' => 'MAT-01',
                        'name' => 'Fabric',
                        'color' => 'Product Color',
                        'size' => 'Product Size',
                        'unit' => (object) ['symbol' => 'MTR', 'name' => 'Meter'],
                    ],
                    'purchaseRequestItem' => (object) [
                        'materialRequestItem' => (object) ['color' => 'MR Color', 'size' => 'MR Size'],
                    ],
                    'quantity' => '2.5000',
                ],
            ]),
        ];

        $row = $export->map($order)[0];

        $this->assertSame('MR Color', $row[9]);
        $this->assertSame('MR Size', $row[10]);
        $this->assertSame(15, count($export->headings()));
    }
}
