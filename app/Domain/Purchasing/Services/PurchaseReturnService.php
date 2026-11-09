<?php

namespace App\Domain\Purchasing\Services;

use App\Domain\Purchasing\Actions\CreatePurchaseReturnAction;
use App\Domain\Purchasing\DTOs\PurchaseReturnData;
use App\Models\PurchaseReturn;

class PurchaseReturnService
{
    public function __construct(private CreatePurchaseReturnAction $create) {}

    public function create(array $payload, int $userId): PurchaseReturn
    {
        return ($this->create)(PurchaseReturnData::fromArray($payload), $userId);
    }
}
