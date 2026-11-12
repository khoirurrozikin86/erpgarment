<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['code' => 'SUP-001', 'name' => 'PT Sumber Makmur', 'contact_person' => 'Andi', 'phone' => '081234567890', 'email' => 'sales@sumbermakmur.co.id', 'address' => 'Jl. Industri No. 10'],
            ['code' => 'SUP-002', 'name' => 'CV Maju Jaya', 'contact_person' => 'Budi', 'phone' => '081298765432', 'email' => 'info@majujaya.co.id', 'address' => 'Jl. Raya Industri No. 25'],
            ['code' => 'SUP-003', 'name' => 'UD Berkah Abadi', 'contact_person' => 'Slamet', 'phone' => '082112345678', 'email' => 'berkahabadi@gmail.com', 'address' => 'Jl. Diponegoro No. 15'],
            ['code' => 'SUP-004', 'name' => 'PT Prima Logistik', 'contact_person' => 'Rudi', 'phone' => '083812345678', 'email' => 'sales@primalogistik.co.id', 'address' => 'Kawasan Industri Candi'],
            ['code' => 'SUP-005', 'name' => 'CV Sentosa Mandiri', 'contact_person' => 'Dedi', 'phone' => '085712345678', 'email' => 'sentosamandiri@gmail.com', 'address' => 'Jl. Soekarno Hatta No. 50'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(['code' => $supplier['code']], $supplier + [
                'fax' => null,
                'country' => 'Indonesia',
                'is_active' => true,
            ]);
        }
    }
}
