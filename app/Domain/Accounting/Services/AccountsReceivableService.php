<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Actions\ReceiveCustomerPaymentAction;
use App\Domain\Accounting\DTOs\ReceiveCustomerPaymentData;
use App\Models\SalesInvoice;

class AccountsReceivableService
{
    public function __construct(private ReceiveCustomerPaymentAction $receivePayment) {}

    public function receivePayment(SalesInvoice $invoice, array $payload, int $userId)
    {
        return ($this->receivePayment)($invoice, ReceiveCustomerPaymentData::fromArray($payload), $userId);
    }
}
