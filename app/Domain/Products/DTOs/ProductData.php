<?php

namespace App\Domain\Products\DTOs;

class ProductData
{
    public function __construct(
        public string $code,
        public string $name,
        public ?int $categoryId,
        public ?int $unitId,
        public string $productType,
        public string $garmentItemType,
        public ?string $styleCode,
        public ?string $color,
        public ?string $size,
        public float $purchasePrice,
        public float $minStock,
        public float $maxStock,
        public float $reorderPoint,
        public bool $trackStock,
        public bool $isActive,
        public bool $isAvailable,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            unitId: isset($data['unit_id']) ? (int) $data['unit_id'] : null,
            productType: $data['product_type'] ?? 'stock',
            garmentItemType: $data['garment_item_type'] ?? 'general',
            styleCode: $data['style_code'] ?? null,
            color: $data['color'] ?? null,
            size: $data['size'] ?? null,
            purchasePrice: (float) ($data['purchase_price'] ?? 0),
            minStock: (float) ($data['min_stock'] ?? 0),
            maxStock: (float) ($data['max_stock'] ?? 0),
            reorderPoint: (float) ($data['reorder_point'] ?? 0),
            trackStock: (bool) ($data['track_stock'] ?? true),
            isActive: (bool) ($data['is_active'] ?? true),
            isAvailable: (bool) ($data['is_available'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'category_id' => $this->categoryId,
            'unit_id' => $this->unitId,
            'product_type' => $this->productType,
            'garment_item_type' => $this->garmentItemType,
            'style_code' => $this->styleCode,
            'color' => $this->color,
            'size' => $this->size,
            'purchase_price' => $this->purchasePrice,
            'min_stock' => $this->minStock,
            'max_stock' => $this->maxStock,
            'reorder_point' => $this->reorderPoint,
            'track_stock' => $this->trackStock,
            'is_active' => $this->isActive,
            'is_available' => $this->isAvailable,
        ];
    }
}
