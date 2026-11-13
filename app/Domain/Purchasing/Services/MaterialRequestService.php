<?php

namespace App\Domain\Purchasing\Services;

use App\Domain\Purchasing\Actions\CreateMaterialRequestAction;
use App\Domain\Purchasing\Actions\ReviewMaterialRequestAction;
use App\Domain\Purchasing\Actions\IssueMaterialRequestAction;
use App\Domain\Purchasing\DTOs\MaterialRequestData;
use App\Domain\Purchasing\DTOs\MaterialRequestReviewData;
use App\Models\MaterialRequest;
use App\Models\MaterialIssueDocument;

class MaterialRequestService
{
    public function __construct(
        private CreateMaterialRequestAction $create,
        private ReviewMaterialRequestAction $review,
        private IssueMaterialRequestAction $issue,
    ) {}

    public function create(array $payload, int $userId): MaterialRequest
    {
        return ($this->create)(MaterialRequestData::fromArray($payload), $userId);
    }

    public function review(MaterialRequest $request, int $reviewerId, string $status, ?string $note): MaterialRequest
    {
        return ($this->review)(
            $request,
            new MaterialRequestReviewData($reviewerId, $status, $note),
        );
    }

    public function issue(MaterialRequest $request, array $items, int $userId, ?string $issueDate = null): MaterialIssueDocument
    {
        return ($this->issue)($request, $items, $userId, $issueDate);
    }
}
