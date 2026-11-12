<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 100);
            $table->foreignId('bill_of_material_id')->constrained('bill_of_materials')->restrictOnDelete();
            $table->foreignId('output_product_id')->constrained('products')->restrictOnDelete();
            $table->string('bom_version', 30);
            $table->decimal('planned_quantity', 18, 4);
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->enum('status', ['draft', 'released', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number'], 'prod_orders_company_number_uq');
            $table->index(['company_id', 'status', 'planned_start_date'], 'prod_orders_company_status_date_idx');
        });

        Schema::create('production_order_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('operation', 40)->nullable();
            $table->decimal('quantity_per_output', 18, 4);
            $table->decimal('wastage_percent', 7, 3)->default(0);
            $table->decimal('required_quantity', 18, 4);
            $table->decimal('issued_quantity', 18, 4)->default(0);
            $table->decimal('consumed_quantity', 18, 4)->default(0);
            $table->timestamps();
            $table->index(['production_order_id', 'product_id'], 'prod_order_material_product_idx');
        });

        Schema::table('material_requests', function (Blueprint $table) {
            $table->foreignId('production_order_id')->nullable()->after('requested_by')->constrained()->restrictOnDelete();
        });

        $now = now();
        DB::table('companies')->select('id')->orderBy('id')->each(function ($company) use ($now) {
            DB::table('document_numberings')->insertOrIgnore([
                'company_id' => $company->id,
                'document_type' => 'production_order',
                'prefix' => 'WO',
                'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}',
                'next_number' => 1,
                'number_length' => 5,
                'reset_period' => 'monthly',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_order_id');
        });
        DB::table('document_numberings')->where('document_type', 'production_order')->delete();
        Schema::dropIfExists('production_order_materials');
        Schema::dropIfExists('production_orders');
    }
};
