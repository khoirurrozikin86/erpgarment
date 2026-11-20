<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('material_requests', function (Blueprint $t) {
        $t->foreignId('production_order_header_id')->nullable()->constrained('production_order_headers')->restrictOnDelete();
        $t->json('source_details')->nullable();
    }); }
    public function down(): void { Schema::table('material_requests', function (Blueprint $t) {
        $t->dropConstrainedForeignId('production_order_header_id'); $t->dropColumn('source_details');
    }); }
};
