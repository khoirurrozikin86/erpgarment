<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuditMutationRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_module_request_without_explicit_audit_is_logged_and_sensitive_inputs_are_redacted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/')
            ->post(route('locale.update'), [
                'locale' => 'id',
                'password' => 'do-not-store-this',
                'smtp_password' => 'also-secret',
            ])
            ->assertRedirect('/');

        $this->assertDatabaseCount('audit_logs', 1);
        $log = AuditLog::query()->firstOrFail();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('LOCALE', $log->module);
        $this->assertSame('UPDATE', $log->action);
        $this->assertSame('id', $log->new_values['locale']);
        $this->assertSame('[REDACTED]', $log->new_values['password']);
        $this->assertSame('[REDACTED]', $log->new_values['smtp_password']);
        $this->assertStringNotContainsString('do-not-store-this', json_encode($log->new_values));
        $this->assertStringNotContainsString('also-secret', json_encode($log->new_values));
    }

    public function test_audit_log_filters_include_master_modules_and_show_history_without_a_date_filter(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::firstOrCreate([
            'name' => 'audit-logs.view',
            'guard_name' => 'web',
        ]));

        foreach (['UNIT', 'CATEGORY', 'STYLE', 'SUPPLIER', 'CUSTOMER'] as $module) {
            AuditLog::query()->create([
                'user_id' => $user->id,
                'action' => 'CREATE',
                'module' => $module,
                'description' => "Create {$module}",
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ]);
        }

        $html = $this->actingAs($user)
            ->get(route('super.audit-logs.index'))
            ->assertOk()
            ->getContent();

        foreach (['UNIT', 'CATEGORY', 'STYLE', 'SUPPLIER', 'CUSTOMER'] as $module) {
            $this->assertStringContainsString('<option value="'.$module.'">'.$module.'</option>', $html);
        }
        $this->assertStringContainsString('id="date_from"', $html);
        $this->assertStringContainsString('id="date_from" value="'.now()->format('Y-m-d').'"', $html);
    }
}
