<?php

namespace App\Domain\Purchasing\Services;

use App\Domain\Purchasing\Actions\CreatePurchaseRequestAction;
use App\Domain\Purchasing\Actions\ReviewPurchaseRequestAction;
use App\Domain\Purchasing\DTOs\PurchaseRequestData;
use App\Domain\Purchasing\DTOs\PurchaseRequestReviewData;
use App\Models\PurchaseRequest;

class PurchaseRequestService
{
    public function __construct(
        private CreatePurchaseRequestAction $create,
        private ReviewPurchaseRequestAction $review,
    ) {}

    public function create(array $payload, int $userId): PurchaseRequest
    {
        return ($this->create)(PurchaseRequestData::fromArray($payload), $userId);
    }

    public function review(PurchaseRequest $request, int $reviewerId, string $status, ?string $note): PurchaseRequest
    {
        return ($this->review)(
            $request,
            new PurchaseRequestReviewData($reviewerId, $status, $note),
        );
    }
}
