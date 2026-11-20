<?php

namespace Tests\Feature\Manufacturing;

use App\Domain\Purchasing\Services\MaterialOrderRequirements;
use App\Models\BillOfMaterial;
use App\Models\Category;
use App\Models\Company;
use App\Models\MaterialRequest;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Style;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleMaterialRequestBomsTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_material_order_can_keep_snapshots_for_multiple_boms_and_reserve_prior_requests(): void
    {
        $company = Company::query()->create(['code' => 'TEST', 'name' => 'Test Co', 'is_active' => true]);
        $user = User::factory()->create();
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'FG', 'name' => 'Finished Goods', 'is_active' => true]);
        $style = Style::query()->create(['code' => 'STYLE-1', 'name' => 'Style One']);
        $finished = Product::query()->create([
            'code' => 'FG-1', 'name' => 'Finished', 'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'stock', 'style_code' => $style->code, 'is_active' => true, 'is_available' => true,
        ]);
        $fabric = Product::query()->create([
            'code' => 'FAB-1', 'name' => 'Fabric', 'unit_id' => $unit->id,
            'product_type' => 'stock', 'is_active' => true, 'is_available' => true,
        ]);
        $accessory = Product::query()->create([
            'code' => 'ACC-1', 'name' => 'Accessory', 'unit_id' => $unit->id,
            'product_type' => 'stock', 'is_active' => true, 'is_available' => true,
        ]);
        $fabricBom = BillOfMaterial::query()->create([
            'company_id' => $company->id, 'style_id' => $style->id, 'bom_type' => 'fabric',
            'version' => '1', 'status' => 'active', 'output_quantity' => 1,
        ]);
        $fabricBom->items()->create(['product_id' => $fabric->id, 'quantity' => 2, 'wastage_percent' => 0]);
        $accessoryBom = BillOfMaterial::query()->create([
            'company_id' => $company->id, 'style_id' => $style->id, 'bom_type' => 'accessory',
            'version' => '1', 'status' => 'active', 'output_quantity' => 1,
        ]);
        $accessoryBom->items()->create(['product_id' => $accessory->id, 'quantity' => 1, 'wastage_percent' => 0]);

        $order = ProductionOrder::query()->create([
            'company_id' => $company->id, 'number' => 'MO-001', 'po_date' => today(),
            'bill_of_material_id' => $fabricBom->id, 'output_product_id' => $finished->id,
            'bom_version' => '1', 'planned_quantity' => 10, 'status' => 'released', 'created_by' => $user->id,
        ]);
        $order->materials()->create([
            'bill_of_material_id' => $fabricBom->id, 'product_id' => $fabric->id,
            'quantity_per_output' => 2, 'wastage_percent' => 0, 'required_quantity' => 20,
        ]);

        $requirements = app(MaterialOrderRequirements::class)->resolve($order, $company->id, $accessoryBom->id);
        $this->assertSame([$accessory->id], array_column($requirements['items'], 'product_id'));
        $this->assertSame(10.0, $requirements['items'][0]['quantity']);
        $this->assertDatabaseCount('production_order_materials', 1);

        app(MaterialOrderRequirements::class)->resolve($order, $company->id, $accessoryBom->id, true);
        $this->assertDatabaseHas('production_order_materials', [
            'production_order_id' => $order->id, 'bill_of_material_id' => $accessoryBom->id,
            'product_id' => $accessory->id, 'required_quantity' => '10.0000',
        ]);
        $request = MaterialRequest::query()->create([
            'number' => 'MR-001', 'requested_by' => $user->id, 'reason' => 'Accessory request',
            'production_order_id' => $order->id, 'bill_of_material_id' => $accessoryBom->id,
            'output_product_id' => $finished->id, 'bom_version' => $accessoryBom->version,
            'production_quantity' => 10, 'status' => 'pending',
        ]);
        $request->items()->create(['product_id' => $accessory->id, 'quantity' => 10]);

        $remaining = app(MaterialOrderRequirements::class)->resolve($order, $company->id, $accessoryBom->id);
        $this->assertSame([], $remaining['items']);
        $this->assertDatabaseHas('production_order_materials', [
            'production_order_id' => $order->id, 'bill_of_material_id' => $fabricBom->id,
            'product_id' => $fabric->id, 'required_quantity' => '20.0000',
        ]);
    }
}
