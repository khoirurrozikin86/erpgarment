<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_order_materials', function (Blueprint $table) {
            $table->foreignId('bill_of_material_id')->nullable()->after('production_order_id')
                ->constrained('bill_of_materials')->restrictOnDelete();
            $table->index(['production_order_id', 'bill_of_material_id', 'product_id'], 'prod_order_material_bom_product_idx');
        });

        DB::table('production_order_materials')->orderBy('id')->chunkById(200, function ($materials) {
            foreach ($materials as $material) {
                $bomId = DB::table('production_orders')->where('id', $material->production_order_id)->value('bill_of_material_id');
                DB::table('production_order_materials')->where('id', $material->id)->update(['bill_of_material_id' => $bomId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_order_materials', function (Blueprint $table) {
            $table->dropIndex('prod_order_material_bom_product_idx');
            $table->dropConstrainedForeignId('bill_of_material_id');
        });
    }
};
