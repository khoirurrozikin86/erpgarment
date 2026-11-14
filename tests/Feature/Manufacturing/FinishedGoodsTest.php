<?php

namespace Tests\Feature\Manufacturing;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SaveFinishedGoodsAction;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{AuditLog,BillOfMaterial,Company,DocumentNumbering,Product,ProductStock,ProductionOrder,ProductionOrderStageEntry,Unit,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FinishedGoodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_finished_goods_receipt_uses_packing_output_and_can_be_edited_deleted_and_exported(): void
    {
        [$company, $user, $order, $finished] = $this->fixture();
        $this->actingAs($user);
        app(CompanyContext::class)->set($company);
        $this->get(route('super.finished-goods.index'))->assertOk()->assertSee('finishedTable')->assertSee('finishedModal');
        $this->getJson(route('super.finished-goods.orders.dt'))->assertOk()->assertJsonFragment(['number' => 'MO-FG-1']);
        Excel::fake();
        $this->get(route('super.finished-goods.export', ['from_date' => today()->toDateString(), 'to_date' => today()->toDateString()]))->assertOk();
        Excel::assertDownloaded('finished-goods.xlsx');

        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')->times(3)->andReturn(new AuditLog());
        $numbers = Mockery::mock(DocumentNumberService::class);
        $numbers->shouldReceive('generate')->once()->with('finished_goods')->andReturn('FIN/2026/11/00001');
        $action = new SaveFinishedGoodsAction(app(CompanyContext::class), $numbers, $audit);
        $data = ['production_order_id' => $order->id, 'finished_goods_date' => today()->toDateString(), 'receive_from_packing' => 6, 'finished_good_quantity' => 5];
        $entry = $action->create($data, $user->id);
        $this->assertSame('FIN/2026/11/00001', $entry->finished_goods_number);
        $this->assertSame('finishing', $entry->stage);
        $this->assertDatabaseHas('production_order_stage_entries', ['id' => $entry->id, 'input_quantity' => '6.0000', 'good_quantity' => '5.0000']);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $finished->id, 'quantity' => '100.0000']);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->getJson(route('super.finished-goods.dt', ['from_date' => today()->toDateString(), 'to_date' => today()->toDateString()]))->assertOk()->assertJsonFragment(['finished_goods_number' => 'FIN/2026/11/00001']);
        $action->update($entry, array_merge($data, ['receive_from_packing' => 4, 'finished_good_quantity' => 4]), $user->id);
        $action->delete($entry->fresh(), $user->id);
        $this->assertSoftDeleted('production_order_stage_entries', ['id' => $entry->id]);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $finished->id, 'quantity' => '100.0000']);
    }

    public function test_finished_goods_receipt_cannot_exceed_available_packing_output(): void
    {
        [$company, $user, $order] = $this->fixture(3);
        app(CompanyContext::class)->set($company);
        $numbers = Mockery::mock(DocumentNumberService::class);
        $numbers->shouldNotReceive('generate');
        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldNotReceive('log');
        $action = new SaveFinishedGoodsAction(app(CompanyContext::class), $numbers, $audit);
        try {
            $action->create(['production_order_id' => $order->id, 'finished_goods_date' => today()->toDateString(), 'receive_from_packing' => 4, 'finished_good_quantity' => 4], $user->id);
            $this->fail('Receipt cannot exceed remaining Packing output.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('receive_from_packing', $exception->errors());
        }
    }

    private function fixture(float $packingQuantity = 10): array
    {
        $company = Company::query()->create(['code' => 'TEST', 'name' => 'Test Co', 'is_active' => true]);
        DocumentNumbering::query()->create(['company_id' => $company->id, 'document_type' => 'finished_goods', 'prefix' => 'FIN', 'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5, 'reset_period' => 'monthly', 'is_active' => true]);
        $user = User::factory()->create();
        $user->givePermissionTo(collect(['view', 'create', 'update', 'delete'])->map(fn ($action) => Permission::firstOrCreate(['name' => "finished-goods.$action", 'guard_name' => 'web'])));
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $finished = Product::query()->create(['code' => 'FG-1', 'name' => 'Finished Item', 'unit_id' => $unit->id, 'product_type' => 'stock', 'style_code' => 'STYLE-1', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);
        $bom = BillOfMaterial::query()->create(['company_id' => $company->id, 'product_id' => $finished->id, 'version' => '1', 'status' => 'active', 'output_quantity' => 1]);
        $order = ProductionOrder::query()->create(['company_id' => $company->id, 'number' => 'MO-FG-1', 'buyer_po_number' => 'PO-1', 'po_date' => today(), 'bill_of_material_id' => $bom->id, 'output_product_id' => $finished->id, 'bom_version' => '1', 'planned_quantity' => 10, 'status' => 'in_progress', 'created_by' => $user->id]);
        $order->stageEntries()->create(['stage' => 'packing', 'input_quantity' => $packingQuantity, 'good_quantity' => $packingQuantity, 'rejected_quantity' => 0, 'waste_quantity' => 0, 'processed_by' => $user->id, 'processed_at' => now()]);
        ProductStock::query()->create(['product_id' => $finished->id, 'quantity' => 100, 'average_unit_cost' => 0]);
        return [$company, $user, $order, $finished];
    }
}
