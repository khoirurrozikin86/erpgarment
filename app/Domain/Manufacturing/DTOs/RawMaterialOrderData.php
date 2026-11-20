<?php

namespace App\Domain\Manufacturing\DTOs;

final class RawMaterialOrderData
{
    public function __construct(
        public readonly string $orderDate,
        public readonly ?string $productionOrderNumber,
        public readonly array $items,
        public readonly string $mode,
        public readonly ?string $notes = null,
        public readonly string $category = 'all',
    ) {}

    public static function fromArray(array $data): self
    {
        $mode = $data['mode'] ?? ($data['production_order_number'] ? 'mo' : 'manual');
        return new self(
            $data['order_date'],
            $data['production_order_number'] ?? null,
            $data['items'],
            $mode,
            $data['notes'] ?? null,
            $data['category'] ?? 'all',
        );
    }
}
