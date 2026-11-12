<?php

namespace App\Domain\Styles\Services;

use App\Domain\Styles\Actions\CreateStyleAction;
use App\Domain\Styles\Actions\DeleteStyleAction;
use App\Domain\Styles\Actions\UpdateStyleAction;
use App\Domain\Styles\DTOs\StyleData;
use App\Models\Style;

class StyleService
{
    public function __construct(private CreateStyleAction $create, private UpdateStyleAction $update, private DeleteStyleAction $delete) {}

    public function create(array $data): Style
    {
        return ($this->create)(StyleData::fromArray($data));
    }

    public function update(Style $style, array $data): Style
    {
        return ($this->update)($style, StyleData::fromArray($data));
    }

    public function delete(Style $style): bool
    {
        return ($this->delete)($style);
    }
}
