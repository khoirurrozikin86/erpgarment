<?php

namespace Tests\Feature\Masters;

use App\Exports\ProductsExport;
use App\Exports\ProductsTemplateExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\Style;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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
            Permission::firstOrCreate(['name' => 'products.view', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'products.create', 'guard_name' => 'web']),
        ]);

        $html = $this->actingAs($user)->get(route('super.products.index'))->assertOk()->getContent();

        foreach (['category_id', 'code', 'name', 'unit_id', 'style_code', 'color', 'size', 'is_active', 'is_available'] as $field) {
            $this->assertStringContainsString('id="'.$field.'"', $html);
        }

        foreach (['barcode', 'sku', 'short_name', 'hs_code', 'composition', 'sales_price', 'taxable', 'tax_rate', 'allow_discount', 'allow_purchase', 'allow_sales', 'description'] as $field) {
            $this->assertStringNotContainsString('for="'.$field.'"', $html);
            $this->assertStringNotContainsString('id="'.$field.'"', $html);
        }
        foreach (['purchase_price', 'min_stock', 'max_stock', 'reorder_point', 'track_stock'] as $field) {
            $this->assertStringContainsString('type="hidden" id="'.$field.'"', $html);
        }

        $this->assertStringNotContainsString('<th>'.__('ui.workflow.barcode').'</th>', $html);
        $this->assertStringNotContainsString('<th>'.__('ui.workflow.current_stock').'</th>', $html);
        $this->assertStringContainsString('<th>'.__('ui.workflow.style_code').'</th>', $html);
        $this->assertStringContainsString('id="style-search"', $html);
        $this->assertStringContainsString('<th>Available</th>', $html);
        $this->assertStringContainsString('modal-lg', $html);
        $this->assertStringNotContainsString('modal-xl', $html);
        $expectedHeadings = ['Kategori', 'Kode Barang', 'Nama Barang', 'Satuan', 'Kode Style', 'Warna', 'Ukuran', 'Available'];
        $this->assertSame($expectedHeadings, (new ProductsExport())->headings());
        $this->assertSame($expectedHeadings, (new ProductsTemplateExport())->headings());
    }

    public function test_unused_product_columns_are_removed_from_schema(): void
    {
        foreach (['barcode', 'sku', 'short_name', 'hs_code', 'composition', 'fabric_width_cm', 'fabric_weight_gsm', 'country_of_origin', 'sales_price', 'taxable', 'tax_rate', 'allow_discount', 'allow_purchase', 'allow_sales', 'description'] as $column) {
            $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('products', $column), $column.' should not remain on products');
        }

        foreach (['code', 'name', 'category_id', 'unit_id', 'style_code', 'color', 'size', 'purchase_price', 'track_stock', 'is_available'] as $column) {
            $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('products', $column), $column.' is required by inventory');
        }
    }

    public function test_product_import_uses_reference_template_columns_and_defaults_hidden_fields(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'products.create', 'guard_name' => 'web']));
        $this->actingAs($user);

        Category::query()->create(['code' => 'FAB', 'name' => 'Fabric', 'is_active' => true]);
        Unit::query()->create(['code' => 'MTR', 'name' => 'Meter', 'symbol' => 'm', 'is_active' => true]);
        Style::query()->create(['code' => 'ST-10', 'name' => 'Basic Shirt']);
        Style::query()->create(['code' => 'ST-11', 'name' => 'Jacket']);

        $csv = "Kategori,Kode Barang,Nama Barang,Satuan,Kode Style,Warna,Ukuran,Available\nFabric,ITM-CSV,Imported Fabric,Meter (m),ST-10,Navy,M,Available\nFabric,ITM-OFF,Inactive Fabric,Meter,ST-11,Black,L,Unavailable\n";
        $this->post(route('super.products.import'), [
            'file' => UploadedFile::fake()->createWithContent('items.csv', $csv),
        ])->assertRedirect(route('super.products.index'));

        $product = Product::query()->where('code', 'ITM-CSV')->firstOrFail();
        $this->assertSame('Imported Fabric', $product->name);
        $this->assertSame('ST-10', $product->style_code);
        $this->assertSame('Navy', $product->color);
        $this->assertSame('M', $product->size);
        $this->assertTrue($product->is_active);
        $this->assertSame('stock', $product->product_type);
        $this->assertSame('general', $product->garment_item_type);
        $this->assertTrue($product->is_available);
        $this->assertDatabaseHas('products', ['code' => 'ITM-OFF', 'is_available' => false]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'PRODUCT', 'action' => 'CREATE']);
    }


    public function test_style_search_returns_matches_from_style_master(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'products.view', 'guard_name' => 'web']));
        Style::query()->create(['code' => 'ST-SEARCH', 'name' => 'Searchable Shirt']);

        $this->actingAs($user)
            ->getJson(route('super.products.style-options', ['q' => 'Searchable']))
            ->assertOk()
            ->assertJsonPath('data.0.code', 'ST-SEARCH')
            ->assertJsonPath('data.0.name', 'Searchable Shirt');
    }

}
