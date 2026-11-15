<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB,Schema};
use Spatie\Permission\PermissionRegistrar;
return new class extends Migration {
 public function up():void {
  foreach(['view','create','update','delete'] as $action){
   $fields=['name'=>'bc41.'.$action,'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()];if(Schema::hasColumn('permissions','group_name'))$fields['group_name']='bc41';DB::table('permissions')->insertOrIgnore($fields);$id=DB::table('permissions')->where('name',$fields['name'])->where('guard_name','web')->value('id');
   foreach(DB::table('roles')->where('guard_name','web')->whereIn('name',['admin','super_admin'])->get() as $role)DB::table('role_has_permissions')->insertOrIgnore(['permission_id'=>$id,'role_id'=>$role->id]);
  }app(PermissionRegistrar::class)->forgetCachedPermissions();
 }
 public function down():void {
  $ids=DB::table('permissions')->where('name','like','bc41.%')->pluck('id');DB::table('role_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('model_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('permissions')->whereIn('id',$ids)->delete();app(PermissionRegistrar::class)->forgetCachedPermissions();
 }
};
