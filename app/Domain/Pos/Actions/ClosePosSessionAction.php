<?php

namespace App\Domain\Pos\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\PosSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClosePosSessionAction
{
    public function __construct(private CompanyContext $company, private AuditLogService $auditLog) {}

    public function __invoke(PosSession $session, array $data, int $userId): PosSession
    {
        return DB::transaction(function () use ($session, $data, $userId) {
            $session = PosSession::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($session->id);
            if ($session->status !== 'open') throw ValidationException::withMessages(['session' => 'Sesi ini sudah ditutup.']);
            $cashIn = (float) $session->cashBankTransactions()->where('cash_bank_account_id', $session->cash_bank_account_id)->where('direction', 'in')->sum('amount');
            $cashOut = (float) $session->cashBankTransactions()->where('cash_bank_account_id', $session->cash_bank_account_id)->where('direction', 'out')->sum('amount');
            $expected = round((float) $session->opening_cash + $cashIn - $cashOut, 2);
            $counted = round((float) $data['counted_cash'], 2);
            $oldValues = $session->only(['status', 'expected_cash', 'counted_cash', 'cash_difference', 'closed_by', 'closed_at']);
            $session->update([
                'status' => 'closed', 'expected_cash' => $expected, 'counted_cash' => $counted,
                'cash_difference' => round($counted - $expected, 2), 'closed_by' => $userId, 'closed_at' => now(),
                'notes' => $data['notes'] ?? $session->notes,
            ]);
            $this->auditLog->log(action: 'CLOSE', module: 'POS_SESSION', description: "Menutup sesi kasir {$session->number}", model: $session, oldValues: $oldValues, newValues: $session->only(['status', 'expected_cash', 'counted_cash', 'cash_difference', 'closed_by', 'closed_at', 'notes']));
            return $session->load(['cashBankAccount', 'opener', 'closer']);
        });
    }
}
