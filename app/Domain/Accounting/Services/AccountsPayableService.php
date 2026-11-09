<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Actions\PaySupplierAction;
use App\Models\GoodsReceipt;
use App\Models\SupplierPayment;

class AccountsPayableService
{
    public function __construct(private PaySupplierAction $paySupplier) {}

    public function pay(GoodsReceipt $receipt, array $data, int $userId): SupplierPayment
    {
        return ($this->paySupplier)($receipt, $data, $userId);
    }
}
