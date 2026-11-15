<?php

namespace App\Domain\Inventory\Queries;

use App\Models\{EquipmentReceiptDocument, Product, Supplier};
use Illuminate\Database\Eloquent\Builder;

class EquipmentReceiptTableQuery
{
    public function documents(int $companyId, ?string $from = null, ?string $to = null): Builder
    {
        return EquipmentReceiptDocument::query()->where('company_id', $companyId)
            ->when($from, fn (Builder $q) => $q->whereDate('receipt_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('receipt_date', '<=', $to))
            ->with(['supplier:id,name'])->withCount('items')->latest('receipt_date')->latest('id')
            ->select('equipment_receipt_documents.*');
    }

    public function products(): Builder
    {
        return Product::query()->where('is_active', true)
            ->whereHas('category', fn (Builder $q) => $q->whereIn('code', ['MACHINE', 'EQUIPMENT']))
            ->with('unit:id,name,symbol')->select(['id', 'code', 'name', 'unit_id', 'purchase_price'])->orderBy('code');
    }

    public function suppliers(): Builder { return Supplier::query()->where('is_active', true)->select(['id', 'code', 'name'])->orderBy('name'); }
}
