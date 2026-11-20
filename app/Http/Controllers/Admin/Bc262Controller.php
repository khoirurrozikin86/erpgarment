<?php
namespace App\Http\Controllers\Admin;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Customs\Queries\BcItemPresentation;
use App\Domain\Customs\Actions\SaveBc262Action;
use App\Domain\Customs\Queries\Bc262Query;
use App\Exports\Bc262Export;
use App\Http\Controllers\Controller;
use App\Models\{CustomsDocument,Product,Supplier,Importer,GoodsReceiptItem,EquipmentReceiptItem,Subcontractor,SubcontractMaterialIssueItem};
use Illuminate\Http\Request;
use Illuminate\Validation\{Rule,ValidationException};
use Maatwebsite\Excel\Facades\Excel;
class Bc262Controller extends Controller {
 private function filters(Request $r):array {
  $f=$r->validate(['from_date'=>['nullable','date_format:Y-m-d'],'to_date'=>['nullable','date_format:Y-m-d'],'search'=>['nullable','string','max:100'],'per_page'=>['nullable','integer','in:10,25,50,100']]);$f['from_date']??=now()->startOfMonth()->toDateString();$f['to_date']??=now()->toDateString();if($f['from_date']>$f['to_date'])throw ValidationException::withMessages(['to_date'=>__('bc262.invalid_range')]);return $f;
 }
 public function index(Request $r,CompanyContext $c,Bc262Query $q){
  $filters=$this->filters(new Request($r->except(['search'])));
  if($r->ajax())return app(\App\Domain\Customs\Queries\BcInDataTable::class)->response($q->builder($c->id(),$filters), 'bc262');
  return view('super.bc262.index',compact('filters'));
 }
 public function export(Request $r,CompanyContext $c){return Excel::download(new Bc262Export($c->id(),$this->filters($r)),'bc-2.6.2.xlsx');}
 public function create(){return $this->form(null);}
 public function edit(CustomsDocument $document,CompanyContext $c){$this->scope($document,$c);return $this->form($document);}
 private function scope(CustomsDocument $d,CompanyContext $c):void {abort_unless((int)$d->company_id===$c->id()&&$d->document_type==='BC 2.6.2',404);}
 private function form(?CustomsDocument $document){
  $document?->load('items');$suppliers=Supplier::orderBy('name')->get(['id','code','name']);$importers=collect();$subcontractors=Subcontractor::where('company_id',app(CompanyContext::class)->id())->where('is_active',true)->orderBy('name')->get(['id','name']);$suppliers=$suppliers->map(fn($p)=>(object)['id'=>'s:'.$p->id,'code'=>$p->code,'name'=>$p->name])->concat($subcontractors->map(fn($p)=>(object)['id'=>'x:'.$p->id,'name'=>$p->name]));
  $lines=array_values(old('items',$document?->items->map->only(['product_id','subcontract_receipt_item_id','goods_receipt_item_id','equipment_receipt_item_id','receipt_number','receipt_date','quantity','unit_price','cif','country_code','hs_code'])->all()??[]));
  foreach($lines as &$line)if(($line['receipt_date']??null) instanceof \DateTimeInterface)$line['receipt_date']=$line['receipt_date']->format('Y-m-d');unset($line);
  $supplierId = (int) old('supplier_id', $document?->supplier_id);
  $lines = BcItemPresentation::lines($lines, app(CompanyContext::class)->id(), $supplierId ?: null);
  $products = BcItemPresentation::products($lines);
  return view('super.bc262.form',compact('document','suppliers','importers','lines','products'));
 }
 private function validated(Request $r,CompanyContext $c,?CustomsDocument $d):array {
  $party=$r->validate(['counterparty'=>['required','regex:/^[sx]:[0-9]+$/']])['counterparty'];[$kind,$id]=explode(':',$party);$r->merge(['supplier_id'=>$kind==='s'?$id:null,'subcontractor_id'=>$kind==='x'?$id:null]);$all=$r->input('intent')==='all';if(!$all)$r->request->remove('items');
  return $r->validate([
   'intent'=>['required','in:header,all'],
   'submission_number'=>['required','string','max:100',Rule::unique('customs_documents','submission_number')->where('company_id',$c->id())->where('document_type','BC 2.6.2')->ignore($d?->id)],
   'document_number'=>['required','string','max:100'],'document_date'=>['required','date_format:Y-m-d'],
   'bl_awb_number'=>['nullable','string','max:100'],'bl_awb_date'=>['nullable','date_format:Y-m-d'],'invoice_number'=>['nullable','string','max:100'],'invoice_date'=>['nullable','date_format:Y-m-d'],
   'supplier_id'=>['nullable','required_without:subcontractor_id','integer','exists:suppliers,id'],'subcontractor_id'=>['nullable','required_without:supplier_id','integer',Rule::exists('subcontractors','id')->where('company_id',$c->id())->whereNull('deleted_at')],'price_type'=>['nullable',Rule::in(['CIF','CNF','FOB'])],'currency'=>['required','string','regex:/^[A-Z]{3}$/'],
   'items'=>$all?['required','array','min:1','max:200']:['exclude'],
   'items.*.subcontract_receipt_item_id'=>['nullable','integer','exists:subcontract_material_issue_items,id'],'items.*.product_id'=>['required','integer','exists:products,id'],
   'items.*.goods_receipt_item_id'=>['nullable','integer','exists:goods_receipt_items,id'],'items.*.equipment_receipt_item_id'=>['nullable','integer','exists:equipment_receipt_items,id'],
   'items.*.receipt_number'=>['required','string','max:100'],'items.*.receipt_date'=>['required','date_format:Y-m-d'],
   'items.*.quantity'=>['required','numeric','min:0.0001','max:999999999.9999','decimal:0,4'],'items.*.unit_price'=>['required','numeric','min:0','max:999999999.9999','decimal:0,4'],
   'items.*.cif'=>['nullable','numeric','min:0','max:999999999999999.9999','decimal:0,4'],'items.*.country_code'=>['nullable','string','max:3'],'items.*.hs_code'=>['nullable','string','max:30'],
  ]);
 }
 public function store(Request $r,CompanyContext $c,SaveBc262Action $a){$data=$this->validated($r,$c,null);$d=$a->save(null,$data,$c->id(),$r->user()->id,$r->input('intent')==='all');return redirect()->route('super.bc-in.bc262.edit',$d)->with('success',__('bc262.saved'));}
 public function update(Request $r,CustomsDocument $document,CompanyContext $c,SaveBc262Action $a){$this->scope($document,$c);$a->save($document,$this->validated($r,$c,$document),$c->id(),$r->user()->id,$r->input('intent')==='all');return back()->with('success',__('bc262.saved'));}
 public function destroy(Request $r,CustomsDocument $document,CompanyContext $c,SaveBc262Action $a){$this->scope($document,$c);$a->delete($document,$c->id(),$r->user()->id);return redirect()->route('super.bc-in.bc262.index')->with('success',__('bc262.deleted'));}
 public function grns(Request $r,CompanyContext $c){
  $party=$r->validate(['counterparty'=>['required','regex:/^[sx]:[0-9]+$/'],'search'=>['nullable','string','max:100']]);[$kind,$id]=explode(':',$party['counterparty']);
  if($kind==='s'){$r->merge(['supplier_id'=>$id]);return app(Bc23Controller::class)->grns($r,$c);}
  $search=$party['search']??'';
  $items=SubcontractMaterialIssueItem::with(['document','product.unit','product.parentProduct:id,code'])->whereHas('document',fn($q)=>$q->where('company_id',$c->id())->where('subcontractor_id',$id)->where('issue_kind','receipt')->where('status','confirmed'))->when($search,fn($q)=>$q->whereHas('product',fn($q)=>$q->where('code','like',"%{$search}%")->orWhere('name','like',"%{$search}%")))->latest('id')->limit(50)->get()->filter(fn($i)=>$i->product)->map(fn($i)=>[...BcItemPresentation::product($i->product),'subcontract_receipt_item_id'=>$i->id,'receipt_number'=>$i->document->number,'receipt_date'=>$i->document->issue_date->toDateString(),'quantity'=>$i->quantity,'unit_price'=>0]);return response()->json($items->values());
 }
}
