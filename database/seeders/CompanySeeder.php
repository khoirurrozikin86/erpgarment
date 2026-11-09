<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::updateOrCreate(
            [
                'code' => 'DW001',
            ],
            [
                'name' => 'PT Nusantara Digital Indonesia',

                'logo' => 'companies/logo.png',

                'email' => 'info@nusantaradigital.co.id',
                'phone' => '+62 21 5555 1234',
                'website' => 'https://www.nusantaradigital.co.id',

                'tax_number' => '01.234.567.8-012.000',

                'address' => 'Jl. Jenderal Sudirman No. 123, Kelurahan Karet Tengsin',
                'city' => 'Jakarta Pusat',
                'province' => 'DKI Jakarta',
                'postal_code' => '10220',

                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'date_format' => 'd/m/Y',

                'invoice_header' => 'PT NUSANTARA DIGITAL INDONESIA',
                'invoice_footer' => 'Terima kasih atas kepercayaan dan kerja sama Anda.',

                'receipt_header' => 'PT NUSANTARA DIGITAL INDONESIA',
                'receipt_footer' => 'Terima kasih atas kunjungan Anda.',

                'is_active' => true,
            ]
        );
    }
}
