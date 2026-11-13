<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('non_production_material_usage_documents')) Schema::create('non_production_material_usage_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 100);
            $table->date('transaction_date')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'number'], 'np_material_usage_company_number_uq');
        });

        if (!Schema::hasTable('non_production_material_usage_items')) Schema::create('non_production_material_usage_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('non_production_material_usage_documents')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('total_price', 20, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['document_id', 'product_id'], 'np_material_usage_document_product_idx');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'non_production_material_usage_document_id')) {
                $table->unsignedBigInteger('non_production_material_usage_document_id')->nullable()->after('material_issue_item_id');
            }
            if (!Schema::hasColumn('stock_movements', 'non_production_material_usage_item_id')) {
                $table->unsignedBigInteger('non_production_material_usage_item_id')->nullable()->after('non_production_material_usage_document_id');
            }
            if (!Schema::hasIndex('stock_movements', 'stock_movements_np_usage_idx')) {
                $table->index(['non_production_material_usage_document_id', 'product_id'], 'stock_movements_np_usage_idx');
            }
        });

        $foreignKeys = collect(Schema::getForeignKeys('stock_movements'));
        if (!$foreignKeys->contains(fn (array $foreignKey) => in_array('non_production_material_usage_document_id', $foreignKey['columns'], true))) {
            Schema::table('stock_movements', fn (Blueprint $table) => $table->foreign('non_production_material_usage_document_id', 'sm_np_usage_doc_fk')->references('id')->on('non_production_material_usage_documents')->restrictOnDelete());
        }
        if (!$foreignKeys->contains(fn (array $foreignKey) => in_array('non_production_material_usage_item_id', $foreignKey['columns'], true))) {
            Schema::table('stock_movements', fn (Blueprint $table) => $table->foreign('non_production_material_usage_item_id', 'sm_np_usage_item_fk')->references('id')->on('non_production_material_usage_items')->restrictOnDelete());
        }

        $now = now();
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            DB::table('document_numberings')->insertOrIgnore([
                'company_id' => $companyId, 'document_type' => 'non_production_material_usage', 'prefix' => 'PBN',
                'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5,
                'reset_period' => 'monthly', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_movements')) {
            foreach (Schema::getForeignKeys('stock_movements') as $foreignKey) {
                if (array_intersect(['non_production_material_usage_document_id', 'non_production_material_usage_item_id'], $foreignKey['columns'])) {
                    Schema::table('stock_movements', fn (Blueprint $table) => $table->dropForeign($foreignKey['name']));
                }
            }
            if (Schema::hasIndex('stock_movements', 'stock_movements_np_usage_idx')) {
                Schema::table('stock_movements', fn (Blueprint $table) => $table->dropIndex('stock_movements_np_usage_idx'));
            }
            Schema::table('stock_movements', function (Blueprint $table) {
                $columns = array_filter(['non_production_material_usage_item_id', 'non_production_material_usage_document_id'], fn ($column) => Schema::hasColumn('stock_movements', $column));
                if ($columns) $table->dropColumn($columns);
            });
        }
        Schema::dropIfExists('non_production_material_usage_items');
        Schema::dropIfExists('non_production_material_usage_documents');
        DB::table('document_numberings')->where('document_type', 'non_production_material_usage')->delete();
    }
};
