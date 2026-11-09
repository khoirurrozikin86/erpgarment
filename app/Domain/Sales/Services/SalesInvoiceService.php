<?php

namespace App\Domain\Sales\Services;

use App\Domain\Sales\Actions\CreateSalesInvoiceAction;
use App\Domain\Sales\Actions\IssueSalesInvoiceAction;
use App\Domain\Sales\Actions\SendSalesInvoiceEmailAction;
use App\Domain\Sales\DTOs\SalesInvoiceData;
use App\Models\SalesInvoice;

class SalesInvoiceService
{
    public function __construct(private CreateSalesInvoiceAction $create, private IssueSalesInvoiceAction $issue, private SendSalesInvoiceEmailAction $sendEmail) {}

    public function create(array $payload, int $userId): SalesInvoice
    {
        return ($this->create)(SalesInvoiceData::fromArray($payload), $userId);
    }

    public function issue(SalesInvoice $invoice, int $userId): SalesInvoice { return ($this->issue)($invoice, $userId); }
    public function sendEmail(SalesInvoice $invoice, string $email, int $userId): void { ($this->sendEmail)($invoice, $email, $userId); }
}
