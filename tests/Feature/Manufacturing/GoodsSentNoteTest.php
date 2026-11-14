<?php

namespace Tests\Feature\Manufacturing;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SaveGoodsSentNoteAction;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{AuditLog, BillOfMaterial, Company, Customer, DocumentNumbering, InventoryLot, Product, ProductStock, ProductionOrder, User, Unit};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GoodsSentNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_gsn_creates_stock_ledger_detail_print_and_filtered_excel(): void
    {
        [$company, $user, $order, $product] = $this->fixture();
        $this->actingAs($user);
        app(CompanyContext::class)->set($company);
        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')->times(5)->andReturn(new AuditLog());
        $numbers = Mockery::mock(DocumentNumberService::class);
        $numbers->shouldReceive('generate')->once()->with('goods_sent_note')->andReturn('GSN-1');
        $action = new SaveGoodsSentNoteAction(app(CompanyContext::class), $numbers, $audit);
        $data = ['sent_date'=>today()->toDateString(),'vehicle_number'=>'B 1234 X','items'=>[['production_order_id'=>$order->id,'product_id'=>$product->id,'customer_id'=>$order->customer_id,'quantity'=>4,'cartons'=>2]]];
        $note = $action->create($data, $user->id);

        $this->assertDatabaseHas('goods_sent_notes', ['id'=>$note->id,'number'=>'GSN-1']);
        $this->assertDatabaseHas('product_stocks', ['product_id'=>$product->id,'quantity'=>'96.0000']);
        $this->assertDatabaseHas('stock_movements', ['goods_sent_note_id'=>$note->id,'movement_type'=>'goods_sent_note','quantity'=>'-4.0000']);
        $this->assertDatabaseHas('stock_movement_lot_allocations', ['quantity'=>'-4.0000']);

        Excel::fake();
        $this->get(route('super.goods-sent-notes.index'))->assertOk()->assertSee('gsnTable');
        $this->getJson(route('super.goods-sent-notes.dt'))->assertOk()->assertJsonFragment(['number'=>'GSN-1']);
        $this->get(route('super.goods-sent-notes.print', $note))->assertOk()->assertSee('DELIVERY NOTE');
        $this->get(route('super.goods-sent-notes.export', ['from_date'=>today()->toDateString(),'to_date'=>today()->toDateString()]))->assertOk();
        Excel::assertDownloaded('goods-sent-notes.xlsx');

        $action->update($note, array_merge($data, ['items'=>[['production_order_id'=>$order->id,'product_id'=>$product->id,'customer_id'=>$order->customer_id,'quantity'=>3,'cartons'=>1]]]), $user->id);
        $note->refresh();
        $action->approve($note, $user->id);
        $this->assertDatabaseHas('product_stocks', ['product_id'=>$product->id,'quantity'=>'97.0000']);
        $this->assertDatabaseHas('stock_movements', ['goods_sent_note_id'=>$note->id,'movement_type'=>'goods_sent_note','quantity'=>'-3.0000']);
        $action->cancelApproval($note, $user->id);
        $this->assertDatabaseHas('product_stocks', ['product_id'=>$product->id,'quantity'=>'97.0000']);
        $action->delete($note->fresh(), $user->id);
        $this->assertSoftDeleted('goods_sent_notes', ['id'=>$note->id]);
        $this->assertDatabaseHas('product_stocks', ['product_id'=>$product->id,'quantity'=>'100.0000']);
    }

    public function test_gsn_cannot_ship_more_than_the_recorded_finished_goods(): void
    {
        [$company, $user, $order, $product] = $this->fixture(2);
        app(CompanyContext::class)->set($company);
        $audit = Mockery::mock(AuditLogService::class); $audit->shouldNotReceive('log');
        $numbers = Mockery::mock(DocumentNumberService::class); $numbers->shouldReceive('generate')->once()->with('goods_sent_note')->andReturn('GSN-INVALID');
        $action = new SaveGoodsSentNoteAction(app(CompanyContext::class), $numbers, $audit);
        try {
            $action->create(['sent_date'=>today()->toDateString(),'items'=>[['production_order_id'=>$order->id,'product_id'=>$product->id,'quantity'=>3,'cartons'=>0]]], $user->id);
            $this->fail('GSN quantity must not exceed finished goods recorded for the MO.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }
    }

    public function test_delivery_note_approval_records_status_without_reposting_stock_and_shows_detail(): void
    {
        [$company, $user, $order, $product] = $this->fixture();
        $this->actingAs($user);
        app(CompanyContext::class)->set($company);
        $note = app(SaveGoodsSentNoteAction::class)->create([
            'sent_date' => today()->toDateString(),
            'items' => [['production_order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2, 'cartons' => 1]],
        ], $user->id);

        $this->get(route('super.delivery-note-approvals.index'))->assertOk()->assertSee(__('ui.delivery_note_approval.title'));
        $this->getJson(route('super.delivery-note-approvals.dt'))->assertOk()->assertJsonFragment(['number' => $note->number]);
        $this->getJson(route('super.delivery-note-approvals.show', $note))->assertOk()->assertJsonFragment(['quantity' => '2.0000']);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '98.0000']);

        $this->postJson(route('super.delivery-note-approvals.approve', $note))->assertOk();
        $this->assertDatabaseHas('goods_sent_notes', ['id' => $note->id, 'approved_by' => $user->id]);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '98.0000']);

        $this->postJson(route('super.delivery-note-approvals.cancel', $note))->assertOk();
        $this->assertDatabaseHas('goods_sent_notes', ['id' => $note->id, 'approved_at' => null]);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '98.0000']);
    }

    private function fixture(float $finished = 10): array
    {
        $company=Company::query()->create(['code'=>'TEST','name'=>'Test Co','is_active'=>true]);
        DocumentNumbering::query()->create(['company_id'=>$company->id,'document_type'=>'goods_sent_note','prefix'=>'GSN','format'=>'{PREFIX}/{YYYY}/{MM}/{NUMBER}','next_number'=>1,'number_length'=>5,'reset_period'=>'monthly','is_active'=>true]);
        $user=User::factory()->create();
        $user->givePermissionTo(collect(['view','create','update','delete'])->map(fn($a)=>Permission::firstOrCreate(['name'=>"goods-sent-notes.$a",'guard_name'=>'web'])));
        $user->givePermissionTo(collect(['view','approve','cancel'])->map(fn($a)=>Permission::firstOrCreate(['name'=>"goods-sent-approvals.$a",'guard_name'=>'web'])));
        $unit=Unit::query()->create(['code'=>'PCS','name'=>'Pieces','symbol'=>'PCS','is_active'=>true]);
        $customer=Customer::query()->create(['code'=>'C1','name'=>'Customer One','is_active'=>true]);
        $product=Product::query()->create(['code'=>'FG-1','name'=>'Finished Item','unit_id'=>$unit->id,'product_type'=>'stock','style_code'=>'STYLE-1','track_stock'=>true,'is_active'=>true,'is_available'=>true]);
        $bom=BillOfMaterial::query()->create(['company_id'=>$company->id,'product_id'=>$product->id,'version'=>'1','status'=>'active','output_quantity'=>1]);
        $order=ProductionOrder::query()->create(['company_id'=>$company->id,'number'=>'MO-1','buyer_po_number'=>'PO-1','po_date'=>today(),'customer_id'=>$customer->id,'bill_of_material_id'=>$bom->id,'output_product_id'=>$product->id,'bom_version'=>'1','planned_quantity'=>10,'status'=>'in_progress','created_by'=>$user->id]);
        $order->stageEntries()->create(['stage'=>'finishing','input_quantity'=>$finished,'good_quantity'=>$finished,'rejected_quantity'=>0,'waste_quantity'=>0,'processed_by'=>$user->id,'processed_at'=>now()]);
        ProductStock::query()->create(['product_id'=>$product->id,'quantity'=>100,'average_unit_cost'=>0]);
        InventoryLot::query()->create(['product_id'=>$product->id,'lot_number'=>'FG-TEST','received_at'=>now()->subDay(),'initial_quantity'=>100,'current_quantity'=>100,'unit_cost'=>0,'source_type'=>'production']);
        return [$company,$user,$order,$product];
    }
}
