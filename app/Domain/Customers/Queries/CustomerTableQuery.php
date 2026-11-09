<?php

namespace App\Domain\Customers\Queries;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class CustomerTableQuery
{
    public function builder(): Builder
    {
        return Customer::query()
            ->select([
                'id',
                'code',
                'name',
                'customer_type',
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
                'credit_limit',
                'notes',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('created_at');
    }
}
