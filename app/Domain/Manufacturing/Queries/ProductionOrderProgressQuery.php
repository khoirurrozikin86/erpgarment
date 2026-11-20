<?php

namespace App\Domain\Manufacturing\Queries;

use App\Models\{GoodsReceipt, MaterialIssueDocument, MaterialRequest, ProductionOrder, ProductionOrderStageEntry, PurchaseOrder, PurchaseRequest, User};
use App\Support\DisplayNumber;
use Illuminate\Support\Collection;

class ProductionOrderProgressQuery
{
    public const STAGES = ['cutting', 'sewing', 'packing', 'finishing'];

    public function documents(ProductionOrder $order, Collection $lines, User $user): array
    {
        $requests = MaterialRequest::query()->where(function ($query) use ($order, $lines) {
            $query->whereIn('production_order_id', $lines->pluck('id'));
            if ($order->production_order_header_id) {
                $query->orWhere('production_order_header_id', $order->production_order_header_id);
            }
        });
        $purchaseRequests = PurchaseRequest::query()
            ->whereIn('material_request_id', (clone $requests)->select('id'));
        $purchaseOrders = PurchaseOrder::query()
            ->whereIn('purchase_request_id', (clone $purchaseRequests)->select('id'));
        $receipts = GoodsReceipt::query()
            ->whereIn('purchase_order_id', (clone $purchaseOrders)->select('id'));
        $issues = MaterialIssueDocument::query()->where('company_id', $order->company_id)
            ->where(function ($query) use ($order, $requests) {
                $query->whereIn('material_request_id', (clone $requests)->select('id'))
                    ->orWhereHas('rawMaterialOrder', fn ($source) => $source
                        ->where('company_id', $order->company_id)
                        ->where('production_order_number', $order->number));
            });

        $sections = [];
        foreach ([
            ['mr', $requests, 'material-requests', 'created_at', 'status'],
            ['pr', $purchaseRequests, 'purchase-requests', 'created_at', 'status'],
            ['po', $purchaseOrders, 'purchase-orders', 'order_date', 'status'],
            ['receipt', $receipts, 'goods-receipts', 'received_at', 'status'],
            ['mis', $issues, 'material-issues', 'issue_date', 'production_receipt_status'],
        ] as [$key, $query, $module, $date, $status]) {
            if (!$user->can($module.'.view')) {
                continue;
            }
            $relations = match ($key) {
                'pr' => ['items.materialRequestItem'],
                'po' => ['items.purchaseRequestItem.materialRequestItem'],
                'receipt' => ['items.purchaseOrderItem.purchaseRequestItem.materialRequestItem'],
                'mis' => ['items.requestItem', 'items.rawMaterialOrderItem.sourceMaterial'],
                default => [],
            };
            $sections[$key] = $query->with(['items.product.unit', 'items.product.parentProduct:id,name', ...$relations])
                ->orderBy($date)->orderBy('id')->get()
                ->map(fn ($document) => [
                    'number' => $document->number,
                    'date' => $document->{$date}?->format('d/m/Y'),
                    'status' => $document->{$status} ?? 'pending',
                    'items' => $this->itemQuantities($document->items, $key),
                ]);
        }

        return $sections;
    }

    public function stages(Collection $lines): Collection
    {
        return ProductionOrderStageEntry::query()->whereIn('production_order_id', $lines->pluck('id'))
            ->whereIn('stage', self::STAGES)
            ->select('production_order_id', 'stage')
            ->selectRaw('SUM(good_quantity) as quantity, MIN(processed_at) as started_at')
            ->groupBy('production_order_id', 'stage')->get()
            ->groupBy('production_order_id')->map(fn (Collection $stages) => $stages->keyBy('stage'));
    }

    private function itemQuantities(Collection $items, string $kind): Collection
    {
        return $items->map(function ($item) use ($kind) {
            $product = $item->product;
            $source = match ($kind) {
                'pr' => $item->materialRequestItem,
                'po' => $item->purchaseRequestItem?->materialRequestItem,
                'receipt' => $item->purchaseOrderItem?->purchaseRequestItem?->materialRequestItem,
                default => $item,
            };
            $color = $kind === 'mis' ? $item->issued_color : $source?->color;
            $size = $kind === 'mis' ? $item->issued_size : $source?->size;

            return [
                'parent_id' => $product?->parent_product_id ?: $item->product_id,
                'unit_id' => $product?->unit_id,
                'unit' => $product?->unit?->symbol ?: $product?->unit?->name,
                'name' => $product?->parentProduct?->name ?? $product?->name ?? '—',
                'color' => trim($color ?? '') ?: ($product?->parent_product_id ? $product->color : null),
                'size' => trim($size ?? '') ?: ($product?->parent_product_id ? $product->size : null),
                'quantity' => $item->quantity,
            ];
        })->groupBy(fn (array $line) => json_encode([
            $line['parent_id'], $line['unit_id'], mb_strtolower($line['color'] ?? ''), mb_strtolower($line['size'] ?? ''),
        ]))->map(function (Collection $lines) {
            $first = $lines->first();

            return [
                'name' => $first['name'],
                'color' => $first['color'],
                'size' => $first['size'],
                'quantity' => trim(DisplayNumber::format($lines->sum('quantity'), 4).' '.$first['unit']),
            ];
        })->sortBy([['name', 'asc'], ['color', 'asc'], ['size', 'asc']])->values();
    }
}
