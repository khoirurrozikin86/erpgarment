<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_quotation_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient', 150);
            $table->string('subject', 255);
            $table->timestamp('sent_at');
            $table->timestamps();
            $table->index(['sales_quotation_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_quotation_email_logs');
    }
};
