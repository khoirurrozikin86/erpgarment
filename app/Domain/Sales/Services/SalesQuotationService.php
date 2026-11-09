<?php

namespace App\Domain\Sales\Services;

use App\Domain\Sales\Actions\CreateSalesQuotationAction;
use App\Domain\Sales\Actions\SendSalesQuotationEmailAction;
use App\Domain\Sales\Actions\TransitionSalesQuotationAction;
use App\Domain\Sales\DTOs\SalesQuotationData;
use App\Models\SalesQuotation;

class SalesQuotationService
{
    public function __construct(
        private CreateSalesQuotationAction $create,
        private TransitionSalesQuotationAction $transition,
        private SendSalesQuotationEmailAction $sendEmail,
    ) {}

    public function create(array $payload, int $userId): SalesQuotation
    {
        return ($this->create)(SalesQuotationData::fromArray($payload), $userId);
    }

    public function transition(SalesQuotation $quotation, string $status, ?string $note, int $userId): SalesQuotation
    {
        return ($this->transition)($quotation, $status, $note, $userId);
    }

    public function sendEmail(SalesQuotation $quotation, string $recipient, int $userId): void
    {
        ($this->sendEmail)($quotation, $recipient, $userId);
    }
}
