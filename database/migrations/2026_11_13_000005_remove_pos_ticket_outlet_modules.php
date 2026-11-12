<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve ledger entries, but remove their links to POS source documents.
        foreach ([
            'cash_bank_transactions' => ['pos_sale_void_id', 'pos_sale_id', 'pos_session_id'],
            'stock_movements' => ['pos_sale_item_id', 'pos_sale_id'],
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
                        $identifier = DB::getDriverName() === 'sqlite' ? $foreignKey['columns'] : $foreignKey['name'];
                        Schema::table($tableName, fn (Blueprint $table) => $table->dropForeign($identifier));
                    }
                }

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
            'scan_records', 'user_outlets',
            'pos_sale_items', 'pos_sales', 'pos_sessions',
            'ticket_qrcodes', 'outlets',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('document_numberings')) {
            DB::table('document_numberings')->where('document_type', 'pos_session')->delete();
        }

        if (Schema::hasTable('permissions')) {
            $permissionNames = DB::table('permissions')->where(function ($query) {
                foreach (['outlets.', 'ticket-qrcode.', 'pos-sessions.', 'pos.', 'pos-sales-report.', 'user-outlets.', 'scan-records.'] as $prefix) {
                    $query->orWhere('name', 'like', $prefix . '%');
                }
                $query->orWhere('name', 'sales-report.view');
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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        throw new RuntimeException('POS, Ticket QR Code, and Outlet tables/data were removed; restore them from a database backup instead of rolling back.');
    }
};
