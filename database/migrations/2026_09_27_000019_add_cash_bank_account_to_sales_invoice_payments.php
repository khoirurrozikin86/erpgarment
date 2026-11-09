<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sales_invoice_payments', 'cash_bank_account_id')) {
            Schema::table('sales_invoice_payments', function (Blueprint $table) {
                $table->foreignId('cash_bank_account_id')->nullable()->after('sales_invoice_id')->constrained()->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('sales_invoice_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_bank_account_id');
        });
    }
};
