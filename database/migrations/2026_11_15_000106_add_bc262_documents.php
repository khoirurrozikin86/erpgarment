<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Spatie\Permission\PermissionRegistrar;
return new class extends Migration {
 public function up():void {
  Schema::table('customs_documents',function(Blueprint $t){$t->unsignedBigInteger('supplier_id')->nullable()->change();$t->foreignId('subcontractor_id')->nullable()->constrained()->restrictOnDelete();$t->index(['company_id','subcontractor_id']);});
  Schema::table('customs_document_items',function(Blueprint $t){$t->foreignId('subcontract_receipt_item_id')->nullable()->constrained('subcontract_material_issue_items')->restrictOnDelete();});
  foreach(['view','create','update','delete'] as $action){$fields=['name'=>'bc262.'.$action,'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()];if(Schema::hasColumn('permissions','group_name'))$fields['group_name']='bc262';DB::table('permissions')->insertOrIgnore($fields);$id=DB::table('permissions')->where('name',$fields['name'])->where('guard_name','web')->value('id');foreach(DB::table('roles')->where('guard_name','web')->whereIn('name',['admin','super_admin'])->get() as $role)DB::table('role_has_permissions')->insertOrIgnore(['permission_id'=>$id,'role_id'=>$role->id]);}app(PermissionRegistrar::class)->forgetCachedPermissions();
 }
 public function down():void {
  if(DB::table('customs_documents')->where('document_type','BC 2.6.2')->exists())throw new RuntimeException('Remove BC 2.6.2 documents before rollback.');
  Schema::table('customs_document_items',fn(Blueprint $t)=>$t->dropConstrainedForeignId('subcontract_receipt_item_id'));
  Schema::table('customs_documents',function(Blueprint $t){$t->dropIndex(['company_id','subcontractor_id']);$t->dropConstrainedForeignId('subcontractor_id');$t->unsignedBigInteger('supplier_id')->nullable(false)->change();});
  $ids=DB::table('permissions')->where('name','like','bc262.%')->pluck('id');DB::table('role_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('model_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('permissions')->whereIn('id',$ids)->delete();app(PermissionRegistrar::class)->forgetCachedPermissions();
 }
};
