<?php

namespace App\Domain\Sales\DTOs;

class SalesQuotationData
{
    public function __construct(public array $attributes) {}

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
