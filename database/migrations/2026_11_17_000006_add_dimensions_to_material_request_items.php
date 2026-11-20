<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            $table->string('color', 80)->nullable();
            $table->string('size', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('material_request_items', fn (Blueprint $table) => $table->dropColumn(['color', 'size']));
    }
};
