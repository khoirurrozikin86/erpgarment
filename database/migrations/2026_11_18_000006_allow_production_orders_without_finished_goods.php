<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('output_product_id')->nullable()->change();
            $table->unsignedBigInteger('bill_of_material_id')->nullable()->change();
            $table->string('bom_version', 30)->nullable()->change();
            $table->string('style_code', 80)->nullable();
            $table->string('order_color', 80)->nullable();
            $table->string('order_size', 40)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('production_orders')->whereNull('output_product_id')->orWhereNull('bill_of_material_id')->orWhereNull('bom_version')->exists()) {
            throw new \RuntimeException('Resolve unlinked production orders before reverting this migration.');
        }

        Schema::table('production_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('output_product_id')->nullable(false)->change();
            $table->unsignedBigInteger('bill_of_material_id')->nullable(false)->change();
            $table->string('bom_version', 30)->nullable(false)->change();
            $table->dropColumn(['style_code', 'order_color', 'order_size']);
        });
    }
};
