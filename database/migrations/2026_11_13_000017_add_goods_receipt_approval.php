<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('notes');
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->string('lot_number', 100)->nullable()->after('inventory_lot_id');
        });

        // Existing receipts already posted their stock and journals before approval existed.
        DB::table('goods_receipts')->update(['status' => 'approved']);
        DB::table('goods_receipt_items')->whereNotNull('inventory_lot_id')->orderBy('id')->chunkById(500, function ($items) {
            $lots = DB::table('inventory_lots')->whereIn('id', $items->pluck('inventory_lot_id'))->pluck('lot_number', 'id');
            foreach ($items as $item) {
                if (isset($lots[$item->inventory_lot_id])) {
                    DB::table('goods_receipt_items')->where('id', $item->id)->update(['lot_number' => $lots[$item->inventory_lot_id]]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->dropColumn('lot_number');
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['status', 'approved_at']);
        });
    }
};
