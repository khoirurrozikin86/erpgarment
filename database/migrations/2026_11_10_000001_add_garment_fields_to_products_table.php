<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('garment_item_type', 32)->default('general')->after('product_type');
            $table->string('style_code', 80)->nullable()->after('garment_item_type');
            $table->string('color', 80)->nullable()->after('style_code');
            $table->string('size', 40)->nullable()->after('color');
            $table->string('hs_code', 30)->nullable()->after('size');
            $table->index(['garment_item_type', 'style_code', 'color', 'size'], 'products_garment_variant_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_garment_variant_index');
            $table->dropColumn(['garment_item_type', 'style_code', 'color', 'size', 'hs_code']);
        });
    }
};
