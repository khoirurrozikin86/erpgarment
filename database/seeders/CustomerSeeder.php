<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'code' => 'CUS-001',
                'name' => 'PT Maju Bersama',
                'customer_type' => 'company',
                'contact_person' => 'Budi Santoso',
                'phone' => '081234567890',
                'email' => 'purchasing@majubersama.co.id',
                'website' => null,
                'tax_number' => null,
                'address' => 'Jl. Industri No. 10',
                'city' => 'Semarang',
                'province' => 'Jawa Tengah',
                'postal_code' => '50100',
                'payment_term' => '30 Days',
                'credit_limit' => 50000000,
                'notes' => 'Customer B2B.',
                'is_active' => true,
            ],

            [
                'code' => 'CUS-002',
                'name' => 'CV Sejahtera Abadi',
                'customer_type' => 'company',
                'contact_person' => 'Andi',
                'phone' => '082112345678',
                'email' => 'admin@sejahteraabadi.co.id',
                'website' => null,
                'tax_number' => null,
                'address' => 'Jl. Raya Kendal No. 20',
                'city' => 'Kendal',
                'province' => 'Jawa Tengah',
                'postal_code' => '51300',
                'payment_term' => '14 Days',
                'credit_limit' => 25000000,
                'notes' => null,
                'is_active' => true,
            ],

            [
                'code' => 'CUS-003',
                'name' => 'Customer Retail Umum',
                'customer_type' => 'individual',
                'contact_person' => null,
                'phone' => null,
                'email' => null,
                'website' => null,
                'tax_number' => null,
                'address' => null,
                'city' => null,
                'province' => null,
                'postal_code' => null,
                'payment_term' => 'COD',
                'credit_limit' => 0,
                'notes' => 'Customer retail / cash.',
                'is_active' => true,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                [
                    'code' => $customer['code'],
                ],
                $customer
            );
        }
    }
}
