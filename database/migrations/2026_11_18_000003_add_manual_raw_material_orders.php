<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_material_order_documents', function (Blueprint $table) {
            $table->string('production_order_number', 100)->nullable()->change();
        });

        if ($this->foreignKeyExists('raw_material_order_items', 'raw_material_order_items_production_order_material_id_foreign')) {
            Schema::table('raw_material_order_items', function (Blueprint $table) {
                $table->dropForeign('raw_material_order_items_production_order_material_id_foreign');
            });
        }
        if ($this->foreignKeyExists('raw_material_order_items', 'raw_material_order_items_raw_material_order_document_id_foreign')) {
            Schema::table('raw_material_order_items', function (Blueprint $table) {
                $table->dropForeign('raw_material_order_items_raw_material_order_document_id_foreign');
            });
        }

        if ($this->indexExists('raw_material_order_items', 'raw_material_order_source_unique')) {
            Schema::table('raw_material_order_items', function (Blueprint $table) {
                $table->dropUnique('raw_material_order_source_unique');
            });
        }
        if (!$this->indexExists('raw_material_order_items', 'raw_material_order_items_document_id_index')) {
            Schema::table('raw_material_order_items', function (Blueprint $table) {
                $table->index('raw_material_order_document_id', 'raw_material_order_items_document_id_index');
            });
        }

        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('production_order_material_id')->nullable()->change();
            if (!Schema::hasColumn('raw_material_order_items', 'color')) {
                $table->string('color', 80)->nullable();
            }
            if (!Schema::hasColumn('raw_material_order_items', 'size')) {
                $table->string('size', 40)->nullable();
            }
        });

        $this->ensureDocumentForeignKey();
        $this->ensureProductionMaterialForeignKey();
    }

    public function down(): void
    {
        if (DB::table('raw_material_order_items')->whereNull('production_order_material_id')->exists()) {
            throw new \RuntimeException('Cannot roll back: manual raw material orders exist.');
        }
        if (DB::table('raw_material_order_documents')->whereNull('production_order_number')->exists()) {
            throw new \RuntimeException('Cannot roll back: manual raw material order documents exist.');
        }

        Schema::table('raw_material_order_documents', function (Blueprint $table) {
            $table->string('production_order_number', 100)->nullable(false)->change();
        });

        if ($this->foreignKeyExists('raw_material_order_items', 'raw_material_order_items_production_order_material_id_foreign')) {
            Schema::table('raw_material_order_items', function (Blueprint $table) {
                $table->dropForeign('raw_material_order_items_production_order_material_id_foreign');
            });
        }
        if ($this->foreignKeyExists('raw_material_order_items', 'raw_material_order_items_raw_material_order_document_id_foreign')) {
            Schema::table('raw_material_order_items', function (Blueprint $table) {
                $table->dropForeign('raw_material_order_items_raw_material_order_document_id_foreign');
            });
        }

        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->dropColumn(['color', 'size']);
            $table->foreignId('production_order_material_id')->nullable(false)->change();
        });

        if (!$this->indexExists('raw_material_order_items', 'raw_material_order_source_unique')) {
            Schema::table('raw_material_order_items', function (Blueprint $table) {
                $table->unique(['raw_material_order_document_id', 'production_order_material_id'], 'raw_material_order_source_unique');
            });
        }
        if ($this->indexExists('raw_material_order_items', 'raw_material_order_items_document_id_index')) {
            Schema::table('raw_material_order_items', function (Blueprint $table) {
                $table->dropIndex('raw_material_order_items_document_id_index');
            });
        }

        $this->ensureDocumentForeignKey();
        $this->ensureProductionMaterialForeignKey();
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        return (bool) DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function indexExists(string $table, string $name): bool
    {
        return (bool) DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $name)
            ->exists();
    }

    private function ensureDocumentForeignKey(): void
    {
        if ($this->foreignKeyExists('raw_material_order_items', 'raw_material_order_items_raw_material_order_document_id_foreign')) {
            return;
        }
        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->foreign(
                'raw_material_order_document_id',
                'raw_material_order_items_raw_material_order_document_id_foreign'
            )->references('id')->on('raw_material_order_documents')->restrictOnDelete();
        });
    }

    private function ensureProductionMaterialForeignKey(): void
    {
        if ($this->foreignKeyExists('raw_material_order_items', 'raw_material_order_items_production_order_material_id_foreign')) {
            return;
        }
        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->foreign(
                'production_order_material_id',
                'raw_material_order_items_production_order_material_id_foreign'
            )->references('id')->on('production_order_materials')->restrictOnDelete();
        });
    }
};
