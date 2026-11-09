<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Actions\CreateCashBankAccountAction;
use App\Domain\Accounting\Actions\RecordCashBankTransactionAction;
use App\Domain\Accounting\Actions\UpdateManualCashBankTransactionAction;
use App\Domain\Accounting\Actions\DeleteManualCashBankTransactionAction;
use App\Domain\Accounting\DTOs\CashBankAccountData;
use App\Domain\Accounting\DTOs\CashBankTransactionData;
use App\Models\CashBankAccount;
use App\Models\CashBankTransaction;

class CashBankService
{
    public function __construct(
        private CreateCashBankAccountAction $createAccount,
        private RecordCashBankTransactionAction $recordTransaction,
        private UpdateManualCashBankTransactionAction $updateTransaction,
        private DeleteManualCashBankTransactionAction $deleteTransaction,
    ) {}

    public function createAccount(array $payload): CashBankAccount
    {
        return ($this->createAccount)(CashBankAccountData::fromArray($payload));
    }

    public function recordTransaction(array $payload, int $userId): CashBankTransaction
    {
        return ($this->recordTransaction)(CashBankTransactionData::fromArray($payload), $userId);
    }

    public function updateTransaction(CashBankTransaction $transaction, array $payload, int $userId): CashBankTransaction
    {
        return ($this->updateTransaction)($transaction, CashBankTransactionData::fromArray($payload), $userId);
    }

    public function deleteTransaction(CashBankTransaction $transaction, int $userId): void
    {
        ($this->deleteTransaction)($transaction, $userId);
    }
}
