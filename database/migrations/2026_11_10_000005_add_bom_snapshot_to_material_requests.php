<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->foreignId('bill_of_material_id')->nullable()->after('requested_by')->constrained('bill_of_materials')->restrictOnDelete();
            $table->foreignId('output_product_id')->nullable()->after('bill_of_material_id')->constrained('products')->restrictOnDelete();
            $table->string('bom_version', 30)->nullable()->after('output_product_id');
            $table->decimal('production_quantity', 18, 4)->nullable()->after('bom_version');
        });
    }

    public function down(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bill_of_material_id');
            $table->dropConstrainedForeignId('output_product_id');
            $table->dropColumn(['bom_version', 'production_quantity']);
        });
    }
};
