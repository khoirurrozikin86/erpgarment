<?php

namespace Tests\Feature\Manufacturing;

use App\Models\BillOfMaterial;
use App\Models\Category;
use App\Models\Company;
use App\Models\DocumentNumbering;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RawMaterialOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_raw_material_order_loads_mo_materials_and_saves_order_without_changing_stock(): void
    {
        $company = Company::query()->create(['code' => 'TEST', 'name' => 'Test Co', 'is_active' => true]);
        DocumentNumbering::query()->create([
            'company_id' => $company->id, 'document_type' => 'raw_material_order', 'prefix' => 'OBB',
            'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5,
            'reset_period' => 'monthly', 'is_active' => true,
        ]);
        $user = User::factory()->create();
        $user->givePermissionTo(collect(['view', 'create', 'update', 'delete'])->map(
            fn (string $action) => Permission::firstOrCreate(['name' => "raw-material-orders.{$action}", 'guard_name' => 'web'])
        ));
        $this->actingAs($user);

        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'FG', 'name' => 'Finished Goods', 'is_active' => true]);
        $finished = Product::query()->create(['code' => 'FG-1', 'name' => 'Finished', 'category_id' => $category->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'style_code' => 'STYLE-1', 'is_active' => true, 'is_available' => true]);
        $material1 = Product::query()->create(['code' => 'MAT-1', 'name' => 'Fabric', 'unit_id' => $unit->id, 'product_type' => 'stock', 'is_active' => true, 'is_available' => true]);
        $material2 = Product::query()->create(['code' => 'MAT-2', 'name' => 'Thread', 'unit_id' => $unit->id, 'product_type' => 'stock', 'is_active' => true, 'is_available' => true]);
        $bom = BillOfMaterial::query()->create(['company_id' => $company->id, 'product_id' => $finished->id, 'version' => '1', 'status' => 'active', 'output_quantity' => 1]);

        $orders = collect([$material1, $material2])->map(function (Product $material, int $index) use ($company, $finished, $bom, $user) {
            $order = ProductionOrder::query()->create([
                'company_id' => $company->id, 'number' => 'MO-001', 'buyer_po_number' => 'PO-1', 'po_date' => '2026-11-13',
                'bill_of_material_id' => $bom->id, 'output_product_id' => $finished->id, 'bom_version' => '1', 'planned_quantity' => 10,
                'status' => 'draft', 'created_by' => $user->id,
            ]);
            $order->materials()->create(['product_id' => $material->id, 'quantity_per_output' => 1, 'wastage_percent' => 0, 'required_quantity' => 10 + $index]);
            return $order;
        });

        $this->get(route('super.raw-material-orders.index'))->assertOk()->assertSee('Order Bahan Baku');
        $this->getJson(route('super.raw-material-orders.mos.dt'))
            ->assertOk()->assertJsonPath('data.0.mo_date', '2026-11-13');
        $this->getJson(route('super.raw-material-orders.materials', ['production_order_number' => 'MO-001']))
            ->assertOk()->assertJsonCount(2, 'items')->assertJsonPath('items.0.code', 'MAT-1');

        $response = $this->postJson(route('super.raw-material-orders.store'), [
            'order_date' => '2026-11-13', 'production_order_number' => 'MO-001',
            'items' => [
                ['production_order_material_id' => $orders[0]->materials()->first()->id, 'quantity' => 10],
                ['production_order_material_id' => $orders[1]->materials()->first()->id, 'quantity' => 11],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('raw_material_order_documents', ['number' => 'OBB/2026/11/00001', 'production_order_number' => 'MO-001']);
        $this->assertDatabaseCount('raw_material_order_items', 2);
        $itemNumbers = \Illuminate\Support\Facades\DB::table('raw_material_order_items')->orderBy('id')->pluck('number')->all();
        $this->assertCount(2, array_unique($itemNumbers));
        $this->assertNotSame($itemNumbers[0], $itemNumbers[1]);
        $this->assertDatabaseHas('raw_material_order_items', ['product_id' => $material1->id, 'style_code' => 'STYLE-1', 'quantity' => '10.0000']);
        $this->assertDatabaseHas('production_order_materials', ['product_id' => $material1->id, 'required_quantity' => '10.0000', 'issued_quantity' => '0.0000']);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $material1->id]);

        $documentId = $response->json('url') ? \App\Models\RawMaterialOrderDocument::query()->firstOrFail()->id : null;
        $this->putJson(route('super.raw-material-orders.update', $documentId), [
            'order_date' => '2026-11-12', 'production_order_number' => 'MO-001',
            'items' => [
                ['production_order_material_id' => $orders[0]->materials()->first()->id, 'quantity' => 9],
                ['production_order_material_id' => $orders[1]->materials()->first()->id, 'quantity' => 11],
            ],
        ])->assertOk();
        $this->assertDatabaseHas('raw_material_order_items', ['product_id' => $material1->id, 'quantity' => '9.0000']);
        $this->deleteJson(route('super.raw-material-orders.destroy', $documentId))->assertOk();
        $this->assertDatabaseMissing('raw_material_order_documents', ['id' => $documentId]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'RAW_MATERIAL_ORDERS', 'action' => 'DELETE']);
    }
}
