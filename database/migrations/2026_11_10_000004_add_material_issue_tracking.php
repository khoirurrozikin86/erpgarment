<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            $table->decimal('issued_quantity', 18, 4)->default(0)->after('quantity');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('material_request_item_id')->nullable()->after('pos_sale_item_id')
                ->constrained('material_request_items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_request_item_id');
        });

        Schema::table('material_request_items', function (Blueprint $table) {
            $table->dropColumn('issued_quantity');
        });
    }
};
