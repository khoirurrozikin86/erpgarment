<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('support_material_order_documents')) Schema::create('support_material_order_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('number', 100);
            $table->date('order_date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'number'], 'support_order_company_number_uq');
            $table->index('order_date');
        });
        $this->foreign('support_material_order_documents', 'company_id', 'companies', 'cascade', 'smo_doc_company_fk');
        $this->foreign('support_material_order_documents', 'created_by', 'users', 'null', 'smo_doc_creator_fk');

        if (!Schema::hasTable('support_material_order_items')) Schema::create('support_material_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 18, 4);
            $table->string('po_other', 100)->nullable();
            $table->string('style', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['document_id', 'product_id'], 'smo_item_doc_product_idx');
        });
        $this->foreign('support_material_order_items', 'document_id', 'support_material_order_documents', 'restrict', 'smo_item_doc_fk');
        $this->foreign('support_material_order_items', 'product_id', 'products', 'restrict', 'smo_item_product_fk');

        $now = now();
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            DB::table('document_numberings')->insertOrIgnore([
                'company_id' => $companyId, 'document_type' => 'support_material_order', 'prefix' => 'OBP',
                'format' => '{PREFIX}/{YYYY}/{MM}/{NUMBER}', 'next_number' => 1, 'number_length' => 5,
                'reset_period' => 'monthly', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function foreign(string $tableName, string $column, string $target, string $deleteRule, string $name): void
    {
        $exists = collect(Schema::getForeignKeys($tableName))->contains(fn (array $key) => in_array($column, $key['columns'], true));
        if ($exists || !Schema::hasColumn($tableName, $column)) return;
        Schema::table($tableName, function (Blueprint $table) use ($column, $target, $deleteRule, $name) {
            $key = $table->foreign($column, $name)->references('id')->on($target);
            match ($deleteRule) { 'cascade' => $key->cascadeOnDelete(), 'null' => $key->nullOnDelete(), default => $key->restrictOnDelete() };
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_material_order_items');
        Schema::dropIfExists('support_material_order_documents');
        if (Schema::hasTable('document_numberings')) DB::table('document_numberings')->where('document_type', 'support_material_order')->delete();
    }
};
