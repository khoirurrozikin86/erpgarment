<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['fax', 'country'] as $column) {
            if (! Schema::hasColumn('customers', $column)) {
                Schema::table('customers', fn (Blueprint $table) => $table->string($column, 100)->nullable());
            }
        }

        $removedColumns = [
            'customer_type', 'website', 'tax_number', 'city', 'province',
            'postal_code', 'payment_term', 'credit_limit', 'notes',
        ];

        foreach (Schema::getForeignKeys('customers') as $foreignKey) {
            if (array_intersect($foreignKey['columns'], $removedColumns)) {
                Schema::table('customers', fn (Blueprint $table) => $table->dropForeign($foreignKey['name']));
            }
        }

        foreach (Schema::getIndexes('customers') as $index) {
            if (! $index['primary'] && array_intersect($index['columns'], $removedColumns)) {
                Schema::table('customers', fn (Blueprint $table) => $index['unique']
                    ? $table->dropUnique($index['name'])
                    : $table->dropIndex($index['name']));
            }
        }

        $existingColumns = array_values(array_filter($removedColumns, fn (string $column) => Schema::hasColumn('customers', $column)));
        if ($existingColumns !== []) {
            Schema::table('customers', fn (Blueprint $table) => $table->dropColumn($existingColumns));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Customer details removed by this migration cannot be restored; recover them from a database backup.');
    }
};
