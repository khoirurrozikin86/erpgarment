<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scrap_receipt_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 100);
            $table->date('receipt_date');
            $table->foreignId('scrap_product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('source_product_id')->constrained('products')->restrictOnDelete();
            $table->string('production_number', 100)->nullable();
            $table->decimal('quantity', 20, 4);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'number'], 'scrap_receipt_company_number_uq');
            $table->index(['company_id', 'receipt_date'], 'scrap_receipt_company_date_idx');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('scrap_receipt_document_id')->nullable();
            $table->index(['scrap_receipt_document_id', 'product_id'], 'sm_scrap_receipt_idx');
            $table->foreign('scrap_receipt_document_id', 'sm_scrap_receipt_doc_fk')
                ->references('id')->on('scrap_receipt_documents')->restrictOnDelete();
        });

        DB::table('companies')->select('id')->orderBy('id')->each(function ($company) {
            DB::table('document_numberings')->insertOrIgnore([
                'company_id' => $company->id,
                'document_type' => 'scrap_receipt',
                'prefix' => 'SCI',
                'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}',
                'next_number' => 1,
                'number_length' => 5,
                'reset_period' => 'monthly',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropForeign('sm_scrap_receipt_doc_fk');
                $table->dropIndex('sm_scrap_receipt_idx');
                $table->dropColumn('scrap_receipt_document_id');
            });
        }
        DB::table('document_numberings')->where('document_type', 'scrap_receipt')->delete();
        $permissionIds = DB::table('permissions')->where('name', 'like', 'scrap-receipts.%')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        Schema::dropIfExists('scrap_receipt_documents');
    }
};
