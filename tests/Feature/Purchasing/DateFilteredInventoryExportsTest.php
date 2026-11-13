<?php

namespace Tests\Feature\Purchasing;

use App\Exports\MaterialOrdersExport;
use App\Exports\MaterialRequestsExport;
use App\Exports\PurchaseOrdersExport;
use Carbon\Carbon;
use Tests\TestCase;

class DateFilteredInventoryExportsTest extends TestCase
{
    public function test_material_request_export_uses_request_date_range(): void
    {
        $query = (new MaterialRequestsExport('2026-11-01', '2026-11-13'))->query();

        $this->assertStringContainsString('created_at', $query->toSql());
        $this->assertCount(2, $query->getBindings());
    }

    public function test_material_order_export_uses_company_and_po_date_range(): void
    {
        $query = (new MaterialOrdersExport(7, '2026-11-01', '2026-11-13'))->query();

        $this->assertStringContainsString('po_date', $query->toSql());
        $this->assertSame([7, '2026-11-01', '2026-11-13'], $query->getBindings());
    }

    public function test_purchase_order_export_uses_po_date_range(): void
    {
        $query = (new PurchaseOrdersExport('2026-11-01', '2026-11-13'))->query();

        $this->assertStringContainsString('order_date', $query->toSql());
        $this->assertCount(2, $query->getBindings());
    }

    public function test_purchase_order_export_maps_style_quantity_and_requested_items(): void
    {
        $export = new PurchaseOrdersExport();
        $unit = (object) ['symbol' => 'PCS', 'name' => 'Pieces'];
        $order = (object) [
            'purchaseRequest' => (object) [
                'number' => 'PR-01',
                'materialRequest' => (object) [
                    'productionOrder' => (object) [
                        'outputProduct' => (object) ['style_code' => 'STYLE-1'],
                        'planned_quantity' => '500.0000',
                    ],
                    'outputProduct' => null,
                    'production_quantity' => null,
                ],
            ],
            'items' => collect([
                (object) [
                    'product' => (object) ['code' => 'MAT-01', 'name' => 'Fabric', 'unit' => $unit],
                    'quantity' => '2.5000',
                ],
                (object) [
                    'product' => (object) ['code' => 'MAT-02', 'name' => 'Thread', 'unit' => $unit],
                    'quantity' => '3.0000',
                ],
            ]),
            'number' => 'PO-01',
            'supplier' => (object) ['name' => 'Supplier'],
            'order_date' => Carbon::parse('2026-11-10'),
            'expected_delivery_at' => Carbon::parse('2026-11-20'),
            'total_amount' => '100.00',
            'status' => 'draft',
        ];

        $rows = $export->map($order);

        $this->assertCount(2, $rows);
        $this->assertSame('STYLE-1', $rows[0][5]);
        $this->assertSame(500.0, $rows[0][6]);
        $this->assertSame('MAT-01', $rows[0][7]);
        $this->assertSame('Fabric', $rows[0][8]);
        $this->assertSame(2.5, $rows[0][9]);
        $this->assertSame('PCS', $rows[0][10]);
        $this->assertSame('MAT-02', $rows[1][7]);
        $this->assertSame('PO-01', $rows[1][0]);
    }
}
