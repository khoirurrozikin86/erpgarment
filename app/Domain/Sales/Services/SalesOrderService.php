<?php

namespace App\Domain\Sales\Services;

use App\Domain\Sales\Actions\ConfirmSalesOrderAction;
use App\Domain\Sales\Actions\CreateSalesOrderAction;
use App\Domain\Sales\DTOs\SalesOrderData;
use App\Models\SalesOrder;

class SalesOrderService
{
    public function __construct(private CreateSalesOrderAction $create, private ConfirmSalesOrderAction $confirm) {}

    public function create(array $payload, int $userId): SalesOrder
    {
        return ($this->create)(SalesOrderData::fromArray($payload), $userId);
    }

    public function confirm(SalesOrder $order, int $userId): SalesOrder
    {
        return ($this->confirm)($order, $userId);
    }
}
