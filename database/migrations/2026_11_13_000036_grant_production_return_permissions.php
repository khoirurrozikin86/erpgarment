<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles') || !Schema::hasTable('role_has_permissions')) return;
        $map = [];
        foreach (['return-to-warehouse' => ['view','create','update','delete'], 'warehouse-production-receipts' => ['view','approve','cancel']] as $group => $actions) {
            foreach ($actions as $action) {
                $name = "$group.$action";
                $row = DB::table('permissions')->where(['name' => $name, 'guard_name' => 'web'])->first();
                if (!$row) {
                    $attributes = ['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()];
                    if (Schema::hasColumn('permissions', 'group_name')) $attributes['group_name'] = $group;
                    $id = DB::table('permissions')->insertGetId($attributes);
                } else $id = $row->id;
                $map[$group][$action] = $id;
            }
        }
        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['admin','super_admin'])->get() as $role) {
            foreach ($map as $permissions) foreach ($permissions as $id) DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $role->id]);
        }
        if ($role = DB::table('roles')->where(['guard_name' => 'web', 'name' => 'user'])->first()) {
            foreach (['return-to-warehouse.view','warehouse-production-receipts.view'] as $key) {
                [$group,$action] = explode('.', $key); DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $map[$group][$action], 'role_id' => $role->id]);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void
    {
        $ids = DB::table('permissions')->where('guard_name','web')->where(function($q){$q->where('name','like','return-to-warehouse.%')->orWhere('name','like','warehouse-production-receipts.%');})->pluck('id');
        if ($ids->isNotEmpty()) { DB::table('role_has_permissions')->whereIn('permission_id',$ids)->delete(); DB::table('model_has_permissions')->whereIn('permission_id',$ids)->delete(); DB::table('permissions')->whereIn('id',$ids)->delete(); }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
