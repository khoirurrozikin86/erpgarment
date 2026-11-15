<?php

namespace Tests\Feature\Inventory;

use App\Domain\Companies\Services\CompanyContext;
use App\Models\{Category, Company, EquipmentIssueDocument, EquipmentIssueItem, EquipmentReceiptDocument, EquipmentReceiptItem, Product, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EquipmentStockCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_stock_card_lists_machines_and_exports_a_running_receipt_issue_ledger(): void
    {
        $company = Company::query()->create(['code' => 'MCH-CARD', 'name' => 'Machine Card Co', 'is_active' => true]);
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'stock-card.view', 'guard_name' => 'web']));
        $this->actingAs($user);
        app(CompanyContext::class)->set($company);

        $unit = Unit::query()->create(['code' => 'UNIT', 'name' => 'Unit', 'symbol' => 'UNIT', 'is_active' => true]);
        $machineCategory = Category::query()->create(['code' => 'MACHINE', 'name' => 'Machine', 'is_active' => true]);
        $equipmentCategory = Category::query()->create(['code' => 'EQUIPMENT', 'name' => 'Equipment', 'is_active' => true]);
        $rawCategory = Category::query()->create(['code' => 'RAW', 'name' => 'Raw Material', 'is_active' => true]);
        $machine = Product::query()->create(['code' => 'MCH-CARD-1', 'name' => 'Zigzag Machine', 'category_id' => $machineCategory->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => false, 'is_active' => true, 'is_available' => true]);
        $equipment = Product::query()->create(['code' => 'EQ-CARD-1', 'name' => 'Fabric Cutter', 'category_id' => $equipmentCategory->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => false, 'is_active' => true, 'is_available' => true]);
        $other = Product::query()->create(['code' => 'RAW-CARD-1', 'name' => 'Fabric', 'category_id' => $rawCategory->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);

        $receipt = EquipmentReceiptDocument::query()->create(['company_id' => $company->id, 'number' => 'MCHI/1/11/26', 'receipt_date' => '2026-11-10', 'ownership_type' => 'own', 'created_by' => $user->id]);
        EquipmentReceiptItem::query()->create(['equipment_receipt_document_id' => $receipt->id, 'product_id' => $machine->id, 'item_code' => $machine->code, 'item_name' => $machine->name, 'unit' => 'UNIT', 'quantity' => 4]);
        $issue = EquipmentIssueDocument::query()->create(['company_id' => $company->id, 'number' => 'MCHO/1/11/26', 'issue_date' => '2026-11-12', 'created_by' => $user->id]);
        EquipmentIssueItem::query()->create(['equipment_issue_document_id' => $issue->id, 'product_id' => $machine->id, 'item_code' => $machine->code, 'item_name' => $machine->name, 'unit' => 'UNIT', 'quantity' => 1]);

        $this->get(route('super.equipment-stock-card.index'))->assertOk()->assertSee('equipmentProductsTable');
        $this->getJson(route('super.equipment-stock-card.products.dt', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()->assertJsonPath('recordsTotal', 2)
            ->assertJsonFragment(['code' => $machine->code])
            ->assertJsonFragment(['code' => $equipment->code])
            ->assertJsonMissing(['code' => $other->code]);
        $this->get(route('super.equipment-stock-card.show', $machine))->assertOk()->assertSee('equipmentMovementsTable');
        $this->getJson(route('super.equipment-stock-card.movements.dt', ['product' => $machine->id, 'draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()->assertJsonPath('recordsTotal', 2)
            ->assertJsonFragment(['number' => $receipt->number, 'direction' => '+', 'balance' => '4,00'])
            ->assertJsonFragment(['number' => $issue->number, 'direction' => '-', 'balance' => '3,00']);

        Excel::fake();
        $this->get(route('super.equipment-stock-card.export'))->assertOk();
        Excel::assertDownloaded('daftar-mesin-peralatan-kartu-stok.xlsx');
        $this->get(route('super.equipment-stock-card.movements.export', $machine))->assertOk();
        Excel::assertDownloaded('kartu-stok-mesin-MCH-CARD-1.xlsx');
    }
}
