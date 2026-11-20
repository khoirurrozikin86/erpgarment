<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_of_materials', fn (Blueprint $table) => $table->string('style_code', 80)->nullable()->index());
        DB::table('bill_of_materials')->orderBy('id')->chunkById(200, function ($boms) {
            foreach ($boms as $bom) {
                $code = $bom->style_id ? DB::table('styles')->where('id', $bom->style_id)->value('code') : null;
                $code ??= $bom->product_id ? DB::table('products')->where('id', $bom->product_id)->value('style_code') : null;
                DB::table('bill_of_materials')->where('id', $bom->id)->update(['style_code' => $code]);
            }
        });
        Schema::table('bill_of_materials', function (Blueprint $table) {
            $table->dropIndex('bom_company_style_status_idx');
            $table->dropConstrainedForeignId('style_id');
        });
    }

    public function down(): void
    {
        Schema::table('bill_of_materials', function (Blueprint $table) {
            $table->foreignId('style_id')->nullable()->constrained('styles')->restrictOnDelete();
            $table->index(['company_id', 'style_id', 'status'], 'bom_company_style_status_idx');
        });
        DB::table('bill_of_materials')->orderBy('id')->chunkById(200, function ($boms) {
            foreach ($boms as $bom) {
                DB::table('bill_of_materials')->where('id', $bom->id)->update(['style_id' => DB::table('styles')->where('code', $bom->style_code)->value('id')]);
            }
        });
        Schema::table('bill_of_materials', fn (Blueprint $table) => $table->dropColumn('style_code'));
    }
};
