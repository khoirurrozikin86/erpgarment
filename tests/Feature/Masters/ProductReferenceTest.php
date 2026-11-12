<?php

namespace Tests\Feature\Masters;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_form_and_list_follow_the_inventory_reference_fields(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::create(['name' => 'products.view', 'guard_name' => 'web']),
            Permission::create(['name' => 'products.create', 'guard_name' => 'web']),
        ]);

        $html = $this->actingAs($user)->get(route('super.products.index'))->assertOk()->getContent();

        foreach (['category_id', 'code', 'name', 'unit_id', 'style_code', 'color', 'size', 'is_active'] as $field) {
            $this->assertStringContainsString('id="'.$field.'"', $html);
        }

        foreach (['barcode', 'sku', 'short_name', 'hs_code', 'composition', 'purchase_price', 'sales_price', 'min_stock', 'tax_rate', 'description'] as $field) {
            $this->assertStringNotContainsString('for="'.$field.'"', $html);
            $this->assertStringContainsString('type="hidden" id="'.$field.'"', $html);
        }

        $this->assertStringNotContainsString('<th>'.__('ui.workflow.barcode').'</th>', $html);
        $this->assertStringNotContainsString('<th>'.__('ui.workflow.current_stock').'</th>', $html);
        $this->assertStringContainsString('<th>'.__('ui.workflow.style_code').'</th>', $html);
    }
}
