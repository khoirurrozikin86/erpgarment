<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_numberings', function (Blueprint $table) {
            $table->timestamp('last_reset_at')
                ->nullable()
                ->after('next_number');
        });
    }

    public function down(): void
    {
        Schema::table('document_numberings', function (Blueprint $table) {
            $table->dropColumn('last_reset_at');
        });
    }
};
