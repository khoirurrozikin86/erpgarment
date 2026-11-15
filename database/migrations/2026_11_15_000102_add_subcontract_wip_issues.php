<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subcontract_material_issue_documents',function(Blueprint $t){
            $t->string('issue_kind',10)->default('raw');
            $t->index(['company_id','issue_kind','issue_date'],'sub_issue_kind_date_idx');
        });
        foreach(DB::table('companies')->get(['id']) as $company)DB::table('document_numberings')->insertOrIgnore(['company_id'=>$company->id,'document_type'=>'subcontract_wip_issue','prefix'=>'TIMW','format'=>'{PREFIX}/{NUMBER}/SBSJK/{MM}/{YY}','next_number'=>1,'number_length'=>1,'reset_period'=>'monthly','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        foreach(['view','create','update','delete','confirm'] as $action){
            $fields=['name'=>'subcontract-wip-issues.'.$action,'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()];
            if(Schema::hasColumn('permissions','group_name'))$fields['group_name']='subcontract-wip-issues';
            DB::table('permissions')->insertOrIgnore($fields);
            $id=DB::table('permissions')->where('name',$fields['name'])->where('guard_name','web')->value('id');
            foreach(DB::table('roles')->where('guard_name','web')->whereIn('name',['admin','super_admin'])->get() as $role)DB::table('role_has_permissions')->insertOrIgnore(['permission_id'=>$id,'role_id'=>$role->id]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void
    {
        if(DB::table('subcontract_material_issue_documents')->where('issue_kind','wip')->exists())throw new RuntimeException('Cannot roll back while WIP issue documents exist.');
        Schema::table('subcontract_material_issue_documents',function(Blueprint $t){$t->dropIndex('sub_issue_kind_date_idx');$t->dropColumn('issue_kind');});
        DB::table('document_numberings')->where('document_type','subcontract_wip_issue')->delete();
        $ids=DB::table('permissions')->where('name','like','subcontract-wip-issues.%')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('model_has_permissions')->whereIn('permission_id',$ids)->delete();DB::table('permissions')->whereIn('id',$ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
