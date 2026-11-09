<?php

namespace App\Domain\Purchasing\DTOs;

class MaterialRequestReviewData
{
    public function __construct(
        public int $reviewerId,
        public string $status,
        public ?string $note,
    ) {}
}
