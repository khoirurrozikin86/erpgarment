<?php

namespace Tests\Feature\Subcontract;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Domain\Subcontract\Actions\SaveSubcontractSemiFinishedAction;
use App\Domain\Subcontract\Queries\SubcontractSemiFinishedTableQuery;
use App\Models\{AuditLog, Category, Company, Product, Unit, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SubcontractSemiFinishedTest extends TestCase
{
    use RefreshDatabase;

    public function test_subcontract_semi_finished_document_can_be_created_updated_and_deleted(): void
    {
        $company = Company::query()->create(['code' => 'SUB', 'name' => 'Subcontract Test Co', 'is_active' => true]);
        $user = User::factory()->create();
        app(CompanyContext::class)->set($company);

        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'WIP', 'name' => 'Barang Setengah Jadi', 'is_active' => true]);
        $regularCategory = Category::query()->create(['code' => 'RAW', 'name' => 'Raw Material', 'is_active' => true]);
        $product = Product::query()->create([
            'code' => 'WIP-SUB-1', 'name' => 'Cut Panel Subkon', 'category_id' => $category->id,
            'unit_id' => $unit->id, 'product_type' => 'stock', 'garment_item_type' => 'work_in_process',
            'track_stock' => true, 'is_active' => true, 'is_available' => true,
        ]);
        $regularProduct = Product::query()->create([
            'code' => 'RM-SUB-1', 'name' => 'Regular Material', 'category_id' => $regularCategory->id,
            'unit_id' => $unit->id, 'product_type' => 'stock', 'garment_item_type' => 'raw_material',
            'track_stock' => true, 'is_active' => true, 'is_available' => true,
        ]);

        $this->assertTrue(app(SubcontractSemiFinishedTableQuery::class)->products()->whereKey($product->id)->exists());
        $this->assertFalse(app(SubcontractSemiFinishedTableQuery::class)->products()->whereKey($regularProduct->id)->exists());

        $numbers = Mockery::mock(DocumentNumberService::class);
        $numbers->shouldReceive('generate')->once()->with('subcontract_semi_finished')->andReturn('TIMW/1/HBSJD/11/26');
        $this->app->instance(DocumentNumberService::class, $numbers);

        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')->times(3)->andReturn(new AuditLog());
        $this->app->instance(AuditLogService::class, $audit);

        $action = app(SaveSubcontractSemiFinishedAction::class);
        $document = $action->create([
            'production_date' => '2026-11-14', 'product_id' => $product->id, 'quantity' => 12.5,
            'buyer_po_number' => 'PO-BUYER-01', 'description' => 'Cut panel output',
        ], $company->id, $user->id);

        $this->assertDatabaseHas('subcontract_semi_finished_documents', [
            'id' => $document->id, 'company_id' => $company->id, 'number' => 'TIMW/1/HBSJD/11/26',
        ]);
        $this->assertDatabaseHas('subcontract_semi_finished_items', [
            'document_id' => $document->id, 'product_id' => $product->id, 'quantity' => '12.5000',
        ]);

        $action->update($document, [
            'production_date' => '2026-11-15', 'product_id' => $product->id, 'quantity' => 8,
            'buyer_po_number' => 'PO-BUYER-02', 'description' => 'Updated output',
        ], $user->id);

        $this->assertDatabaseHas('subcontract_semi_finished_documents', [
            'id' => $document->id, 'buyer_po_number' => 'PO-BUYER-02', 'description' => 'Updated output',
        ]);
        $this->assertDatabaseHas('subcontract_semi_finished_items', [
            'document_id' => $document->id, 'product_id' => $product->id, 'quantity' => '8.0000',
        ]);

        $action->delete($document, $user->id);
        $this->assertSoftDeleted('subcontract_semi_finished_documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('subcontract_semi_finished_items', ['document_id' => $document->id]);
    }
}
