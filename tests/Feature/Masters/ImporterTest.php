<?php

namespace Tests\Feature\Masters;

use App\Models\Importer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_permissions_can_manage_importers_and_changes_are_audited(): void
    {
        $user = User::factory()->create();
        $permissions = collect(['view', 'create', 'update', 'delete'])
            ->map(fn (string $action) => Permission::create(['name' => "importers.{$action}", 'guard_name' => 'web']));
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        $index = $this->get(route('super.importers.index'))->assertOk();
        $html = $index->getContent();
        $this->assertStringContainsString('id="importer-modal"', $html);
        $this->assertGreaterThan(strpos($html, 'jquery.dataTables.js'), strpos($html, '$(document).ready(function ()'));
        $this->assertStringContainsString('href="'.route('super.importers.index').'"', $html);
        $this->assertStringContainsString('PENGATURAN', $html);
        $this->postJson(route('super.importers.store'), [
            'tax_number' => '01.234.567.8-999.000',
            'name' => 'PT Importir Contoh',
            'address' => 'Jakarta',
            'apip' => 'APIP-01',
            'is_active' => '1',
        ])->assertCreated();

        $importer = Importer::query()->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['module' => 'IMPORTER', 'action' => 'CREATE']);

        $this->putJson(route('super.importers.update', $importer), [
            'tax_number' => '01.234.567.8-999.000',
            'name' => 'PT Importir Diperbarui',
            'address' => 'Bandung',
            'apip' => null,
            'is_active' => '0',
        ])->assertOk();
        $this->assertDatabaseHas('importers', ['id' => $importer->id, 'name' => 'PT Importir Diperbarui', 'is_active' => false]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'IMPORTER', 'action' => 'UPDATE']);

        $this->deleteJson(route('super.importers.destroy', $importer))->assertOk();
        $this->assertDatabaseMissing('importers', ['id' => $importer->id]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'IMPORTER', 'action' => 'DELETE']);
    }

    public function test_importer_name_is_required_and_routes_require_permissions(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::create(['name' => 'importers.create', 'guard_name' => 'web']));
        $this->actingAs($user);

        $this->postJson(route('super.importers.store'), ['is_active' => true])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->get(route('super.importers.index'))->assertForbidden();
    }
}
