<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // $this->call(UsersAndPermissionsSeeder::class);

        // $this->call([LandingSeed::class]);


        $this->call([

            UsersAndPermissionsSeeder::class,
            PosVoidAccessSeeder::class,
            CustomerReturnsAccessSeeder::class,
            // UserSeeder::class,
            CompanySeeder::class,
            CategorySeeder::class,
            UnitSeeder::class,
            SupplierSeeder::class,
            DocumentNumberingSeeder::class,
            ChartOfAccountsSeeder::class,
            CashBankSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
            PriceListSeeder::class,

        ]);
    }
}
