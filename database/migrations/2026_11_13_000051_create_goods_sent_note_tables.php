<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goods_sent_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 100);
            $table->date('sent_date');
            $table->string('contract_number', 100)->nullable();
            $table->date('contract_date')->nullable();
            $table->string('delivery_route', 100)->nullable();
            $table->string('warehouse', 150)->nullable();
            $table->string('vehicle_number', 100)->nullable();
            $table->string('driver_phone', 50)->nullable();
            $table->string('driver_name', 150)->nullable();
            $table->string('seal_number', 100)->nullable();
            $table->string('receiver_type', 100)->nullable();
            $table->string('container_number', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'number'], 'gsn_company_no_uq');
            $table->index(['company_id', 'sent_date'], 'gsn_company_date_idx');
        });

        Schema::create('goods_sent_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_sent_note_id')->constrained('goods_sent_notes')->cascadeOnDelete();
            $table->foreignId('production_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('cartons', 18, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['production_order_id', 'product_id'], 'gsn_order_product_idx');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('goods_sent_note_id')->nullable()->after('production_order_stage_entry_id');
            $table->foreignId('goods_sent_note_item_id')->nullable()->after('goods_sent_note_id');
            $table->foreign('goods_sent_note_id', 'stock_mov_gsn_fk')->references('id')->on('goods_sent_notes')->restrictOnDelete();
            $table->foreign('goods_sent_note_item_id', 'stock_mov_gsn_item_fk')->references('id')->on('goods_sent_note_items')->restrictOnDelete();
        });

        if (Schema::hasTable('document_numberings') && Schema::hasTable('companies')) {
            foreach (DB::table('companies')->pluck('id') as $companyId) {
                DB::table('document_numberings')->insertOrIgnore([
                    'company_id' => $companyId, 'document_type' => 'goods_sent_note', 'prefix' => 'GSN',
                    'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5,
                    'reset_period' => 'monthly', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign('stock_mov_gsn_item_fk');
            $table->dropForeign('stock_mov_gsn_fk');
            $table->dropColumn(['goods_sent_note_item_id', 'goods_sent_note_id']);
        });
        Schema::dropIfExists('goods_sent_note_items');
        Schema::dropIfExists('goods_sent_notes');
        if (Schema::hasTable('document_numberings')) DB::table('document_numberings')->where('document_type', 'goods_sent_note')->delete();
    }
};
