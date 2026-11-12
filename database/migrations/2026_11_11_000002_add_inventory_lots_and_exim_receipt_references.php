<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lot_number', 100);
            $table->dateTime('received_at');
            $table->decimal('initial_quantity', 18, 4)->default(0);
            $table->decimal('current_quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 6)->default(0);
            $table->string('source_type', 30)->default('receipt');
            $table->timestamps();
            $table->unique(['product_id', 'lot_number'], 'inventory_lots_product_number_uq');
            $table->index(['product_id', 'current_quantity', 'received_at'], 'inventory_lots_fifo_idx');
        });

        Schema::create('stock_movement_lot_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_movement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_lot_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 6)->default(0);
            $table->timestamps();
            $table->index(['inventory_lot_id', 'created_at'], 'lot_allocations_lot_date_idx');
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->string('customs_document_code', 30)->nullable()->after('received_at');
            $table->string('customs_document_number', 100)->nullable()->after('customs_document_code');
            $table->date('customs_document_date')->nullable()->after('customs_document_number');
        });

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->foreignId('inventory_lot_id')->nullable()->after('product_id')->constrained()->restrictOnDelete();
        });

        DB::table('product_stocks')->where('quantity', '>', 0)->orderBy('product_id')->each(function ($stock) {
            DB::table('inventory_lots')->insertOrIgnore([
                'product_id' => $stock->product_id,
                'lot_number' => 'OPENING-' . $stock->product_id,
                'received_at' => $stock->created_at ?? now(),
                'initial_quantity' => $stock->quantity,
                'current_quantity' => $stock->quantity,
                'unit_cost' => $stock->average_unit_cost ?? 0,
                'source_type' => 'opening_balance',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_lot_id');
        });
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn(['customs_document_code', 'customs_document_number', 'customs_document_date']);
        });
        Schema::dropIfExists('stock_movement_lot_allocations');
        Schema::dropIfExists('inventory_lots');
    }
};
