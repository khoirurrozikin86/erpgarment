<?php

namespace App\Domain\Companies\Queries;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

class CompanyTableQuery
{
    public function builder(): Builder
    {
        return Company::query()
            ->select([
                'id',
                'code',
                'name',
                'logo',
                'email',
                'phone',
                'website',
                'tax_number',
                'address',
                'city',
                'province',
                'postal_code',
                'currency',
                'timezone',
                'date_format',
                'invoice_header',
                'invoice_footer',
                'receipt_header',
                'receipt_footer',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('created_at');
    }
}
