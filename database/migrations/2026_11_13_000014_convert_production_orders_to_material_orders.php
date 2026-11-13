<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->string('buyer_po_number', 100)->nullable()->after('number');
            $table->date('po_date')->nullable()->after('buyer_po_number');
            $table->date('due_date')->nullable()->after('po_date');
            $table->foreignId('customer_id')->nullable()->after('due_date')->constrained('customers')->nullOnDelete();
            $table->index(['company_id', 'po_date'], 'material_orders_company_po_date_idx');
        });
        DB::table('document_numberings')->where('document_type', 'production_order')->update(['prefix' => 'MO']);
        $deletePermission = Permission::findOrCreate('production-orders.delete', 'web');
        Role::query()->whereIn('name', ['admin', 'super_admin'])->get()->each(fn (Role $role) => $role->givePermissionTo($deletePermission));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Remove the old production order dataset. Keep issued material requests and
        // stock movements as history, but detach references to records being cleared.
        if (Schema::hasTable('material_requests') && Schema::hasColumn('material_requests', 'production_order_id')) {
            DB::table('material_requests')->whereNotNull('production_order_id')->update(['production_order_id' => null]);
        }

        if (Schema::hasTable('stock_movements') && Schema::hasColumn('stock_movements', 'production_order_stage_entry_id')) {
            DB::table('stock_movements')->whereNotNull('production_order_stage_entry_id')->update(['production_order_stage_entry_id' => null]);
        }

        if (Schema::hasTable('production_order_stage_entries')) {
            DB::table('production_order_stage_entries')->delete();
        }
        DB::table('production_order_materials')->delete();
        DB::table('production_orders')->delete();
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropIndex('material_orders_company_po_date_idx');
            $table->dropColumn(['buyer_po_number', 'po_date', 'due_date']);
        });
        // Deleted operational rows and detached references cannot be reconstructed.
    }
};
