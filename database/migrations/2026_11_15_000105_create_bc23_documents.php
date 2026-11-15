<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Spatie\Permission\PermissionRegistrar;
return new class extends Migration {
 public function up():void {
  Schema::create('customs_documents',function(Blueprint $t){
   $t->id();$t->foreignId('company_id')->constrained()->restrictOnDelete();$t->string('document_type',20)->default('BC 2.3');
   $t->string('submission_number',100);$t->string('document_number',100);$t->date('document_date');
   $t->string('bl_awb_number',100)->nullable();$t->date('bl_awb_date')->nullable();$t->string('invoice_number',100)->nullable();$t->date('invoice_date')->nullable();
   $t->foreignId('supplier_id')->constrained()->restrictOnDelete();$t->foreignId('importer_id')->nullable()->constrained()->restrictOnDelete();$t->string('price_type',30)->nullable();$t->string('currency',3);
   $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();$t->softDeletes();
   $t->unique(['company_id','document_type','submission_number'],'customs_company_type_aju_uq');$t->index(['company_id','document_type','document_date'],'customs_company_date_idx');
  });
  Schema::create('customs_document_items',function(Blueprint $t){
   $t->id();$t->foreignId('document_id')->constrained('customs_documents')->cascadeOnDelete();$t->foreignId('product_id')->constrained()->restrictOnDelete();
   $t->foreignId('goods_receipt_item_id')->nullable()->constrained()->restrictOnDelete();$t->foreignId('equipment_receipt_item_id')->nullable()->constrained()->restrictOnDelete();
   $t->string('receipt_number',100);$t->date('receipt_date');$t->string('item_code',100);$t->text('item_name');$t->string('unit',100)->nullable();
   $t->decimal('quantity',20,4);$t->decimal('unit_price',20,4);$t->decimal('total_price',24,4);$t->decimal('cif',24,4)->nullable();$t->string('country_code',3)->nullable();$t->string('hs_code',30)->nullable();$t->timestamps();$t->index(['product_id','document_id'],'customs_item_product_idx');
  });
  foreach(['view','create','update','delete'] as $action){
   $fields=['name'=>'bc23.'.$action,'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()];if(Schema::hasColumn('permissions','group_name'))$fields['group_name']='bc23';DB::table('permissions')->insertOrIgnore($fields);$id=DB::table('permissions')->where('name',$fields['name'])->where('guard_name','web')->value('id');
   foreach(DB::table('roles')->where('guard_name','web')->whereIn('name',['admin','super_admin'])->get() as $role)DB::table('role_has_permissions')->insertOrIgnore(['permission_id'=>$id,'role_id'=>$role->id]);
  }app(PermissionRegistrar::class)->forgetCachedPermissions();
 }
 public function down():void {
  Schema::dropIfExists('customs_document_items');Schema::dropIfExists('customs_documents');$ids=DB::table('permissions')->where('name','like','bc23.%')->pluck('id');DB::table('role_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('model_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('permissions')->whereIn('id',$ids)->delete();app(PermissionRegistrar::class)->forgetCachedPermissions();
 }
};
