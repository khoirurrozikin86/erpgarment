<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('journal_entries', 'source_type')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->string('source_type', 100)->nullable()->after('description');
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                $table->unique(['company_id', 'source_type', 'source_id'], 'journal_entries_source_unique');
            });
        }

        if (!Schema::hasColumn('cash_bank_accounts', 'chart_of_account_id')) {
            Schema::table('cash_bank_accounts', function (Blueprint $table) {
                $table->foreignId('chart_of_account_id')->nullable()->after('company_id')->constrained('chart_of_accounts')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cash_bank_accounts', 'chart_of_account_id')) {
            Schema::table('cash_bank_accounts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('chart_of_account_id');
            });
        }
        if (Schema::hasColumn('journal_entries', 'source_type')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropUnique('journal_entries_source_unique');
                $table->dropColumn(['source_type', 'source_id']);
            });
        }
    }
};
