<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            if (!DB::table('document_numberings')->where('company_id', $companyId)->where('document_type', 'raw_material_order')->exists()) {
                DB::table('document_numberings')->insert([
                    'company_id' => $companyId, 'document_type' => 'raw_material_order', 'prefix' => 'OBB',
                    'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5,
                    'reset_period' => 'monthly', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('document_numberings')->where('document_type', 'raw_material_order')->where('prefix', 'OBB')->where('next_number', 1)->delete();
    }
};
