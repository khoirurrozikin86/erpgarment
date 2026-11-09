<?php

namespace App\Domain\Accounting\DTOs;

class JournalEntryData
{
    public function __construct(public array $attributes) {}
    public static function fromArray(array $data): self { return new self($data); }
}
