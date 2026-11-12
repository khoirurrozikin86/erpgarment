<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('composition', 255)->nullable()->after('hs_code');
            $table->decimal('fabric_width_cm', 10, 2)->nullable()->after('composition');
            $table->decimal('fabric_weight_gsm', 10, 2)->nullable()->after('fabric_width_cm');
            $table->string('country_of_origin', 100)->nullable()->after('fabric_weight_gsm');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['composition', 'fabric_width_cm', 'fabric_weight_gsm', 'country_of_origin']);
        });
    }
};
