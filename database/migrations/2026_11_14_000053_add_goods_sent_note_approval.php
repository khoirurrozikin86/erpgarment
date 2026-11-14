<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('goods_sent_notes', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('created_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreign('approved_by', 'gsn_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['company_id', 'approved_at', 'sent_date'], 'gsn_approval_date_idx');
        });

        // GSNs created by the previous implementation already posted their stock movements.
        DB::table('goods_sent_notes')->whereNull('approved_at')
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('stock_movements')
                    ->whereColumn('stock_movements.goods_sent_note_id', 'goods_sent_notes.id')
                    ->where('stock_movements.movement_type', 'goods_sent_note');
            })->update([
                'approved_at' => DB::raw('(select min(sm.created_at) from stock_movements sm where sm.goods_sent_note_id = goods_sent_notes.id and sm.movement_type = "goods_sent_note")'),
                'approved_by' => DB::raw('created_by'),
            ]);

        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) return;
        $ids = [];
        foreach (['view', 'approve', 'cancel'] as $action) {
            $name = "goods-sent-approvals.$action";
            $permission = DB::table('permissions')->where(['name' => $name, 'guard_name' => 'web'])->first();
            $ids[$action] = $permission?->id ?? DB::table('permissions')->insertGetId(array_filter([
                'name' => $name, 'guard_name' => 'web', 'group_name' => Schema::hasColumn('permissions', 'group_name') ? 'goods-sent-approvals' : null,
                'created_at' => now(), 'updated_at' => now(),
            ], fn ($value) => $value !== null));
        }
        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['admin', 'super_admin'])->get() as $role) {
            foreach ($ids as $id) DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $role->id]);
        }
        if ($role = DB::table('roles')->where(['guard_name' => 'web', 'name' => 'user'])->first()) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $ids['view'], 'role_id' => $role->id]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $ids = DB::table('permissions')->where('guard_name', 'web')->where('name', 'like', 'goods-sent-approvals.%')->pluck('id');
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
        Schema::table('goods_sent_notes', function (Blueprint $table) {
            $table->dropForeign('gsn_approved_by_fk');
            $table->dropIndex('gsn_approval_date_idx');
            $table->dropColumn(['approved_by', 'approved_at']);
        });
    }
};
