<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Actions\CreateJournalEntryAction;
use App\Domain\Accounting\Actions\DeleteDraftJournalEntryAction;
use App\Domain\Accounting\Actions\PostJournalEntryAction;
use App\Domain\Accounting\DTOs\JournalEntryData;
use App\Models\JournalEntry;

class JournalEntryService
{
    public function __construct(private CreateJournalEntryAction $create, private PostJournalEntryAction $post, private DeleteDraftJournalEntryAction $delete) {}
    public function create(array $payload, int $userId): JournalEntry { return ($this->create)(JournalEntryData::fromArray($payload), $userId); }
    public function post(JournalEntry $entry, int $userId): JournalEntry { return ($this->post)($entry, $userId); }
    public function deleteDraft(JournalEntry $entry): void { ($this->delete)($entry); }
}
