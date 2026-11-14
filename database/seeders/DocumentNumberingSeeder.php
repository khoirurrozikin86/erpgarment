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
                'document_type' => 'supplier_payment',
                'prefix' => 'PAY',
            ],
            [
                'document_type' => 'journal_entry',
                'prefix' => 'JRN',
            ],
            [
                'document_type' => 'stock_opname',
                'prefix' => 'STO',
            ],
            [
                'document_type' => 'stock_transfer',
                'prefix' => 'TRF',
            ],
            [
                'document_type' => 'damaged_item',
                'prefix' => 'BRSK',
            ],
            [
                'document_type' => 'lost_item',
                'prefix' => 'BHLNG',
            ],
            [
                'document_type' => 'raw_material_order',
                'prefix' => 'OBB',
            ],
            [
                'document_type' => 'material_issue',
                'prefix' => 'MIS',
            ],
            [
                'document_type' => 'non_production_material_usage',
                'prefix' => 'PBN',
            ],
            ['document_type' => 'wip_issue', 'prefix' => 'WIP'],
            ['document_type' => 'scrap_receipt', 'prefix' => 'SCI'],
            [
                'document_type' => 'return_to_warehouse',
                'prefix' => 'RETG',
            ],
            ['document_type' => 'support_material_order', 'prefix' => 'OBP'],
            ['document_type' => 'cutting', 'prefix' => 'CUT'],
            ['document_type' => 'sewing', 'prefix' => 'SEW'],
            ['document_type' => 'packing', 'prefix' => 'PKG'],
            ['document_type' => 'finished_goods', 'prefix' => 'FIN'],
            ['document_type' => 'goods_sent_note', 'prefix' => 'GSN'],
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
