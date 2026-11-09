<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->enum('status', ['completed', 'returned', 'voided'])->default('completed')->change();
            $table->foreignId('voided_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->dateTime('voided_at')->nullable()->after('voided_by');
            $table->text('void_reason')->nullable()->after('voided_at');
        });

        Schema::table('cash_bank_transactions', function (Blueprint $table) {
            $table->foreignId('pos_sale_void_id')->nullable()->unique()->after('pos_sale_id')->constrained('pos_sales')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_bank_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pos_sale_void_id');
        });

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'void_reason']);
            $table->enum('status', ['completed', 'returned'])->default('completed')->change();
        });
    }
};
