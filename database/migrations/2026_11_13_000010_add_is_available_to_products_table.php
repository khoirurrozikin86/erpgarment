<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'is_available')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_available')->default(true)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'is_available')) {
            Schema::table('products', fn (Blueprint $table) => $table->dropColumn('is_available'));
        }
    }
};
