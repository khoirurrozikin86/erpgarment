<?php

namespace App\Domain\Accounting\Services;

use App\Models\ChartOfAccount;
use App\Models\Company;

class ChartOfAccountDefaultsService
{
    public function seedForCompany(Company $company): void
    {
        $definitions = [
            ['code' => '1000', 'name' => 'ASET', 'type' => 'asset', 'normal' => 'debit', 'group' => true],
            ['code' => '1100', 'name' => 'Aset Lancar', 'type' => 'asset', 'normal' => 'debit', 'group' => true, 'parent' => '1000'],
            ['code' => '1110', 'name' => 'Kas', 'type' => 'asset', 'normal' => 'debit', 'parent' => '1100'],
            ['code' => '1120', 'name' => 'Bank', 'type' => 'asset', 'normal' => 'debit', 'parent' => '1100'],
            ['code' => '1130', 'name' => 'Piutang Usaha', 'type' => 'asset', 'normal' => 'debit', 'parent' => '1100'],
            ['code' => '1140', 'name' => 'Persediaan Barang', 'type' => 'asset', 'normal' => 'debit', 'parent' => '1100'],
            ['code' => '2000', 'name' => 'KEWAJIBAN', 'type' => 'liability', 'normal' => 'credit', 'group' => true],
            ['code' => '2100', 'name' => 'Kewajiban Lancar', 'type' => 'liability', 'normal' => 'credit', 'group' => true, 'parent' => '2000'],
            ['code' => '2110', 'name' => 'Utang Usaha', 'type' => 'liability', 'normal' => 'credit', 'parent' => '2100'],
            ['code' => '2120', 'name' => 'Pajak Keluaran', 'type' => 'liability', 'normal' => 'credit', 'parent' => '2100'],
            ['code' => '2130', 'name' => 'Barang Diterima Belum Ditagih', 'type' => 'liability', 'normal' => 'credit', 'parent' => '2100'],
            ['code' => '3000', 'name' => 'EKUITAS', 'type' => 'equity', 'normal' => 'credit', 'group' => true],
            ['code' => '3100', 'name' => 'Modal Pemilik', 'type' => 'equity', 'normal' => 'credit', 'parent' => '3000'],
            ['code' => '3200', 'name' => 'Saldo Laba', 'type' => 'equity', 'normal' => 'credit', 'parent' => '3000'],
            ['code' => '4000', 'name' => 'PENDAPATAN', 'type' => 'revenue', 'normal' => 'credit', 'group' => true],
            ['code' => '4100', 'name' => 'Penjualan', 'type' => 'revenue', 'normal' => 'credit', 'parent' => '4000'],
            ['code' => '4200', 'name' => 'Retur dan Potongan Penjualan', 'type' => 'revenue', 'normal' => 'debit', 'parent' => '4000'],
            ['code' => '5000', 'name' => 'HARGA POKOK PENJUALAN', 'type' => 'expense', 'normal' => 'debit', 'group' => true],
            ['code' => '5100', 'name' => 'Harga Pokok Penjualan', 'type' => 'expense', 'normal' => 'debit', 'parent' => '5000'],
            ['code' => '5200', 'name' => 'Selisih Harga Persediaan', 'type' => 'expense', 'normal' => 'debit', 'parent' => '5000'],
            ['code' => '5300', 'name' => 'Kerugian Penyesuaian Persediaan', 'type' => 'expense', 'normal' => 'debit', 'parent' => '5000'],
            ['code' => '4300', 'name' => 'Keuntungan Penyesuaian Persediaan', 'type' => 'revenue', 'normal' => 'credit', 'parent' => '4000'],
            ['code' => '6000', 'name' => 'BEBAN', 'type' => 'expense', 'normal' => 'debit', 'group' => true],
            ['code' => '6100', 'name' => 'Beban Operasional', 'type' => 'expense', 'normal' => 'debit', 'parent' => '6000'],
            ['code' => '6200', 'name' => 'Beban Gaji', 'type' => 'expense', 'normal' => 'debit', 'parent' => '6000'],
        ];

        $accountIds = [];
        foreach ($definitions as $definition) {
            $parentId = isset($definition['parent']) ? ($accountIds[$definition['parent']] ?? null) : null;
            $account = ChartOfAccount::firstOrCreate(
                ['company_id' => $company->id, 'code' => $definition['code']],
                [
                    'parent_id' => $parentId,
                    'name' => $definition['name'],
                    'account_type' => $definition['type'],
                    'normal_balance' => $definition['normal'],
                    'is_group' => $definition['group'] ?? false,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
            $accountIds[$definition['code']] = $account->id;
        }
    }
}
