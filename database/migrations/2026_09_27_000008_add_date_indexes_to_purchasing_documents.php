<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index('order_date');
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', fn (Blueprint $table) => $table->dropIndex(['received_at']));
        Schema::table('purchase_orders', fn (Blueprint $table) => $table->dropIndex(['order_date']));
        Schema::table('purchase_requests', fn (Blueprint $table) => $table->dropIndex(['created_at']));
        Schema::table('material_requests', fn (Blueprint $table) => $table->dropIndex(['created_at']));
    }
};
