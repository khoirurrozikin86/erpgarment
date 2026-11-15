<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subcontract_semi_finished_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 100);
            $table->date('production_date');
            $table->foreignId('production_order_id')->nullable();
            $table->string('buyer_po_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'number'], 'sub_sfg_company_no_uq');
            $table->index(['company_id', 'production_date'], 'sub_sfg_company_date_idx');
            $table->foreign('production_order_id', 'sub_sfg_mo_fk')->references('id')->on('production_orders')->restrictOnDelete();
        });

        Schema::create('subcontract_semi_finished_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id');
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->timestamps();
            $table->foreign('document_id', 'sub_sfg_document_fk')->references('id')->on('subcontract_semi_finished_documents')->cascadeOnDelete();
            $table->index(['product_id', 'document_id'], 'sub_sfg_product_doc_idx');
        });

        DB::table('companies')->select('id')->orderBy('id')->each(function ($company) {
            DB::table('document_numberings')->insertOrIgnore([
                'company_id' => $company->id, 'document_type' => 'subcontract_semi_finished',
                'prefix' => 'TIMW', 'format' => '{PREFIX}/{NUMBER}/HBSJD/{MM}/{YY}',
                'next_number' => 1, 'number_length' => 1, 'reset_period' => 'monthly',
                'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('document_numberings')->where('document_type', 'subcontract_semi_finished')->delete();
        Schema::dropIfExists('subcontract_semi_finished_items');
        Schema::dropIfExists('subcontract_semi_finished_documents');
    }
};
