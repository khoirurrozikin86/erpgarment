<?php
namespace Tests\Feature\Inventory;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions\SaveSupportMaterialOrderItemAction;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{AuditLog, Category, Company, Product, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SupportMaterialOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_material_order_can_contain_multiple_lines_without_changing_stock(): void
    {
        $company=Company::query()->create(['code'=>'TEST','name'=>'Test Co','is_active'=>true]);
        $user=User::factory()->create();
        $unit=Unit::query()->create(['code'=>'PCS','name'=>'Pieces','symbol'=>'PCS','is_active'=>true]);
        $category=Category::query()->create(['code'=>'SUP','name'=>'Support','is_active'=>true]);
        $first=Product::query()->create(['code'=>'SUP-1','name'=>'Support One','category_id'=>$category->id,'unit_id'=>$unit->id,'product_type'=>'stock','track_stock'=>true,'is_active'=>true,'is_available'=>true]);
        $second=Product::query()->create(['code'=>'SUP-2','name'=>'Support Two','category_id'=>$category->id,'unit_id'=>$unit->id,'product_type'=>'stock','track_stock'=>true,'is_active'=>true,'is_available'=>true]);
        app(CompanyContext::class)->set($company);
        $audit=Mockery::mock(AuditLogService::class); $audit->shouldReceive('log')->times(4)->andReturn(new AuditLog());
        $numbers=Mockery::mock(DocumentNumberService::class); $numbers->shouldReceive('generate')->once()->with('support_material_order')->andReturn('OBP/2026/11/00001');
        $action=new SaveSupportMaterialOrderItemAction(app(CompanyContext::class),$numbers,$audit);
        $date=today()->toDateString();
        $line1=$action->create(['order_date'=>$date,'product_id'=>$first->id,'quantity'=>2,'style'=>'ST-1','po_other'=>'PO-X'],$user->id);
        $line2=$action->create(['document_id'=>$line1->document_id,'order_date'=>$date,'product_id'=>$second->id,'quantity'=>3],$user->id);
        $this->assertSame($line1->document_id,$line2->document_id);
        $this->assertSame('OBP/2026/11/00001',$line2->document->number);
        $this->assertDatabaseCount('support_material_order_items',2);
        $this->assertDatabaseCount('stock_movements',0);
        $this->assertDatabaseCount('product_stocks',0);
        $action->update($line1,['order_date'=>$date,'product_id'=>$first->id,'quantity'=>4,'style'=>'ST-2','po_other'=>'PO-Y'],$user->id);
        $this->assertDatabaseHas('support_material_order_items',['id'=>$line1->id,'quantity'=>'4.0000','style'=>'ST-2']);
        $action->delete($line2,$user->id);
        $this->assertSoftDeleted('support_material_order_items',['id'=>$line2->id]);
        $this->assertDatabaseCount('stock_movements',0);
    }
}
