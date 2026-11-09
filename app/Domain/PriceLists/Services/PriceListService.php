<?php

namespace App\Domain\PriceLists\Services;

use App\Domain\PriceLists\Actions\CreatePriceListAction;
use App\Domain\PriceLists\Actions\DeletePriceListAction;
use App\Domain\PriceLists\Actions\UpdatePriceListAction;
use App\Domain\PriceLists\DTOs\PriceListData;
use App\Models\PriceList;

class PriceListService
{
    public function __construct(
        private CreatePriceListAction $create,
        private UpdatePriceListAction $update,
        private DeletePriceListAction $delete,
    ) {}

    public function create(array $payload): PriceList
    {
        return ($this->create)(PriceListData::fromArray($payload));
    }

    public function update(PriceList $priceList, array $payload): PriceList
    {
        return ($this->update)($priceList, PriceListData::fromArray($payload));
    }

    public function delete(PriceList $priceList): bool
    {
        return ($this->delete)($priceList);
    }
}
