<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cash_bank_accounts')) {
            Schema::create('cash_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('code', 50);
                $table->string('name', 150);
                $table->enum('type', ['cash', 'bank']);
                $table->string('bank_name', 100)->nullable();
                $table->string('account_number', 100)->nullable();
                $table->string('account_name', 150)->nullable();
                $table->decimal('opening_balance', 18, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'code']);
                $table->index(['company_id', 'is_active', 'type']);
            });
        }

        if (!Schema::hasTable('cash_bank_transactions')) {
            Schema::create('cash_bank_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cash_bank_account_id')->constrained()->restrictOnDelete();
                $table->foreignId('sales_invoice_payment_id')->nullable()->unique()->constrained()->restrictOnDelete();
                $table->date('transaction_date');
                $table->enum('direction', ['in', 'out']);
                $table->decimal('amount', 18, 2);
                $table->string('description', 255);
                $table->string('reference_number', 150)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['company_id', 'cash_bank_account_id', 'transaction_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_bank_transactions');
        Schema::dropIfExists('cash_bank_accounts');
    }
};
