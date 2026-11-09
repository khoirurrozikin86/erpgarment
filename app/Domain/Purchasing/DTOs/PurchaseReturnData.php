<?php

namespace App\Domain\Purchasing\DTOs;

class PurchaseReturnData
{
    public function __construct(
        public int $goodsReceiptId,
        public string $returnedAt,
        public string $reason,
        public ?string $notes,
        public array $items,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            goodsReceiptId: (int) $data['goods_receipt_id'],
            returnedAt: $data['returned_at'],
            reason: $data['reason'],
            notes: $data['notes'] ?? null,
            items: $data['items'],
        );
    }
}
