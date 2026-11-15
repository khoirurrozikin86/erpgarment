<?php
namespace App\Domain\Subcontract\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\SubcontractMaterialIssueDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveSubcontractMaterialIssueAction
{
    public function save(?SubcontractMaterialIssueDocument $document,array $data,int $companyId,int $userId): SubcontractMaterialIssueDocument
    {
        return DB::transaction(function()use($document,$data,$companyId,$userId){
            $before=null;$action='CREATE';
            if($document){$document=SubcontractMaterialIssueDocument::where('company_id',$companyId)->lockForUpdate()->findOrFail($document->id);if($document->status!=='draft')throw ValidationException::withMessages(['document'=>__(($document->issue_kind==='receipt'?'subcontract_receipt':'subcontract_material_issue').'.locked')]);$before=$document->load('items')->toArray();$action='UPDATE';}
            $items=$data['items'];unset($data['items']);
            if(!$document){$data+=['company_id'=>$companyId,'created_by'=>$userId,'number'=>app(DocumentNumberService::class)->generate(match($data['issue_kind']??'raw'){'wip'=>'subcontract_wip_issue','receipt'=>'subcontract_receipt',default=>'subcontract_material_issue'})];$document=SubcontractMaterialIssueDocument::create($data);}
            else $document->update($data);
            $document->items()->delete();$document->items()->createMany($items);
            app(AuditLogService::class)->log($action,'SUBCONTRACT_DOCUMENT',"{$action} subcontract {$document->issue_kind} document {$document->number}",$document,$before,$document->fresh()->load('items')->toArray(),$userId);
            return $document;
        });
    }
    public function delete(SubcontractMaterialIssueDocument $document,int $companyId,int $userId): void
    {
        DB::transaction(function()use($document,$companyId,$userId){
            $document=SubcontractMaterialIssueDocument::where('company_id',$companyId)->lockForUpdate()->findOrFail($document->id);
            if($document->status!=='draft')throw ValidationException::withMessages(['document'=>__(($document->issue_kind==='receipt'?'subcontract_receipt':'subcontract_material_issue').'.locked')]);
            $before=$document->load('items')->toArray();$document->delete();
            app(AuditLogService::class)->log('DELETE','SUBCONTRACT_DOCUMENT',"Deleted subcontract {$document->issue_kind} document {$document->number}",$document,$before,null,$userId);
        });
    }
}
