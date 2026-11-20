<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('material-requests.delete', 'web');
        if (Schema::hasColumn($permission->getTable(), 'group_name')) {
            $permission->group_name = 'material-requests';
            $permission->save();
        }

        foreach (Role::where('guard_name', 'web')->whereIn('name', ['admin', 'super_admin'])->get() as $role) {
            $role->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'material-requests.delete')->where('guard_name', 'web')->first()?->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
