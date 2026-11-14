<?php

namespace Tests\Feature\Inventory;

use App\Models\{Category, InventoryLot, Product, StockMovement, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FinishedGoodsStockCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_finished_goods_stock_card_lists_only_fg_products_and_exports_ledger(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'stock-card.view', 'guard_name' => 'web']));
        $this->actingAs($user);

        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $finished = Category::query()->create(['code' => 'FG', 'name' => 'Finished Goods', 'is_active' => true]);
        $material = Category::query()->create(['code' => 'MAT', 'name' => 'Material', 'is_active' => true]);
        $product = Product::query()->create([
            'code' => 'FG-001', 'name' => 'Finished Shirt', 'category_id' => $finished->id, 'unit_id' => $unit->id,
            'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true,
        ]);
        Product::query()->create([
            'code' => 'MAT-001', 'name' => 'Fabric', 'category_id' => $material->id, 'unit_id' => $unit->id,
            'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true,
        ]);
        InventoryLot::query()->create([
            'product_id' => $product->id, 'lot_number' => 'FG-TEST', 'received_at' => now()->subDay(),
            'initial_quantity' => 12, 'current_quantity' => 12, 'unit_cost' => 0, 'source_type' => 'production',
        ]);
        StockMovement::query()->create([
            'product_id' => $product->id, 'created_by' => $user->id, 'movement_type' => 'production_output',
            'quantity' => 12, 'quantity_before' => 0, 'quantity_after' => 12, 'notes' => 'Production output',
        ]);
        StockMovement::query()->create([
            'product_id' => $product->id, 'created_by' => $user->id, 'movement_type' => 'goods_sent_note',
            'quantity' => -3, 'quantity_before' => 12, 'quantity_after' => 9, 'notes' => 'Delivery',
        ]);

        $this->get(route('super.finished-goods-stock-card.index'))->assertOk()->assertSee('finishedGoodsProductsTable');
        $this->getJson(route('super.finished-goods-stock-card.products.dt'))->assertOk()
            ->assertJsonFragment(['code' => 'FG-001'])
            ->assertJsonMissing(['code' => 'MAT-001']);
        $this->get(route('super.finished-goods-stock-card.show', $product))->assertOk()->assertSee('finishedGoodsMovementsTable');
        $this->getJson(route('super.finished-goods-stock-card.movements.dt', $product))->assertOk()
            ->assertJsonFragment(['description' => 'Hasil Produksi'])
            ->assertJsonFragment(['direction' => '+'])
            ->assertJsonFragment(['balance' => '12,0000'])
            ->assertJsonFragment(['description' => 'Pengiriman Barang Jadi'])
            ->assertJsonFragment(['direction' => '-'])
            ->assertJsonFragment(['balance' => '9,0000']);

        Excel::fake();
        $this->get(route('super.finished-goods-stock-card.export'))->assertOk();
        Excel::assertDownloaded('kartu-stok-barang-jadi-'.now()->format('Ymd-His').'.xlsx');
        $this->get(route('super.finished-goods-stock-card.movements.export', $product))->assertOk();
        Excel::assertDownloaded('kartu-stok-FG-001.xlsx');
    }
}
