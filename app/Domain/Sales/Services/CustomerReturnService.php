<?php

namespace App\Domain\Sales\Services;

use App\Domain\Sales\Actions\CreateCustomerReturnAction;
use App\Domain\Sales\DTOs\CustomerReturnData;
use App\Models\CustomerReturn;

class CustomerReturnService
{
    public function __construct(private CreateCustomerReturnAction $create) {}

    public function create(array $payload, int $userId): CustomerReturn
    {
        return ($this->create)(CustomerReturnData::fromArray($payload), $userId);
    }
}
