<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_numberings')->where('document_type', 'production_order')->delete();
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropUnique('prod_orders_company_number_uq');
            $table->index(['company_id', 'number'], 'material_orders_company_number_idx');
            $table->string('no_cbd', 100)->nullable()->after('buyer_po_number');
            $table->string('currency', 10)->default('USD')->after('no_cbd');
            $table->string('destination', 150)->nullable()->after('currency');
            $table->decimal('price_per_unit', 18, 4)->default(0)->after('planned_quantity');
            $table->decimal('total_price', 32, 4)->default(0)->after('price_per_unit');
            $table->text('description')->nullable()->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropIndex('material_orders_company_number_idx');
            $table->dropColumn(['no_cbd', 'currency', 'destination', 'price_per_unit', 'total_price', 'description']);
        });
        // The former unique constraint is intentionally not recreated because MO
        // numbers now identify a group of finished-good rows.
    }
};
