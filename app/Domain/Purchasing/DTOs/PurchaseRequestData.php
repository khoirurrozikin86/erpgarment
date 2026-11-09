<?php

namespace App\Domain\Purchasing\DTOs;

class PurchaseRequestData
{
    public function __construct(
        public int $materialRequestId,
        public int $supplierId,
        public ?string $notes,
        public array $items,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            materialRequestId: (int) $data['material_request_id'],
            supplierId: (int) $data['supplier_id'],
            notes: $data['notes'] ?? null,
            items: $data['items'],
        );
    }
}
