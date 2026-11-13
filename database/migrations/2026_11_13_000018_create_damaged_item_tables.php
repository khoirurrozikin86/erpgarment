<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('damaged_item_documents', function (Blueprint $table) {
            $table->id();
            $table->string('number', 100)->unique();
            $table->date('document_date')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('damaged_item_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('damaged_item_document_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['damaged_item_document_id', 'product_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('damaged_item_document_id')->nullable()->after('stock_opname_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('damaged_item_line_id')->nullable()->after('damaged_item_document_id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('damaged_item_line_id');
            $table->dropConstrainedForeignId('damaged_item_document_id');
        });
        Schema::dropIfExists('damaged_item_lines');
        Schema::dropIfExists('damaged_item_documents');
    }
};
