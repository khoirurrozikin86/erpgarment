<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('production_order_headers')) Schema::create('production_order_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('number', 100);
            $table->string('buyer_po_number', 100)->nullable(); $table->string('no_cbd', 100)->nullable();
            $table->string('currency', 10); $table->string('destination', 150)->nullable();
            $table->date('po_date'); $table->date('due_date')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps(); $table->unique(['company_id', 'number'], 'mo_header_company_number_unique');
        });
        if (!Schema::hasColumn('production_orders', 'production_order_header_id')) Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('production_order_header_id')->nullable()->constrained('production_order_headers')->restrictOnDelete();
        });
        // Keep all existing detail IDs: downstream material/production/stock references remain intact.
        DB::transaction(function () {
            DB::table('production_orders')->whereNull('production_order_header_id')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $identity = ['company_id' => $row->company_id, 'number' => $row->number];
                    $header = DB::table('production_order_headers')->where($identity)->first();
                    if (!$header) {
                        $attributes = array_intersect_key((array) $row, array_flip(['buyer_po_number','no_cbd','currency','destination','po_date','due_date','customer_id']));
                        $id = DB::table('production_order_headers')->insertGetId($identity + $attributes + ['created_at' => $row->created_at, 'updated_at' => now()]);
                    } else $id = $header->id;
                    DB::table('production_orders')->where('id', $row->id)->update(['production_order_header_id' => $id]);
                }
            });
        });
    }
    public function down(): void
    {
        Schema::table('production_orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('production_order_header_id'));
        Schema::dropIfExists('production_order_headers');
    }
};
