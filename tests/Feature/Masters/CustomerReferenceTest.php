<?php

namespace Tests\Feature\Masters;

use App\Exports\CustomersExport;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CustomerReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_crud_and_list_match_reference_fields(): void
    {
        $user = User::factory()->create();
        $permissions = collect(['view', 'create', 'update', 'delete'])
            ->map(fn (string $action) => Permission::create(['name' => "customers.{$action}", 'guard_name' => 'web']));
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        $html = $this->get(route('super.customers.index'))->assertOk()->getContent();
        foreach (['Kontak Person', 'Fax', 'Negara', 'value="Indonesia">Indonesia</option>'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertGreaterThanOrEqual(250, substr_count($html, '<option value='));
        foreach (['customer_type', 'payment_term', 'credit_limit', 'website', 'tax_number'] as $removedField) {
            $this->assertStringNotContainsString($removedField, $html);
        }
        $this->assertSame(['Kode Customer', 'Nama Customer', 'Alamat', 'Contact Person', 'Telepon', 'Fax', 'Email', 'Negara'], (new CustomersExport())->headings());

        $this->postJson(route('super.customers.store'), [
            'code' => 'CUS-TEST', 'name' => 'Customer Acuan', 'address' => 'Jakarta',
            'contact_person' => 'Lanny', 'phone' => '0812345', 'fax' => '0211234',
            'email' => 'lanny@example.com', 'country' => 'Indonesia', 'is_active' => '1',
        ])->assertCreated();

        $customer = Customer::query()->firstOrFail();
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'fax' => '0211234', 'country' => 'Indonesia', 'is_active' => true]);

        $this->putJson(route('super.customers.update', $customer), [
            'code' => 'CUS-TEST', 'name' => 'Customer Diperbarui', 'address' => 'Bandung',
            'contact_person' => 'Lanny', 'phone' => '0812345', 'fax' => null,
            'email' => 'lanny@example.com', 'country' => 'Indonesia', 'is_active' => '0',
        ])->assertOk();
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Customer Diperbarui', 'is_active' => false]);

        $this->deleteJson(route('super.customers.destroy', $customer))->assertOk();
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }
}
