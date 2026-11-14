<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) return;
        $ids = [];
        foreach (['view', 'create', 'update', 'delete'] as $action) {
            $name = "goods-sent-notes.$action";
            $permission = DB::table('permissions')->where(['name' => $name, 'guard_name' => 'web'])->first();
            $ids[$action] = $permission?->id ?? DB::table('permissions')->insertGetId(array_filter([
                'name' => $name, 'guard_name' => 'web', 'group_name' => Schema::hasColumn('permissions', 'group_name') ? 'goods-sent-notes' : null,
                'created_at' => now(), 'updated_at' => now(),
            ], fn ($value) => $value !== null));
        }
        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['admin', 'super_admin'])->get() as $role) {
            foreach ($ids as $id) DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $role->id]);
        }
        if ($role = DB::table('roles')->where(['guard_name' => 'web', 'name' => 'user'])->first()) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $ids['view'], 'role_id' => $role->id]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) return;
        $ids = DB::table('permissions')->where('guard_name', 'web')->where('name', 'like', 'goods-sent-notes.%')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
