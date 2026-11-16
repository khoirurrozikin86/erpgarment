<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subcontractors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'name'], 'subcontractors_company_name_uq');
        });

        Schema::create('subcontract_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcontractor_id')->constrained('subcontractors')->restrictOnDelete();
            $table->string('contract_number', 100);
            $table->string('bc_decree_number', 150)->nullable();
            $table->text('description')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('quota', 20, 4)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'contract_number'], 'subcontract_contract_no_uq');
            $table->index(['company_id', 'starts_on'], 'subcontract_contract_date_idx');
        });

        DB::table('companies')->select('id')->orderBy('id')->each(function ($company) {
            DB::table('document_numberings')->insertOrIgnore([
                'company_id' => $company->id, 'document_type' => 'subcontract_contract',
                'prefix' => 'SDI', 'format' => '{PREFIX}/{NUMBER}/KTRKS/{MM}/{YY}',
                'next_number' => 1, 'number_length' => 1, 'reset_period' => 'monthly',
                'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('document_numberings')->where('document_type', 'subcontract_contract')->delete();
        Schema::dropIfExists('subcontract_contracts');
        Schema::dropIfExists('subcontractors');
    }
};
