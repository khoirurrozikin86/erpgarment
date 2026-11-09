<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DocumentNumbering;
use Illuminate\Database\Seeder;

class DocumentNumberingSeeder extends Seeder
{
    public function run(): void
    {
        $documentNumberings = [
            [
                'document_type' => 'material_request',
                'prefix' => 'MR',
            ],
            [
                'document_type' => 'purchase_request',
                'prefix' => 'PR',
            ],
            [
                'document_type' => 'purchase_order',
                'prefix' => 'PO',
            ],
            [
                'document_type' => 'goods_receipt',
                'prefix' => 'GR',
            ],
            [
                'document_type' => 'purchase_return',
                'prefix' => 'PRT',
            ],
            [
                'document_type' => 'sales_order',
                'prefix' => 'SO',
            ],
            [
                'document_type' => 'sales_quotation',
                'prefix' => 'QUO',
            ],
            [
                'document_type' => 'delivery_order',
                'prefix' => 'DO',
            ],
            [
                'document_type' => 'sales_invoice',
                'prefix' => 'INV',
            ],
            [
                'document_type' => 'customer_return',
                'prefix' => 'CRT',
            ],
            [
                'document_type' => 'customer_payment',
                'prefix' => 'RCPT',
            ],
            [
                'document_type' => 'supplier_payment',
                'prefix' => 'PAY',
            ],
            [
                'document_type' => 'journal_entry',
                'prefix' => 'JRN',
            ],
            [
                'document_type' => 'pos',
                'prefix' => 'POS',
            ],
            [
                'document_type' => 'pos_session',
                'prefix' => 'SHIFT',
            ],
            [
                'document_type' => 'stock_opname',
                'prefix' => 'STO',
            ],
            [
                'document_type' => 'stock_transfer',
                'prefix' => 'TRF',
            ],
        ];

        Company::query()->each(function (Company $company) use ($documentNumberings) {

            foreach ($documentNumberings as $numbering) {

                DocumentNumbering::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'document_type' => $numbering['document_type'],
                    ],
                    [
                        'prefix' => $numbering['prefix'],
                        'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}',
                        'next_number' => 1,
                        'number_length' => 5,
                        'reset_period' => 'monthly',
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
