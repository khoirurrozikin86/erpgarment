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

        $permissions = [];
        foreach (['view', 'create', 'update', 'delete'] as $action) {
            $name = "exporters.{$action}";
            $permission = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->first();

            if (! $permission) {
                $attributes = [
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('permissions', 'group_name')) {
                    $attributes['group_name'] = 'exporters';
                }
                $id = DB::table('permissions')->insertGetId($attributes);
            } else {
                $id = $permission->id;
                if (Schema::hasColumn('permissions', 'group_name')) {
                    DB::table('permissions')->where('id', $id)->update(['group_name' => 'exporters', 'updated_at' => now()]);
                }
            }

            $permissions[$action] = $id;
        }

        $roles = DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['user', 'admin', 'super_admin'])->get();
        foreach ($roles as $role) {
            $actions = $role->name === 'user' ? ['view'] : ['view', 'create', 'update', 'delete'];
            foreach ($actions as $action) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissions[$action],
                    'role_id' => $role->id,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', ['exporters.view', 'exporters.create', 'exporters.update', 'exporters.delete'])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
