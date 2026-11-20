<?php

namespace App\Domain\Inventory\Queries;

use App\Models\EquipmentIssueItem;
use App\Models\EquipmentReceiptItem;
use App\Models\Product;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EquipmentStockCardQuery
{
    public function products(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->whereHas('category', fn (Builder $query) => $query->whereIn('code', ['MACHINE', 'EQUIPMENT']))
            ->with(['category:id,code,name', 'unit:id,name,symbol'])
            ->select(['id', 'code', 'name', 'category_id', 'unit_id', 'track_stock'])
            ->orderBy('code');
    }

    public function balance(int $productId, int $companyId): BigDecimal
    {
        return BigDecimal::of((string) $this->receipts($productId, $companyId)->sum('quantity'))
            ->minus((string) $this->issues($productId, $companyId)->sum('quantity'));
    }

    /**
     * Build a read-only running ledger from the active equipment receipt and
     * issue documents. These legacy documents do not currently post stock movements.
     */
    public function movements(int $productId, int $companyId): Collection
    {
        $receipts = $this->receipts($productId, $companyId)
            ->with(['document.creator:id,name'])
            ->get()
            ->map(fn (EquipmentReceiptItem $item) => $this->movementRow(
                $item->document->receipt_date?->format('Y-m-d'),
                $item->document->number,
                __('ui.machine_stock_card.receipt'),
                (float) $item->quantity,
                $item->document->creator?->name,
                $item->document->created_at,
                $item->id,
            ));

        $issues = $this->issues($productId, $companyId)
            ->with(['document.creator:id,name'])
            ->get()
            ->map(fn (EquipmentIssueItem $item) => $this->movementRow(
                $item->document->issue_date?->format('Y-m-d'),
                $item->document->number,
                __('ui.machine_stock_card.issue'),
                -(float) $item->quantity,
                $item->document->creator?->name,
                $item->document->created_at,
                $item->id,
            ));

        $rows = $receipts->concat($issues)
            ->sortBy(fn (array $row) => sprintf('%s %s %020d', $row['date'] ?? '', $row['created_at'] ?? '', $row['sort_id']))
            ->values();

        $balance = 0.0;

        return $rows->map(function (array $row) use (&$balance): array {
            $balance += $row['signed_quantity'];
            $row['amount'] = abs($row['signed_quantity']);
            $row['direction'] = $row['signed_quantity'] > 0 ? '+' : ($row['signed_quantity'] < 0 ? '-' : 'S');
            $row['balance'] = $balance;

            return $row;
        });
    }

    private function receipts(int $productId, int $companyId): Builder
    {
        return EquipmentReceiptItem::query()
            ->where('product_id', $productId)
            ->whereHas('document', fn (Builder $query) => $query->where('company_id', $companyId));
    }

    private function issues(int $productId, int $companyId): Builder
    {
        return EquipmentIssueItem::query()
            ->where('product_id', $productId)
            ->whereHas('document', fn (Builder $query) => $query->where('company_id', $companyId));
    }

    private function movementRow(?string $date, string $number, string $description, float $quantity, ?string $operator, mixed $createdAt, int $sortId): array
    {
        return [
            'date' => $date,
            'number' => $number,
            'description' => $description,
            'signed_quantity' => $quantity,
            'operator' => $operator ?: '—',
            'created_at' => $createdAt?->format('Y-m-d H:i:s') ?? '',
            'sort_id' => $sortId,
        ];
    }
}
