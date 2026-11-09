<?php

namespace App\Domain\Purchasing\DTOs;

class GoodsReceiptData
{
    public function __construct(
        public int $purchaseOrderId,
        public string $receivedAt,
        public ?string $notes,
        public array $items,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            purchaseOrderId: (int) $data['purchase_order_id'],
            receivedAt: $data['received_at'],
            notes: $data['notes'] ?? null,
            items: $data['items'],
        );
    }
}
