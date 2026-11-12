<?php

namespace Tests\Feature\RetiredModules;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PosOutletRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_ticket_and_outlet_tables_routes_and_permissions_are_removed_but_ledgers_remain(): void
    {
        foreach ([
            'outlets', 'ticket_qrcodes', 'user_outlets', 'scan_records',
            'pos_sessions', 'pos_sales', 'pos_sale_items',
        ] as $table) {
            $this->assertFalse(Schema::hasTable($table), "Expected {$table} to be removed.");
        }

        foreach ([
            'super.pos.index', 'super.pos.checkout', 'super.pos-sessions.index',
            'super.reports.pos-sales.index', 'super.outlets.index',
        ] as $route) {
            $this->assertFalse(Route::has($route), "Expected {$route} to be removed.");
        }

        foreach (['pos_session_id', 'pos_sale_id', 'pos_sale_void_id'] as $column) {
            $this->assertFalse(Schema::hasColumn('cash_bank_transactions', $column));
        }
        foreach (['pos_sale_id', 'pos_sale_item_id'] as $column) {
            $this->assertFalse(Schema::hasColumn('stock_movements', $column));
        }
        $this->assertTrue(Schema::hasTable('cash_bank_transactions'));
        $this->assertTrue(Schema::hasTable('stock_movements'));
        $this->assertTrue(Schema::hasTable('journal_entries'));
        $this->assertFalse(Permission::where('name', 'pos.view')->exists());
        $this->assertFalse(Permission::where('name', 'outlets.view')->exists());
        $this->assertFalse(Permission::where('name', 'ticket-qrcode.view')->exists());
    }
}
