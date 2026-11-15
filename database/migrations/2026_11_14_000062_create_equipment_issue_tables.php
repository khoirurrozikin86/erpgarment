<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipment_issue_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 100);
            $table->date('issue_date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'number'], 'equip_issue_company_number_uq');
            $table->index(['company_id', 'issue_date'], 'equip_issue_company_date_idx');
            $table->foreign('created_by', 'equip_issue_creator_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('equipment_issue_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_issue_document_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('item_code', 100);
            $table->string('item_name', 255);
            $table->string('unit', 50);
            $table->decimal('quantity', 20, 4);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique('equipment_issue_document_id', 'equip_issue_one_item_uq');
            $table->foreign('equipment_issue_document_id', 'equip_issue_item_document_fk')->references('id')->on('equipment_issue_documents')->cascadeOnDelete();
            $table->foreign('product_id', 'equip_issue_item_product_fk')->references('id')->on('products')->nullOnDelete();
        });

        DB::table('companies')->select('id')->orderBy('id')->each(function ($company) {
            DB::table('document_numberings')->insertOrIgnore([
                'company_id' => $company->id, 'document_type' => 'equipment_issue', 'prefix' => 'MCHO',
                'format' => '{PREFIX}/{NUMBER}/{MM}/{YY}', 'next_number' => 1, 'number_length' => 1,
                'reset_period' => 'monthly', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('document_numberings')->where('document_type', 'equipment_issue')->delete();
        Schema::dropIfExists('equipment_issue_items');
        Schema::dropIfExists('equipment_issue_documents');
    }
};
