<?php

namespace Tests\Feature\Pos;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Pos\Queries\PosSessionQuery;
use App\Domain\Pos\Services\PosSaleService;
use App\Http\Controllers\Admin\PosController;
use App\Models\CashBankAccount;
use App\Models\CashBankTransaction;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PosSaleVoidTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_void_restores_stock_refunds_and_reverses_accounting(): void
    {
        [$company, $cashier, $sale, $session, $product] = $this->createSaleFixture('open');
        $this->actingAs($cashier);

        $voided = app(PosSaleService::class)->void($sale, 'Salah input jumlah barang', $cashier->id, false);

        $this->assertSame('voided', $voided->status);
        $this->assertSame('Salah input jumlah barang', $voided->void_reason);
        $this->assertSame($cashier->id, $voided->voided_by);
        $this->assertEquals(2, (float) ProductStock::query()->where('product_id', $product->id)->value('quantity'));
        $this->assertEquals(20, (float) ProductStock::query()->where('product_id', $product->id)->value('average_unit_cost'));

        $refund = CashBankTransaction::query()->where('pos_sale_void_id', $sale->id)->firstOrFail();
        $this->assertSame('out', $refund->direction);
        $this->assertEquals(200, (float) $refund->amount);
        $this->assertSame($session->id, $refund->pos_session_id);
        $this->assertDatabaseHas('stock_movements', [
            'pos_sale_id' => $sale->id,
            'movement_type' => 'pos_void',
            'quantity' => 2,
            'cost_amount' => 40,
        ]);

        $originalJournal = JournalEntry::query()
            ->where('source_type', PosSale::class)
            ->where('source_id', $sale->id)
            ->where('source_action', 'original')
            ->firstOrFail();
        $reversal = JournalEntry::query()
            ->where('source_type', PosSale::class)
            ->where('source_id', $sale->id)
            ->where('source_action', 'reversal-' . $originalJournal->id)
            ->firstOrFail();
        $this->assertEquals(200, (float) $reversal->lines()->sum('debit'));
        $this->assertEquals(200, (float) $reversal->lines()->sum('credit'));

        $cashIn = (float) $session->cashBankTransactions()->where('direction', 'in')->sum('amount');
        $cashOut = (float) $session->cashBankTransactions()->where('direction', 'out')->sum('amount');
        $this->assertEquals((float) $session->opening_cash, round((float) $session->opening_cash + $cashIn - $cashOut, 2));
        $this->assertSame(2, CashBankTransaction::query()->where('company_id', $company->id)->count());
    }

    public function test_voiding_a_closed_session_is_rejected_for_every_user(): void
    {
        [$company, $cashier, $sale] = $this->createSaleFixture('closed');
        $this->actingAs($cashier);
        $service = app(PosSaleService::class);

        try {
            $service->void($sale, 'Salah input', $cashier->id);
            $this->fail('Voiding a closed session must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('sale', $exception->errors());
        }

        $this->assertSame('completed', $sale->fresh()->status);
        $historyRequest = Request::create('/super/pos/sales/history', 'GET');
        $historyRequest->setUserResolver(fn() => $cashier);
        $history = app(PosController::class)->history($historyRequest, app(CompanyContext::class), app(PosSessionQuery::class))->getData(true);
        $this->assertFalse($history[0]['can_void']);
        $this->assertNotEmpty($history[0]['receipt_url']);
        $this->assertDatabaseHas('pos_sessions', [
            'id' => $sale->pos_session_id,
            'status' => 'closed',
            'expected_cash' => 200,
        ]);
        $this->assertSame(0, CashBankTransaction::query()->where('company_id', $company->id)->where('direction', 'out')->count());
    }

    public function test_cashier_history_exposes_reprint_and_void_only_for_the_open_session(): void
    {
        [$company, $cashier, $sale, $session] = $this->createSaleFixture('open');
        $this->actingAs($cashier);
        $controller = app(PosController::class);
        $historyRequest = Request::create('/super/pos/sales/history', 'GET');
        $historyRequest->setUserResolver(fn() => $cashier);

        $history = $controller->history($historyRequest, app(CompanyContext::class), app(PosSessionQuery::class))->getData(true);

        $this->assertCount(1, $history);
        $this->assertSame($sale->number, $history[0]['number']);
        $this->assertTrue($history[0]['can_void']);
        $this->assertSame(route('super.pos.receipt', $sale), $history[0]['receipt_url']);

        $receiptRequest = Request::create('/super/pos/sales/' . $sale->id . '/receipt', 'GET', ['autoprint' => '1']);
        $receiptRequest->setUserResolver(fn() => $cashier);
        $receipt = $controller->receipt($receiptRequest, $sale, app(CompanyContext::class), app(CompanyPdfBrandingService::class));
        $this->assertSame('super.pos.receipt', $receipt->name());
        $this->assertTrue($receipt->getData()['autoPrint']);
    }

    private function createSaleFixture(string $sessionStatus): array
    {
        $this->seed();
        $company = Company::query()->firstOrFail();
        app(CompanyContext::class)->set($company);
        $cashier = User::factory()->create();
        $account = CashBankAccount::query()->where('company_id', $company->id)->where('type', 'cash')->firstOrFail();
        $product = Product::query()->where('track_stock', true)->firstOrFail();
        $suffix = strtoupper(substr(sha1(uniqid('', true)), 0, 8));
        $session = PosSession::query()->create([
            'company_id' => $company->id,
            'number' => "TEST-SHIFT-{$suffix}",
            'cash_bank_account_id' => $account->id,
            'opened_by' => $cashier->id,
            'opened_at' => now()->subHour(),
            'closed_at' => $sessionStatus === 'closed' ? now() : null,
            'opening_cash' => 200,
            'expected_cash' => $sessionStatus === 'closed' ? 200 : null,
            'counted_cash' => $sessionStatus === 'closed' ? 200 : null,
            'cash_difference' => $sessionStatus === 'closed' ? 0 : null,
            'status' => $sessionStatus,
        ]);
        $sale = PosSale::query()->create([
            'company_id' => $company->id,
            'number' => "TEST-POS-{$suffix}",
            'pos_session_id' => $session->id,
            'cashier_id' => $cashier->id,
            'cash_bank_account_id' => $account->id,
            'sold_at' => now()->subMinutes(30),
            'subtotal' => 200,
            'discount_rate' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 200,
            'payment_method' => 'cash',
            'paid_amount' => 200,
            'change_amount' => 0,
            'status' => 'completed',
        ]);
        $saleItem = PosSaleItem::query()->create([
            'pos_sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'discount_amount' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'line_total' => 200,
            'cost_amount' => 40,
        ]);
        ProductStock::query()->create(['product_id' => $product->id, 'quantity' => 0, 'average_unit_cost' => 20]);
        $movement = StockMovement::query()->create([
            'product_id' => $product->id,
            'pos_sale_id' => $sale->id,
            'pos_sale_item_id' => $saleItem->id,
            'created_by' => $cashier->id,
            'movement_type' => 'pos_sale',
            'quantity' => -2,
            'quantity_before' => 2,
            'quantity_after' => 0,
            'cost_amount' => 40,
        ]);
        CashBankTransaction::query()->create([
            'company_id' => $company->id,
            'cash_bank_account_id' => $account->id,
            'pos_session_id' => $session->id,
            'pos_sale_id' => $sale->id,
            'transaction_date' => today(),
            'direction' => 'in',
            'amount' => 200,
            'description' => "Penjualan POS {$sale->number}",
            'created_by' => $cashier->id,
        ]);

        $journals = app(AutomaticJournalService::class);
        $journals->recordStockMovement($movement, $cashier->id);
        $journals->recordPosSale($sale, $cashier->id);

        return [$company, $cashier, $sale, $session, $product];
    }
}
