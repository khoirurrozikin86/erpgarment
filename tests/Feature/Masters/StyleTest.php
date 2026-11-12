<?php

namespace Tests\Feature\Masters;

use App\Models\Product;
use App\Models\Style;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_style_permissions_can_manage_styles_from_ppic_menu(): void
    {
        $user = User::factory()->create();
        $permissions = collect(['view', 'create', 'update', 'delete'])
            ->map(fn (string $action) => Permission::firstOrCreate(['name' => "styles.{$action}", 'guard_name' => 'web']));
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        $html = $this->get(route('super.styles.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Kode Style', $html);
        $this->assertStringContainsString('Nama Style', $html);
        $this->assertStringContainsString('id="style-modal"', $html);
        $this->assertStringContainsString('id="btn-add-style"', $html);
        $this->assertStringContainsString('href="'.route('super.styles.index').'"', $html);
        $this->assertStringContainsString(__('ui.nav.ppic'), $html);
        $this->assertStringNotContainsString('style-description', $html);

        $this->postJson(route('super.styles.store'), ['code' => '13275N013C', 'name' => '13275N013C'])
            ->assertCreated();
        $style = Style::query()->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['module' => 'STYLE', 'action' => 'CREATE']);

        $this->putJson(route('super.styles.update', $style), ['code' => '13275N013C', 'name' => 'Nama Diperbarui'])
            ->assertOk();
        $this->assertDatabaseHas('styles', ['id' => $style->id, 'name' => 'Nama Diperbarui']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'STYLE', 'action' => 'UPDATE']);

        Product::query()->create(['code' => 'ITEM-STYLE', 'name' => 'Item style', 'style_code' => $style->code]);
        $this->putJson(route('super.styles.update', $style), ['code' => 'NEW-CODE', 'name' => 'Nama Diperbarui'])
            ->assertUnprocessable();
        $this->deleteJson(route('super.styles.destroy', $style))->assertUnprocessable();

        Product::query()->where('code', 'ITEM-STYLE')->delete();
        $this->deleteJson(route('super.styles.destroy', $style))->assertOk();
        $this->assertDatabaseMissing('styles', ['id' => $style->id]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'STYLE', 'action' => 'DELETE']);
    }

    public function test_style_routes_require_permissions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('super.styles.index'))->assertForbidden();
    }
}
