<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('raw_material_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_material_order_items', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('raw_material_order_document_id');
            }
            if (!Schema::hasColumn('raw_material_order_items', 'number')) {
                $table->string('number', 100)->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('raw_material_order_items', 'issued_quantity')) {
                $table->decimal('issued_quantity', 18, 4)->default(0)->after('quantity');
            }
        });
        if (!$this->hasForeignKey('raw_material_order_items', ['company_id'])) {
            Schema::table('raw_material_order_items', fn (Blueprint $table) => $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete());
        }

        Schema::table('material_issue_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('material_issue_documents', 'raw_material_order_document_id')) {
                $table->foreignId('raw_material_order_document_id')->nullable()->after('material_request_id');
            }
        });
        $this->dropForeignKey('material_issue_documents', ['material_request_id']);
        Schema::table('material_issue_documents', function (Blueprint $table) {
            $table->foreignId('material_request_id')->nullable()->change();
        });
        if (!$this->hasForeignKey('material_issue_documents', ['material_request_id'])) {
            Schema::table('material_issue_documents', fn (Blueprint $table) => $table->foreign('material_request_id')->references('id')->on('material_requests')->restrictOnDelete());
        }
        if (!$this->hasForeignKey('material_issue_documents', ['raw_material_order_document_id'])) {
            Schema::table('material_issue_documents', fn (Blueprint $table) => $table->foreign('raw_material_order_document_id')->references('id')->on('raw_material_order_documents')->restrictOnDelete());
        }

        Schema::table('material_issue_items', function (Blueprint $table) {
            if (!Schema::hasColumn('material_issue_items', 'raw_material_order_item_id')) {
                $table->foreignId('raw_material_order_item_id')->nullable()->after('material_request_item_id');
            }
        });
        // The composite unique index also starts with material_issue_document_id,
        // so InnoDB may be using it to support that FK as well.
        $this->dropForeignKey('material_issue_items', ['material_issue_document_id']);
        $this->dropForeignKey('material_issue_items', ['material_request_item_id']);
        if (Schema::hasIndex('material_issue_items', 'material_issue_document_item_uq')) {
            Schema::table('material_issue_items', fn (Blueprint $table) => $table->dropUnique('material_issue_document_item_uq'));
        }
        Schema::table('material_issue_items', function (Blueprint $table) {
            $table->foreignId('material_request_item_id')->nullable()->change();
        });
        if (!$this->hasForeignKey('material_issue_items', ['material_request_item_id'])) {
            Schema::table('material_issue_items', fn (Blueprint $table) => $table->foreign('material_request_item_id')->references('id')->on('material_request_items')->restrictOnDelete());
        }
        if (!$this->hasForeignKey('material_issue_items', ['material_issue_document_id'])) {
            Schema::table('material_issue_items', fn (Blueprint $table) => $table->foreign('material_issue_document_id')->references('id')->on('material_issue_documents')->restrictOnDelete());
        }
        if (!$this->hasForeignKey('material_issue_items', ['raw_material_order_item_id'])) {
            Schema::table('material_issue_items', fn (Blueprint $table) => $table->foreign('raw_material_order_item_id')->references('id')->on('raw_material_order_items')->restrictOnDelete());
        }
        if (!Schema::hasIndex('material_issue_items', 'material_issue_document_item_uq')) {
            Schema::table('material_issue_items', fn (Blueprint $table) => $table->unique(['material_issue_document_id', 'material_request_item_id'], 'material_issue_document_item_uq'));
        }
        if (!Schema::hasIndex('material_issue_items', 'material_issue_document_raw_item_uq')) {
            Schema::table('material_issue_items', fn (Blueprint $table) => $table->unique(['material_issue_document_id', 'raw_material_order_item_id'], 'material_issue_document_raw_item_uq'));
        }

        DB::table('raw_material_order_items')->orderBy('id')->each(function ($item) {
            $document = DB::table('raw_material_order_documents')->where('id', $item->raw_material_order_document_id)->first();
            if (!$document) return;
            $ordinal = DB::table('raw_material_order_items')->where('raw_material_order_document_id', $document->id)->where('id', '<=', $item->id)->count();
            $updates = [];
            if ($item->company_id === null) $updates['company_id'] = $document->company_id;
            if ($item->number === null) $updates['number'] = $document->number . '/' . str_pad((string) $ordinal, 3, '0', STR_PAD_LEFT);
            if ($updates) DB::table('raw_material_order_items')->where('id', $item->id)->update($updates);
        });
        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->string('number', 100)->nullable(false)->change();
            if (!Schema::hasIndex('raw_material_order_items', 'raw_material_order_item_company_number_uq')) {
                $table->unique(['company_id', 'number'], 'raw_material_order_item_company_number_uq');
            }
        });
    }

    public function down(): void
    {
        $this->dropForeignKey('material_issue_items', ['material_request_item_id']);
        $this->dropForeignKey('material_issue_items', ['material_issue_document_id']);
        $this->dropForeignKey('material_issue_items', ['raw_material_order_item_id']);
        Schema::table('material_issue_items', function (Blueprint $table) {
            $table->dropUnique('material_issue_document_raw_item_uq');
            $table->dropUnique('material_issue_document_item_uq');
            $table->dropColumn('raw_material_order_item_id');
            $table->foreignId('material_request_item_id')->constrained()->restrictOnDelete();
            $table->unique(['material_issue_document_id', 'material_request_item_id'], 'material_issue_document_item_uq');
        });
        Schema::table('material_issue_items', fn (Blueprint $table) => $table->foreign('material_issue_document_id')->references('id')->on('material_issue_documents')->restrictOnDelete());
        Schema::table('material_issue_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('raw_material_order_document_id');
            $table->dropForeign(['material_request_id']);
            $table->foreignId('material_request_id')->nullable(false)->change();
            $table->foreign('material_request_id')->references('id')->on('material_requests')->restrictOnDelete();
        });
        $this->dropForeignKey('raw_material_order_items', ['company_id']);
        Schema::table('raw_material_order_items', function (Blueprint $table) {
            $table->dropUnique('raw_material_order_item_company_number_uq');
            $table->dropColumn('company_id');
            $table->dropColumn(['number', 'issued_quantity']);
        });
    }

    private function hasForeignKey(string $table, array $columns): bool
    {
        return collect(Schema::getForeignKeys($table))->contains(fn (array $key) => $key['columns'] === $columns);
    }

    private function dropForeignKey(string $table, array $columns): void
    {
        $exists = collect(Schema::getForeignKeys($table))->contains(fn (array $key) => $key['columns'] === $columns);
        if ($exists) {
            // Drop by columns for SQLite test support; Laravel resolves the MySQL FK name too.
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($columns));
        }
    }
};
