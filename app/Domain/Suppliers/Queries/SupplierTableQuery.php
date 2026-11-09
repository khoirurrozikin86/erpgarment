<?php

namespace App\Domain\Suppliers\Queries;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

class SupplierTableQuery
{
    public function builder(): Builder
    {
        return Supplier::query()
            ->select([
                'id',
                'code',
                'name',
                'supplier_type',
                'contact_person',
                'phone',
                'email',
                'website',
                'tax_number',
                'address',
                'city',
                'province',
                'postal_code',
                'payment_term',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'notes',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('created_at');
    }
}
