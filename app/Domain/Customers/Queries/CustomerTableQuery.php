<?php

namespace App\Domain\Customers\Queries;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class CustomerTableQuery
{
    public function builder(): Builder
    {
        return Customer::query()
            ->select(['id', 'code', 'name', 'address', 'contact_person', 'phone', 'fax', 'email', 'country', 'is_active', 'created_at'])
            ->orderByDesc('created_at');
    }
}
