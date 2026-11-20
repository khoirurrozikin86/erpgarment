<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bill_of_materials', function (Blueprint $table) {
            $table->string('number', 40)->nullable()->unique();
            $table->foreignId('style_id')->nullable()->constrained('styles')->restrictOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->index(['company_id', 'style_id', 'status'], 'bom_company_style_status_idx');
        });
        DB::table('bill_of_materials')->orderBy('id')->chunkById(200, function ($boms) {
            foreach ($boms as $bom) {
                $code = DB::table('products')->where('id', $bom->product_id)->value('style_code');
                DB::table('bill_of_materials')->where('id', $bom->id)->update([
                    'number' => 'BOM-'.str_pad((string) $bom->id, 8, '0', STR_PAD_LEFT),
                    'style_id' => $code ? DB::table('styles')->where('code', $code)->value('id') : null,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Style-only BOMs have no legacy output product; rolling back must not discard them.
        if (DB::table('bill_of_materials')->whereNull('product_id')->exists()) {
            throw new RuntimeException('Cannot roll back while style-only BOMs exist.');
        }
        Schema::table('bill_of_materials', function (Blueprint $table) {
            $table->dropIndex('bom_company_style_status_idx');
            $table->dropConstrainedForeignId('style_id');
            $table->dropUnique(['number']);
            $table->dropColumn('number');
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
