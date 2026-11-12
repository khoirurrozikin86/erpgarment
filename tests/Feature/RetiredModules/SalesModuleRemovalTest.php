<?php

namespace Tests\Feature\RetiredModules;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesModuleRemovalTest extends TestCase
{
    use RefreshDatabase;
    public function test_sales_and_price_list_tables_and_routes_are_removed_while_ledgers_remain(): void
    {
        $this->assertFalse(Schema::hasTable('price_lists'));
        $this->assertFalse(Schema::hasTable('price_list_items'));
        foreach ([
            'sales_quotations', 'sales_quotation_items', 'sales_quotation_email_logs',
            'sales_orders', 'sales_order_items', 'deliveries', 'delivery_items',
            'sales_invoices', 'sales_invoice_items', 'sales_invoice_payments', 'sales_invoice_email_logs',
            'customer_returns', 'customer_return_items',
        ] as $table) {
            $this->assertFalse(Schema::hasTable($table), "Expected {$table} to be removed.");
        }

        foreach ([
            'super.sales-quotations.index', 'super.sales-orders.index', 'super.deliveries.index',
            'super.sales-invoices.index', 'super.customer-returns.index', 'super.account-receivable.index',
            'super.pricelists.index', 'super.reports.sales.index',
        ] as $route) {
            $this->assertFalse(Route::has($route), "Expected {$route} to be removed.");
        }

        $this->assertTrue(Schema::hasTable('stock_movements'));
        $this->assertTrue(Schema::hasTable('cash_bank_transactions'));
        foreach (['delivery_id', 'delivery_item_id', 'customer_return_id', 'customer_return_item_id'] as $column) {
            $this->assertFalse(Schema::hasColumn('stock_movements', $column));
        }
        foreach (['sales_invoice_payment_id', 'customer_return_id'] as $column) {
            $this->assertFalse(Schema::hasColumn('cash_bank_transactions', $column));
        }
    }
}
