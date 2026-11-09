<?php

namespace App\Domain\Inventory\DTOs;

class StockOpnameCountsData
{
    public function __construct(public array $items) {}

    public static function fromArray(array $data): self
    {
        return new self($data['items']);
    }
}
