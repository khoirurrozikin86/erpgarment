<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Actions\CreateChartOfAccountAction;
use App\Domain\Accounting\Actions\UpdateChartOfAccountAction;
use App\Domain\Accounting\DTOs\ChartOfAccountData;
use App\Models\ChartOfAccount;

class ChartOfAccountService
{
    public function __construct(private CreateChartOfAccountAction $create, private UpdateChartOfAccountAction $update) {}

    public function create(array $payload): ChartOfAccount { return ($this->create)(ChartOfAccountData::fromArray($payload)); }
    public function update(ChartOfAccount $account, array $payload): ChartOfAccount { return ($this->update)($account, ChartOfAccountData::fromArray($payload)); }
}
