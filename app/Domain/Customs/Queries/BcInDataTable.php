<?php

namespace App\Domain\Customs\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BcInDataTable
{
    public function response(Builder $query, string $module, string $direction = 'bc-in')
    {
        $party = $direction === 'bc-out' ? 'customer_name' : 'supplier_name';
        $reference = $direction === 'bc-out' ? 'shipment_number' : 'receipt_number';
        $rows = $query->reorder();
        $documents = DB::query()->fromSub($rows, 'items')
            ->select('document_id', 'submission_number', 'document_number', 'document_date', $party, 'currency')
            ->selectRaw('COUNT(item_id) as items_count, COALESCE(SUM(total_price), 0) as total_price')
            ->groupBy('document_id', 'submission_number', 'document_number', 'document_date', $party, 'currency');

        return DataTables::query(DB::query()->fromSub($documents, 'documents'))
            ->filter(function (Builder $list) use ($rows, $reference, $party) {
                $search = request()->input('search.value');
                if (!is_string($search) || trim($search) === '') {
                    return;
                }

                $pattern = '%'.mb_substr(trim($search), 0, 100).'%';
                $matches = DB::query()->fromSub($rows, 'search_items')->select('document_id')
                    ->where(function (Builder $searchRows) use ($reference, $party, $pattern) {
                        foreach (['submission_number', 'document_number', 'item_name', 'parent_code', 'sku_code', 'color', 'size', $reference, $party] as $field) {
                            $searchRows->orWhere($field, 'like', $pattern);
                        }
                    });
                $list->whereIn('document_id', $matches);
            })
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
