<?php

namespace App\Domain\PriceLists\DTOs;

class PriceListData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $type,
        public ?int $customerId,
        public ?int $supplierId,
        public ?string $validFrom,
        public ?string $validUntil,
        public bool $isActive,
        public ?string $notes,
        public array $items,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            type: $data['type'],
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            supplierId: isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            validFrom: $data['valid_from'] ?? null,
            validUntil: $data['valid_until'] ?? null,
            isActive: (bool) $data['is_active'],
            notes: $data['notes'] ?? null,
            items: $data['items'],
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'customer_id' => $this->customerId,
            'supplier_id' => $this->supplierId,
            'valid_from' => $this->validFrom,
            'valid_until' => $this->validUntil,
            'is_active' => $this->isActive,
            'notes' => $this->notes,
        ];
    }
}
