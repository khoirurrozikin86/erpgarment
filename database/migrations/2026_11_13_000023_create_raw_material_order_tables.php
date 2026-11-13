<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('raw_material_order_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 100);
            $table->date('order_date')->index();
            $table->string('production_order_number', 100)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'order_date']);
        });

        Schema::create('raw_material_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_order_document_id')->constrained()->restrictOnDelete();
            $table->foreignId('production_order_material_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('style_code', 100)->nullable();
            $table->decimal('quantity', 18, 4);
            $table->timestamps();
            $table->unique(['raw_material_order_document_id', 'production_order_material_id'], 'raw_material_order_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_order_items');
        Schema::dropIfExists('raw_material_order_documents');
    }
};
