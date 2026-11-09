<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient', 150);
            $table->string('subject', 255);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['purchase_order_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_email_logs');
    }
};
