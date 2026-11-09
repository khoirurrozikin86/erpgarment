<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supplier_payments')) {
            Schema::create('supplier_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('number', 100)->unique();
                $table->foreignId('goods_receipt_id')->constrained()->restrictOnDelete();
                $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
                $table->foreignId('cash_bank_account_id')->constrained()->restrictOnDelete();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('payment_date');
                $table->decimal('amount', 18, 2);
                $table->string('reference_number', 150)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['goods_receipt_id', 'payment_date']);
            });
        }

        if (!Schema::hasColumn('cash_bank_transactions', 'supplier_payment_id')) {
            Schema::table('cash_bank_transactions', function (Blueprint $table) {
                $table->foreignId('supplier_payment_id')->nullable()->after('sales_invoice_payment_id')->constrained('supplier_payments')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cash_bank_transactions', 'supplier_payment_id')) {
            Schema::table('cash_bank_transactions', fn (Blueprint $table) => $table->dropConstrainedForeignId('supplier_payment_id'));
        }
        Schema::dropIfExists('supplier_payments');
    }
};
