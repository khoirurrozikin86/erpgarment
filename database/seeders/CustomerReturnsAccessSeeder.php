<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CustomerReturnsAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(['view', 'create'])->map(function (string $action) {
            $permission = Permission::firstOrCreate([
                'name' => "customer-returns.{$action}",
                'guard_name' => 'web',
            ]);
            if (\Illuminate\Support\Facades\Schema::hasColumn($permission->getTable(), 'group_name') && $permission->group_name !== 'customer-returns') {
                $permission->group_name = 'customer-returns';
                $permission->save();
            }

            return $permission;
        });

        Role::query()->where('guard_name', 'web')->whereIn('name', ['admin', 'super_admin'])
            ->get()->each(fn(Role $role) => $role->givePermissionTo($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
