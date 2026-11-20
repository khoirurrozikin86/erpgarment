<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->string('category', 20)->nullable()->after('size')->index();
        });
    }

    public function down(): void
    {
        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
