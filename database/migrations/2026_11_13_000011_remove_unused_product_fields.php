<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'barcode', 'sku', 'short_name', 'hs_code', 'composition',
        'fabric_width_cm', 'fabric_weight_gsm', 'country_of_origin',
        'sales_price', 'taxable', 'tax_rate', 'allow_discount',
        'allow_purchase', 'allow_sales', 'description',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $present = array_values(array_intersect($this->columns, Schema::getColumnListing('products')));
        if ($present === []) {
            return;
        }

        foreach (Schema::getIndexes('products') as $index) {
            if (array_intersect($index['columns'] ?? [], $present) !== []) {
                Schema::table('products', fn (Blueprint $table) => $table->dropIndex($index['name']));
            }
        }

        Schema::table('products', fn (Blueprint $table) => $table->dropColumn($present));
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $missing = array_values(array_diff($this->columns, Schema::getColumnListing('products')));
        if ($missing === []) {
            return;
        }

        Schema::table('products', function (Blueprint $table) use ($missing) {
            foreach ($missing as $column) {
                match ($column) {
                    'barcode' => $table->string('barcode', 100)->nullable(),
                    'sku' => $table->string('sku', 100)->nullable(),
                    'short_name' => $table->string('short_name', 100)->nullable(),
                    'hs_code' => $table->string('hs_code', 30)->nullable(),
                    'composition' => $table->string('composition', 255)->nullable(),
                    'fabric_width_cm', 'fabric_weight_gsm' => $table->decimal($column, 12, 2)->nullable(),
                    'country_of_origin' => $table->string('country_of_origin', 100)->nullable(),
                    'sales_price' => $table->decimal('sales_price', 18, 2)->default(0),
                    'taxable', 'allow_discount', 'allow_purchase', 'allow_sales' => $table->boolean($column)->default(false),
                    'tax_rate' => $table->decimal('tax_rate', 5, 2)->default(0),
                    'description' => $table->text('description')->nullable(),
                };
            }
        });
    }
};
