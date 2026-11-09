<?php

namespace App\Domain\Purchasing\DTOs;

class MaterialRequestData
{
    public function __construct(
        public ?string $department,
        public ?string $neededAt,
        public string $reason,
        public array $items,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            department: $data['department'] ?? null,
            neededAt: $data['needed_at'] ?? null,
            reason: $data['reason'],
            items: $data['items'],
        );
    }

    public function toArray(): array
    {
        return [
            'department' => $this->department,
            'needed_at' => $this->neededAt,
            'reason' => $this->reason,
        ];
    }
}
