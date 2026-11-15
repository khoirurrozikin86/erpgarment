<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipment_receipt_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 100);
            $table->date('receipt_date');
            $table->enum('ownership_type', ['own', 'rent']);
            $table->string('invoice_number', 150)->nullable();
            $table->date('invoice_date')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('currency_code', 10)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'number'], 'equip_receipt_company_number_uq');
            $table->index(['company_id', 'receipt_date'], 'equip_receipt_company_date_idx');
            $table->foreign('supplier_id', 'equip_receipt_supplier_fk')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('created_by', 'equip_receipt_creator_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('equipment_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_receipt_document_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('item_code', 100);
            $table->string('item_name', 255);
            $table->string('unit', 50);
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4)->default(0);
            $table->decimal('total_price', 20, 4)->default(0);
            $table->string('supplier_po_number', 150)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['equipment_receipt_document_id', 'id'], 'equip_receipt_items_document_idx');
            $table->foreign('equipment_receipt_document_id', 'equip_receipt_items_document_fk')->references('id')->on('equipment_receipt_documents')->cascadeOnDelete();
            $table->foreign('product_id', 'equip_receipt_items_product_fk')->references('id')->on('products')->nullOnDelete();
        });

        DB::table('companies')->select('id')->orderBy('id')->each(function ($company) {
            DB::table('document_numberings')->insertOrIgnore([
                'company_id' => $company->id, 'document_type' => 'equipment_receipt', 'prefix' => 'MCHI',
                'format' => '{PREFIX}/{NUMBER}/{MM}/{YY}', 'next_number' => 1, 'number_length' => 1,
                'reset_period' => 'monthly', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('document_numberings')->where('document_type', 'equipment_receipt')->delete();
        Schema::dropIfExists('equipment_receipt_items');
        Schema::dropIfExists('equipment_receipt_documents');
    }
};
