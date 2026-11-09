<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'SUP-001',
                'name' => 'PT Sumber Makmur',
                'supplier_type' => 'company',
                'contact_person' => 'Andi',
                'phone' => '081234567890',
                'email' => 'sales@sumbermakmur.co.id',
                'website' => null,
                'tax_number' => null,
                'address' => 'Jl. Industri No. 10',
                'city' => 'Semarang',
                'province' => 'Jawa Tengah',
                'postal_code' => '50100',
                'payment_term' => '30 Days',
                'bank_name' => 'BCA',
                'bank_account_number' => '1234567890',
                'bank_account_name' => 'PT Sumber Makmur',
                'notes' => 'Supplier material utama.',
                'is_active' => true,
            ],

            [
                'code' => 'SUP-002',
                'name' => 'CV Maju Jaya',
                'supplier_type' => 'company',
                'contact_person' => 'Budi',
                'phone' => '081298765432',
                'email' => 'info@majujaya.co.id',
                'website' => null,
                'tax_number' => null,
                'address' => 'Jl. Raya Industri No. 25',
                'city' => 'Kendal',
                'province' => 'Jawa Tengah',
                'postal_code' => '51300',
                'payment_term' => '14 Days',
                'bank_name' => 'BRI',
                'bank_account_number' => '9876543210',
                'bank_account_name' => 'CV Maju Jaya',
                'notes' => 'Supplier umum.',
                'is_active' => true,
            ],

            [
                'code' => 'SUP-003',
                'name' => 'UD Berkah Abadi',
                'supplier_type' => 'company',
                'contact_person' => 'Slamet',
                'phone' => '082112345678',
                'email' => 'berkahabadi@gmail.com',
                'website' => null,
                'tax_number' => null,
                'address' => 'Jl. Diponegoro No. 15',
                'city' => 'Semarang',
                'province' => 'Jawa Tengah',
                'postal_code' => '50241',
                'payment_term' => 'COD',
                'bank_name' => 'Mandiri',
                'bank_account_number' => '1122334455',
                'bank_account_name' => 'UD Berkah Abadi',
                'notes' => 'Pembelian tunai / COD.',
                'is_active' => true,
            ],

            [
                'code' => 'SUP-004',
                'name' => 'PT Prima Logistik',
                'supplier_type' => 'company',
                'contact_person' => 'Rudi',
                'phone' => '083812345678',
                'email' => 'sales@primalogistik.co.id',
                'website' => null,
                'tax_number' => null,
                'address' => 'Kawasan Industri Candi',
                'city' => 'Semarang',
                'province' => 'Jawa Tengah',
                'postal_code' => '50181',
                'payment_term' => '30 Days',
                'bank_name' => 'BCA',
                'bank_account_number' => '5566778899',
                'bank_account_name' => 'PT Prima Logistik',
                'notes' => 'Supplier packaging dan kebutuhan logistik.',
                'is_active' => true,
            ],

            [
                'code' => 'SUP-005',
                'name' => 'CV Sentosa Mandiri',
                'supplier_type' => 'company',
                'contact_person' => 'Dedi',
                'phone' => '085712345678',
                'email' => 'sentosamandiri@gmail.com',
                'website' => null,
                'tax_number' => null,
                'address' => 'Jl. Soekarno Hatta No. 50',
                'city' => 'Demak',
                'province' => 'Jawa Tengah',
                'postal_code' => '59511',
                'payment_term' => '7 Days',
                'bank_name' => 'BRI',
                'bank_account_number' => '6677889900',
                'bank_account_name' => 'CV Sentosa Mandiri',
                'notes' => null,
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                [
                    'code' => $supplier['code'],
                ],
                $supplier
            );
        }
    }
}
