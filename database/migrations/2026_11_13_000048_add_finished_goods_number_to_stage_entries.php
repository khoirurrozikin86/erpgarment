<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('production_order_stage_entries') && !Schema::hasColumn('production_order_stage_entries', 'finished_goods_number')) {
            Schema::table('production_order_stage_entries', fn (Blueprint $table) => $table->string('finished_goods_number', 100)->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('production_order_stage_entries') && Schema::hasColumn('production_order_stage_entries', 'finished_goods_number')) {
            Schema::table('production_order_stage_entries', fn (Blueprint $table) => $table->dropColumn('finished_goods_number'));
        }
    }
};
