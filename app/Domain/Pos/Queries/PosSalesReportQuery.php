<?php

namespace App\Domain\Pos\Queries;

use App\Domain\Companies\Services\CompanyContext;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PosSalesReportQuery
{
    public function __construct(private CompanyContext $company) {}

    public function sales(array $filters): Builder
    {
        return PosSale::query()
            ->where('company_id', $this->company->id())
            ->whereIn('status', ['completed', 'voided'])
            ->when($filters['from_date'] ?? null, fn(Builder $query, string $date) => $query->where('sold_at', '>=', $date . ' 00:00:00'))
            ->when($filters['to_date'] ?? null, fn(Builder $query, string $date) => $query->where('sold_at', '<=', $date . ' 23:59:59'))
            ->when($filters['cashier_id'] ?? null, fn(Builder $query, int $id) => $query->where('cashier_id', $id))
            ->when($filters['pos_session_id'] ?? null, fn(Builder $query, int $id) => $query->where('pos_session_id', $id))
            ->when($filters['payment_method'] ?? null, fn(Builder $query, string $method) => $query->where('payment_method', $method))
            ->with([
                'session:id,number,status',
                'cashier:id,name',
                'voider:id,name',
                'customer:id,code,name',
                'items.product:id,code,name,unit_id',
                'items.product.unit:id,name,symbol',
            ])
            ->withCount('items')
            ->orderByDesc('sold_at')
            ->orderByDesc('id');
    }

    public function cashiers()
    {
        $cashierIds = PosSale::query()
            ->where('company_id', $this->company->id())
            ->whereNotNull('cashier_id')
            ->distinct()
            ->pluck('cashier_id');

        return User::query()->whereIn('id', $cashierIds)->orderBy('name')->get(['id', 'name']);
    }

    public function sessions()
    {
        return PosSession::query()
            ->where('company_id', $this->company->id())
            ->orderByDesc('opened_at')
            ->get(['id', 'number', 'opened_at', 'status']);
    }
}
