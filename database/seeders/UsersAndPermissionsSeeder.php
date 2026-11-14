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
            'production-orders' => ['view', 'create', 'release', 'update', 'delete'],
            'purchase-requests' => ['view', 'create', 'approve'],
            'purchase-orders' => ['view', 'create', 'issue', 'email'],
            'goods-receipts' => ['view', 'create', 'approve', 'delete'],
            'purchase-returns' => ['view', 'create', 'delete'],
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
            'damaged-items' => ['view', 'create', 'update', 'delete'],
            'lost-items' => ['view', 'create', 'update', 'delete'],
            'raw-material-orders' => ['view', 'create', 'update', 'delete'],
            'material-issues' => ['view', 'create', 'update', 'delete'],
            'production-receipts' => ['view', 'approve'],
            'non-production-material-usages' => ['view', 'create', 'update', 'delete'],
            'wip-issues' => ['view', 'create', 'update', 'delete'],
            'scrap-receipts' => ['view', 'create', 'update', 'delete'],
            'return-to-warehouse' => ['view', 'create', 'update', 'delete'],
            'warehouse-production-receipts' => ['view', 'approve', 'cancel'],
            'support-material-orders' => ['view', 'create', 'update', 'delete'],
            'cutting' => ['view', 'create', 'update', 'delete'],
            'sewing' => ['view', 'create', 'update', 'delete'],
            'packing' => ['view', 'create', 'update', 'delete'],
            'finished-goods' => ['view', 'create', 'update', 'delete'],
            'goods-sent-notes' => ['view', 'create', 'update', 'delete'],
            'goods-sent-approvals' => ['view', 'approve', 'cancel'],





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
            'damaged-items.view',
            'lost-items.view',
            'raw-material-orders.view',
            'material-issues.view',
            'production-receipts.view',
            'non-production-material-usages.view',
            'wip-issues.view',
            'scrap-receipts.view',
            'return-to-warehouse.view',
            'warehouse-production-receipts.view',
            'support-material-orders.view',
            'cutting.view',
            'sewing.view',
            'packing.view',
            'finished-goods.view',
            'goods-sent-notes.view',
            'goods-sent-approvals.view',
            'material-requests.view',
            'material-requests.create',
            'production-orders.view',
            'production-orders.create',
            'production-orders.update',
            'production-orders.delete',

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
            'production-orders.delete',
            'purchase-requests.view',
            'purchase-requests.create',
            'purchase-requests.approve',
            'purchase-orders.view',
            'purchase-orders.create',
            'purchase-orders.issue',
            'purchase-orders.email',
            'goods-receipts.view',
            'goods-receipts.create',
            'goods-receipts.approve',
            'goods-receipts.delete',
            'purchase-returns.view',
            'purchase-returns.create',
            'purchase-returns.delete',
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
            'damaged-items.view',
            'damaged-items.create',
            'damaged-items.update',
            'damaged-items.delete',
            'lost-items.view',
            'lost-items.create',
            'lost-items.update',
            'lost-items.delete',
            'raw-material-orders.view',
            'raw-material-orders.create',
            'raw-material-orders.update',
            'raw-material-orders.delete',
            'material-issues.view',
            'material-issues.create',
            'material-issues.update',
            'material-issues.delete',
            'production-receipts.view',
            'production-receipts.approve',
            'non-production-material-usages.view',
            'non-production-material-usages.create',
            'non-production-material-usages.update',
            'non-production-material-usages.delete',
            'wip-issues.view',
            'wip-issues.create',
            'wip-issues.update',
            'wip-issues.delete',
            'scrap-receipts.view',
            'scrap-receipts.create',
            'scrap-receipts.update',
            'scrap-receipts.delete',
            'return-to-warehouse.view',
            'return-to-warehouse.create',
            'return-to-warehouse.update',
            'return-to-warehouse.delete',
            'warehouse-production-receipts.view',
            'warehouse-production-receipts.approve',
            'warehouse-production-receipts.cancel',
            'support-material-orders.view',
            'support-material-orders.create',
            'support-material-orders.update',
            'support-material-orders.delete',
            'cutting.view',
            'cutting.create',
            'cutting.update',
            'cutting.delete',
            'sewing.view',
            'sewing.create',
            'sewing.update',
            'sewing.delete',
            'packing.view',
            'packing.create',
            'packing.update',
            'packing.delete',
            'finished-goods.view',
            'finished-goods.create',
            'finished-goods.update',
            'finished-goods.delete',
            'goods-sent-notes.view',
            'goods-sent-notes.create',
            'goods-sent-notes.update',
            'goods-sent-notes.delete',
            'goods-sent-approvals.view',
            'goods-sent-approvals.approve',
            'goods-sent-approvals.cancel',







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
