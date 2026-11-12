<?php

namespace App\Domain\Styles\DTOs;

class StyleData
{
    public function __construct(public string $code, public string $name) {}

    public static function fromArray(array $data): self
    {
        return new self(code: $data['code'], name: $data['name']);
    }

    public function toArray(): array
    {
        return ['code' => $this->code, 'name' => $this->name];
    }
}
