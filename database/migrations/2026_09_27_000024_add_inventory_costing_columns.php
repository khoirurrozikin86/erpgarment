<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('product_stocks', 'average_unit_cost')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->decimal('average_unit_cost', 18, 6)->default(0)->after('quantity');
            });
        }
        if (!Schema::hasColumn('stock_movements', 'cost_amount')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->decimal('cost_amount', 18, 2)->nullable()->after('quantity_after');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_movements', 'cost_amount')) {
            Schema::table('stock_movements', fn (Blueprint $table) => $table->dropColumn('cost_amount'));
        }
        if (Schema::hasColumn('product_stocks', 'average_unit_cost')) {
            Schema::table('product_stocks', fn (Blueprint $table) => $table->dropColumn('average_unit_cost'));
        }
    }
};
