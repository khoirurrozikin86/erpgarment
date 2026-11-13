<?php

namespace Tests\Feature\Inventory;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Inventory\Actions\SaveDamagedItemAction;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{AuditLog, Category, InventoryLot, Product, ProductStock, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DamagedItemStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_reduces_stock_and_edit_records_a_compensating_movement(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::firstOrCreate(['name' => 'damaged-items.view', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'damaged-items.create', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'damaged-items.update', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'damaged-items.delete', 'guard_name' => 'web']),
        ]);
        $this->actingAs($user)->get(route('super.damaged-items.index'))->assertOk()->assertSee('damagedItemsTable')->assertSee('damagedItemModal')->assertSee('name="_token"', false);
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'MAT', 'name' => 'Material', 'is_active' => true]);
        $product = Product::query()->create([
            'code' => 'MAT-1', 'name' => 'Material One', 'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true, 'purchase_price' => 2,
        ]);
        ProductStock::query()->create(['product_id' => $product->id, 'quantity' => 10, 'average_unit_cost' => 2]);
        InventoryLot::query()->create([
            'product_id' => $product->id, 'lot_number' => 'LOT-1', 'received_at' => now(), 'initial_quantity' => 10,
            'current_quantity' => 10, 'unit_cost' => 2, 'source_type' => 'purchase_receipt',
        ]);

        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')->times(4)->andReturn(new AuditLog());
        $journals = Mockery::mock(AutomaticJournalService::class);
        $journals->shouldReceive('recordStockMovement')->times(4);
        $numbers = Mockery::mock(DocumentNumberService::class);
        $numbers->shouldReceive('generate')->once()->with('damaged_item')->andReturn('BRSK/2026/11/00001');
        $action = new SaveDamagedItemAction($audit, $journals, $numbers);

        $line = $action->create(['document_date' => today()->toDateString(), 'product_id' => $product->id, 'quantity' => 2], $user->id);
        $this->assertSame('BRSK/2026/11/00001', $line->document->number);
        $tableResponse = $this->getJson(route('super.damaged-items.dt'))->assertOk();
        $this->assertStringContainsString('dropdown-menu', $tableResponse->json('data.0.actions'));
        $this->assertStringContainsString('btn-delete-damaged-item', $tableResponse->json('data.0.actions'));
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '8.0000']);
        $this->assertDatabaseHas('stock_movements', ['damaged_item_line_id' => $line->id, 'movement_type' => 'damaged_item', 'quantity' => '-2.0000']);

        $action->update($line, ['document_date' => today()->toDateString(), 'product_id' => $product->id, 'quantity' => 3], $user->id);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '7.0000']);

        $action->update($line->fresh(), ['document_date' => today()->toDateString(), 'product_id' => $product->id, 'quantity' => 1], $user->id);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '9.0000']);
        $this->assertDatabaseHas('stock_movements', ['damaged_item_line_id' => $line->id, 'movement_type' => 'damaged_item_reversal', 'quantity' => '2.0000']);
        $this->assertSame(9.0, (float) InventoryLot::query()->where('product_id', $product->id)->sum('current_quantity'));

        $action->delete($line->fresh(), $user->id);
        $this->assertSoftDeleted('damaged_item_lines', ['id' => $line->id]);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '10.0000']);
        $this->assertSame(10.0, (float) InventoryLot::query()->where('product_id', $product->id)->sum('current_quantity'));
    }
}
