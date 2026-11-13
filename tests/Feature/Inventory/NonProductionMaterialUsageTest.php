<?php

namespace Tests\Feature\Inventory;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveNonProductionMaterialUsageAction;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{AuditLog, Category, Company, DocumentNumbering, InventoryLot, Product, ProductStock, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NonProductionMaterialUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_numbering_stock_history_edit_and_delete_are_tracked(): void
    {
        $company = Company::query()->create(['code' => 'TEST', 'name' => 'Test Co', 'is_active' => true]);
        DocumentNumbering::query()->create([
            'company_id' => $company->id, 'document_type' => 'non_production_material_usage', 'prefix' => 'PBN',
            'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5,
            'reset_period' => 'monthly', 'is_active' => true,
        ]);
        $user = User::factory()->create();
        $user->givePermissionTo(collect(['non-production-material-usages.view', 'non-production-material-usages.create', 'non-production-material-usages.update', 'non-production-material-usages.delete', 'stock-card.view'])->map(
            fn (string $permission) => Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web'])
        ));
        $this->actingAs($user);

        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'MAT', 'name' => 'Material', 'is_active' => true]);
        $product = Product::query()->create([
            'code' => 'MAT-1', 'name' => 'Office Material', 'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'stock', 'style_code' => 'STYLE-1', 'track_stock' => true, 'is_active' => true, 'is_available' => true,
        ]);
        ProductStock::query()->create(['product_id' => $product->id, 'quantity' => 10, 'average_unit_cost' => 2]);
        InventoryLot::query()->create(['product_id' => $product->id, 'lot_number' => 'LOT-1', 'received_at' => now(), 'initial_quantity' => 10, 'current_quantity' => 10, 'unit_cost' => 2, 'source_type' => 'purchase_receipt']);

        $this->get(route('super.non-production-material-usages.index'))->assertOk()->assertSee('nonProductionUsageTable')->assertSee('usageProductModal');
        $this->getJson(route('super.non-production-material-usages.number-preview'))->assertOk()->assertJsonStructure(['number']);
        app(CompanyContext::class)->set($company);
        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')->times(3)->andReturn(new AuditLog());
        $journals = Mockery::mock(AutomaticJournalService::class);
        $journals->shouldReceive('recordStockMovement')->times(4);
        $numbers = Mockery::mock(DocumentNumberService::class);
        $numbers->shouldReceive('generate')->once()->with('non_production_material_usage')->andReturn('PBN/2026/11/00001');
        $action = new SaveNonProductionMaterialUsageAction(app(CompanyContext::class), $audit, $journals, $numbers);

        $item = $action->create(['transaction_date' => today()->toDateString(), 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => 3], $user->id);
        $this->assertSame('PBN/2026/11/00001', $item->document->number);
        $this->assertDatabaseHas('non_production_material_usage_items', ['id' => $item->id, 'total_price' => '6.0000']);
        $this->assertDatabaseHas('stock_movements', ['non_production_material_usage_item_id' => $item->id, 'movement_type' => 'non_production_material_usage', 'quantity' => '-2.0000', 'cost_amount' => '6.00']);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '8.0000']);
        $this->assertSame(8.0, (float) InventoryLot::query()->where('product_id', $product->id)->sum('current_quantity'));
        $this->getJson(route('super.non-production-material-usages.dt'))->assertOk()->assertJsonFragment(['number' => 'PBN/2026/11/00001'])->assertJsonFragment(['style' => 'STYLE-1']);
        $this->getJson(route('super.stock-card.dt', ['product_id' => $product->id]))->assertOk()
            ->assertJsonFragment(['reference_number' => 'PBN/2026/11/00001'])
            ->assertJsonFragment(['quantity_out' => '2,0000']);

        $action->update($item, ['transaction_date' => today()->toDateString(), 'product_id' => $product->id, 'quantity' => 3, 'unit_price' => 4], $user->id);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '7.0000']);
        $this->assertDatabaseHas('stock_movements', ['non_production_material_usage_item_id' => $item->id, 'movement_type' => 'non_production_material_usage_reversal', 'quantity' => '2.0000']);
        $this->assertSame(7.0, (float) InventoryLot::query()->where('product_id', $product->id)->sum('current_quantity'));

        $action->delete($item->fresh(), $user->id);
        $this->assertSoftDeleted('non_production_material_usage_items', ['id' => $item->id]);
        $this->assertSoftDeleted('non_production_material_usage_documents', ['id' => $item->document_id]);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '10.0000']);
        $this->assertSame(10.0, (float) InventoryLot::query()->where('product_id', $product->id)->sum('current_quantity'));
    }
}
