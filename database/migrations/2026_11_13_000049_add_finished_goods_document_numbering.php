<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('document_numberings') || !Schema::hasTable('companies')) return;
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            DB::table('document_numberings')->insertOrIgnore([
                'company_id' => $companyId, 'document_type' => 'finished_goods', 'prefix' => 'FIN',
                'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5,
                'reset_period' => 'monthly', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_numberings')) DB::table('document_numberings')->where('document_type', 'finished_goods')->delete();
    }
};
