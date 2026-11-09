<?php

namespace App\Domain\Units\DTOs;

class UnitData
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $symbol = null,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            symbol: $data['symbol'] ?? null,
            description: $data['description'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ];
    }
}
