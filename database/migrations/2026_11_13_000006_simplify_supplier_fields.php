<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['fax', 'country'] as $column) {
            if (! Schema::hasColumn('suppliers', $column)) {
                Schema::table('suppliers', fn (Blueprint $table) => $table->string($column, 100)->nullable());
            }
        }

        foreach ([
            'supplier_type', 'website', 'tax_number', 'city', 'province', 'postal_code',
            'payment_term', 'bank_name', 'bank_account_number', 'bank_account_name', 'notes',
        ] as $column) {
            if (! Schema::hasColumn('suppliers', $column)) {
                continue;
            }

            foreach (Schema::getIndexes('suppliers') as $index) {
                if (in_array($column, $index['columns'], true) && ! $index['primary']) {
                    Schema::table('suppliers', fn (Blueprint $table) => $index['unique']
                        ? $table->dropUnique($index['name'])
                        : $table->dropIndex($index['name']));
                }
            }

            Schema::table('suppliers', fn (Blueprint $table) => $table->dropColumn($column));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Supplier details removed by this migration cannot be restored; recover them from a database backup.');
    }
};
