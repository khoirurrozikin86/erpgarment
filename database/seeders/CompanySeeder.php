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
                'code' => 'SD001',
            ],
            [
                'name' => 'PT SMART DRAGON INDUSTRIAL',

                'logo' => 'companies/logo.png',

                'email' => 'info@smartdragon.co.id',
                'phone' => '+62 21 5555 1234',
                'website' => 'https://www.sdi.co.id',

                'tax_number' => '01.234.567.8-012.000',

                'address' => 'Jl. Soekarno Hatta No.10B, Gembongan, Karangjati, Semarang District, Kabupaten Semarang, Jawa Tengah 50552',
                'city' => 'Semarang',
                'province' => 'Jawa Tengah',
                'postal_code' => '50552',

                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'date_format' => 'd/m/Y',

                'invoice_header' => 'PT SMART DRAGON INDUSTRIAL',
                'invoice_footer' => 'Terima kasih atas kepercayaan dan kerja sama Anda.',

                'receipt_header' => 'PT SMART DRAGON INDUSTRIAL',
                'receipt_footer' => 'Terima kasih atas kunjungan Anda.',

                'is_active' => true,
            ]
        );
    }
}
