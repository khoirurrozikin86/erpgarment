<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Separate steps also allow resuming after MySQL commits a partial DDL change.
        if (!Schema::hasColumn('products', 'parent_product_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('parent_product_id')->nullable()->constrained('products')->restrictOnDelete();
                $table->string('variant_key', 64)->nullable();
                $table->unique(['parent_product_id', 'variant_key'], 'products_parent_variant_unique');
            });
        }
        if (!Schema::hasColumn('bill_of_material_items', 'applies_to_color')) {
            Schema::table('bill_of_material_items', function (Blueprint $table) {
                $table->string('applies_to_color', 80)->nullable();
                $table->string('applies_to_size', 40)->nullable();
            });
        }
        if (!Schema::hasIndex('bill_of_material_items', 'bom_item_variant_uq')) {
            Schema::table('bill_of_material_items', function (Blueprint $table) {
                $table->unique(['bill_of_material_id', 'product_id', 'operation', 'applies_to_color', 'applies_to_size'], 'bom_item_variant_uq');
            });
        }
        if (Schema::hasIndex('bill_of_material_items', 'bom_item_product_operation_uq')) {
            Schema::table('bill_of_material_items', fn (Blueprint $table) => $table->dropUnique('bom_item_product_operation_uq'));
        }
    }

    public function down(): void
    {
        // Restore the narrower constraint first. Rollback stops if newer selector rows conflict.
        Schema::table('bill_of_material_items', fn (Blueprint $table) => $table->unique(['bill_of_material_id', 'product_id', 'operation'], 'bom_item_product_operation_uq'));
        Schema::table('bill_of_material_items', function (Blueprint $table) {
            $table->dropUnique('bom_item_variant_uq');
            $table->dropColumn(['applies_to_color', 'applies_to_size']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_parent_variant_unique');
            $table->dropConstrainedForeignId('parent_product_id');
            $table->dropColumn('variant_key');
        });
    }
};
