<?php

namespace App\Domain\Manufacturing\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderHeader;
use App\Models\Style;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateProductionOrderAction
{
    public function __construct(
        private CompanyContext $company,
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(array $data, int $userId): ProductionOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $sharedHeader = [
                'buyer_po_number' => $data['buyer_po_number'] ?? null,
                'no_cbd' => $data['no_cbd'] ?? null,
                'currency' => $data['currency'],
                'destination' => $data['destination'] ?? null,
                'po_date' => $data['po_date'],
                'due_date' => $data['due_date'],
                'customer_id' => $data['customer_id'],
            ];
            \App\Models\Company::whereKey($this->company->id())->lockForUpdate()->firstOrFail();
            $header = ProductionOrderHeader::firstOrCreate([
                'company_id' => $this->company->id(), 'number' => $data['number'],
            ], $sharedHeader);
            if (!$header->wasRecentlyCreated) {
                $this->assertMatchingHeader($header, $sharedHeader);
            }
            $existingLine = ProductionOrder::query()->where('company_id', $this->company->id())
                ->where('number', $data['number'])->lockForUpdate()->first();
            if ($existingLine) {
                $this->assertMatchingHeader($existingLine, $sharedHeader);
            }

            $productId = $data['output_product_id'] ?? null;
            $bom = $productId === null || empty($data['bill_of_material_id']) ? null : app(\App\Domain\Manufacturing\Services\ResolveProductionBom::class)
                ->resolve($this->company->id(), (int) $productId, $data['po_date'], isset($data['bill_of_material_id']) ? (int) $data['bill_of_material_id'] : null, $data['style_code'] ?? null);

            $order = ProductionOrder::create([
                'production_order_header_id' => $header->id,
                'company_id' => $this->company->id(),
                'number' => $data['number'],
                ...$sharedHeader,
                'price_per_unit' => $data['price_per_unit'] ?? 0,
                'total_price' => round((float) $data['planned_quantity'] * (float) ($data['price_per_unit'] ?? 0), 4),
                'description' => $data['description'] ?? null,
                'image_path' => $data['image_path'] ?? null,
                'bill_of_material_id' => $bom?->id,
                'output_product_id' => $productId,
                'bom_version' => $bom?->version,
                'style_code' => Style::register($data['style_code'] ?? $bom?->style_code),
                'order_color' => $productId === null ? $data['color'] : null,
                'order_size' => $productId === null ? $data['size'] : null,
                'planned_quantity' => $data['planned_quantity'],
                'planned_start_date' => $data['po_date'],
                'planned_end_date' => $data['due_date'],
                'status' => $productId !== null ? 'released' : 'draft',
                'created_by' => $userId,
                'released_by' => $productId !== null ? $userId : null,
                'released_at' => $productId !== null ? now() : null,
            ]);

            if ($bom) {
                $ratio = (float) $data['planned_quantity'] / (float) $bom->output_quantity;
                $order->materials()->createMany($bom->items->map(fn ($item) => [
                    'bill_of_material_id' => $bom->id,
                    'product_id' => $item->product_id,
                    'operation' => $item->operation,
                    'color' => $item->applies_to_color ?? '',
                    'size' => $item->applies_to_size ?? '',
                    'quantity_per_output' => $item->quantity,
                    'wastage_percent' => $item->wastage_percent,
                    'required_quantity' => round((float) $item->quantity * $ratio * (1 + (float) $item->wastage_percent / 100), 4),
                ])->all());
            }

            $this->auditLog->log(
                action: 'CREATE', module: 'MATERIAL_ORDER',
                description: __('ui.workflow.material_order_created_audit', ['number' => $order->number]),
                model: $order, oldValues: null,
                newValues: $order->load('materials.product')->toArray(),
            );

            return $order->load(['outputProduct.unit', 'materials.product.unit', 'creator']);
        });
    }

    private function assertMatchingHeader(ProductionOrderHeader|ProductionOrder $order, array $submitted): void
    {
        $labels = [
            'buyer_po_number' => 'ui.workflow.buyer_po_no',
            'no_cbd' => 'ui.workflow.no_cbd',
            'currency' => 'ui.workflow.currency',
            'destination' => 'ui.workflow.destination',
            'po_date' => 'ui.workflow.po_date',
            'due_date' => 'ui.workflow.material_order_due_date',
            'customer_id' => 'ui.workflow.buyer',
        ];
        $differences = collect($submitted)->filter(function ($value, string $field) use ($order) {
            $stored = match ($field) {
                'po_date', 'due_date' => $order->{$field}?->format('Y-m-d'),
                default => $order->{$field},
            };

            return trim((string) $stored) !== trim((string) $value);
        })->keys()->map(fn (string $field) => __($labels[$field]))->implode(', ');

        if ($differences !== '') {
            throw ValidationException::withMessages(['number' => __('variants.mo_header_differences', ['fields' => $differences])]);
        }
    }
}
