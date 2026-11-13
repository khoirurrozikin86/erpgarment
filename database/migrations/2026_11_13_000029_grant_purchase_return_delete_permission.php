<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles') || !Schema::hasTable('role_has_permissions')) return;

        $name = 'purchase-returns.delete';
        $permission = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->first();
        if (!$permission) {
            $attributes = ['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()];
            if (Schema::hasColumn('permissions', 'group_name')) $attributes['group_name'] = 'purchase-returns';
            $permissionId = DB::table('permissions')->insertGetId($attributes);
        } else {
            $permissionId = $permission->id;
            if (Schema::hasColumn('permissions', 'group_name')) {
                DB::table('permissions')->where('id', $permissionId)->update(['group_name' => 'purchase-returns', 'updated_at' => now()]);
            }
        }

        $roleIds = DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['admin', 'super_admin'])->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'purchase-returns.delete')->where('guard_name', 'web')->value('id');
        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
