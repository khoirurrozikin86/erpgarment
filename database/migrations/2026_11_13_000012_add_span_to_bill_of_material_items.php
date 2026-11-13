<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bill_of_material_items') && ! Schema::hasColumn('bill_of_material_items', 'span')) {
            Schema::table('bill_of_material_items', function (Blueprint $table) {
                $table->decimal('span', 18, 4)->default(0)->after('quantity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bill_of_material_items', 'span')) {
            Schema::table('bill_of_material_items', fn (Blueprint $table) => $table->dropColumn('span'));
        }
    }
};
