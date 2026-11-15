<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subcontract_material_issue_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->restrictOnDelete();
            $t->string('number', 100);
            $t->date('issue_date');
            $t->foreignId('contract_id')->constrained('subcontract_contracts')->restrictOnDelete();
            $t->foreignId('subcontractor_id')->constrained('subcontractors')->restrictOnDelete();
            $t->string('vehicle_number', 50)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->softDeletes();
            $t->unique(['company_id', 'number'], 'sub_mat_issue_number_uq');
            $t->index(['company_id', 'issue_date'], 'sub_mat_issue_date_idx');
        });
        Schema::create('subcontract_material_issue_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained('subcontract_material_issue_documents')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity', 20, 4);
            $t->string('style', 80)->nullable();
            $t->string('description', 1000)->nullable();
            $t->timestamps();
            $t->index(['product_id', 'document_id'], 'sub_mat_issue_product_idx');
        });
        foreach (DB::table('companies')->get(['id']) as $company) {
            DB::table('document_numberings')->insertOrIgnore(['company_id'=>$company->id,'document_type'=>'subcontract_material_issue','prefix'=>'TIMW','format'=>'{PREFIX}/{NUMBER}/SBBK/{MM}/{YY}','next_number'=>1,'number_length'=>1,'reset_period'=>'monthly','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        }
        foreach (['view','create','update','delete'] as $action) {
            $name='subcontract-material-issues.'.$action;
            $fields=['name'=>$name,'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()];
            if(Schema::hasColumn('permissions','group_name'))$fields['group_name']='subcontract-material-issues';
            DB::table('permissions')->insertOrIgnore($fields);
            $id=DB::table('permissions')->where('name',$name)->where('guard_name','web')->value('id');
            foreach(DB::table('roles')->where('guard_name','web')->whereIn('name',['admin','super_admin'])->get() as $role)DB::table('role_has_permissions')->insertOrIgnore(['permission_id'=>$id,'role_id'=>$role->id]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void
    {
        Schema::dropIfExists('subcontract_material_issue_items');
        Schema::dropIfExists('subcontract_material_issue_documents');
        DB::table('document_numberings')->where('document_type','subcontract_material_issue')->delete();
        $ids=DB::table('permissions')->where('name','like','subcontract-material-issues.%')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id',$ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id',$ids)->delete();
        DB::table('permissions')->whereIn('id',$ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
