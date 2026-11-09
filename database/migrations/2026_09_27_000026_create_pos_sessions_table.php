<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_sessions')) {
            Schema::create('pos_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('number', 100);
                $table->foreignId('cash_bank_account_id')->constrained()->restrictOnDelete();
                $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('opened_at');
                $table->dateTime('closed_at')->nullable();
                $table->decimal('opening_cash', 18, 2)->default(0);
                $table->decimal('expected_cash', 18, 2)->nullable();
                $table->decimal('counted_cash', 18, 2)->nullable();
                $table->decimal('cash_difference', 18, 2)->nullable();
                $table->enum('status', ['open', 'closed'])->default('open')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'number']);
                $table->index(['company_id', 'cash_bank_account_id', 'status']);
            });
        }

        if (!Schema::hasColumn('cash_bank_transactions', 'pos_session_id')) {
            Schema::table('cash_bank_transactions', function (Blueprint $table) {
                $table->foreignId('pos_session_id')->nullable()->after('supplier_payment_id')->constrained('pos_sessions')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cash_bank_transactions', 'pos_session_id')) {
            Schema::table('cash_bank_transactions', fn (Blueprint $table) => $table->dropConstrainedForeignId('pos_session_id'));
        }
        Schema::dropIfExists('pos_sessions');
    }
};
