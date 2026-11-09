<?php

namespace App\Exports;

use App\Domain\Accounting\Queries\CashBankReportQuery;
use App\Models\CashBankAccount;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CashBankReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private string $fromDate, private string $toDate) {}

    public function query()
    {
        return app(CashBankReportQuery::class)->accounts($this->fromDate, $this->toDate);
    }

    public function headings(): array
    {
        return ['Kode Akun', 'Nama Akun', 'Jenis', 'Saldo Awal Periode', 'Kas Masuk', 'Kas Keluar', 'Saldo Akhir Periode', 'Status'];
    }

    public function map($account): array
    {
        /** @var CashBankAccount $account */
        $opening = (float) $account->opening_balance + (float) $account->prior_in - (float) $account->prior_out;
        $incoming = (float) $account->period_in;
        $outgoing = (float) $account->period_out;
        return [
            $account->code, $account->name, $account->type === 'cash' ? 'Kas' : 'Bank',
            round($opening, 2), round($incoming, 2), round($outgoing, 2), round($opening + $incoming - $outgoing, 2),
            $account->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }
}
