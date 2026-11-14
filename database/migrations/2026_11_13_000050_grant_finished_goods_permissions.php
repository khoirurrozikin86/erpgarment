<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) return;
        $permissionIds = [];
        foreach (['view', 'create', 'update', 'delete'] as $action) {
            $name = "finished-goods.$action";
            $permission = DB::table('permissions')->where(['name' => $name, 'guard_name' => 'web'])->first();
            if ($permission) $permissionIds[$action] = $permission->id;
            else {
                $row = ['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()];
                if (Schema::hasColumn('permissions', 'group_name')) $row['group_name'] = 'finished-goods';
                $permissionIds[$action] = DB::table('permissions')->insertGetId($row);
            }
        }
        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['admin', 'super_admin'])->get() as $role) {
            foreach ($permissionIds as $permissionId) DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $role->id]);
        }
        $userRole = DB::table('roles')->where(['guard_name' => 'web', 'name' => 'user'])->first();
        if ($userRole) DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionIds['view'], 'role_id' => $userRole->id]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('guard_name', 'web')->where('name', 'like', 'finished-goods.%')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
