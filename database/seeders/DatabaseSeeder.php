<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UsersAndPermissionsSeeder::class,
            CompanySeeder::class,
            CategorySeeder::class,
            UnitSeeder::class,
            SupplierSeeder::class,
            DocumentNumberingSeeder::class,
            ChartOfAccountsSeeder::class,
            CashBankSeeder::class,
        ]);
    }
}
