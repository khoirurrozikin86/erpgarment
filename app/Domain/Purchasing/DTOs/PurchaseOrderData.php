<?php

namespace App\Domain\Purchasing\DTOs;

class PurchaseOrderData
{
    public function __construct(
        public int $purchaseRequestId,
        public ?string $expectedDeliveryAt,
        public ?string $paymentTerms,
        public ?string $notes,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            purchaseRequestId: (int) $data['purchase_request_id'],
            expectedDeliveryAt: $data['expected_delivery_at'] ?? null,
            paymentTerms: $data['payment_terms'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
