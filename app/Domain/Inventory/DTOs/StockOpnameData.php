<?php

namespace App\Domain\Inventory\DTOs;

class StockOpnameData
{
    public function __construct(public array $productIds, public ?string $notes) {}

    public static function fromArray(array $data): self
    {
        return new self(array_map('intval', $data['product_ids']), $data['notes'] ?? null);
    }
}
