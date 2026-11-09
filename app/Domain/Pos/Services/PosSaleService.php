<?php

namespace App\Domain\Pos\Services;

use App\Domain\Pos\Actions\CreatePosSaleAction;
use App\Domain\Pos\Actions\VoidPosSaleAction;
use App\Models\PosSale;

class PosSaleService
{
    public function __construct(private CreatePosSaleAction $create, private VoidPosSaleAction $voidSale) {}
    public function create(array $data, int $userId): PosSale
    {
        return ($this->create)($data, $userId);
    }
    public function void(PosSale $sale, string $reason, int $userId): PosSale
    {
        return ($this->voidSale)($sale, $reason, $userId);
    }
}
