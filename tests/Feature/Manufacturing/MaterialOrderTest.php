<?php

namespace Tests\Feature\Manufacturing;

use App\Models\BillOfMaterial;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DocumentNumbering;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MaterialOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_order_matches_reference_and_supports_crud_for_unused_orders(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(collect(['view', 'create', 'update', 'delete'])
            ->map(fn (string $action) => Permission::firstOrCreate(['name' => "production-orders.{$action}", 'guard_name' => 'web'])));
        $this->actingAs($user);

        $company = Company::query()->create(['code' => 'TEST', 'name' => 'Test Co', 'is_active' => true]);
        DocumentNumbering::query()->create([
            'company_id' => $company->id, 'document_type' => 'production_order', 'prefix' => 'MO',
            'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5,
            'reset_period' => 'monthly', 'is_active' => true,
        ]);
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'FG', 'name' => 'Finished Goods', 'is_active' => true]);
        $finishedGood = Product::query()->create([
            'code' => 'FG-001', 'name' => 'Finished Item', 'category_id' => $category->id,
            'unit_id' => $unit->id, 'product_type' => 'stock', 'style_code' => 'STYLE-1',
            'garment_item_type' => 'finished_good', 'is_active' => true, 'is_available' => true,
        ]);
        $material = Product::query()->create([
            'code' => 'MAT-001', 'name' => 'Material', 'unit_id' => $unit->id,
            'product_type' => 'stock', 'is_active' => true,
        ]);
        $customer = Customer::query()->create(['code' => 'BUYER-1', 'name' => 'Buyer One', 'is_active' => true]);
        $bom = BillOfMaterial::query()->create([
            'company_id' => $company->id, 'product_id' => $finishedGood->id, 'version' => '1',
            'status' => 'active', 'output_quantity' => 1,
        ]);
        $bom->items()->create(['product_id' => $material->id, 'quantity' => 2, 'wastage_percent' => 0]);

        $this->get(route('super.material-orders.index'))->assertOk()
            ->assertSee('Material Order (MO)')->assertSee('PO Buyer')->assertSee('Tanggal Tempo')
            ->assertSee('Tanggal PO')->assertSee('Kode Barang')->assertSee('Jumlah')
            ->assertDontSee('ui.workflow.po_date');

        $payload = [
            'number' => '155M27', 'buyer_po_number' => 'BUYER-PO-10', 'no_cbd' => 'CBD-10', 'currency' => 'USD',
            'destination' => 'PHILIPPINE', 'po_date' => '2026-11-01', 'due_date' => '2026-12-01',
            'customer_id' => $customer->id, 'output_product_id' => $finishedGood->id,
            'planned_quantity' => '25', 'price_per_unit' => '2.5', 'description' => 'Initial line',
        ];
        $this->postJson(route('super.material-orders.store'), $payload)->assertCreated();
        $this->assertDatabaseHas('production_orders', [
            'number' => '155M27', 'buyer_po_number' => 'BUYER-PO-10', 'no_cbd' => 'CBD-10',
            'currency' => 'USD', 'destination' => 'PHILIPPINE', 'customer_id' => $customer->id,
            'output_product_id' => $finishedGood->id, 'planned_quantity' => '25.0000', 'total_price' => '62.5000',
        ]);
        $order = \App\Models\ProductionOrder::query()->firstOrFail();

        $secondLine = $payload;
        $secondLine['planned_quantity'] = '3';
        $secondLine['price_per_unit'] = '4';
        $this->postJson(route('super.material-orders.store'), $secondLine)->assertCreated();

        $payload['buyer_po_number'] = 'BUYER-PO-11';
        $payload['planned_quantity'] = '30';
        $payload['price_per_unit'] = '4';
        $this->putJson(route('super.material-orders.update', $order), $payload)->assertOk();
        $this->assertDatabaseHas('production_orders', ['id' => $order->id, 'buyer_po_number' => 'BUYER-PO-11', 'planned_quantity' => '30.0000', 'total_price' => '120.0000']);
        $this->assertDatabaseHas('production_orders', ['number' => '155M27', 'planned_quantity' => '3.0000', 'buyer_po_number' => 'BUYER-PO-11']);
        $this->deleteJson(route('super.material-orders.destroy', $order))->assertOk();
        $this->assertDatabaseMissing('production_orders', ['id' => $order->id]);
        $this->assertDatabaseHas('production_orders', ['number' => '155M27', 'planned_quantity' => '3.0000']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'MATERIAL_ORDER', 'action' => 'DELETE']);
    }
}
