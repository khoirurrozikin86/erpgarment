<?php

namespace App\Domain\Pos\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\CashBankAccount;
use App\Models\PosSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpenPosSessionAction
{
    public function __construct(private CompanyContext $company, private DocumentNumberService $numbers, private AuditLogService $auditLog) {}

    public function __invoke(array $data, int $userId): PosSession
    {
        return DB::transaction(function () use ($data, $userId) {
            $account = CashBankAccount::query()->where('company_id', $this->company->id())->where('type', 'cash')
                ->where('is_active', true)->lockForUpdate()->find($data['cash_bank_account_id']);
            if (!$account || !$account->chart_of_account_id) {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Pilih rekening kas aktif yang sudah dipetakan ke COA.']);
            }
            if (PosSession::query()->where('company_id', $this->company->id())->where('cash_bank_account_id', $account->id)->where('status', 'open')->exists()) {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Masih ada sesi kasir terbuka pada rekening ini. Tutup sesi tersebut lebih dahulu.']);
            }
            $session = PosSession::create([
                'company_id' => $this->company->id(), 'number' => $this->numbers->generate('pos_session'),
                'cash_bank_account_id' => $account->id, 'opened_by' => $userId, 'opened_at' => now(),
                'opening_cash' => round((float) $data['opening_cash'], 2), 'status' => 'open', 'notes' => $data['notes'] ?? null,
            ]);
            $this->auditLog->log(action: 'OPEN', module: 'POS_SESSION', description: "Membuka sesi kasir {$session->number}", model: $session, oldValues: null, newValues: $session->toArray());
            return $session->load(['cashBankAccount', 'opener']);
        });
    }
}
