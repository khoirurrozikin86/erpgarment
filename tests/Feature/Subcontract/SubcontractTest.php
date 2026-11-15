<?php

namespace Tests\Feature\Subcontract;

use App\Domain\Companies\Services\CompanyContext;
use App\Models\{AuditLog, Company, DocumentNumbering, SubcontractContract, Subcontractor, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SubcontractTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::query()->create(['code' => 'SUB', 'name' => 'Subcontract Co', 'is_active' => true]);
        $this->user = User::factory()->create();
        $permissions = collect(['view', 'create', 'update', 'delete'])->flatMap(fn (string $ability) => [
            Permission::firstOrCreate(['name' => "subcontractors.{$ability}", 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => "subcontract-contracts.{$ability}", 'guard_name' => 'web']),
        ]);
        $this->user->givePermissionTo($permissions);
        $this->actingAs($this->user);
        app(CompanyContext::class)->set($this->company);
    }

    public function test_subcontractor_and_contract_crud_are_company_scoped_and_numbered(): void
    {
        $audit = Mockery::mock(\App\Domain\Audit\Services\AuditLogService::class);
        $audit->shouldReceive('log')->atLeast()->once()->andReturn(new AuditLog());
        $this->instance(\App\Domain\Audit\Services\AuditLogService::class, $audit);

        DocumentNumbering::query()->create([
            'company_id' => $this->company->id,
            'document_type' => 'subcontract_contract',
            'prefix' => 'TIMW',
            'format' => '{PREFIX}/{NUMBER}/KTRKS/{MM}/{YY}',
            'next_number' => 3,
            'number_length' => 1,
            'reset_period' => 'never',
            'is_active' => true,
        ]);

        $this->get(route('super.subcontract.index'))->assertOk()->assertSee('subcontractorsTable')->assertSee('subcontractContractsTable');

        $this->getJson(route('super.subcontract.contracts.number-preview'))
            ->assertOk()
            ->assertJsonPath('number', 'TIMW/3/KTRKS/'.now()->format('m/y'));

        $this->postJson(route('super.subcontract.subcontractors.store'), ['name' => 'Mitra Jahit', 'is_active' => true])
            ->assertOk();
        $subcontractor = Subcontractor::query()->where('company_id', $this->company->id)->firstOrFail();

        $this->putJson(route('super.subcontract.subcontractors.update', $subcontractor), ['name' => 'Mitra Jahit Baru', 'is_active' => true])
            ->assertOk();

        $payload = [
            'subcontractor_id' => $subcontractor->id,
            'bc_decree_number' => 'SK-BC-01',
            'description' => 'Kontrak jahit',
            'starts_on' => '2026-11-14',
            'ends_on' => '2026-12-31',
            'quota' => '1500',
        ];
        $response = $this->postJson(route('super.subcontract.contracts.store'), $payload)->assertOk();
        $contract = SubcontractContract::query()->firstOrFail();
        $this->assertSame('TIMW/3/KTRKS/'.now()->format('m/y'), $contract->contract_number);
        $response->assertJsonPath('number', $contract->contract_number);

        $this->getJson(route('super.subcontract.contracts.dt', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonFragment(['subcontractor_name' => 'Mitra Jahit Baru']);

        $this->putJson(route('super.subcontract.contracts.update', $contract), array_merge($payload, ['quota' => '1800']))->assertOk();
        $this->deleteJson(route('super.subcontract.contracts.destroy', $contract))->assertOk();
        $this->assertSoftDeleted('subcontract_contracts', ['id' => $contract->id]);

        $this->deleteJson(route('super.subcontract.subcontractors.destroy', $subcontractor))
            ->assertStatus(422);
        $this->assertDatabaseHas('subcontractors', ['id' => $subcontractor->id]);
    }

    public function test_contract_cannot_reference_another_company_subcontractor(): void
    {
        $otherCompany = Company::query()->create(['code' => 'OTH', 'name' => 'Other Company', 'is_active' => true]);
        $otherSubcontractor = Subcontractor::query()->create(['company_id' => $otherCompany->id, 'name' => 'Other Partner', 'is_active' => true]);

        $this->postJson(route('super.subcontract.contracts.store'), [
            'subcontractor_id' => $otherSubcontractor->id,
            'starts_on' => '2026-11-14',
            'ends_on' => '2026-12-31',
        ])->assertUnprocessable()->assertJsonValidationErrors('subcontractor_id');
    }
}
