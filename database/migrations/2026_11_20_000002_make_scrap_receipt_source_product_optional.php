<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scrap_receipt_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('source_product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('scrap_receipt_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('source_product_id')->nullable(false)->change();
        });
    }
};
