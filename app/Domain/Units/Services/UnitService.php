<?php

namespace App\Domain\Units\Services;

use App\Domain\Units\Actions\CreateUnitAction;
use App\Domain\Units\Actions\DeleteUnitAction;
use App\Domain\Units\Actions\UpdateUnitAction;
use App\Domain\Units\DTOs\UnitData;
use App\Models\Unit;

class UnitService
{
    public function __construct(
        protected CreateUnitAction $create,
        protected UpdateUnitAction $update,
        protected DeleteUnitAction $delete,
    ) {}

    public function create(array $payload): Unit
    {
        return ($this->create)(
            UnitData::fromArray($payload)
        );
    }

    public function update(Unit $unit, array $payload): Unit
    {
        return ($this->update)(
            $unit,
            UnitData::fromArray($payload)
        );
    }

    public function delete(Unit $unit): bool
    {
        return ($this->delete)($unit);
    }
}
