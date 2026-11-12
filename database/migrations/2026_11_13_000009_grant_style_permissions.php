<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permissionIds = [];
        foreach (['view', 'create', 'update', 'delete'] as $action) {
            $name = "styles.{$action}";
            $permission = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->first();
            if ($permission) {
                $id = $permission->id;
                if (Schema::hasColumn('permissions', 'group_name')) {
                    DB::table('permissions')->where('id', $id)->update(['group_name' => 'styles', 'updated_at' => now()]);
                }
            } else {
                $attributes = ['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()];
                if (Schema::hasColumn('permissions', 'group_name')) {
                    $attributes['group_name'] = 'styles';
                }
                $id = DB::table('permissions')->insertGetId($attributes);
            }
            $permissionIds[$action] = $id;
        }

        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['user', 'admin', 'super_admin'])->get() as $role) {
            $actions = $role->name === 'user' ? ['view'] : ['view', 'create', 'update', 'delete'];
            foreach ($actions as $action) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionIds[$action],
                    'role_id' => $role->id,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', [
            'styles.view', 'styles.create', 'styles.update', 'styles.delete',
        ])->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
