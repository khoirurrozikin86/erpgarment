<?php

namespace App\Domain\Sales\DTOs;

class SalesOrderData
{
    public function __construct(public array $attributes) {}

    public static function fromArray(array $data): self { return new self($data); }
}
