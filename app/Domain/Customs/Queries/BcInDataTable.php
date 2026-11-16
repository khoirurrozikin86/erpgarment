<?php

namespace App\Domain\Customs\Queries;

use Illuminate\Database\Query\Builder;
use Yajra\DataTables\Facades\DataTables;

class BcInDataTable
{
    public function response(Builder $query, string $module, string $direction = 'bc-in')
    {
        return DataTables::query($query->reorder())
            ->addIndexColumn()
            ->addColumn('actions', fn ($item) => view('super.bc-in.partials.actions', [
                'item' => $item,
                'module' => $module,
                'direction' => $direction,
            ])->render())
            ->rawColumns(['actions'])
            ->toJson();
    }
}
