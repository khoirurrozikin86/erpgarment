<?php

namespace Tests\Feature\Manufacturing;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SavePackingAction;
use App\Domain\Manufacturing\Services\EnsureProductionMaterialsIssued;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\PackingExport;
use App\Models\{AuditLog,BillOfMaterial,Category,Company,DocumentNumbering,InventoryLot,Product,ProductStock,ProductionOrder,StockMovement,Unit,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_packing_is_numbered_exportable_and_posts_finished_goods_stock_with_reversal_on_delete(): void
    {
        $company=Company::query()->create(['code'=>'TEST','name'=>'Test Co','is_active'=>true]);
        DocumentNumbering::query()->create(['company_id'=>$company->id,'document_type'=>'packing','prefix'=>'PKG','format'=>'{PREFIX}/{YYYY}/{MM}/{NUMBER}','next_number'=>1,'number_length'=>5,'reset_period'=>'monthly','is_active'=>true]);
        $user=User::factory()->create();
        $user->givePermissionTo(collect(['view','create','update','delete'])->map(fn($a)=>Permission::firstOrCreate(['name'=>"packing.$a",'guard_name'=>'web'])));
        $this->actingAs($user);
        $unit=Unit::query()->create(['code'=>'PCS','name'=>'Pieces','symbol'=>'PCS','is_active'=>true]);
        $category=Category::query()->create(['code'=>'FG','name'=>'Finished Goods','is_active'=>true]);
        $finished=Product::query()->create(['code'=>'FG-1','name'=>'Finished','category_id'=>$category->id,'unit_id'=>$unit->id,'product_type'=>'stock','style_code'=>'STYLE-1','track_stock'=>true,'is_active'=>true,'is_available'=>true]);
        $material=Product::query()->create(['code'=>'MAT-1','name'=>'Thread','unit_id'=>$unit->id,'product_type'=>'stock','track_stock'=>true,'is_active'=>true,'is_available'=>true]);
        $bom=BillOfMaterial::query()->create(['company_id'=>$company->id,'product_id'=>$finished->id,'version'=>'1','status'=>'active','output_quantity'=>1]);
        $mo=ProductionOrder::query()->create(['company_id'=>$company->id,'number'=>'MO-1','buyer_po_number'=>'BUY-1','po_date'=>today(),'bill_of_material_id'=>$bom->id,'output_product_id'=>$finished->id,'bom_version'=>'1','planned_quantity'=>10,'status'=>'in_progress','created_by'=>$user->id]);
        $mo->materials()->create(['product_id'=>$material->id,'quantity_per_output'=>1,'wastage_percent'=>0,'required_quantity'=>10,'issued_quantity'=>10]);
        $mo->stageEntries()->create(['stage'=>'sewing','input_quantity'=>10,'good_quantity'=>8,'rejected_quantity'=>2,'waste_quantity'=>0,'processed_by'=>$user->id,'processed_at'=>now()]);
        app(CompanyContext::class)->set($company);
        $this->get(route('super.packing.index'))->assertOk()->assertSee('packingTable')->assertSee('packingModal');
        $this->getJson(route('super.packing.number-preview'))->assertOk()->assertJsonStructure(['number']);
        $this->getJson(route('super.packing.production-orders.dt'))->assertOk()->assertJsonFragment(['number'=>'MO-1']);
        Excel::fake();
        $this->get(route('super.packing.export',['from_date'=>today()->toDateString(),'to_date'=>today()->toDateString()]))->assertOk();
        Excel::assertDownloaded('packing.xlsx');

        $audit=Mockery::mock(AuditLogService::class);$audit->shouldReceive('log')->times(2)->andReturn(new AuditLog());
        $numbers=Mockery::mock(DocumentNumberService::class);$numbers->shouldReceive('generate')->once()->with('packing')->andReturn('PKG/2026/11/00001');
        $action=new SavePackingAction(app(CompanyContext::class),$numbers,$audit,app(EnsureProductionMaterialsIssued::class));
        $data=['production_order_id'=>$mo->id,'packing_date'=>today()->toDateString(),'material_product_id'=>$material->id,'input_quantity'=>8,'send_to_finished_goods'=>7,'rejected_quantity'=>1,'material_waste_quantity'=>0.25];
        $entry=$action->create($data,$user->id);
        $this->assertSame('PKG/2026/11/00001',$entry->packing_number);
        $this->assertDatabaseHas('product_stocks',['product_id'=>$finished->id,'quantity'=>'7.0000']);
        $this->assertDatabaseHas('stock_movements',['production_order_stage_entry_id'=>$entry->id,'movement_type'=>'production_output','quantity'=>'7.0000']);
        $this->assertDatabaseHas('inventory_lots',['product_id'=>$finished->id,'initial_quantity'=>'7.0000']);
        $this->getJson(route('super.packing.dt',['from_date'=>today()->toDateString(),'to_date'=>today()->toDateString()]))->assertOk()->assertJsonFragment(['number'=>'PKG/2026/11/00001'])->assertJsonFragment(['mo'=>'MO-1']);
        $action->delete($entry,$user->id);
        $this->assertDatabaseHas('product_stocks',['product_id'=>$finished->id,'quantity'=>'0.0000']);
        $this->assertSoftDeleted('production_order_stage_entries',['id'=>$entry->id]);
        $this->assertDatabaseHas('stock_movements',['production_order_stage_entry_id'=>$entry->id,'movement_type'=>'production_output_reversal','quantity'=>'-7.0000']);
        $this->assertDatabaseHas('inventory_lots',['product_id'=>$finished->id,'current_quantity'=>'0.0000']);
    }

    public function test_packing_cannot_exceed_sewing_good_output(): void
    {
        $company=Company::query()->create(['code'=>'TEST','name'=>'Test Co','is_active'=>true]);
        $unit=Unit::query()->create(['code'=>'PCS','name'=>'Pieces','symbol'=>'PCS','is_active'=>true]);
        $category=Category::query()->create(['code'=>'FG','name'=>'Finished Goods','is_active'=>true]);
        $finished=Product::query()->create(['code'=>'FG-1','name'=>'Finished','category_id'=>$category->id,'unit_id'=>$unit->id,'product_type'=>'stock','style_code'=>'STYLE-1','track_stock'=>true,'is_active'=>true,'is_available'=>true]);
        $material=Product::query()->create(['code'=>'MAT-1','name'=>'Thread','unit_id'=>$unit->id,'product_type'=>'stock','track_stock'=>true,'is_active'=>true,'is_available'=>true]);
        $bom=BillOfMaterial::query()->create(['company_id'=>$company->id,'product_id'=>$finished->id,'version'=>'1','status'=>'active','output_quantity'=>1]);
        $user=User::factory()->create();
        $mo=ProductionOrder::query()->create(['company_id'=>$company->id,'number'=>'MO-1','po_date'=>today(),'bill_of_material_id'=>$bom->id,'output_product_id'=>$finished->id,'bom_version'=>'1','planned_quantity'=>10,'status'=>'in_progress','created_by'=>$user->id]);
        $mo->materials()->create(['product_id'=>$material->id,'quantity_per_output'=>1,'wastage_percent'=>0,'required_quantity'=>10,'issued_quantity'=>10]);
        $mo->stageEntries()->create(['stage'=>'sewing','input_quantity'=>10,'good_quantity'=>3,'rejected_quantity'=>0,'waste_quantity'=>0,'processed_by'=>$user->id,'processed_at'=>now()]);
        app(CompanyContext::class)->set($company);
        $numbers=Mockery::mock(DocumentNumberService::class);$numbers->shouldNotReceive('generate');
        $audit=Mockery::mock(AuditLogService::class);$audit->shouldNotReceive('log');
        $action=new SavePackingAction(app(CompanyContext::class),$numbers,$audit,app(EnsureProductionMaterialsIssued::class));
        try {
            $action->create(['production_order_id'=>$mo->id,'packing_date'=>today()->toDateString(),'material_product_id'=>$material->id,'input_quantity'=>4,'send_to_finished_goods'=>4,'rejected_quantity'=>0,'material_waste_quantity'=>0],$user->id);
            $this->fail('Packing must not exceed available Sewing good output.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('input_quantity',$e->errors());
        }
    }
}
