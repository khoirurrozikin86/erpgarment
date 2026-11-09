<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Spatie\Permission\Models\{Role, Permission};
use Spatie\Permission\PermissionRegistrar;

class UsersAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // === 1️⃣ ROLES ===
        $roles = collect(['user', 'admin', 'super_admin'])
            ->mapWithKeys(fn($r) => [$r => Role::firstOrCreate(['name' => $r, 'guard_name' => $guard])]);

        // === 2️⃣ PERMISSIONS ===
        $modules = [
            'dashboard'  => ['view'],
            'user'       => ['menu', 'create', 'read', 'update', 'delete'],
            'role'       => ['menu', 'create', 'read', 'update', 'delete'],
            'permission' => ['menu', 'create', 'read', 'update', 'delete'],
            'audit-logs' => ['view', 'create', 'update', 'delete'],
            'company' => ['view', 'create', 'update', 'delete'],
            'general-settings' => ['view', 'update'],
            'email-settings' => ['view', 'update'],
            'document-numbering' => ['view', 'create', 'update', 'delete'],
            'categories' => ['view', 'create', 'update', 'delete'],
            'units' => ['view', 'create', 'update', 'delete'],
            'suppliers' => ['view', 'create', 'update', 'delete'],
            'customers' => ['view', 'create', 'update', 'delete'],
            'products' => ['view', 'create', 'update', 'delete'],
            'pricelists' => ['view', 'create', 'update', 'delete'],
            'material-requests' => ['view', 'create', 'approve'],
            'purchase-requests' => ['view', 'create', 'approve'],
            'purchase-orders' => ['view', 'create', 'issue', 'email'],
            'goods-receipts' => ['view', 'create'],
            'purchase-returns' => ['view', 'create'],
            'sales-quotations' => ['view', 'create', 'issue', 'review', 'email'],
            'sales-orders' => ['view', 'create', 'confirm'],
            'deliveries' => ['view', 'create', 'post'],
            'sales-invoices' => ['view', 'create', 'issue', 'email'],
            'customer-returns' => ['view', 'create'],
            'account-receivable' => ['view', 'receive'],
            'account-payable' => ['view', 'pay'],
            'pos-sessions' => ['view', 'open', 'close'],
            'pos' => ['view', 'sell', 'void'],
            'cash-bank' => ['view', 'create', 'update', 'delete'],
            'chart-of-accounts' => ['view', 'create', 'update'],
            'journal' => ['view', 'create', 'post', 'delete'],
            'profit-loss' => ['view'],
            'sales-report' => ['view'],
            'purchase-report' => ['view'],
            'inventory-report' => ['view'],
            'cash-bank-report' => ['view'],
            'stocks' => ['view'],
            'stock-card' => ['view'],
            'stock-opnames' => ['view', 'create', 'count', 'post'],





        ];

        foreach ($modules as $group => $actions) {
            foreach ($actions as $action) {
                $name = "{$group}.{$action}";
                $perm = Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
                if (Schema::hasColumn($perm->getTable(), 'group_name') && $perm->group_name !== $group) {
                    $perm->group_name = $group;
                    $perm->save();
                }
            }
        }

        Permission::whereIn('name', [
            'outlets.view',
            'outlets.create',
            'outlets.update',
            'outlets.delete',
            'ticket-qrcode.view',
            'ticket-qrcode.create',
            'ticket-qrcode.update',
            'ticket-qrcode.delete',
            'user-outlets.view',
            'user-outlets.create',
            'user-outlets.update',
            'user-outlets.delete',
            'scan-records.view',
            'scan-records.create',
            'scan-records.update',
            'scan-records.delete',
        ])->delete();

        // === 3️⃣ ROLE → PERMISSION MAPPING ===

        // 👤 USER: hanya bisa lihat (read/view)
        $roles['user']->syncPermissions([
            'dashboard.view',
            'audit-logs.view',
            'company.view',
            'company.create',
            'company.update',
            'company.delete',
            'general-settings.view',
            'email-settings.view',
            'document-numbering.view',
            'categories.view',
            'units.view',
            'suppliers.view',
            'customers.view',
            'products.view',
            'pricelists.view',
            'material-requests.view',
            'material-requests.create',

        ]);

        // 👨‍💼 ADMIN: CRUD penuh semua modul utama
        $roles['admin']->syncPermissions([
            'dashboard.view',

            // 'user.menu',
            // 'user.create',
            // 'user.read',
            // 'user.update',
            // 'user.delete',
            // 'role.menu',
            // 'role.create',
            // 'role.read',
            // 'role.update',
            // 'role.delete',




            'audit-logs.view',

            'company.view',
            'company.create',
            'company.update',
            'company.delete',

            'general-settings.view',
            'general-settings.update',

            'email-settings.view',
            'email-settings.update',


            'document-numbering.view',
            'document-numbering.create',
            'document-numbering.update',
            'document-numbering.delete',

            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',

            'units.view',
            'units.create',
            'units.update',
            'units.delete',

            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',

            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',

            'products.view',
            'products.create',
            'products.update',
            'products.delete',

            'pricelists.view',
            'pricelists.create',
            'pricelists.update',
            'pricelists.delete',

            'material-requests.view',
            'material-requests.create',
            'material-requests.approve',
            'purchase-requests.view',
            'purchase-requests.create',
            'purchase-requests.approve',
            'purchase-orders.view',
            'purchase-orders.create',
            'purchase-orders.issue',
            'purchase-orders.email',
            'goods-receipts.view',
            'goods-receipts.create',
            'purchase-returns.view',
            'purchase-returns.create',
            'sales-quotations.view',
            'sales-quotations.create',
            'sales-quotations.issue',
            'sales-quotations.review',
            'sales-quotations.email',
            'sales-orders.view',
            'sales-orders.create',
            'sales-orders.confirm',
            'deliveries.view',
            'deliveries.create',
            'deliveries.post',
            'sales-invoices.view',
            'sales-invoices.create',
            'sales-invoices.issue',
            'sales-invoices.email',
            'customer-returns.view',
            'customer-returns.create',
            'account-receivable.view',
            'account-receivable.receive',
            'account-payable.view',
            'account-payable.pay',
            'pos-sessions.view',
            'pos-sessions.open',
            'pos-sessions.close',
            'pos.view',
            'pos.sell',
            'pos.void',
            'cash-bank.view',
            'cash-bank.create',
            'cash-bank.update',
            'cash-bank.delete',
            'chart-of-accounts.view',
            'chart-of-accounts.create',
            'chart-of-accounts.update',
            'journal.view',
            'journal.create',
            'journal.post',
            'journal.delete',
            'stocks.view',
            'stock-card.view',
            'stock-opnames.view',
            'stock-opnames.create',
            'stock-opnames.count',
            'stock-opnames.post',







        ]);

        // 👑 SUPER ADMIN: semua permission
        $roles['super_admin']->syncPermissions(Permission::pluck('name')->all());

        // === 4️⃣ USER DEFAULT & ADMIN & SUPER ===
        $super = User::updateOrCreate(
            ['email' => 'super@example.com'],
            ['name' => 'Super Admin Stylus', 'password' => Hash::make('password')]
        );
        $super->syncRoles(['super_admin']);

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin Stylus', 'password' => Hash::make('password')]
        );
        $admin->syncRoles(['admin']);

        // === 5️⃣ USER PER LOKASI ===
        // $lokasiUsers = [
        //     ['name' => 'admin',  'email' => 'admin@gmail.com',  'server_id' => 2],  // NAMAR
        //     ['name' => 'Heru',  'email' => 'heru@gmail.com',  'server_id' => 1],  // ASRI
        //     ['name' => 'Joyo',  'email' => 'joyo@gmail.com',  'server_id' => 4],  // BREYON
        //     ['name' => 'Risfa', 'email' => 'risfa@gmail.com', 'server_id' => 5],  // TLOGO
        //     ['name' => 'Heri',  'email' => 'heri@gmail.com',  'server_id' => 6],  // HERI
        //     ['name' => 'Dika',  'email' => 'dika@gmail.com',  'server_id' => 12], // PABELAN
        //     ['name' => 'Faris', 'email' => 'faris@gmail.com', 'server_id' => 13], // OZ
        // ];

        // foreach ($lokasiUsers as $u) {
        //     $user = User::updateOrCreate(
        //         ['email' => $u['email']],
        //         [
        //             'name' => $u['name'],
        //             'password' => Hash::make('password'),
        //             'server_id' => $u['server_id'],
        //         ]
        //     );
        //     $user->syncRoles(['user']);
        //     $this->command->info("✅ User {$u['name']} ({$u['email']}) dibuat untuk server_id {$u['server_id']}");
        // }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('🎉 Semua roles, permissions, dan users berhasil disetup!');
    }
}
