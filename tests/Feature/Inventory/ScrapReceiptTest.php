<?php

namespace Tests\Feature\Inventory;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveScrapReceiptAction;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\{AuditLog, Category, Company, DocumentNumbering, Product, ProductStock, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ScrapReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrap_receipt_adds_stock_is_visible_in_stock_card_and_exportable(): void
    {
        $company = Company::query()->create(['code' => 'SCRAP', 'name' => 'Scrap Test Co', 'is_active' => true]);
        DocumentNumbering::query()->create(['company_id' => $company->id, 'document_type' => 'scrap_receipt', 'prefix' => 'SCI', 'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5, 'reset_period' => 'monthly', 'is_active' => true]);
        $user = User::factory()->create();
        $permissions = collect(['scrap-receipts.view', 'scrap-receipts.create', 'scrap-receipts.update', 'scrap-receipts.delete', 'stock-card.view'])
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        $unit = Unit::query()->create(['code' => 'KG', 'name' => 'Kilogram', 'symbol' => 'KG', 'is_active' => true]);
        $waste = Category::query()->create(['code' => 'WASTE', 'name' => 'Waste', 'is_active' => true]);
        $raw = Category::query()->create(['code' => 'RAW', 'name' => 'Raw Material', 'is_active' => true]);
        $scrap = Product::query()->create(['code' => 'SCRAP-1', 'name' => 'Fabric Scrap', 'category_id' => $waste->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);
        $source = Product::query()->create(['code' => 'FABRIC-1', 'name' => 'Fabric', 'category_id' => $raw->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);

        $this->get(route('super.scrap-receipts.index'))->assertOk()->assertSee('scrapReceiptTable')->assertSee('scrapProductModal');
        $this->getJson(route('super.scrap-receipts.products.dt', ['type' => 'scrap']))->assertOk()->assertJsonFragment(['code' => 'SCRAP-1'])->assertJsonMissing(['code' => 'FABRIC-1']);
        $this->getJson(route('super.scrap-receipts.number-preview'))->assertOk()->assertJsonStructure(['number']);
        app(CompanyContext::class)->set($company);

        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')->once()->andReturn(new AuditLog());
        $numbers = Mockery::mock(DocumentNumberService::class);
        $numbers->shouldReceive('generate')->once()->with('scrap_receipt')->andReturn('SCI/2026/11/00001');
        $document = (new SaveScrapReceiptAction(app(CompanyContext::class), $audit, $numbers))->create([
            'receipt_date' => today()->toDateString(), 'scrap_product_id' => $scrap->id, 'source_product_id' => $source->id,
            'production_number' => 'MO-001', 'quantity' => 4.25, 'description' => 'Fabric offcuts',
        ], $user->id);

        $this->assertDatabaseHas('product_stocks', ['product_id' => $scrap->id, 'quantity' => '4.2500']);
        $this->assertDatabaseHas('stock_movements', ['scrap_receipt_document_id' => $document->id, 'movement_type' => 'scrap_receipt', 'quantity' => '4.2500']);
        $this->getJson(route('super.stock-card.dt', ['product_id' => $scrap->id]))->assertOk()->assertJsonFragment(['reference_number' => 'SCI/2026/11/00001']);
        Excel::fake();
        $this->get(route('super.scrap-receipts.export'))->assertOk();
        Excel::assertDownloaded('scrap-receipts.xlsx');
    }

    public function test_deleting_scrap_receipt_restores_stock_and_keeps_ledger_reversal(): void
    {
        $company = Company::query()->create(['code' => 'SCRAP2', 'name' => 'Scrap Test Co 2', 'is_active' => true]);
        $user = User::factory()->create();
        app(CompanyContext::class)->set($company);
        $unit = Unit::query()->create(['code' => 'KG', 'name' => 'Kilogram', 'symbol' => 'KG', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'WASTE', 'name' => 'Waste', 'is_active' => true]);
        $scrap = Product::query()->create(['code' => 'SCRAP-2', 'name' => 'Scrap', 'category_id' => $category->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);
        $source = Product::query()->create(['code' => 'SOURCE-2', 'name' => 'Source', 'category_id' => $category->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);
        ProductStock::query()->create(['product_id' => $scrap->id, 'quantity' => 0, 'average_unit_cost' => 0]);
        $document = \App\Models\ScrapReceiptDocument::query()->create(['company_id' => $company->id, 'number' => 'SCI/2026/11/00002', 'receipt_date' => today(), 'scrap_product_id' => $scrap->id, 'source_product_id' => $source->id, 'quantity' => 3, 'created_by' => $user->id]);
        $movement = \App\Models\StockMovement::query()->create(['product_id' => $scrap->id, 'scrap_receipt_document_id' => $document->id, 'created_by' => $user->id, 'movement_type' => 'scrap_receipt', 'quantity' => 3, 'quantity_before' => 0, 'quantity_after' => 3, 'cost_amount' => 0, 'notes' => 'fixture']);
        ProductStock::query()->where('product_id', $scrap->id)->update(['quantity' => 3]);

        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')->once()->andReturn(new AuditLog());
        (new SaveScrapReceiptAction(app(CompanyContext::class), $audit, app(DocumentNumberService::class)))->delete($document, $user->id);

        $this->assertSoftDeleted('scrap_receipt_documents', ['id' => $document->id]);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $scrap->id, 'quantity' => '0.0000']);
        $this->assertDatabaseHas('stock_movements', ['scrap_receipt_document_id' => $document->id, 'movement_type' => 'scrap_receipt_reversal', 'quantity' => '-3.0000']);
    }
}
