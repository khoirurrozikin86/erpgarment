<?php

namespace Tests\Feature\Inventory;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveWipIssueAction;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{AuditLog, Category, Company, DocumentNumbering, InventoryLot, Product, ProductStock, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WipIssueTest extends TestCase
{
    use RefreshDatabase;

    public function test_wip_issue_reduces_stock_and_is_exportable(): void
    {
        $company = Company::query()->create(['code' => 'WIP', 'name' => 'WIP Test Co', 'is_active' => true]);
        DocumentNumbering::query()->create(['company_id' => $company->id, 'document_type' => 'wip_issue', 'prefix' => 'WIP', 'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5, 'reset_period' => 'monthly', 'is_active' => true]);
        $user = User::factory()->create();
        $user->givePermissionTo(collect(['wip-issues.view', 'wip-issues.create', 'wip-issues.update', 'wip-issues.delete', 'stock-card.view'])->map(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'])));
        $this->actingAs($user);
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'WIP', 'name' => 'Barang Setengah Jadi', 'is_active' => true]);
        $product = Product::query()->create(['code' => 'WIP-1', 'name' => 'Cut Panel', 'category_id' => $category->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'garment_item_type' => 'work_in_process', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);
        ProductStock::query()->create(['product_id' => $product->id, 'quantity' => 12, 'average_unit_cost' => 3]);
        InventoryLot::query()->create(['product_id' => $product->id, 'lot_number' => 'WIP-LOT', 'received_at' => now(), 'initial_quantity' => 12, 'current_quantity' => 12, 'unit_cost' => 3, 'source_type' => 'production_output']);
        $this->get(route('super.wip-issues.index'))->assertOk()->assertSee('wipIssueTable')->assertSee('wipProductModal');
        $this->getJson(route('super.wip-issues.products.dt'))->assertOk()->assertJsonFragment(['code' => 'WIP-1']);
        $this->getJson(route('super.wip-issues.number-preview'))->assertOk()->assertJsonStructure(['number']);
        app(CompanyContext::class)->set($company);
        $audit = Mockery::mock(AuditLogService::class); $audit->shouldReceive('log')->once()->andReturn(new AuditLog());
        $journal = Mockery::mock(AutomaticJournalService::class); $journal->shouldReceive('recordStockMovement')->once();
        $numbers = Mockery::mock(DocumentNumberService::class); $numbers->shouldReceive('generate')->once()->with('wip_issue')->andReturn('WIP/2026/11/00001');
        $line = (new SaveWipIssueAction(app(CompanyContext::class), $audit, $journal, $numbers))->create(['transaction_date' => today()->toDateString(), 'product_id' => $product->id, 'quantity' => 2.5], $user->id);
        $this->assertDatabaseHas('stock_movements', ['wip_issue_line_id' => $line->id, 'movement_type' => 'wip_issue', 'quantity' => '-2.5000']);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '9.5000']);
        $this->getJson(route('super.stock-card.dt', ['product_id' => $product->id]))->assertOk()->assertJsonFragment(['reference_number' => 'WIP/2026/11/00001']);
        Excel::fake();
        $this->get(route('super.wip-issues.export'))->assertOk();
        Excel::assertDownloaded('wip-issues.xlsx');
    }
}
