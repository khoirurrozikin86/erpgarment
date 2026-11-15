<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Spatie\Permission\PermissionRegistrar;
return new class extends Migration {
 public function up():void {
  Schema::table('customs_documents',function(Blueprint $t){$t->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();$t->foreignId('exporter_id')->nullable()->constrained()->restrictOnDelete();$t->index(['company_id','customer_id']);});
  Schema::table('customs_document_items',function(Blueprint $t){$t->string('receipt_number',100)->nullable()->change();$t->date('receipt_date')->nullable()->change();$t->foreignId('goods_sent_note_item_id')->nullable()->constrained()->restrictOnDelete();$t->string('shipment_number',100)->nullable();$t->date('shipment_date')->nullable();});
  foreach(['view','create','update','delete'] as $action){
   $fields=['name'=>'bc30.'.$action,'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()];if(Schema::hasColumn('permissions','group_name'))$fields['group_name']='bc30';DB::table('permissions')->insertOrIgnore($fields);$id=DB::table('permissions')->where('name',$fields['name'])->where('guard_name','web')->value('id');
   foreach(DB::table('roles')->where('guard_name','web')->whereIn('name',['admin','super_admin'])->get() as $role)DB::table('role_has_permissions')->insertOrIgnore(['permission_id'=>$id,'role_id'=>$role->id]);
  }app(PermissionRegistrar::class)->forgetCachedPermissions();
 }
 public function down():void {
  if(DB::table('customs_documents')->where('document_type','BC 3.0')->exists())throw new RuntimeException('Remove BC 3.0 documents before rollback.');
  Schema::table('customs_document_items',function(Blueprint $t){$t->dropConstrainedForeignId('goods_sent_note_item_id');$t->dropColumn(['shipment_number','shipment_date']);$t->string('receipt_number',100)->nullable(false)->change();$t->date('receipt_date')->nullable(false)->change();});
  Schema::table('customs_documents',function(Blueprint $t){$t->dropIndex(['company_id','customer_id']);$t->dropConstrainedForeignId('customer_id');$t->dropConstrainedForeignId('exporter_id');});
  $ids=DB::table('permissions')->where('name','like','bc30.%')->pluck('id');DB::table('role_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('model_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('permissions')->whereIn('id',$ids)->delete();app(PermissionRegistrar::class)->forgetCachedPermissions();
 }
};
