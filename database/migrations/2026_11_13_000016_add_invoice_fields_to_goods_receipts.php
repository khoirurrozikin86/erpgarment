<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->string('invoice_number', 100)->nullable()->after('received_at');
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->string('currency', 10)->nullable()->after('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'invoice_date', 'currency']);
        });
    }
};
