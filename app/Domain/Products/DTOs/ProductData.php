<?php

namespace App\Domain\Products\DTOs;

class ProductData
{
    public function __construct(
        public string $code,
        public ?string $barcode,
        public ?string $sku,
        public string $name,
        public ?string $shortName,
        public ?int $categoryId,
        public ?int $unitId,
        public string $productType,
        public float $purchasePrice,
        public float $salesPrice,
        public float $minStock,
        public float $maxStock,
        public float $reorderPoint,
        public bool $trackStock,
        public bool $taxable,
        public float $taxRate,
        public bool $allowDiscount,
        public bool $allowPurchase,
        public bool $allowSales,
        public ?string $description,
        public bool $isActive,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            barcode: $data['barcode'] ?? null,
            sku: $data['sku'] ?? null,
            name: $data['name'],
            shortName: $data['short_name'] ?? null,
            categoryId: isset($data['category_id'])
                ? (int) $data['category_id']
                : null,
            unitId: isset($data['unit_id'])
                ? (int) $data['unit_id']
                : null,
            productType: $data['product_type'] ?? 'stock',
            purchasePrice: (float) ($data['purchase_price'] ?? 0),
            salesPrice: (float) ($data['sales_price'] ?? 0),
            minStock: (float) ($data['min_stock'] ?? 0),
            maxStock: (float) ($data['max_stock'] ?? 0),
            reorderPoint: (float) ($data['reorder_point'] ?? 0),
            trackStock: (bool) ($data['track_stock'] ?? true),
            taxable: (bool) ($data['taxable'] ?? false),
            taxRate: (float) ($data['tax_rate'] ?? 0),
            allowDiscount: (bool) ($data['allow_discount'] ?? true),
            allowPurchase: (bool) ($data['allow_purchase'] ?? true),
            allowSales: (bool) ($data['allow_sales'] ?? true),
            description: $data['description'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'barcode' => $this->barcode,
            'sku' => $this->sku,
            'name' => $this->name,
            'short_name' => $this->shortName,
            'category_id' => $this->categoryId,
            'unit_id' => $this->unitId,
            'product_type' => $this->productType,
            'purchase_price' => $this->purchasePrice,
            'sales_price' => $this->salesPrice,
            'min_stock' => $this->minStock,
            'max_stock' => $this->maxStock,
            'reorder_point' => $this->reorderPoint,
            'track_stock' => $this->trackStock,
            'taxable' => $this->taxable,
            'tax_rate' => $this->taxRate,
            'allow_discount' => $this->allowDiscount,
            'allow_purchase' => $this->allowPurchase,
            'allow_sales' => $this->allowSales,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ];
    }
}
