<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 100)
                ->nullable()
                ->unique()
                ->after('barcode');

            $table->string('short_name', 100)
                ->nullable()
                ->after('name');

            $table->decimal('reorder_point', 18, 4)
                ->default(0)
                ->after('max_stock');

            $table->boolean('track_stock')
                ->default(true)
                ->after('reorder_point');

            $table->decimal('tax_rate', 5, 2)
                ->default(0)
                ->after('taxable');

            $table->boolean('allow_discount')
                ->default(true)
                ->after('tax_rate');

            $table->boolean('allow_purchase')
                ->default(true)
                ->after('allow_discount');

            $table->boolean('allow_sales')
                ->default(true)
                ->after('allow_purchase');

            $table->index('track_stock');
            $table->index('allow_purchase');
            $table->index('allow_sales');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_track_stock_index');
            $table->dropIndex('products_allow_purchase_index');
            $table->dropIndex('products_allow_sales_index');

            $table->dropUnique('products_sku_unique');

            $table->dropColumn([
                'sku',
                'short_name',
                'reorder_point',
                'track_stock',
                'tax_rate',
                'allow_discount',
                'allow_purchase',
                'allow_sales',
            ]);
        });
    }
};
