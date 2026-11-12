<?php

namespace App\Domain\Exporters\Services;

use App\Domain\Exporters\Actions\CreateExporterAction;
use App\Domain\Exporters\Actions\DeleteExporterAction;
use App\Domain\Exporters\Actions\UpdateExporterAction;
use App\Domain\Exporters\DTOs\ExporterData;
use App\Models\Exporter;

class ExporterService
{
    public function __construct(
        private CreateExporterAction $create,
        private UpdateExporterAction $update,
        private DeleteExporterAction $delete,
    ) {}

    public function create(array $payload): Exporter
    {
        return ($this->create)(ExporterData::fromArray($payload));
    }

    public function update(Exporter $exporter, array $payload): Exporter
    {
        return ($this->update)($exporter, ExporterData::fromArray($payload));
    }

    public function delete(Exporter $exporter): bool
    {
        return ($this->delete)($exporter);
    }
}
