<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_numberings', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'document_type'],
                'document_numberings_company_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('document_numberings', function (Blueprint $table) {
            $table->dropUnique(
                'document_numberings_company_type_unique'
            );
        });
    }
};
