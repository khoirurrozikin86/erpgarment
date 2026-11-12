<?php

namespace App\Domain\Suppliers\Queries;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

class SupplierTableQuery
{
    public function builder(): Builder
    {
        return Supplier::query()
            ->select(['id', 'code', 'name', 'address', 'contact_person', 'phone', 'fax', 'email', 'country', 'is_active', 'created_at'])
            ->orderByDesc('created_at');
    }
}
