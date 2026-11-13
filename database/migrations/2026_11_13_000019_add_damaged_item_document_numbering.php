<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            $exists = DB::table('document_numberings')->where('company_id', $companyId)->where('document_type', 'damaged_item')->exists();
            if (!$exists) {
                DB::table('document_numberings')->insert([
                    'company_id' => $companyId,
                    'document_type' => 'damaged_item',
                    'prefix' => 'BRSK',
                    'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}',
                    'next_number' => 1,
                    'number_length' => 5,
                    'reset_period' => 'monthly',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('document_numberings')
            ->where('document_type', 'damaged_item')
            ->where('prefix', 'BRSK')
            ->where('next_number', 1)
            ->delete();
    }
};
