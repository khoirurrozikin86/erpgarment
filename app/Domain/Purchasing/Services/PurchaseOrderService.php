<?php

namespace App\Domain\Purchasing\Services;

use App\Domain\Purchasing\Actions\CreatePurchaseOrderAction;
use App\Domain\Purchasing\Actions\IssuePurchaseOrderAction;
use App\Domain\Purchasing\Actions\SendPurchaseOrderEmailAction;
use App\Domain\Purchasing\DTOs\PurchaseOrderData;
use App\Models\PurchaseOrder;

class PurchaseOrderService
{
    public function __construct(
        private CreatePurchaseOrderAction $create,
        private IssuePurchaseOrderAction $issue,
        private SendPurchaseOrderEmailAction $sendEmail,
    ) {}

    public function create(array $payload, int $userId): PurchaseOrder
    {
        return ($this->create)(PurchaseOrderData::fromArray($payload), $userId);
    }

    public function issue(PurchaseOrder $order): PurchaseOrder
    {
        return ($this->issue)($order);
    }

    public function sendEmail(PurchaseOrder $order, string $recipient, int $userId): void
    {
        ($this->sendEmail)($order, $recipient, $userId);
    }

}
