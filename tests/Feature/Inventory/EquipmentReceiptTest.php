<?php

namespace Tests\Feature\Inventory;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveEquipmentReceiptAction;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{AuditLog, Category, Company, DocumentNumbering, EquipmentReceiptDocument, Product, Supplier, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EquipmentReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_receipt_can_be_created_edited_listed_and_exported_without_stock_posting(): void
    {
        $company = Company::query()->create(['code' => 'MCH', 'name' => 'Machine Co', 'is_active' => true]);
        DocumentNumbering::query()->create(['company_id' => $company->id, 'document_type' => 'equipment_receipt', 'prefix' => 'MCHI', 'format' => '{PREFIX}/{NUMBER}/{MM}/{YY}', 'next_number' => 1, 'number_length' => 1, 'reset_period' => 'monthly', 'is_active' => true]);
        $user = User::factory()->create();
        $user->givePermissionTo(collect(['view', 'create', 'update', 'delete'])->map(fn ($a) => Permission::firstOrCreate(['name' => "equipment-receipts.$a", 'guard_name' => 'web'])));
        $this->actingAs($user);
        $supplier = Supplier::query()->create(['code' => 'SUP-MCH', 'name' => 'Machine Supplier', 'is_active' => true]);
        $unit = Unit::query()->create(['code' => 'UNIT', 'name' => 'Unit', 'symbol' => 'UNIT', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'MACHINE', 'name' => 'Machine', 'is_active' => true]);
        $product = Product::query()->create(['code' => 'MCH-01', 'name' => 'Sewing Machine', 'category_id' => $category->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => false, 'is_active' => true, 'is_available' => true, 'purchase_price' => 125]);
        app(CompanyContext::class)->set($company);
        $audit = Mockery::mock(AuditLogService::class); $audit->shouldReceive('log')->times(3)->andReturn(new AuditLog());
        $numbers = Mockery::mock(DocumentNumberService::class); $numbers->shouldReceive('generate')->once()->with('equipment_receipt')->andReturn('MCHI/1/11/26');
        $action = new SaveEquipmentReceiptAction(app(CompanyContext::class), $audit, $numbers);
        $data = ['receipt_date' => today()->toDateString(), 'ownership_type' => 'own', 'invoice_number' => 'INV-1', 'invoice_date' => today()->toDateString(), 'supplier_id' => $supplier->id, 'currency_code' => 'USD', 'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 125, 'supplier_po_number' => 'PO-1']]];
        $document = $action->create($data, $user->id);
        $this->assertDatabaseHas('equipment_receipt_documents', ['id' => $document->id, 'number' => 'MCHI/1/11/26', 'ownership_type' => 'own']);
        $this->assertDatabaseHas('equipment_receipt_items', ['equipment_receipt_document_id' => $document->id, 'product_id' => $product->id, 'total_price' => '250.0000']);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->get(route('super.equipment-receipts.index'))->assertOk()->assertSee('equipmentTable')->assertSee('equipmentModal');
        $this->getJson(route('super.equipment-receipts.dt', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonFragment(['supplier_name' => 'Machine Supplier']);
        $this->getJson(route('super.equipment-receipts.products.dt', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonFragment(['code' => 'MCH-01']);
        $this->getJson(route('super.equipment-receipts.suppliers.dt', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonFragment(['name' => 'Machine Supplier']);
        $this->getJson(route('super.equipment-receipts.show', $document))->assertOk()->assertJsonPath('ownership_label', 'Own');
        $action->update($document, array_merge($data, ['ownership_type' => 'rent', 'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 100]]]), $user->id);
        $this->assertDatabaseHas('equipment_receipt_documents', ['id' => $document->id, 'ownership_type' => 'rent']);
        $this->assertDatabaseHas('equipment_receipt_items', ['equipment_receipt_document_id' => $document->id, 'quantity' => '3.0000', 'total_price' => '300.0000']);
        Excel::fake(); $this->get(route('super.equipment-receipts.export'))->assertOk(); Excel::assertDownloaded('equipment-receipts.xlsx');
        $action->delete($document->fresh(), $user->id);
        $this->assertSoftDeleted('equipment_receipt_documents', ['id' => $document->id]);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
