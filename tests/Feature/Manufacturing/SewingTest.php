<?php
namespace Tests\Feature\Manufacturing;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SaveSewingAction;
use App\Domain\Manufacturing\Services\EnsureProductionMaterialsIssued;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{AuditLog,BillOfMaterial,Category,Company,DocumentNumbering,Product,ProductionOrder,Unit,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Mockery;
use Tests\TestCase;

class SewingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sewing_is_numbered_linked_to_cutting_output_and_exportable(): void
    {
        $company = Company::query()->create(['code'=>'TEST','name'=>'Test Co','is_active'=>true]);
        DocumentNumbering::query()->create(['company_id'=>$company->id,'document_type'=>'sewing','prefix'=>'SEW','format'=>'{PREFIX}/{YYYY}/{MM}/{NUMBER}','next_number'=>1,'number_length'=>5,'reset_period'=>'monthly','is_active'=>true]);
        $user=User::factory()->create();
        $user->givePermissionTo(collect(['view','create','update','delete'])->map(fn($a)=>Permission::firstOrCreate(['name'=>"sewing.$a",'guard_name'=>'web'])));
        $this->actingAs($user);
        $unit=Unit::query()->create(['code'=>'PCS','name'=>'Pieces','symbol'=>'PCS','is_active'=>true]);
        $category=Category::query()->create(['code'=>'FG','name'=>'Finished Goods','is_active'=>true]);
        $finished=Product::query()->create(['code'=>'FG-1','name'=>'Finished','category_id'=>$category->id,'unit_id'=>$unit->id,'product_type'=>'stock','style_code'=>'STYLE-1','is_active'=>true,'is_available'=>true]);
        $material=Product::query()->create(['code'=>'MAT-1','name'=>'Thread','unit_id'=>$unit->id,'product_type'=>'stock','is_active'=>true,'is_available'=>true]);
        $bom=BillOfMaterial::query()->create(['company_id'=>$company->id,'product_id'=>$finished->id,'version'=>'1','status'=>'active','output_quantity'=>1]);
        $mo=ProductionOrder::query()->create(['company_id'=>$company->id,'number'=>'MO-1','buyer_po_number'=>'BUY-1','po_date'=>today(),'bill_of_material_id'=>$bom->id,'output_product_id'=>$finished->id,'bom_version'=>'1','planned_quantity'=>10,'status'=>'in_progress','created_by'=>$user->id]);
        $mo->materials()->create(['product_id'=>$material->id,'quantity_per_output'=>1,'wastage_percent'=>0,'required_quantity'=>10,'issued_quantity'=>10]);
        $mo->stageEntries()->create(['stage'=>'cutting','input_quantity'=>10,'good_quantity'=>8,'rejected_quantity'=>2,'waste_quantity'=>0,'processed_by'=>$user->id,'processed_at'=>now()]);
        app(CompanyContext::class)->set($company);
        $this->get(route('super.sewing.index'))->assertOk()->assertSee('sewingTable')->assertSee('sewingModal');
        $this->getJson(route('super.sewing.number-preview'))->assertOk()->assertJsonStructure(['number']);
        $this->getJson(route('super.sewing.production-orders.dt'))->assertOk()->assertJsonFragment(['number'=>'MO-1']);
        Excel::fake();
        $this->get(route('super.sewing.export',['from_date'=>today()->toDateString(),'to_date'=>today()->toDateString()]))->assertOk();
        Excel::assertDownloaded('sewing.xlsx');

        $audit=Mockery::mock(AuditLogService::class);$audit->shouldReceive('log')->times(3)->andReturn(new AuditLog());
        $numbers=Mockery::mock(DocumentNumberService::class);$numbers->shouldReceive('generate')->once()->with('sewing')->andReturn('SEW/2026/11/00001');
        $action=new SaveSewingAction(app(CompanyContext::class),$numbers,$audit,app(EnsureProductionMaterialsIssued::class));
        $data=['production_order_id'=>$mo->id,'sewing_date'=>today()->toDateString(),'material_product_id'=>$material->id,'input_quantity'=>8,'send_to_packing'=>7,'rejected_quantity'=>1,'material_waste_quantity'=>0.25];
        $entry=$action->create($data,$user->id);
        $this->assertSame('SEW/2026/11/00001',$entry->sewing_number);
        $this->assertDatabaseHas('production_order_stage_entries',['id'=>$entry->id,'stage'=>'sewing','good_quantity'=>'7.0000','material_waste_quantity'=>'0.2500']);
        $this->getJson(route('super.sewing.dt',['from_date'=>today()->toDateString(),'to_date'=>today()->toDateString()]))->assertOk()->assertJsonFragment(['number'=>'SEW/2026/11/00001'])->assertJsonFragment(['mo'=>'MO-1']);
        $action->update($entry,array_merge($data,['send_to_packing'=>6,'rejected_quantity'=>2]),$user->id);
        $action->delete($entry->fresh(),$user->id);
        $this->assertDatabaseMissing('production_order_stage_entries',['id'=>$entry->id]);

        $data['input_quantity']=9;$data['send_to_packing']=9;$data['rejected_quantity']=0;
        try {$action->create($data,$user->id);$this->fail('Sewing must not exceed available cutting output.');} catch (ValidationException $e) {$this->assertArrayHasKey('input_quantity',$e->errors());}
    }
}
