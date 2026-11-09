<?php

namespace App\Domain\Sales\Queries;

use App\Domain\Purchasing\Queries\Concerns\AppliesDateRange;
use App\Models\Customer;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\SalesQuotation;
use Illuminate\Database\Eloquent\Builder;

class SalesQuotationTableQuery
{
    use AppliesDateRange;

    public function builder(?string $fromDate = null, ?string $toDate = null): Builder
    {
        $query = SalesQuotation::query()
            ->with(['customer:id,code,name,email', 'creator:id,name', 'issuer:id,name', 'reviewer:id,name'])
            ->withCount('items')
            ->orderByDesc('quote_date')->orderByDesc('id');

        return $this->applyDateRange($query, 'quote_date', $fromDate, $toDate);
    }

    public function customers()
    {
        return Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'email', 'address', 'city', 'province', 'payment_term']);
    }

    public function products(?int $customerId): Builder
    {
        $priceQuery = PriceListItem::query()
            ->select('price_list_items.price')
            ->join('price_lists', 'price_lists.id', '=', 'price_list_items.price_list_id')
            ->whereColumn('price_list_items.product_id', 'products.id')
            ->where('price_lists.type', 'sales')
            ->where('price_lists.is_active', true)
            ->where(fn ($query) => $query->whereNull('price_lists.valid_from')->orWhereDate('price_lists.valid_from', '<=', today()))
            ->where(fn ($query) => $query->whereNull('price_lists.valid_until')->orWhereDate('price_lists.valid_until', '>=', today()))
            ->where(function ($query) use ($customerId) {
                if ($customerId) {
                    $query->where('price_lists.customer_id', $customerId)->orWhereNull('price_lists.customer_id');
                } else {
                    $query->whereNull('price_lists.customer_id');
                }
            })
            ->orderByRaw('CASE WHEN price_lists.customer_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('price_lists.valid_from')
            ->limit(1);

        return Product::query()
            ->where('is_active', true)
            ->where('allow_sales', true)
            ->with('unit:id,name,symbol')
            ->select(['id', 'code', 'name', 'unit_id', 'sales_price', 'taxable', 'tax_rate'])
            ->selectSub($priceQuery, 'price_list_price')
            ->orderBy('name');
    }

    public function details(SalesQuotation $quotation): SalesQuotation
    {
        return SalesQuotation::query()
            ->with(['customer', 'creator', 'issuer', 'reviewer', 'items.product.unit'])
            ->findOrFail($quotation->id);
    }
}
