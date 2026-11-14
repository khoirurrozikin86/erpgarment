<?php

namespace Tests\Feature\Inventory;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\{ApproveProductionReturnAction, CreateProductionReturnAction};
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{AuditLog, Category, Company, DocumentNumbering, InventoryLot, Product, ProductStock, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductionReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_approval_posts_return_to_stock_and_stock_ledger(): void
    {
        $company=Company::query()->create(['code'=>'TEST','name'=>'Test Co','is_active'=>true]);
        DocumentNumbering::query()->create(['company_id'=>$company->id,'document_type'=>'return_to_warehouse','prefix'=>'RETG','format'=>'{PREFIX}/{YYYY}/{MM}/{NUMBER}','next_number'=>1,'number_length'=>5,'reset_period'=>'monthly','is_active'=>true]);
        $user=User::factory()->create();
        $unit=Unit::query()->create(['code'=>'PCS','name'=>'Pieces','symbol'=>'PCS','is_active'=>true]);
        $category=Category::query()->create(['code'=>'MAT','name'=>'Material','is_active'=>true]);
        $product=Product::query()->create(['code'=>'MAT-1','name'=>'Material','category_id'=>$category->id,'unit_id'=>$unit->id,'product_type'=>'stock','track_stock'=>true,'is_active'=>true,'is_available'=>true]);
        ProductStock::query()->create(['product_id'=>$product->id,'quantity'=>3,'average_unit_cost'=>2]);
        InventoryLot::query()->create(['product_id'=>$product->id,'lot_number'=>'LOT-1','received_at'=>now()->subDay(),'initial_quantity'=>3,'current_quantity'=>3,'unit_cost'=>2,'source_type'=>'purchase_receipt']);
        app(CompanyContext::class)->set($company);
        $audit=Mockery::mock(AuditLogService::class);$audit->shouldReceive('log')->twice()->andReturn(new AuditLog());
        $numbers=Mockery::mock(DocumentNumberService::class);$numbers->shouldReceive('generate')->once()->with('return_to_warehouse')->andReturn('RETG/2026/11/00001');
        $document=(new CreateProductionReturnAction(app(CompanyContext::class),$numbers,$audit))(['return_date'=>today()->toDateString(),'product_id'=>$product->id,'quantity'=>1.5],$user->id);
        $this->assertDatabaseHas('product_stocks',['product_id'=>$product->id,'quantity'=>'3.0000']);
        $journals=Mockery::mock(AutomaticJournalService::class);$journals->shouldReceive('recordStockMovement')->once();
        (new ApproveProductionReturnAction($audit,$journals))($document,$company->id,$user->id);
        $this->assertDatabaseHas('production_return_documents',['id'=>$document->id,'status'=>'approved','number'=>'RETG/2026/11/00001']);
        $this->assertDatabaseHas('stock_movements',['production_return_document_id'=>$document->id,'movement_type'=>'production_return','quantity'=>'1.5000','quantity_before'=>'3.0000','quantity_after'=>'4.5000']);
        $this->assertDatabaseHas('product_stocks',['product_id'=>$product->id,'quantity'=>'4.5000']);
        $this->assertSame(4.5,(float)InventoryLot::query()->where('product_id',$product->id)->sum('current_quantity'));

        $user->givePermissionTo(Permission::firstOrCreate(['name'=>'warehouse-production-receipts.view','guard_name'=>'web']));
        $this->actingAs($user)->getJson(route('super.warehouse-production-receipts.dt'))
            ->assertOk()->assertJsonFragment(['number'=>'RETG/2026/11/00001'])->assertJsonFragment(['mo'=>'—'])
            ->assertJsonFragment(['approved_at'=>$document->fresh()->approved_at?->format('d-m-Y H:i')]);
    }
}
