<?php

namespace Tests\Feature\Audit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MasterCrudAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_reference_masters_writes_audit_rows(): void
    {
        $user = User::factory()->create();
        $names = [
            'categories.create', 'units.create', 'styles.create',
            'suppliers.create', 'customers.create', 'company.view',
            'suppliers.view', 'purchase-orders.view',
        ];
        $user->givePermissionTo(collect($names)->map(fn (string $name) => Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ])));
        $this->actingAs($user);

        $this->postJson(route('super.categories.store'), [
            'code' => 'CAT-AUDIT', 'name' => 'Audit Category', 'is_active' => true,
        ])->assertCreated();
        $this->postJson(route('super.units.store'), [
            'code' => 'UNT-AUDIT', 'name' => 'Audit Unit', 'symbol' => 'au', 'is_active' => true,
        ])->assertCreated();
        $this->postJson(route('super.styles.store'), [
            'code' => 'ST-AUDIT', 'name' => 'Audit Style',
        ])->assertCreated();
        $this->postJson(route('super.suppliers.store'), [
            'code' => 'SUP-AUDIT', 'name' => 'Audit Supplier', 'is_active' => true,
        ])->assertCreated();
        $this->postJson(route('super.customers.store'), [
            'code' => 'CUS-AUDIT', 'name' => 'Audit Customer', 'is_active' => true,
        ])->assertCreated();

        foreach (['CATEGORY', 'UNIT', 'STYLE', 'SUPPLIER', 'CUSTOMER'] as $module) {
            $this->assertDatabaseHas('audit_logs', [
                'user_id' => $user->id,
                'module' => $module,
                'action' => 'CREATE',
            ]);
        }
        $this->assertDatabaseCount('audit_logs', 5);
    }
}
