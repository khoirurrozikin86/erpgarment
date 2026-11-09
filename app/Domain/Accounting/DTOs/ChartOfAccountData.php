<?php

namespace App\Domain\Accounting\DTOs;

class ChartOfAccountData
{
    public function __construct(public array $attributes) {}
    public static function fromArray(array $data): self { return new self($data); }
}
