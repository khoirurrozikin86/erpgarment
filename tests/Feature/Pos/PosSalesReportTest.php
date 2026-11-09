<?php

namespace Tests\Feature\Pos;

use App\Domain\Companies\Services\CompanyContext;
use App\Http\Controllers\Admin\PosSalesReportController;
use App\Domain\Pos\Queries\PosSalesReportQuery;
use App\Models\CashBankAccount;
use App\Models\Company;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PosSalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_filters_sales_and_excludes_voided_transactions_from_totals(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();
        $otherCompany = Company::query()->create(['code' => 'OTHER', 'name' => 'Other Company']);
        app(CompanyContext::class)->set($company);
        $cashier = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $account = CashBankAccount::query()->where('company_id', $company->id)->where('type', 'cash')->firstOrFail();
        $session = PosSession::query()->create([
            'company_id' => $company->id,
            'number' => 'TEST-SHIFT-001',
            'cash_bank_account_id' => $account->id,
            'opened_by' => $cashier->id,
            'opened_at' => '2026-09-27 08:00:00',
            'opening_cash' => 100000,
            'status' => 'closed',
        ]);
        $otherSession = PosSession::query()->create([
            'company_id' => $otherCompany->id,
            'number' => 'OTHER-SHIFT-001',
            'cash_bank_account_id' => $account->id,
            'opened_by' => $cashier->id,
            'opened_at' => '2026-09-27 08:00:00',
            'opening_cash' => 100000,
            'status' => 'closed',
        ]);

        $cashSale = $this->createSale($company, $cashier, $account, $session, 'POS-CASH', '2026-09-27 10:00:00', 'cash');
        $voidedSale = $this->createSale($company, $cashier, $account, $session, 'POS-VOIDED', '2026-09-27 10:30:00', 'cash', 'voided');
        $this->createSale($company, $cashier, $account, $session, 'POS-TRANSFER', '2026-09-27 11:00:00', 'bank_transfer');
        $this->createSale($company, $cashier, $account, $session, 'POS-YESTERDAY', '2026-09-26 11:00:00', 'cash');
        $this->createSale($otherCompany, $cashier, $account, $otherSession, 'POS-OTHER-COMPANY', '2026-09-27 12:00:00', 'cash');

        $sales = app(PosSalesReportQuery::class)->sales([
            'from_date' => '2026-09-27',
            'to_date' => '2026-09-27',
            'cashier_id' => $cashier->id,
            'pos_session_id' => $session->id,
            'payment_method' => 'cash',
        ])->get();

        $this->assertCount(2, $sales);
        $this->assertEqualsCanonicalizing([$cashSale->id, $voidedSale->id], $sales->pluck('id')->all());

        $view = app(PosSalesReportController::class)->index(Request::create('/super/reports/pos-sales', 'GET', [
            'from_date' => '2026-09-27',
            'to_date' => '2026-09-27',
            'cashier_id' => $cashier->id,
            'pos_session_id' => $session->id,
            'payment_method' => 'cash',
        ]), app(PosSalesReportQuery::class));
        $summary = $view->getData()['summary'];
        $this->assertSame(1, $summary['transaction_count']);
        $this->assertSame(1, $summary['voided_count']);
        $this->assertSame(100000.0, $summary['total']);
        $this->assertSame(100000.0, $summary['voided_total']);
    }

    private function createSale(Company $company, User $cashier, CashBankAccount $account, PosSession $session, string $number, string $soldAt, string $paymentMethod, string $status = 'completed'): PosSale
    {
        return PosSale::query()->create([
            'company_id' => $company->id,
            'number' => $number,
            'pos_session_id' => $session->id,
            'cashier_id' => $cashier->id,
            'cash_bank_account_id' => $account->id,
            'sold_at' => $soldAt,
            'subtotal' => 100000,
            'discount_rate' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 100000,
            'payment_method' => $paymentMethod,
            'paid_amount' => 100000,
            'change_amount' => 0,
            'status' => $status,
        ]);
    }
}
