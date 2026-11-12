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
        $adminEmail = config('seed.admin_email') ?: 'superadmin@localhost.test';
        $adminPassword = config('seed.admin_password');

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('SEED_ADMIN_EMAIL harus berupa alamat email yang valid.');
        }

        if ($adminPassword !== null && $adminPassword !== '' && (!is_string($adminPassword) || strlen($adminPassword) < 16)) {
            throw new \RuntimeException('SEED_ADMIN_PASSWORD harus berupa password unik minimal 16 karakter jika diisi.');
        }

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
            'styles' => ['view', 'create', 'update', 'delete'],
            'suppliers' => ['view', 'create', 'update', 'delete'],
            'importers' => ['view', 'create', 'update', 'delete'],
            'exporters' => ['view', 'create', 'update', 'delete'],
            'customers' => ['view', 'create', 'update', 'delete'],
            'products' => ['view', 'create', 'update', 'delete'],
            'boms' => ['view', 'create', 'update', 'delete'],
            'material-requests' => ['view', 'create', 'approve', 'issue'],
            'production-orders' => ['view', 'create', 'release', 'update'],
            'purchase-requests' => ['view', 'create', 'approve'],
            'purchase-orders' => ['view', 'create', 'issue', 'email'],
            'goods-receipts' => ['view', 'create'],
            'purchase-returns' => ['view', 'create'],
            'account-payable' => ['view', 'pay'],
            'cash-bank' => ['view', 'create', 'update', 'delete'],
            'chart-of-accounts' => ['view', 'create', 'update'],
            'journal' => ['view', 'create', 'post', 'delete'],
            'profit-loss' => ['view'],
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
            'styles.view',
            'suppliers.view',
            'importers.view',
            'exporters.view',
            'customers.view',
            'products.view',
            'boms.view',
            'material-requests.view',
            'material-requests.create',
            'production-orders.view',
            'production-orders.create',

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

            'styles.view',
            'styles.create',
            'styles.update',
            'styles.delete',

            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',

            'importers.view',
            'importers.create',
            'importers.update',
            'importers.delete',
            'exporters.view',
            'exporters.create',
            'exporters.update',
            'exporters.delete',

            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',

            'products.view',
            'products.create',
            'products.update',
            'products.delete',

            'boms.view',
            'boms.create',
            'boms.update',
            'boms.delete',

            'material-requests.view',
            'material-requests.create',
            'material-requests.approve',
            'material-requests.issue',
            'production-orders.view',
            'production-orders.create',
            'production-orders.release',
            'production-orders.update',
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
            'account-payable.view',
            'account-payable.pay',
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
        $super = User::firstOrNew(['email' => $adminEmail]);
        $generatedPassword = null;
        if (!$super->exists) {
            $generatedPassword = $adminPassword ?: bin2hex(random_bytes(32));
            $super->name = 'Super Admin';
            $super->password = Hash::make($generatedPassword);
            $super->save();
        }
        $super->syncRoles(['super_admin']);

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
        $this->command->info("Setup role dan permission selesai. Super admin: {$adminEmail}");
        if ($generatedPassword !== null && !$adminPassword) {
            $this->command->warn("Password awal super admin (simpan sekarang): {$generatedPassword}");
        }
    }
}
