<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Actions\IssueRawMaterialOrderItemAction;
use App\Domain\Inventory\Actions\UpdateMaterialIssueItemAction;
use App\Domain\Inventory\Actions\DeleteMaterialIssueItemAction;
use App\Models\MaterialIssueDocument;
use App\Models\RawMaterialOrderItem;

class MaterialIssueService
{
    public function __construct(
        private IssueRawMaterialOrderItemAction $issue,
        private UpdateMaterialIssueItemAction $update,
        private DeleteMaterialIssueItemAction $delete,
    ) {}

    public function issue(RawMaterialOrderItem $source, float $quantity, string $date, int $userId): MaterialIssueDocument
    {
        return ($this->issue)($source, $quantity, $date, $userId);
    }

    public function issueMany(array $lines, string $date, int $userId): MaterialIssueDocument
    {
        return $this->issue->issueMany($lines, $date, $userId);
    }

    public function updateItem(\App\Models\MaterialIssueItem $item, float $quantity, string $date, int $userId): \App\Models\MaterialIssueItem
    {
        return ($this->update)($item, $quantity, $date, $userId);
    }

    public function deleteItem(\App\Models\MaterialIssueItem $item, int $userId): void
    {
        ($this->delete)($item, $userId);
    }
}
