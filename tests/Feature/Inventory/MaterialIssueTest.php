<?php

namespace Tests\Feature\Inventory;

use App\Models\BillOfMaterial;
use App\Models\Category;
use App\Models\Company;
use App\Models\DocumentNumbering;
use App\Models\InventoryLot;
use App\Models\MaterialIssueDocument;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductStock;
use App\Models\RawMaterialOrderDocument;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MaterialIssueTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_issue_loads_raw_material_order_and_reduces_stock_with_partial_shipment_history(): void
    {
        $company = Company::query()->create(['code' => 'TEST', 'name' => 'Test Co', 'is_active' => true]);
        DocumentNumbering::query()->create([
            'company_id' => $company->id, 'document_type' => 'material_issue', 'prefix' => 'MIS',
            'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5,
            'reset_period' => 'monthly', 'is_active' => true,
        ]);
        $user = User::factory()->create();
        $user->givePermissionTo(collect(['material-issues.view', 'material-issues.create', 'material-issues.update', 'material-issues.delete', 'production-receipts.view', 'production-receipts.approve', 'stock-card.view'])->map(
            fn (string $permission) => Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web'])
        ));
        $this->actingAs($user);

        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'FG', 'name' => 'Finished Goods', 'is_active' => true]);
        $finished = Product::query()->create(['code' => 'FG-1', 'name' => 'Finished', 'category_id' => $category->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'style_code' => 'STYLE-1', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);
        $material = Product::query()->create(['code' => 'MAT-1', 'name' => 'Fabric', 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true, 'purchase_price' => 2, 'is_active' => true, 'is_available' => true]);
        $material2 = Product::query()->create(['code' => 'MAT-2', 'name' => 'Thread', 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true, 'purchase_price' => 1, 'is_active' => true, 'is_available' => true]);
        $bom = BillOfMaterial::query()->create(['company_id' => $company->id, 'product_id' => $finished->id, 'version' => '1', 'status' => 'active', 'output_quantity' => 1]);
        $mo = ProductionOrder::query()->create([
            'company_id' => $company->id, 'number' => 'MO-001', 'bill_of_material_id' => $bom->id,
            'output_product_id' => $finished->id, 'bom_version' => '1', 'planned_quantity' => 10, 'status' => 'draft',
        ]);
        $moMaterial = $mo->materials()->create(['product_id' => $material->id, 'quantity_per_output' => 2, 'wastage_percent' => 0, 'required_quantity' => 30]);
        $moMaterial2 = $mo->materials()->create(['product_id' => $material2->id, 'quantity_per_output' => 1, 'wastage_percent' => 0, 'required_quantity' => 20]);
        $rawOrder = RawMaterialOrderDocument::query()->create([
            'company_id' => $company->id, 'number' => 'OBB/2026/11/00001', 'order_date' => '2026-11-12',
            'production_order_number' => 'MO-001', 'created_by' => $user->id,
        ]);
        $rawOrderItem = $rawOrder->items()->create([
            'company_id' => $company->id, 'number' => 'OBB/2026/11/00002',
            'production_order_material_id' => $moMaterial->id, 'product_id' => $material->id,
            'style_code' => 'STYLE-1', 'quantity' => 20, 'issued_quantity' => 0,
        ]);
        $rawOrderItem2 = $rawOrder->items()->create([
            'company_id' => $company->id, 'number' => 'OBB/2026/11/00003',
            'production_order_material_id' => $moMaterial2->id, 'product_id' => $material2->id,
            'style_code' => 'STYLE-1', 'quantity' => 10, 'issued_quantity' => 0,
        ]);
        ProductStock::query()->create(['product_id' => $material->id, 'quantity' => 50, 'average_unit_cost' => 2]);
        InventoryLot::query()->create(['product_id' => $material->id, 'lot_number' => 'LOT-1', 'received_at' => now(), 'initial_quantity' => 50, 'current_quantity' => 50, 'unit_cost' => 2, 'source_type' => 'receipt']);
        ProductStock::query()->create(['product_id' => $material2->id, 'quantity' => 20, 'average_unit_cost' => 1]);
        InventoryLot::query()->create(['product_id' => $material2->id, 'lot_number' => 'LOT-2', 'received_at' => now(), 'initial_quantity' => 20, 'current_quantity' => 20, 'unit_cost' => 1, 'source_type' => 'receipt']);

        $this->get(route('super.material-issues.index'))->assertOk()->assertSee('Kirim ke Produksi (MIS)');
        $this->getJson(route('super.material-issues.requests.dt'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['request_number' => 'OBB/2026/11/00001']);
        $this->getJson(route('super.material-issues.requests.show', $rawOrder))
            ->assertOk()->assertJsonCount(2, 'items');
        $this->postJson(route('super.material-issues.bulk.store'), [
            'issue_date' => '2026-11-13',
            'items' => [
                ['raw_material_order_item_id' => $rawOrderItem->id, 'quantity' => 8],
                ['raw_material_order_item_id' => $rawOrderItem2->id, 'quantity' => 4],
            ],
        ])->assertOk()->assertJsonPath('number', 'MIS/2026/11/00001')->assertJsonPath('items_count', 2);

        $this->get(route('super.production-receipts.index'))->assertOk()->assertSee('Penerimaan Produksi dari Gudang');
        $this->getJson(route('super.production-receipts.dt'))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.approval_status', '<span class="badge bg-danger">Tidak</span>');
        $this->getJson(route('super.production-receipts.documents.show', 1))->assertOk()->assertJsonCount(2, 'items')->assertJsonPath('items.0.quantity', 8);
        $this->putJson(route('super.production-receipts.approve', 1))->assertOk();
        $this->assertDatabaseHas('material_issue_documents', ['id' => 1, 'production_receipt_status' => 'received', 'production_received_by' => $user->id]);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $material->id, 'quantity' => '42.0000']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'PRODUCTION_RECEIPT', 'action' => 'APPROVE']);
        $this->getJson(route('super.stock-card.dt', ['product_id' => $material->id]))->assertOk()
            ->assertJsonFragment(['reference_number' => 'MIS/2026/11/00001'])
            ->assertJsonFragment(['quantity_out' => '8,0000']);

        $this->getJson(route('super.material-issues.requests.show', $rawOrder))
            ->assertOk()->assertJsonPath('items.0.sent_before', 8)->assertJsonPath('items.0.remaining', 12);
        $this->postJson(route('super.material-issues.store', $rawOrderItem), [
            'issue_date' => '2026-11-14', 'quantity' => 5,
        ])->assertOk()->assertJsonPath('number', 'MIS/2026/11/00002');
        $this->getJson(route('super.material-issues.history.dt'))->assertOk()->assertJsonCount(3, 'data');

        $this->assertDatabaseHas('material_issue_documents', ['number' => 'MIS/2026/11/00001', 'raw_material_order_document_id' => $rawOrder->id]);
        $this->assertDatabaseHas('material_issue_items', ['raw_material_order_item_id' => $rawOrderItem->id, 'product_id' => $material->id, 'quantity' => '8.0000']);
        $this->assertDatabaseHas('material_issue_items', ['raw_material_order_item_id' => $rawOrderItem2->id, 'product_id' => $material2->id, 'quantity' => '4.0000']);
        $this->assertDatabaseHas('raw_material_order_items', ['id' => $rawOrderItem->id, 'issued_quantity' => '13.0000']);
        $this->assertDatabaseHas('production_order_materials', ['id' => $moMaterial->id, 'issued_quantity' => '13.0000']);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $material->id, 'quantity' => '37.0000']);
        $this->assertDatabaseHas('stock_movements', ['material_issue_document_id' => 1, 'movement_type' => 'material_issue', 'quantity' => '-8.0000']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'MATERIAL_ISSUE', 'action' => 'CREATE']);
        $this->get(route('super.material-issues.documents.pdf', 1))->assertOk()->assertHeader('content-type', 'application/pdf');

        $firstIssueItem = MaterialIssueDocument::query()->where('number', 'MIS/2026/11/00001')->firstOrFail()->items()->firstOrFail();
        $this->putJson(route('super.material-issues.items.update', $firstIssueItem), [
            'issue_date' => '2026-11-15', 'quantity' => 6,
        ])->assertOk()->assertJsonPath('quantity', 6);

        $secondIssueItem = MaterialIssueDocument::query()->where('number', 'MIS/2026/11/00002')->firstOrFail()->items()->firstOrFail();
        $this->deleteJson(route('super.material-issues.items.destroy', $secondIssueItem))->assertOk();

        $this->assertDatabaseHas('raw_material_order_items', ['id' => $rawOrderItem->id, 'issued_quantity' => '6.0000']);
        $this->assertDatabaseHas('production_order_materials', ['id' => $moMaterial->id, 'issued_quantity' => '6.0000']);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $material->id, 'quantity' => '44.0000']);
        $this->assertDatabaseHas('inventory_lots', ['product_id' => $material->id, 'lot_number' => 'LOT-1', 'current_quantity' => '44.0000']);
        $this->assertDatabaseHas('raw_material_order_items', ['id' => $rawOrderItem2->id, 'issued_quantity' => '4.0000']);
        $this->assertDatabaseHas('production_order_materials', ['id' => $moMaterial2->id, 'issued_quantity' => '4.0000']);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $material2->id, 'quantity' => '16.0000']);
        $this->assertDatabaseHas('inventory_lots', ['product_id' => $material2->id, 'lot_number' => 'LOT-2', 'current_quantity' => '16.0000']);
        $this->assertDatabaseMissing('material_issue_documents', ['number' => 'MIS/2026/11/00002']);
        $this->assertDatabaseHas('material_issue_items', ['id' => $firstIssueItem->id, 'quantity' => '6.0000']);
        $this->assertDatabaseHas('stock_movements', ['movement_type' => 'material_issue_reversal', 'quantity' => '8.0000']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'MATERIAL_ISSUE', 'action' => 'UPDATE']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'MATERIAL_ISSUE', 'action' => 'DELETE']);

        $partialStatus = $this->getJson(route('super.material-issues.requests.dt'))->assertOk()->json('data.0.actions');
        $this->assertStringContainsString('btn-primary', $partialStatus);
        $this->postJson(route('super.material-issues.bulk.store'), [
            'issue_date' => '2026-11-16',
            'items' => [
                ['raw_material_order_item_id' => $rawOrderItem->id, 'quantity' => 14],
                ['raw_material_order_item_id' => $rawOrderItem2->id, 'quantity' => 6],
            ],
        ])->assertOk();
        $completeStatus = $this->getJson(route('super.material-issues.requests.dt'))->assertOk()->json('data.0.actions');
        $this->assertStringContainsString('btn-danger', $completeStatus);
    }
}
