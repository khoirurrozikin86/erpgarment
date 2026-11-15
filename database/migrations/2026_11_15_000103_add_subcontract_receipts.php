<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subcontract_material_issue_documents',function(Blueprint $t){$t->string('invoice_number',100)->nullable();$t->date('invoice_date')->nullable();});
        foreach(DB::table('companies')->get(['id']) as $company)DB::table('document_numberings')->insertOrIgnore(['company_id'=>$company->id,'document_type'=>'subcontract_receipt','prefix'=>'TIMW','format'=>'{PREFIX}/{NUMBER}/SBBM/{MM}/{YY}','next_number'=>1,'number_length'=>1,'reset_period'=>'monthly','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        foreach(['view','create','update','delete','confirm'] as $action){
            $fields=['name'=>'subcontract-receipts.'.$action,'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()];
            if(Schema::hasColumn('permissions','group_name'))$fields['group_name']='subcontract-receipts';
            DB::table('permissions')->insertOrIgnore($fields);$id=DB::table('permissions')->where('name',$fields['name'])->where('guard_name','web')->value('id');
            foreach(DB::table('roles')->where('guard_name','web')->whereIn('name',['admin','super_admin'])->get() as $role)DB::table('role_has_permissions')->insertOrIgnore(['permission_id'=>$id,'role_id'=>$role->id]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void
    {
        if(DB::table('subcontract_material_issue_documents')->where('issue_kind','receipt')->exists())throw new RuntimeException('Cannot roll back while receipt documents exist.');
        Schema::table('subcontract_material_issue_documents',function(Blueprint $t){$t->dropColumn(['invoice_number','invoice_date']);});
        DB::table('document_numberings')->where('document_type','subcontract_receipt')->delete();
        $ids=DB::table('permissions')->where('name','like','subcontract-receipts.%')->pluck('id');DB::table('role_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('model_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('permissions')->whereIn('id',$ids)->delete();app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
