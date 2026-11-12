<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('version', 30)->default('1');
            $table->string('name', 150)->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->decimal('output_quantity', 18, 4)->default(1);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'product_id', 'version'], 'bom_company_product_version_uq');
            $table->index(['company_id', 'status', 'effective_from'], 'bom_company_status_from_idx');
        });

        Schema::create('bill_of_material_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_material_id')->constrained('bill_of_materials')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('wastage_percent', 7, 3)->default(0);
            $table->string('operation', 40)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['bill_of_material_id', 'product_id', 'operation'], 'bom_item_product_operation_uq');
            $table->index('product_id', 'bom_items_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_of_material_items');
        Schema::dropIfExists('bill_of_materials');
    }
};
