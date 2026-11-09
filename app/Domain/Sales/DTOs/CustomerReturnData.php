<?php

namespace App\Domain\Sales\DTOs;

class CustomerReturnData
{
    public function __construct(
        public readonly int $salesInvoiceId,
        public readonly int $cashBankAccountId,
        public readonly string $returnedAt,
        public readonly string $reason,
        public readonly ?string $notes,
        public readonly array $items,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            salesInvoiceId: (int) $data['sales_invoice_id'],
            cashBankAccountId: (int) $data['cash_bank_account_id'],
            returnedAt: $data['returned_at'],
            reason: $data['reason'],
            notes: $data['notes'] ?? null,
            items: $data['items'],
        );
    }
}
