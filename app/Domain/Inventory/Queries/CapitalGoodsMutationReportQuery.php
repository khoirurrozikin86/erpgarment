<?php

namespace App\Domain\Inventory\Queries;

use App\Models\{Product, StockMovement};
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CapitalGoodsMutationReportQuery extends MaterialMutationReportQuery
{
    private const CAPITAL = ['MACHINE', 'EQUIP', 'EQUIPMENT', 'OFFICE'];

    protected function products(array $filters): Builder
    {
        $from = CarbonImmutable::parse($filters['from_date'])->startOfDay();
        $until = CarbonImmutable::parse($filters['to_date'])->addDay()->startOfDay();
        return Product::query()->where('product_type', 'stock')
            ->where(function (Builder $q) {
                $q->whereHas('category', fn (Builder $q) => $q->whereIn('code', self::CAPITAL))
                    ->orWhere(function (Builder $q) {
                        $q->where('track_stock', true)->where('garment_item_type', 'general')
                            ->whereDoesntHave('billOfMaterialComponents')
                            ->whereDoesntHave('category', fn (Builder $q) => $q->whereIn('code', ['RAW', 'FAB', 'ACC', 'PACK', 'FG', 'WIP', 'WASTE'])->orWhereRaw('LOWER(name) = ?', ['finished goods']));
                    });
            })->with(['unit:id,name,symbol', 'category:id,code,name'])
            ->select(['products.id', 'products.code', 'products.name', 'products.unit_id', 'products.category_id'])
            ->withExists(['category as equipment_source' => fn (Builder $q) => $q->whereIn('code', self::CAPITAL)])
            ->addSelect(['opening_balance' => StockMovement::query()->select('quantity_after')->whereColumn('product_id', 'products.id')->where('created_at', '<', $from)->orderByDesc('created_at')->orderByDesc('id')->limit(1)])
            ->withSum(['stockMovements as quantity_in' => fn (Builder $q) => $q->where('created_at', '>=', $from)->where('created_at', '<', $until)->where('quantity', '>', 0)], 'quantity')
            ->withSum(['stockMovements as quantity_out' => fn (Builder $q) => $q->where('created_at', '>=', $from)->where('created_at', '<', $until)->where('quantity', '<', 0)], 'quantity');
    }

    public function builder(array $filters): Builder
    {
        $query = parent::builder($filters);
        foreach ([['equipment_receipt', 'receipt_date', 'received'], ['equipment_issue', 'issue_date', 'issued']] as [$table, $date, $alias]) {
            $base = DB::table($table.'_items as ei')->join($table.'_documents as ed', 'ed.id', '=', 'ei.'.$table.'_document_id')
                ->whereColumn('ei.product_id', 'products.id')->where('ed.company_id', $filters['_company_id'])->whereNull('ed.deleted_at');
            $query->addSelect([
                $alias.'_before' => (clone $base)->where('ed.'.$date, '<', $filters['from_date'])->selectRaw('COALESCE(SUM(ei.quantity), 0)'),
                $alias.'_period' => (clone $base)->where('ed.'.$date, '>=', $filters['from_date'])->where('ed.'.$date, '<=', $filters['to_date'])->selectRaw('COALESCE(SUM(ei.quantity), 0)'),
            ]);
        }
        return $query;
    }

    public static function values(Product $product): array
    {
        if (!$product->equipment_source) return parent::values($product);
        $opening = (float) $product->received_before - (float) $product->issued_before;
        $incoming = (float) $product->received_period;
        $outgoing = (float) $product->issued_period;
        return ['opening' => $opening, 'incoming' => $incoming, 'outgoing' => $outgoing, 'adjustment' => null,
            'closing' => round($opening + $incoming - $outgoing, 4), 'counted' => null, 'difference' => null,
            'notes' => 'Dokumen mesin/peralatan'];
    }
}
