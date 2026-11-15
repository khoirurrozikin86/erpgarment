<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subcontract_material_issue_documents',function(Blueprint $t){
            $t->unsignedBigInteger('contract_id')->nullable()->change();
            $t->string('contract_number',100)->nullable();
        });
        DB::table('subcontract_material_issue_documents as d')->join('subcontract_contracts as c','c.id','=','d.contract_id')->where('d.issue_kind','receipt')->select(['d.id','c.contract_number'])->orderBy('d.id')->each(function($row){DB::table('subcontract_material_issue_documents')->where('id',$row->id)->update(['contract_number'=>$row->contract_number]);});
    }
    public function down(): void
    {
        if(DB::table('subcontract_material_issue_documents')->whereNull('contract_id')->exists())throw new RuntimeException('Cannot roll back receipts with manually entered contracts.');
        Schema::table('subcontract_material_issue_documents',function(Blueprint $t){$t->dropColumn('contract_number');$t->unsignedBigInteger('contract_id')->nullable(false)->change();});
    }
};
