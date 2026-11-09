<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('cash_bank_transactions', 'counter_chart_account_id')) {
            Schema::table('cash_bank_transactions', function (Blueprint $table) {
                $table->foreignId('counter_chart_account_id')->nullable()->after('cash_bank_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            });
        }

        if (!Schema::hasColumn('journal_entries', 'source_action')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropUnique('journal_entries_source_unique');
                $table->string('source_action', 100)->nullable()->after('source_id');
                $table->unique(['company_id', 'source_type', 'source_id', 'source_action'], 'journal_entries_source_action_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('journal_entries', 'source_action')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropUnique('journal_entries_source_action_unique');
                $table->dropColumn('source_action');
                $table->unique(['company_id', 'source_type', 'source_id'], 'journal_entries_source_unique');
            });
        }
        if (Schema::hasColumn('cash_bank_transactions', 'counter_chart_account_id')) {
            Schema::table('cash_bank_transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('counter_chart_account_id');
            });
        }
    }
};
