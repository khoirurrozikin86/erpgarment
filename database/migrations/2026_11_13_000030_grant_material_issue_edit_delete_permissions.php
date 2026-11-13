<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles') || !Schema::hasTable('role_has_permissions')) return;

        $permissionIds = [];
        foreach (['update', 'delete'] as $action) {
            $name = "material-issues.{$action}";
            $permission = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->first();
            if (!$permission) {
                $attributes = ['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()];
                if (Schema::hasColumn('permissions', 'group_name')) $attributes['group_name'] = 'material-issues';
                $permissionIds[$action] = DB::table('permissions')->insertGetId($attributes);
            } else {
                $permissionIds[$action] = $permission->id;
                if (Schema::hasColumn('permissions', 'group_name')) {
                    DB::table('permissions')->where('id', $permission->id)->update(['group_name' => 'material-issues', 'updated_at' => now()]);
                }
            }
        }

        $roles = DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['admin', 'super_admin'])->pluck('id');
        foreach ($roles as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('guard_name', 'web')
            ->whereIn('name', ['material-issues.update', 'material-issues.delete'])->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
