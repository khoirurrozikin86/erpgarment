<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wip_issue_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 100);
            $table->date('transaction_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'number'], 'wip_issue_company_number_uq');
            $table->index(['company_id', 'transaction_date'], 'wip_issue_company_date_idx');
        });

        Schema::create('wip_issue_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('wip_issue_documents')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['document_id', 'product_id'], 'wip_issue_line_doc_product_idx');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('wip_issue_document_id')->nullable();
            $table->unsignedBigInteger('wip_issue_line_id')->nullable();
            $table->index(['wip_issue_document_id', 'product_id'], 'stock_movements_wip_issue_idx');
            $table->foreign('wip_issue_document_id', 'sm_wip_issue_doc_fk')->references('id')->on('wip_issue_documents')->restrictOnDelete();
            $table->foreign('wip_issue_line_id', 'sm_wip_issue_line_fk')->references('id')->on('wip_issue_lines')->restrictOnDelete();
        });

        DB::table('companies')->select('id')->orderBy('id')->each(function ($company) {
            DB::table('document_numberings')->insertOrIgnore(
                ['company_id' => $company->id, 'document_type' => 'wip_issue'],
                ['prefix' => 'WIP', 'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5, 'reset_period' => 'monthly', 'is_active' => 1, 'updated_at' => now(), 'created_at' => now()],
            );
        });

    }

    public function down(): void
    {
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropForeign('sm_wip_issue_line_fk');
                $table->dropForeign('sm_wip_issue_doc_fk');
                $table->dropIndex('stock_movements_wip_issue_idx');
                $table->dropColumn(['wip_issue_line_id', 'wip_issue_document_id']);
            });
        }
        DB::table('document_numberings')->where('document_type', 'wip_issue')->delete();
        $ids = DB::table('permissions')->where('name', 'like', 'wip-issues.%')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('wip_issue_lines');
        Schema::dropIfExists('wip_issue_documents');
    }
};
