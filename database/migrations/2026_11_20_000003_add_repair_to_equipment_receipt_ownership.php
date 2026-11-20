<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_receipt_documents', function (Blueprint $table) {
            $table->enum('ownership_type', ['own', 'rent', 'repair'])->change();
        });
    }

    public function down(): void
    {
        if (DB::table('equipment_receipt_documents')->where('ownership_type', 'repair')->exists()) {
            throw new RuntimeException('Cannot remove the repair category while equipment receipts still use it.');
        }

        Schema::table('equipment_receipt_documents', function (Blueprint $table) {
            $table->enum('ownership_type', ['own', 'rent'])->change();
        });
    }
};
