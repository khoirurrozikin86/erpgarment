<?php

namespace Tests\Feature\Inventory;

use App\Models\{Category, Company, Product, ScrapIssueDocument, ScrapReceiptDocument, StockMovement, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ScrapStockCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrap_stock_card_lists_only_waste_items_and_shows_exportable_movement_ledger(): void
    {
        $company = Company::query()->create(['code' => 'SCRAP-CARD', 'name' => 'Scrap Card Co', 'is_active' => true]);
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'stock-card.view', 'guard_name' => 'web']));
        $this->actingAs($user);

        $unit = Unit::query()->create(['code' => 'KG', 'name' => 'Kilogram', 'symbol' => 'KG', 'is_active' => true]);
        $waste = Category::query()->create(['code' => 'WASTE', 'name' => 'Waste', 'is_active' => true]);
        $raw = Category::query()->create(['code' => 'RAW', 'name' => 'Raw Material', 'is_active' => true]);
        $scrap = Product::query()->create(['code' => 'SCRAP-CARD-1', 'name' => 'Local Yarn Scrap', 'category_id' => $waste->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);
        $source = Product::query()->create(['code' => 'SOURCE-CARD-1', 'name' => 'Source Item', 'category_id' => $raw->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);
        $notScrap = Product::query()->create(['code' => 'RAW-CARD-1', 'name' => 'Regular Fabric', 'category_id' => $raw->id, 'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true, 'is_active' => true, 'is_available' => true]);

        $receipt = ScrapReceiptDocument::query()->create(['company_id' => $company->id, 'number' => 'SCI/2026/11/00001', 'receipt_date' => '2026-11-10', 'scrap_product_id' => $scrap->id, 'source_product_id' => $source->id, 'quantity' => 8, 'created_by' => $user->id]);
        $issue = ScrapIssueDocument::query()->create(['company_id' => $company->id, 'number' => 'SCO/2026/11/00001', 'issue_date' => '2026-11-11', 'customer_id' => \App\Models\Customer::query()->create(['code' => 'SCRAP-CUST', 'name' => 'Scrap Buyer', 'is_active' => true])->id, 'scrap_product_id' => $scrap->id, 'quantity' => 3, 'created_by' => $user->id]);
        StockMovement::query()->create(['product_id' => $scrap->id, 'scrap_receipt_document_id' => $receipt->id, 'created_by' => $user->id, 'movement_type' => 'scrap_receipt', 'quantity' => 8, 'quantity_before' => 0, 'quantity_after' => 8, 'cost_amount' => 0]);
        StockMovement::query()->create(['product_id' => $scrap->id, 'scrap_issue_document_id' => $issue->id, 'created_by' => $user->id, 'movement_type' => 'scrap_issue', 'quantity' => -3, 'quantity_before' => 8, 'quantity_after' => 5, 'cost_amount' => 0]);

        $this->get(route('super.scrap-stock-card.index'))->assertOk()->assertSee('scrapProductsTable');
        $this->getJson(route('super.scrap-stock-card.products.dt'))->assertOk()
            ->assertJsonFragment(['code' => 'SCRAP-CARD-1', 'group_label' => 'SCRAP', 'category_label' => 'Waste'])
            ->assertJsonMissing(['code' => $notScrap->code]);
        $this->get(route('super.scrap-stock-card.show', $scrap))->assertOk()->assertSee('scrapMovementsTable');
        $this->getJson(route('super.scrap-stock-card.movements.dt', $scrap))->assertOk()
            ->assertJsonFragment(['document_number' => $receipt->number, 'description' => __('ui.scrap_receipt.title'), 'direction' => '+', 'balance' => '8,0000'])
            ->assertJsonFragment(['document_number' => $issue->number, 'description' => __('ui.scrap_issue.title'), 'direction' => '-', 'balance' => '5,0000']);

        Excel::fake();
        $this->get(route('super.scrap-stock-card.movements.export', $scrap))->assertOk();
        Excel::assertDownloaded('kartu-stok-scrap-SCRAP-CARD-1.xlsx');
    }
}
