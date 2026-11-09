<?php

namespace App\Domain\Sales\Services;

use App\Domain\Sales\Actions\CreateDeliveryAction;
use App\Domain\Sales\Actions\PostDeliveryAction;
use App\Domain\Sales\Actions\DeleteDraftDeliveryAction;
use App\Domain\Sales\DTOs\DeliveryData;
use App\Models\Delivery;

class DeliveryService
{
    public function __construct(private CreateDeliveryAction $create, private PostDeliveryAction $post, private DeleteDraftDeliveryAction $deleteDraft) {}

    public function create(array $payload, int $userId): Delivery
    {
        return ($this->create)(DeliveryData::fromArray($payload), $userId);
    }

    public function post(Delivery $delivery, int $userId): Delivery
    {
        return ($this->post)($delivery, $userId);
    }

    public function deleteDraft(Delivery $delivery): void
    {
        ($this->deleteDraft)($delivery);
    }
}
