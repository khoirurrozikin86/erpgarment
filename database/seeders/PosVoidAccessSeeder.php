<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PosVoidAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'pos.void',
            'guard_name' => 'web',
        ]);
        if (Schema::hasColumn($permission->getTable(), 'group_name') && $permission->group_name !== 'pos') {
            $permission->group_name = 'pos';
            $permission->save();
        }
        $closedSessionPermission = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'pos.void-closed-session')
            ->first();

        Role::query()->where('guard_name', 'web')->whereIn('name', ['admin', 'super_admin'])
            ->get()->each(function (Role $role) use ($permission, $closedSessionPermission) {
                $role->givePermissionTo($permission);
                if ($closedSessionPermission) {
                    $role->revokePermissionTo($closedSessionPermission);
                }
            });

        $closedSessionPermission?->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
