<?php

namespace App\Domain\Purchasing\DTOs;

class MaterialRequestData
{
    public function __construct(
        public ?string $department,
        public ?string $neededAt,
        public string $reason,
        public array $items,
        public ?int $billOfMaterialId = null,
        public ?int $outputProductId = null,
        public ?string $bomVersion = null,
        public ?float $productionQuantity = null,
        public ?int $productionOrderId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            department: $data['department'] ?? null,
            neededAt: $data['needed_at'] ?? null,
            reason: $data['reason'],
            items: $data['items'],
            billOfMaterialId: isset($data['bill_of_material_id']) ? (int) $data['bill_of_material_id'] : null,
            outputProductId: isset($data['output_product_id']) ? (int) $data['output_product_id'] : null,
            bomVersion: $data['bom_version'] ?? null,
            productionQuantity: isset($data['production_quantity']) ? (float) $data['production_quantity'] : null,
            productionOrderId: isset($data['production_order_id']) ? (int) $data['production_order_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'department' => $this->department,
            'needed_at' => $this->neededAt,
            'reason' => $this->reason,
            'bill_of_material_id' => $this->billOfMaterialId,
            'output_product_id' => $this->outputProductId,
            'bom_version' => $this->bomVersion,
            'production_quantity' => $this->productionQuantity,
            'production_order_id' => $this->productionOrderId,
        ];
    }
}
