<?php

namespace App\Domain\Exporters\Queries;

use App\Models\Exporter;
use Illuminate\Database\Eloquent\Builder;

class ExporterTableQuery
{
    public function builder(): Builder
    {
        return Exporter::query()
            ->select(['id', 'tax_number', 'name', 'address', 'is_active', 'created_at', 'updated_at'])
            ->orderByDesc('created_at');
    }
}
