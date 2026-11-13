<?php

namespace Tests\Feature\Purchasing;

use App\Exports\PurchaseRequestsExport;
use Carbon\Carbon;
use Tests\TestCase;

class PurchaseRequestsExportTest extends TestCase
{
    public function test_export_maps_finished_good_style_code_and_production_quantity(): void
    {
        $export = new PurchaseRequestsExport('2026-11-01', '2026-11-30');
        $productionOrder = (object) [
            'buyer_po_number' => 'BUYER-PO-10',
            'outputProduct' => (object) ['style_code' => 'STYLE-1', 'code' => 'FG-001'],
            'planned_quantity' => '25.0000',
        ];
        $purchaseRequest = (object) [
            'number' => 'PR-001',
            'created_at' => Carbon::parse('2026-11-10 09:30:00'),
            'materialRequest' => (object) [
                'number' => 'MR-001',
                'productionOrder' => $productionOrder,
                'outputProduct' => null,
                'production_quantity' => null,
            ],
            'items' => collect([
                (object) ['product' => (object) ['code' => 'MAT-01', 'name' => 'Fabric', 'unit' => (object) ['symbol' => 'MTR', 'name' => 'Meter']], 'quantity' => '2.5000'],
                (object) ['product' => (object) ['code' => 'MAT-02', 'name' => 'Thread', 'unit' => (object) ['symbol' => 'PCS', 'name' => 'Pieces']], 'quantity' => '3.0000'],
            ]),
            'supplier' => (object) ['name' => 'Supplier One'],
            'requester' => (object) ['name' => 'Requester One'],
            'total_amount' => '125.50',
            'status' => 'pending',
        ];

        $rows = $export->map($purchaseRequest);
        $this->assertCount(2, $rows);
        $this->assertSame('STYLE-1', $rows[0][4]);
        $this->assertSame(25.0, $rows[0][6]);
        $this->assertSame('MAT-01', $rows[0][7]);
        $this->assertSame('Fabric', $rows[0][8]);
        $this->assertSame(2.5, $rows[0][9]);
        $this->assertSame('MAT-02', $rows[1][7]);
        $this->assertSame('PR-001', $rows[1][0]);
        $this->assertSame(15, count($export->headings()));
    }

    public function test_export_query_applies_selected_date_range(): void
    {
        $query = (new PurchaseRequestsExport('2026-11-01', '2026-11-30'))->query();

        $this->assertSame(2, count($query->getBindings()));
        $this->assertStringContainsString('created_at', $query->toSql());
    }
}
