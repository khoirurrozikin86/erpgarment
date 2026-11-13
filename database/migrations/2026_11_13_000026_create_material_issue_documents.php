<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('material_issue_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_request_id')->constrained()->restrictOnDelete();
            $table->string('number', 100);
            $table->date('issue_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'issue_date']);
            $table->index(['material_request_id', 'issue_date']);
        });

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_document_id')->constrained()->restrictOnDelete();
            $table->foreignId('material_request_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->timestamps();
            $table->unique(['material_issue_document_id', 'material_request_item_id'], 'material_issue_document_item_uq');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('material_issue_document_id')->nullable()->after('material_request_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('material_issue_item_id')->nullable()->after('material_issue_document_id')->constrained()->restrictOnDelete();
            $table->index(['material_issue_document_id', 'product_id'], 'stock_movements_material_issue_idx');
        });

        $now = now();
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            DB::table('document_numberings')->insertOrIgnore([
                'company_id' => $companyId, 'document_type' => 'material_issue', 'prefix' => 'MIS',
                'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5,
                'reset_period' => 'monthly', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_material_issue_idx');
            $table->dropConstrainedForeignId('material_issue_item_id');
            $table->dropConstrainedForeignId('material_issue_document_id');
        });
        Schema::dropIfExists('material_issue_items');
        Schema::dropIfExists('material_issue_documents');
        DB::table('document_numberings')->where('document_type', 'material_issue')->delete();
    }
};
