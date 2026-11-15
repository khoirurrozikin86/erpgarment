<?php

namespace Tests\Feature\Inventory;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveScrapIssueAction;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{AuditLog, Category, Company, Customer, DocumentNumbering, InventoryLot, Product, ProductStock, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ScrapIssueTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrap_issue_reduces_stock_writes_ledger_and_exports(): void
    {
        $company = Company::query()->create(['code' => 'SCO', 'name' => 'Scrap Issue Co', 'is_active' => true]);
        DocumentNumbering::query()->create(['company_id' => $company->id, 'document_type' => 'scrap_issue', 'prefix' => 'SCO', 'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5, 'reset_period' => 'monthly', 'is_active' => true]);
        $user = User::factory()->create();
        $user->givePermissionTo(collect(['scrap-issues.view', 'scrap-issues.create', 'scrap-issues.update', 'scrap-issues.delete', 'stock-card.view'])->map(fn ($name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'])));
        $this->actingAs($user);
        $unit = Unit::query()->create(['code' => 'KG', 'name' => 'Kilogram', 'symbol' => 'KG', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'WASTE', 'name' => 'Waste', 'is_active' => true]);
        $product = Product::query()->create(['code' => 'SCRAP-OUT-1', 'name' => 'Scrap Import', 'category_id' => $category->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);
        $customer = Customer::query()->create(['code' => 'CUST-SCO', 'name' => 'Scrap Customer', 'is_active' => true]);
        ProductStock::query()->create(['product_id' => $product->id, 'quantity' => 12.5, 'average_unit_cost' => 0]);
        InventoryLot::query()->create(['product_id' => $product->id, 'lot_number' => 'SCRAP-LOT-1', 'received_at' => now(), 'initial_quantity' => 12.5, 'current_quantity' => 12.5, 'unit_cost' => 0, 'source_type' => 'opening_balance']);
        app(CompanyContext::class)->set($company);
        $audit = Mockery::mock(AuditLogService::class); $audit->shouldReceive('log')->once()->andReturn(new AuditLog());
        $numbers = Mockery::mock(DocumentNumberService::class); $numbers->shouldReceive('generate')->once()->with('scrap_issue')->andReturn('SCO/2026/11/00001');

        $document = (new SaveScrapIssueAction(app(CompanyContext::class), $audit, $numbers))->create(['issue_date' => today()->toDateString(), 'customer_id' => $customer->id, 'scrap_product_id' => $product->id, 'quantity' => 3.25, 'description' => 'Disposal'], $user->id);

        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '9.2500']);
        $this->assertDatabaseHas('inventory_lots', ['id' => 1, 'current_quantity' => '9.2500']);
        $this->assertDatabaseHas('stock_movements', ['scrap_issue_document_id' => $document->id, 'movement_type' => 'scrap_issue', 'quantity' => '-3.2500']);
        $this->get(route('super.scrap-issues.index'))->assertOk()->assertSee('scrapIssuesTable')->assertSee('scrapIssueModal');
        $this->getJson(route('super.scrap-issues.dt', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonFragment(['customer_name' => 'Scrap Customer', 'scrap_code' => 'SCRAP-OUT-1']);
        $this->getJson(route('super.stock-card.dt', ['product_id' => $product->id]))->assertOk()->assertJsonFragment(['reference_number' => 'SCO/2026/11/00001']);
        Excel::fake(); $this->get(route('super.scrap-issues.export'))->assertOk(); Excel::assertDownloaded('scrap-issues.xlsx');
    }

    public function test_scrap_issue_rejects_quantity_above_stock(): void
    {
        $company = Company::query()->create(['code' => 'SCO2', 'name' => 'Scrap Issue Co 2', 'is_active' => true]);
        $user = User::factory()->create(); app(CompanyContext::class)->set($company);
        $unit = Unit::query()->create(['code' => 'KG2', 'name' => 'Kilogram', 'symbol' => 'KG', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'WASTE', 'name' => 'Waste', 'is_active' => true]);
        $product = Product::query()->create(['code' => 'SCRAP-OUT-2', 'name' => 'Scrap', 'category_id' => $category->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);
        $customer = Customer::query()->create(['code' => 'CUST-SCO2', 'name' => 'Customer', 'is_active' => true]);
        ProductStock::query()->create(['product_id' => $product->id, 'quantity' => 1, 'average_unit_cost' => 0]);
        InventoryLot::query()->create(['product_id' => $product->id, 'lot_number' => 'SCO2-LOT', 'received_at' => now(), 'initial_quantity' => 1, 'current_quantity' => 1, 'unit_cost' => 0, 'source_type' => 'opening_balance']);
        $numbers = Mockery::mock(DocumentNumberService::class); $numbers->shouldReceive('generate')->once()->with('scrap_issue')->andReturn('SCO-2');
        $audit = Mockery::mock(AuditLogService::class);
        try {
            (new SaveScrapIssueAction(app(CompanyContext::class), $audit, $numbers))->create(['issue_date' => today()->toDateString(), 'customer_id' => $customer->id, 'scrap_product_id' => $product->id, 'quantity' => 2], $user->id);
            $this->fail('Expected insufficient stock validation.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '1.0000']);
        $this->assertDatabaseCount('scrap_issue_documents', 0);
    }
}
