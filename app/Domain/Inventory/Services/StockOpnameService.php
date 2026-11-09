<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Actions\CreateStockOpnameAction;
use App\Domain\Inventory\Actions\PostStockOpnameAction;
use App\Domain\Inventory\Actions\SaveStockOpnameCountsAction;
use App\Domain\Inventory\DTOs\StockOpnameCountsData;
use App\Domain\Inventory\DTOs\StockOpnameData;
use App\Models\StockOpname;

class StockOpnameService
{
    public function __construct(
        private CreateStockOpnameAction $create,
        private SaveStockOpnameCountsAction $saveCounts,
        private PostStockOpnameAction $post,
    ) {}

    public function create(array $payload, int $userId): StockOpname
    {
        return ($this->create)(StockOpnameData::fromArray($payload), $userId);
    }

    public function saveCounts(StockOpname $stockOpname, array $payload, int $userId): StockOpname
    {
        return ($this->saveCounts)($stockOpname, StockOpnameCountsData::fromArray($payload), $userId);
    }

    public function post(StockOpname $stockOpname, int $userId): StockOpname
    {
        return ($this->post)($stockOpname, $userId);
    }
}
