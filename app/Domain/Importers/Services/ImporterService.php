<?php

namespace App\Domain\Importers\Services;

use App\Domain\Importers\Actions\CreateImporterAction;
use App\Domain\Importers\Actions\DeleteImporterAction;
use App\Domain\Importers\Actions\UpdateImporterAction;
use App\Domain\Importers\DTOs\ImporterData;
use App\Models\Importer;

class ImporterService
{
    public function __construct(
        private CreateImporterAction $create,
        private UpdateImporterAction $update,
        private DeleteImporterAction $delete,
    ) {}

    public function create(array $payload): Importer
    {
        return ($this->create)(ImporterData::fromArray($payload));
    }

    public function update(Importer $importer, array $payload): Importer
    {
        return ($this->update)($importer, ImporterData::fromArray($payload));
    }

    public function delete(Importer $importer): bool
    {
        return ($this->delete)($importer);
    }
}
