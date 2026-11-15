<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    private const PERMISSIONS = [
        'subcontract-semi-finished.view',
        'subcontract-semi-finished.create',
        'subcontract-semi-finished.update',
        'subcontract-semi-finished.delete',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $ids = [];
        foreach (self::PERMISSIONS as $name) {
            $permission = DB::table('permissions')->where(['name' => $name, 'guard_name' => 'web'])->first();
            if (! $permission) {
                $fields = ['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()];
                if (Schema::hasColumn('permissions', 'group_name')) {
                    $fields['group_name'] = 'subcontract-semi-finished';
                }
                $ids[$name] = DB::table('permissions')->insertGetId($fields);
            } else {
                $ids[$name] = $permission->id;
            }
        }

        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['admin', 'super_admin'])->get() as $role) {
            foreach ($ids as $id) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $role->id]);
            }
        }

        if ($role = DB::table('roles')->where(['guard_name' => 'web', 'name' => 'user'])->first()) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $ids['subcontract-semi-finished.view'],
                'role_id' => $role->id,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', self::PERMISSIONS)->pluck('id');
        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        }
        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        }
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
