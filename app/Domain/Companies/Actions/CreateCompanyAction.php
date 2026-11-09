<?php

namespace App\Domain\Companies\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Accounting\Services\ChartOfAccountDefaultsService;
use App\Domain\Companies\DTOs\CompanyData;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use App\Models\DocumentNumbering;
use App\Models\GeneralSetting;

class CreateCompanyAction
{
    public function __construct(
        protected AuditLogService $auditLog,
        protected ChartOfAccountDefaultsService $chartOfAccountDefaults,
    ) {}

    public function __invoke(
        CompanyData $data
    ): Company {
        return DB::transaction(function () use ($data) {

            $company = Company::create(
                $data->toArray()
            );


            GeneralSetting::create([
                'company_id' => $company->id,
                'decimal_places' => 2,
                'negative_stock' => false,
                'tax_included' => false,
            ]);

            $this->chartOfAccountDefaults->seedForCompany($company);


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

            foreach ($documentNumberings as $numbering) {
                DocumentNumbering::create([
                    'company_id' => $company->id,
                    'document_type' => $numbering['document_type'],
                    'prefix' => $numbering['prefix'],
                    'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}',
                    'next_number' => 1,
                    'number_length' => 5,
                    'reset_period' => 'monthly',
                    'is_active' => true,
                ]);
            }





            $this->auditLog->log(
                action: 'CREATE',
                module: 'COMPANY',
                description: "Membuat company {$company->name}",
                oldValues: null,
                newValues: $company->toArray(),
            );

            return $company;
        });
    }
}
