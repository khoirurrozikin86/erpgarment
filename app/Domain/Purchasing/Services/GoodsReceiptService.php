<?php

namespace App\Domain\Purchasing\Services;

use App\Domain\Purchasing\Actions\CreateGoodsReceiptAction;
use App\Domain\Purchasing\DTOs\GoodsReceiptData;
use App\Models\GoodsReceipt;

class GoodsReceiptService
{
    public function __construct(private CreateGoodsReceiptAction $create) {}

    public function receive(array $payload, int $userId): GoodsReceipt
    {
        return ($this->create)(GoodsReceiptData::fromArray($payload), $userId);
    }
}
