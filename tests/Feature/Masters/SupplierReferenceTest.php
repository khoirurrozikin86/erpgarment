<?php

namespace Tests\Feature\Masters;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SupplierReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_crud_uses_only_reference_fields_and_preserves_active_flag(): void
    {
        $user = User::factory()->create();
        $permissions = collect(['view', 'create', 'update', 'delete'])
            ->map(fn (string $action) => Permission::create(['name' => "suppliers.{$action}", 'guard_name' => 'web']));
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        $html = $this->get(route('super.suppliers.index'))->assertOk()->getContent();
        foreach (['Kontak Person', 'Fax', 'Negara'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringContainsString('>Pilih</option>', $html);
        $this->assertStringContainsString('value="Indonesia">Indonesia</option>', $html);
        $this->assertGreaterThanOrEqual(250, substr_count($html, '<option value='));
        foreach (['supplier_type', 'payment_term', 'bank_name', 'tax_number', 'website'] as $removedField) {
            $this->assertStringNotContainsString($removedField, $html);
        }

        $this->postJson(route('super.suppliers.store'), [
            'code' => 'SUP-TEST', 'name' => 'Supplier Acuan', 'address' => 'Jakarta',
            'contact_person' => 'Sari', 'phone' => '0812345', 'fax' => '0211234',
            'email' => 'sari@example.com', 'country' => 'Indonesia', 'is_active' => '1',
        ])->assertCreated();

        $supplier = Supplier::query()->firstOrFail();
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'fax' => '0211234', 'country' => 'Indonesia', 'is_active' => true]);

        $this->putJson(route('super.suppliers.update', $supplier), [
            'code' => 'SUP-TEST', 'name' => 'Supplier Diperbarui', 'address' => 'Bandung',
            'contact_person' => 'Sari', 'phone' => '0812345', 'fax' => null,
            'email' => 'sari@example.com', 'country' => 'Indonesia', 'is_active' => '0',
        ])->assertOk();
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Supplier Diperbarui', 'is_active' => false]);

        $this->deleteJson(route('super.suppliers.destroy', $supplier))->assertOk();
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }
}
