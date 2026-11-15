<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subcontract_material_issue_documents',function(Blueprint $t){
            $t->string('status',20)->default('draft')->index();
            $t->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('confirmed_at')->nullable();
        });
        Schema::table('stock_movements',function(Blueprint $t){
            $t->foreignId('subcontract_material_issue_document_id')->nullable()->constrained('subcontract_material_issue_documents','id','stock_sub_issue_doc_fk')->restrictOnDelete();
            $t->foreignId('subcontract_material_issue_item_id')->nullable()->constrained('subcontract_material_issue_items','id','stock_sub_issue_item_fk')->restrictOnDelete();
            $t->unique('subcontract_material_issue_item_id','stock_sub_issue_item_uq');
        });
        $fields=['name'=>'subcontract-material-issues.confirm','guard_name'=>'web','created_at'=>now(),'updated_at'=>now()];
        if(Schema::hasColumn('permissions','group_name'))$fields['group_name']='subcontract-material-issues';
        DB::table('permissions')->insertOrIgnore($fields);
        $id=DB::table('permissions')->where('name',$fields['name'])->where('guard_name','web')->value('id');
        foreach(DB::table('roles')->where('guard_name','web')->whereIn('name',['admin','super_admin'])->get() as $role)DB::table('role_has_permissions')->insertOrIgnore(['permission_id'=>$id,'role_id'=>$role->id]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void
    {
        // Posted transactions must be preserved; never silently detach source references.
        if(DB::table('stock_movements')->whereNotNull('subcontract_material_issue_item_id')->exists())throw new RuntimeException('Cannot roll back while confirmed subcontract stock movements exist.');
        Schema::table('stock_movements',function(Blueprint $t){$t->dropForeign('stock_sub_issue_doc_fk');$t->dropForeign('stock_sub_issue_item_fk');$t->dropUnique('stock_sub_issue_item_uq');$t->dropColumn(['subcontract_material_issue_document_id','subcontract_material_issue_item_id']);});
        Schema::table('subcontract_material_issue_documents',function(Blueprint $t){$t->dropConstrainedForeignId('confirmed_by');$t->dropIndex(['status']);$t->dropColumn(['status','confirmed_at']);});
        $ids=DB::table('permissions')->where('name','subcontract-material-issues.confirm')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('model_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('permissions')->whereIn('id',$ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
