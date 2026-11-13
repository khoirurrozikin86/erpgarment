<?php

namespace App\Domain\Purchasing\DTOs;

class GoodsReceiptData
{
    public function __construct(
        public int $purchaseOrderId,
        public string $receivedAt,
        public ?string $notes,
        public array $items,
        public ?string $customsDocumentCode = null,
        public ?string $customsDocumentNumber = null,
        public ?string $customsDocumentDate = null,
        public ?string $invoiceNumber = null,
        public ?string $invoiceDate = null,
        public ?string $currency = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            purchaseOrderId: (int) $data['purchase_order_id'],
            receivedAt: $data['received_at'],
            notes: $data['notes'] ?? null,
            items: $data['items'],
            customsDocumentCode: $data['customs_document_code'] ?? null,
            customsDocumentNumber: $data['customs_document_number'] ?? null,
            customsDocumentDate: $data['customs_document_date'] ?? null,
            invoiceNumber: $data['invoice_number'] ?? null,
            invoiceDate: $data['invoice_date'] ?? null,
            currency: $data['currency'] ?? null,
        );
    }
}
