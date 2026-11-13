<?php

namespace Tests\Feature\Purchasing;

use App\Exports\MaterialRequestsExport;
use Carbon\Carbon;
use Tests\TestCase;

class MaterialRequestsExportTest extends TestCase
{
    public function test_export_writes_each_required_item_on_its_own_row(): void
    {
        $export = new MaterialRequestsExport();
        $materialRequest = (object) [
            'number' => 'MR-001',
            'created_at' => Carbon::parse('2026-11-10 09:30:00'),
            'productionOrder' => (object) [
                'buyer_po_number' => 'BUYER-PO-10',
                'outputProduct' => (object) ['style_code' => 'STYLE-1', 'code' => 'FG-001'],
                'planned_quantity' => '25.0000',
            ],
            'outputProduct' => null,
            'production_quantity' => null,
            'requester' => (object) ['name' => 'Requester One'],
            'department' => 'PPIC',
            'needed_at' => Carbon::parse('2026-11-20'),
            'items' => collect([
                (object) ['product' => (object) ['code' => 'MAT-01', 'name' => 'Fabric', 'unit' => (object) ['symbol' => 'MTR', 'name' => 'Meter']], 'quantity' => '2.5000'],
                (object) ['product' => (object) ['code' => 'MAT-02', 'name' => 'Thread', 'unit' => (object) ['symbol' => 'PCS', 'name' => 'Pieces']], 'quantity' => '3.0000'],
            ]),
            'items_count' => 2,
            'items_total_quantity' => '5.5000',
            'status' => 'approved',
            'purchase_status' => 'not_processed',
            'reason' => 'Production needs',
        ];

        $rows = $export->map($materialRequest);

        $this->assertCount(2, $rows);
        $this->assertSame('STYLE-1', $rows[0][4]);
        $this->assertSame('MAT-01', $rows[0][10]);
        $this->assertSame('Fabric', $rows[0][11]);
        $this->assertSame(2.5, $rows[0][12]);
        $this->assertSame('MAT-02', $rows[1][10]);
        $this->assertSame('MR-001', $rows[1][1]);
        $this->assertSame(19, count($export->headings()));
    }
}
