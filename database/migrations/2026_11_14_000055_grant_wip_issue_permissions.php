<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles') || !Schema::hasTable('role_has_permissions')) return;
        $ids = [];
        foreach (['view', 'create', 'update', 'delete'] as $action) {
            $name = "wip-issues.{$action}";
            $permission = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->first();
            if ($permission) {
                $ids[$action] = $permission->id;
                if (Schema::hasColumn('permissions', 'group_name')) DB::table('permissions')->where('id', $permission->id)->update(['group_name' => 'wip-issues', 'updated_at' => now()]);
            } else {
                $attrs = ['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()];
                if (Schema::hasColumn('permissions', 'group_name')) $attrs['group_name'] = 'wip-issues';
                $ids[$action] = DB::table('permissions')->insertGetId($attrs);
            }
        }
        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['admin', 'super_admin'])->get() as $role) {
            foreach ($ids as $id) DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $role->id]);
        }
        if ($role = DB::table('roles')->where('guard_name', 'web')->where('name', 'user')->first()) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $ids['view'], 'role_id' => $role->id]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) return;
        $ids = DB::table('permissions')->where('guard_name', 'web')->where('name', 'like', 'wip-issues.%')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        if (Schema::hasTable('model_has_permissions')) DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
