<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep stock and cash/bank ledger rows, but detach them from deleted source documents.
        foreach ([
            'cash_bank_transactions' => ['sales_invoice_payment_id', 'customer_return_id'],
            'stock_movements' => ['delivery_item_id', 'delivery_id', 'customer_return_item_id', 'customer_return_id'],
        ] as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($tableName, $column)) {
                    continue;
                }

                foreach (Schema::getForeignKeys($tableName) as $foreignKey) {
                    if (in_array($column, $foreignKey['columns'], true)) {
                        $foreignKeyIdentifier = DB::getDriverName() === 'sqlite' ? $foreignKey['columns'] : $foreignKey['name'];
                        Schema::table($tableName, fn (Blueprint $table) => $table->dropForeign($foreignKeyIdentifier));
                    }
                }

                // Existing installations may use custom index names or may lack the index.
                foreach (Schema::getIndexes($tableName) as $index) {
                    if (! in_array($column, $index['columns'], true) || $index['primary']) {
                        continue;
                    }

                    Schema::table($tableName, fn (Blueprint $table) => $index['unique']
                        ? $table->dropUnique($index['name'])
                        : $table->dropIndex($index['name']));
                }

                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($column));
            }
        }

        foreach ([
            'customer_return_items', 'customer_returns',
            'sales_invoice_email_logs', 'sales_invoice_items', 'sales_invoice_payments', 'sales_invoices',
            'delivery_items', 'deliveries',
            'sales_order_items', 'sales_orders',
            'sales_quotation_email_logs', 'sales_quotation_items', 'sales_quotations',
            'price_list_items', 'price_lists',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('document_numberings')) {
            DB::table('document_numberings')->whereIn('document_type', ['sales_order', 'sales_quotation', 'delivery_order', 'sales_invoice', 'customer_payment', 'customer_return'])->delete();
        }

        $permissionNames = DB::table('permissions')->where(function ($query) {
            foreach (['pricelists.', 'sales-quotations.', 'sales-orders.', 'deliveries.', 'sales-invoices.', 'customer-returns.', 'account-receivable.'] as $prefix) {
                $query->orWhere('name', 'like', $prefix . '%');
            }
        })->pluck('name');

        if ($permissionNames->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', function ($query) use ($permissionNames) {
                $query->select('id')->from('permissions')->whereIn('name', $permissionNames);
            })->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', function ($query) use ($permissionNames) {
                $query->select('id')->from('permissions')->whereIn('name', $permissionNames);
            })->delete();
            DB::table('permissions')->whereIn('name', $permissionNames)->delete();
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Sales and Price List tables were permanently removed by this migration; restore them from a database backup instead of rolling back.');
    }
};
