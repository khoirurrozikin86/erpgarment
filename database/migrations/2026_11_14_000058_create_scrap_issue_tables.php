<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scrap_issue_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 100);
            $table->date('issue_date');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('scrap_product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'number'], 'scrap_issue_company_number_uq');
            $table->index(['company_id', 'issue_date'], 'scrap_issue_company_date_idx');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('scrap_issue_document_id')->nullable();
            $table->index(['scrap_issue_document_id', 'product_id'], 'sm_scrap_issue_idx');
            $table->foreign('scrap_issue_document_id', 'sm_scrap_issue_fk')->references('id')->on('scrap_issue_documents')->restrictOnDelete();
        });

        DB::table('companies')->select('id')->orderBy('id')->each(function ($company) {
            DB::table('document_numberings')->insertOrIgnore([
                'company_id' => $company->id, 'document_type' => 'scrap_issue', 'prefix' => 'SCO',
                'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5,
                'reset_period' => 'monthly', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_movements')) Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign('sm_scrap_issue_fk'); $table->dropIndex('sm_scrap_issue_idx'); $table->dropColumn('scrap_issue_document_id');
        });
        DB::table('document_numberings')->where('document_type', 'scrap_issue')->delete();
        $ids = DB::table('permissions')->where('name', 'like', 'scrap-issues.%')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('scrap_issue_documents');
    }
};
