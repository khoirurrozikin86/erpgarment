<?php

namespace App\Domain\Manufacturing\DTOs;

final class RawMaterialOrderData
{
    public function __construct(public readonly string $orderDate, public readonly string $productionOrderNumber, public readonly array $items) {}

    public static function fromArray(array $data): self
    {
        return new self($data['order_date'], $data['production_order_number'], $data['items']);
    }
}
