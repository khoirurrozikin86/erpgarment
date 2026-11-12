<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_stage_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 30);
            $table->decimal('input_quantity', 18, 4);
            $table->decimal('good_quantity', 18, 4)->default(0);
            $table->decimal('rejected_quantity', 18, 4)->default(0);
            $table->decimal('waste_quantity', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at');
            $table->timestamps();
            $table->index(['production_order_id', 'stage', 'processed_at'], 'prod_order_stage_date_idx');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('production_order_stage_entry_id')->nullable()->after('material_request_item_id')
                ->constrained('production_order_stage_entries')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_order_stage_entry_id');
        });
        Schema::dropIfExists('production_order_stage_entries');
    }
};
