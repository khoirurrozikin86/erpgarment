<?php

namespace App\Domain\Importers\DTOs;

class ImporterData
{
    public function __construct(
        public ?string $taxNumber,
        public string $name,
        public ?string $address,
        public ?string $apip,
        public bool $isActive,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            taxNumber: $data['tax_number'] ?? null,
            name: $data['name'],
            address: $data['address'] ?? null,
            apip: $data['apip'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'tax_number' => $this->taxNumber,
            'name' => $this->name,
            'address' => $this->address,
            'apip' => $this->apip,
            'is_active' => $this->isActive,
        ];
    }
}
