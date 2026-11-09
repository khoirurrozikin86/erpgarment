<?php

namespace Tests\Feature\Sales;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Sales\Services\CustomerReturnService;
use App\Models\CashBankAccount;
use App\Models\CashBankTransaction;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerReturn;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_customer_return_refunds_restocks_and_enforces_invoice_quantity(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();
        app(CompanyContext::class)->set($company);
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $customer = Customer::query()->firstOrFail();
        $product = Product::query()->where('track_stock', true)->firstOrFail();
        $cashAccount = CashBankAccount::query()->where('company_id', $company->id)->where('type', 'cash')->firstOrFail();
        $returnsAccount = ChartOfAccount::query()->where('company_id', $company->id)->where('code', '4200')->firstOrFail();

        $quotationId = DB::table('sales_quotations')->insertGetId([
            'number' => 'TEST-QUO-001',
            'customer_id' => $customer->id,
            'quote_date' => today(),
            'subtotal' => 180,
            'tax_amount' => 18,
            'total_amount' => 198,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $quotationItemId = DB::table('sales_quotation_items')->insertGetId([
            'sales_quotation_id' => $quotationId,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'discount_amount' => 20,
            'tax_rate' => 10,
            'line_total' => 198,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $salesOrderId = DB::table('sales_orders')->insertGetId([
            'number' => 'TEST-SO-001',
            'sales_quotation_id' => $quotationId,
            'customer_id' => $customer->id,
            'order_date' => today(),
            'subtotal' => 180,
            'tax_amount' => 18,
            'total_amount' => 198,
            'status' => 'delivered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $salesOrderItemId = DB::table('sales_order_items')->insertGetId([
            'sales_order_id' => $salesOrderId,
            'sales_quotation_item_id' => $quotationItemId,
            'product_id' => $product->id,
            'quantity' => 2,
            'delivered_quantity' => 2,
            'unit_price' => 100,
            'discount_amount' => 20,
            'tax_rate' => 10,
            'line_total' => 198,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $deliveryId = DB::table('deliveries')->insertGetId([
            'number' => 'TEST-DO-001',
            'sales_order_id' => $salesOrderId,
            'customer_id' => $customer->id,
            'delivered_at' => now(),
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $deliveryItemId = DB::table('delivery_items')->insertGetId([
            'delivery_id' => $deliveryId,
            'sales_order_item_id' => $salesOrderItemId,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invoiceId = DB::table('sales_invoices')->insertGetId([
            'number' => 'TEST-INV-001',
            'delivery_id' => $deliveryId,
            'sales_order_id' => $salesOrderId,
            'customer_id' => $customer->id,
            'invoice_date' => today(),
            'subtotal' => 180,
            'tax_amount' => 18,
            'total_amount' => 198,
            'status' => 'issued',
            'issued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invoiceItemId = DB::table('sales_invoice_items')->insertGetId([
            'sales_invoice_id' => $invoiceId,
            'delivery_item_id' => $deliveryItemId,
            'sales_order_item_id' => $salesOrderItemId,
            'product_id' => $product->id,
            'quantity' => 2,
            'returned_quantity' => 0,
            'unit_price' => 100,
            'discount_amount' => 20,
            'tax_rate' => 10,
            'line_total' => 198,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        StockMovement::query()->create([
            'product_id' => $product->id,
            'delivery_id' => $deliveryId,
            'delivery_item_id' => $deliveryItemId,
            'created_by' => $user->id,
            'movement_type' => 'sales_delivery',
            'quantity' => -2,
            'quantity_before' => 2,
            'quantity_after' => 0,
            'cost_amount' => 40,
        ]);
        ProductStock::query()->create([
            'product_id' => $product->id,
            'quantity' => 0,
            'average_unit_cost' => 20,
        ]);

        $service = app(CustomerReturnService::class);
        $customerReturn = $service->create([
            'sales_invoice_id' => $invoiceId,
            'cash_bank_account_id' => $cashAccount->id,
            'returned_at' => now()->format('Y-m-d H:i:s'),
            'reason' => 'Barang rusak',
            'items' => [['sales_invoice_item_id' => $invoiceItemId, 'quantity' => 1]],
        ], $user->id);

        $this->assertEquals(90, (float) $customerReturn->subtotal);
        $this->assertEquals(9, (float) $customerReturn->tax_amount);
        $this->assertEquals(99, (float) $customerReturn->total_amount);
        $this->assertEquals(1, (float) SalesInvoice::query()->findOrFail($invoiceId)->items()->firstOrFail()->returned_quantity);
        $this->assertEquals(1, (float) ProductStock::query()->where('product_id', $product->id)->value('quantity'));
        $this->assertEquals(20, (float) ProductStock::query()->where('product_id', $product->id)->value('average_unit_cost'));

        $refund = CashBankTransaction::query()->where('customer_return_id', $customerReturn->id)->firstOrFail();
        $refundJournal = JournalEntry::query()->where('source_type', CashBankTransaction::class)->where('source_id', $refund->id)->where('source_action', 'original')->firstOrFail();
        $this->assertEquals(99, (float) $refund->amount);
        $this->assertEquals(99, (float) $refundJournal->lines()->sum('debit'));
        $this->assertEquals(99, (float) $refundJournal->lines()->sum('credit'));
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $refundJournal->id,
            'chart_of_account_id' => $returnsAccount->id,
            'debit' => 90,
        ]);

        try {
            $service->create([
                'sales_invoice_id' => $invoiceId,
                'cash_bank_account_id' => $cashAccount->id,
                'returned_at' => now()->format('Y-m-d H:i:s'),
                'reason' => 'Retur melebihi sisa',
                'items' => [['sales_invoice_item_id' => $invoiceItemId, 'quantity' => 1.01]],
            ], $user->id);
            $this->fail('An over-return must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->assertSame(1, CustomerReturn::query()->count());
        $this->assertSame(1, CashBankTransaction::query()->whereNotNull('customer_return_id')->count());
    }
}
