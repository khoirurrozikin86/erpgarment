<?php

namespace Tests\Feature\Inventory;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveEquipmentIssueAction;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{AuditLog, Category, Company, DocumentNumbering, Product, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EquipmentIssueTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_issue_crud_list_and_export_are_available_without_stock_posting(): void
    {
        $company = Company::query()->create(['code' => 'MCH', 'name' => 'Machine Co', 'is_active' => true]);
        DocumentNumbering::query()->create(['company_id' => $company->id, 'document_type' => 'equipment_issue', 'prefix' => 'MCHO', 'format' => '{PREFIX}/{NUMBER}/{MM}/{YY}', 'next_number' => 1, 'number_length' => 1, 'reset_period' => 'monthly', 'is_active' => true]);
        $user = User::factory()->create();
        $user->givePermissionTo(collect(['view', 'create', 'update', 'delete'])->map(fn ($a) => Permission::firstOrCreate(['name' => "equipment-issues.$a", 'guard_name' => 'web'])));
        $this->actingAs($user);
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'MACHINE', 'name' => 'Machine', 'is_active' => true]);
        $product = Product::query()->create(['code' => 'NEEDLE-01', 'name' => 'Sewing needle', 'category_id' => $category->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => false, 'is_active' => true, 'is_available' => true]);
        app(CompanyContext::class)->set($company);
        $audit = Mockery::mock(AuditLogService::class); $audit->shouldReceive('log')->times(3)->andReturn(new AuditLog());
        $numbers = Mockery::mock(DocumentNumberService::class); $numbers->shouldReceive('generate')->once()->with('equipment_issue')->andReturn('MCHO/1/11/26');
        $action = new SaveEquipmentIssueAction(app(CompanyContext::class), $audit, $numbers);
        $data = ['issue_date' => today()->toDateString(), 'product_id' => $product->id, 'quantity' => 11, 'description' => 'Replacement'];
        $doc = $action->create($data, $user->id);
        $this->assertDatabaseHas('equipment_issue_documents', ['id' => $doc->id, 'number' => 'MCHO/1/11/26']);
        $this->assertDatabaseHas('equipment_issue_items', ['equipment_issue_document_id' => $doc->id, 'item_code' => 'NEEDLE-01', 'quantity' => '11.0000']);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->get(route('super.equipment-issues.index'))->assertOk()->assertSee('equipmentIssueTable');
        $this->getJson(route('super.equipment-issues.dt', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonFragment(['item_code' => 'NEEDLE-01']);
        $this->getJson(route('super.equipment-issues.products.dt', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertOk()->assertJsonPath('recordsTotal', 1);
        $this->getJson(route('super.equipment-issues.show', $doc))->assertOk()->assertJsonPath('item.quantity', '11.0000');
        $action->update($doc, array_merge($data, ['quantity' => 7]), $user->id);
        $this->assertDatabaseHas('equipment_issue_items', ['equipment_issue_document_id' => $doc->id, 'quantity' => '7.0000']);
        Excel::fake(); $this->get(route('super.equipment-issues.export'))->assertOk(); Excel::assertDownloaded('equipment-issues.xlsx');
        $action->delete($doc->fresh(), $user->id);
        $this->assertSoftDeleted('equipment_issue_documents', ['id' => $doc->id]);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
