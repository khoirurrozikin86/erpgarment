<?php

namespace App\Domain\Manufacturing\Services;

use App\Domain\Manufacturing\Actions\{CreateRawMaterialOrderAction, DeleteRawMaterialOrderAction, UpdateRawMaterialOrderAction};
use App\Domain\Manufacturing\DTOs\RawMaterialOrderData;
use App\Models\RawMaterialOrderDocument;

class RawMaterialOrderService
{
    public function __construct(private CreateRawMaterialOrderAction $create, private UpdateRawMaterialOrderAction $update, private DeleteRawMaterialOrderAction $delete) {}
    public function create(array $payload, int $userId): RawMaterialOrderDocument { return ($this->create)(RawMaterialOrderData::fromArray($payload), $userId); }
    public function update(RawMaterialOrderDocument $document, array $payload, int $userId): RawMaterialOrderDocument { return ($this->update)($document, RawMaterialOrderData::fromArray($payload), $userId); }
    public function delete(RawMaterialOrderDocument $document, int $userId): void { ($this->delete)($document, $userId); }
}
