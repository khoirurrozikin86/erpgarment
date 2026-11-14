<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('production_return_documents')) Schema::create('production_return_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('number', 100);
            $table->date('return_date');
            $table->unsignedBigInteger('raw_material_order_document_id')->nullable();
            $table->string('po_supplier_number')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'number'], 'prod_return_company_number_uq');
            $table->index('return_date');
            $table->index('status');
        });

        $this->ensureForeignKey('production_return_documents', 'company_id', 'companies', 'cascade', 'pr_doc_company_fk');
        $this->ensureForeignKey('production_return_documents', 'raw_material_order_document_id', 'raw_material_order_documents', 'restrict', 'pr_doc_mo_fk');
        $this->ensureForeignKey('production_return_documents', 'created_by', 'users', 'null', 'pr_doc_created_fk');
        $this->ensureForeignKey('production_return_documents', 'approved_by', 'users', 'null', 'pr_doc_approved_fk');
        $this->ensureForeignKey('production_return_documents', 'cancelled_by', 'users', 'null', 'pr_doc_cancelled_fk');

        if (!Schema::hasTable('production_return_items')) Schema::create('production_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_return_document_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 18, 4);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['production_return_document_id', 'product_id'], 'prod_return_doc_product_idx');
        });
        $this->ensureForeignKey('production_return_items', 'production_return_document_id', 'production_return_documents', 'restrict', 'pr_item_doc_fk');
        $this->ensureForeignKey('production_return_items', 'product_id', 'products', 'restrict', 'pr_item_product_fk');

        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'production_return_document_id')) $table->unsignedBigInteger('production_return_document_id')->nullable();
            if (!Schema::hasColumn('stock_movements', 'production_return_item_id')) $table->unsignedBigInteger('production_return_item_id')->nullable();
            if (!Schema::hasIndex('stock_movements', 'sm_prod_return_idx')) $table->index(['production_return_document_id', 'product_id'], 'sm_prod_return_idx');
        });
        $this->ensureForeignKey('stock_movements', 'production_return_document_id', 'production_return_documents', 'restrict', 'sm_prod_return_doc_fk');
        $this->ensureForeignKey('stock_movements', 'production_return_item_id', 'production_return_items', 'restrict', 'sm_prod_return_item_fk');

        $now = now();
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            DB::table('document_numberings')->insertOrIgnore([
                'company_id' => $companyId, 'document_type' => 'return_to_warehouse', 'prefix' => 'RETG',
                'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5,
                'reset_period' => 'monthly', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                foreach (Schema::getForeignKeys('stock_movements') as $foreignKey) {
                    if (array_intersect(['production_return_document_id', 'production_return_item_id'], $foreignKey['columns'])) $table->dropForeign($foreignKey['name']);
                }
                if (Schema::hasIndex('stock_movements', 'sm_prod_return_idx')) $table->dropIndex('sm_prod_return_idx');
                $columns = array_filter(['production_return_item_id', 'production_return_document_id'], fn ($column) => Schema::hasColumn('stock_movements', $column));
                if ($columns) $table->dropColumn($columns);
            });
        }
        Schema::dropIfExists('production_return_items');
        Schema::dropIfExists('production_return_documents');
        if (Schema::hasTable('document_numberings')) DB::table('document_numberings')->where('document_type', 'return_to_warehouse')->delete();
    }

    private function ensureForeignKey(string $tableName, string $column, string $targetTable, string $deleteRule, string $name): void
    {
        $existing = collect(Schema::getForeignKeys($tableName))->first(fn (array $key) => in_array($column, $key['columns'], true));
        if ($existing || !Schema::hasColumn($tableName, $column)) return;
        Schema::table($tableName, function (Blueprint $table) use ($column, $targetTable, $deleteRule, $name) {
            $foreign = $table->foreign($column, $name)->references('id')->on($targetTable);
            match ($deleteRule) {
                'cascade' => $foreign->cascadeOnDelete(),
                'null' => $foreign->nullOnDelete(),
                default => $foreign->restrictOnDelete(),
            };
        });
    }
};
