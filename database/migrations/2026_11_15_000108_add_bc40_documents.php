<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Spatie\Permission\PermissionRegistrar;
return new class extends Migration {
 public function up():void {
  Schema::table('customs_document_items',function(Blueprint $t){$t->string('tax_invoice_number',100)->nullable();$t->date('tax_invoice_date')->nullable();});
  foreach(['view','create','update','delete'] as $action){
   $fields=['name'=>'bc40.'.$action,'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()];if(Schema::hasColumn('permissions','group_name'))$fields['group_name']='bc40';DB::table('permissions')->insertOrIgnore($fields);$id=DB::table('permissions')->where('name',$fields['name'])->where('guard_name','web')->value('id');
   foreach(DB::table('roles')->where('guard_name','web')->whereIn('name',['admin','super_admin'])->get() as $role)DB::table('role_has_permissions')->insertOrIgnore(['permission_id'=>$id,'role_id'=>$role->id]);
  }app(PermissionRegistrar::class)->forgetCachedPermissions();
 }
 public function down():void {
  if(DB::table('customs_documents')->where('document_type','BC 4.0')->exists())throw new RuntimeException('Remove BC 4.0 documents before rolling back tax invoice fields.');
  Schema::table('customs_document_items',fn(Blueprint $t)=>$t->dropColumn(['tax_invoice_number','tax_invoice_date']));
  $ids=DB::table('permissions')->where('name','like','bc40.%')->pluck('id');DB::table('role_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('model_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('permissions')->whereIn('id',$ids)->delete();app(PermissionRegistrar::class)->forgetCachedPermissions();
 }
};
