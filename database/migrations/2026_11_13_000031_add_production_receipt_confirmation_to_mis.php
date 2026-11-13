<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('material_issue_documents', function (Blueprint $table) {
            $table->string('production_receipt_status', 20)->default('pending')->after('issue_date')->index();
            $table->foreignId('production_received_by')->nullable()->after('production_receipt_status')->constrained('users')->nullOnDelete();
            $table->timestamp('production_received_at')->nullable()->after('production_received_by');
        });
    }

    public function down(): void
    {
        Schema::table('material_issue_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_received_by');
            $table->dropIndex(['production_receipt_status']);
            $table->dropColumn(['production_receipt_status', 'production_received_at']);
        });
    }
};
