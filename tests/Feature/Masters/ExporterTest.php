<?php

namespace Tests\Feature\Masters;

use App\Models\Exporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_permissions_can_manage_exporters_and_changes_are_audited(): void
    {
        $user = User::factory()->create();
        $permissions = collect(['view', 'create', 'update', 'delete'])
            ->map(fn (string $action) => Permission::firstOrCreate(['name' => "exporters.{$action}", 'guard_name' => 'web']));
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        $this->get(route('super.exporters.index'))
            ->assertOk()
            ->assertSee('id="exporter-modal"', false)
            ->assertSee('exporters-table', false)
            ->assertDontSee('APIP');

        $this->postJson(route('super.exporters.store'), [
            'tax_number' => '01.234.567.8-999.001',
            'name' => 'PT Exportir Contoh',
            'address' => 'Jakarta',
            'is_active' => '1',
        ])->assertCreated();

        $exporter = Exporter::query()->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['module' => 'EXPORTER', 'action' => 'CREATE']);

        $this->putJson(route('super.exporters.update', $exporter), [
            'tax_number' => '01.234.567.8-999.001',
            'name' => 'PT Exportir Diperbarui',
            'address' => 'Bandung',
            'is_active' => '0',
        ])->assertOk();
        $this->assertDatabaseHas('exporters', ['id' => $exporter->id, 'name' => 'PT Exportir Diperbarui', 'is_active' => false]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'EXPORTER', 'action' => 'UPDATE']);

        $this->deleteJson(route('super.exporters.destroy', $exporter))->assertOk();
        $this->assertDatabaseMissing('exporters', ['id' => $exporter->id]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'EXPORTER', 'action' => 'DELETE']);
    }

    public function test_exporter_permissions_are_granted_to_default_roles_by_migration(): void
    {
        foreach (['user', 'admin', 'super_admin'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }

        $migration = require database_path('migrations/2026_11_13_000004_grant_exporter_permissions.php');
        $migration->up();

        $viewPermission = Permission::query()->where('name', 'exporters.view')->firstOrFail();
        $this->assertDatabaseHas('role_has_permissions', [
            'permission_id' => $viewPermission->id,
            'role_id' => Role::query()->where('name', 'user')->value('id'),
        ]);
        $this->assertSame(4, DB::table('role_has_permissions')
            ->where('role_id', Role::query()->where('name', 'admin')->value('id'))
            ->whereIn('permission_id', Permission::query()->where('name', 'like', 'exporters.%')->pluck('id'))
            ->count());
    }

    public function test_exporter_name_is_required_and_routes_require_permissions(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'exporters.create', 'guard_name' => 'web']));
        $this->actingAs($user);

        $this->postJson(route('super.exporters.store'), ['is_active' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
        $this->get(route('super.exporters.index'))->assertForbidden();
    }
}
