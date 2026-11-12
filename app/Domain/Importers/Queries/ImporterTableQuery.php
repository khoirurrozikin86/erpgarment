<?php

namespace App\Domain\Importers\Queries;

use App\Models\Importer;
use Illuminate\Database\Eloquent\Builder;

class ImporterTableQuery
{
    public function builder(): Builder
    {
        return Importer::query()
            ->select(['id', 'tax_number', 'name', 'address', 'apip', 'is_active', 'created_at', 'updated_at'])
            ->orderByDesc('created_at');
    }
}
