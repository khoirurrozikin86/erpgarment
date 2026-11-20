<?php
namespace App\Http\Controllers\Admin;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Customs\Queries\BcItemPresentation;
use App\Domain\Customs\Actions\SaveBc25Action;
use App\Domain\Customs\Queries\Bc25Query;
use App\Exports\Bc25Export;
use App\Http\Controllers\Controller;
use App\Models\{CustomsDocument,Product,Customer,Exporter,GoodsSentNoteItem};
use Illuminate\Http\Request;
use Illuminate\Validation\{Rule,ValidationException};
use Maatwebsite\Excel\Facades\Excel;
class Bc25Controller extends Controller {
 private function filters(Request $r):array {
  $f=$r->validate(['from_date'=>['nullable','date_format:Y-m-d'],'to_date'=>['nullable','date_format:Y-m-d'],'search'=>['nullable','string','max:100'],'per_page'=>['nullable','integer','in:10,25,50,100']]);$f['from_date']??=now()->startOfMonth()->toDateString();$f['to_date']??=now()->toDateString();if($f['from_date']>$f['to_date'])throw ValidationException::withMessages(['to_date'=>__('bc25.invalid_range')]);return $f;
 }
 public function index(Request $r,CompanyContext $c,Bc25Query $q){
  $filters=$this->filters(new Request($r->except(['search'])));
  if($r->ajax())return app(\App\Domain\Customs\Queries\BcInDataTable::class)->response($q->builder($c->id(),$filters), 'bc25', 'bc-out');
  return view('super.bc25.index',compact('filters'));
 }
 public function export(Request $r,CompanyContext $c){return Excel::download(new Bc25Export($c->id(),$this->filters($r)),'bc-2.5.xlsx');}
 public function create(){return $this->form(null);}
 public function edit(CustomsDocument $document,CompanyContext $c){$this->scope($document,$c);return $this->form($document);}
 private function scope(CustomsDocument $d,CompanyContext $c):void {abort_unless((int)$d->company_id===$c->id()&&$d->document_type==='BC 2.5',404);}
 private function form(?CustomsDocument $document){
  $document?->load('items');$customers=Customer::orderBy('name')->get(['id','code','name']);$exporters=Exporter::orderBy('name')->get(['id','tax_number','name']);
  $lines=array_values(old('items',$document?->items->map->only(['product_id','goods_sent_note_item_id','shipment_number','shipment_date','quantity','unit_price','item_per_unit','hs_code'])->all()??[]));
  foreach($lines as &$line)if(($line['shipment_date']??null) instanceof \DateTimeInterface)$line['shipment_date']=$line['shipment_date']->format('Y-m-d');unset($line);
  $supplierId = (int) old('supplier_id', $document?->supplier_id);
  $lines = BcItemPresentation::lines($lines, app(CompanyContext::class)->id(), $supplierId ?: null);
  $products = BcItemPresentation::products($lines);
  return view('super.bc25.form',compact('document','customers','exporters','lines','products'));
 }
 private function validated(Request $r,CompanyContext $c,?CustomsDocument $d):array {
  $all=$r->input('intent')==='all';if(!$all)$r->request->remove('items');
  return $r->validate([
   'intent'=>['required','in:header,all'],
   'submission_number'=>['required','string','max:100',Rule::unique('customs_documents','submission_number')->where('company_id',$c->id())->where('document_type','BC 2.5')->ignore($d?->id)],
   'document_number'=>['required','string','max:100'],'document_date'=>['required','date_format:Y-m-d'],
   'bl_awb_number'=>['nullable','string','max:100'],'bl_awb_date'=>['nullable','date_format:Y-m-d'],'invoice_number'=>['nullable','string','max:100'],'invoice_date'=>['nullable','date_format:Y-m-d'],
   'customer_id'=>['required','integer','exists:customers,id'],'exporter_id'=>['nullable','integer','exists:exporters,id'],'price_type'=>['nullable',Rule::in(['CIF','CNF','FOB'])],'currency'=>['required','string','regex:/^[A-Z]{3}$/'],
   'items'=>$all?['required','array','min:1','max:200']:['exclude'],
   'items.*.product_id'=>['required','integer','exists:products,id'],
   'items.*.goods_sent_note_item_id'=>['nullable','integer','exists:goods_sent_note_items,id'],
   'items.*.shipment_number'=>['required','string','max:100'],'items.*.shipment_date'=>['required','date_format:Y-m-d'],
   'items.*.quantity'=>['required','numeric','min:0.0001','max:999999999.9999','decimal:0,4'],'items.*.unit_price'=>['required','numeric','min:0','max:999999999.9999','decimal:0,4'],
   'items.*.item_per_unit'=>['nullable','integer','min:0','max:2147483647'],'items.*.hs_code'=>['nullable','string','max:30'],
  ]);
 }
 public function store(Request $r,CompanyContext $c,SaveBc25Action $a){$data=$this->validated($r,$c,null);$d=$a->save(null,$data,$c->id(),$r->user()->id,$r->input('intent')==='all');return redirect()->route('super.bc-out.bc25.edit',$d)->with('success',__('bc25.saved'));}
 public function update(Request $r,CustomsDocument $document,CompanyContext $c,SaveBc25Action $a){$this->scope($document,$c);$a->save($document,$this->validated($r,$c,$document),$c->id(),$r->user()->id,$r->input('intent')==='all');return back()->with('success',__('bc25.saved'));}
 public function destroy(Request $r,CustomsDocument $document,CompanyContext $c,SaveBc25Action $a){$this->scope($document,$c);$a->delete($document,$c->id(),$r->user()->id);return redirect()->route('super.bc-out.bc25.index')->with('success',__('bc25.deleted'));}
 public function gsns(Request $r,CompanyContext $c){
  $f=$r->validate(['customer_id'=>['required','integer','exists:customers,id'],'search'=>['nullable','string','max:100']]);$search=$f['search']??'';
  $items=GoodsSentNoteItem::with(['note', 'product.unit', 'product.parentProduct:id,code', 'productionOrder:id,order_color,order_size'])->where('customer_id',$f['customer_id'])->whereHas('note',fn($q)=>$q->where('company_id',$c->id())->whereNotNull('approved_at'))->when($search,fn($q)=>$q->where(fn($q)=>$q->whereHas('note',fn($q)=>$q->where('number','like',"%{$search}%"))->orWhereHas('product',fn($q)=>$q->where('code','like',"%{$search}%")->orWhere('name','like',"%{$search}%"))))->latest('id')->limit(50)->get()->filter(fn($i)=>$i->product)->map(fn($i)=>[...BcItemPresentation::product($i->product),'color'=>$i->productionOrder?->order_color ?? $i->product->color,'size'=>$i->productionOrder?->order_size ?? $i->product->size,'goods_sent_note_item_id'=>$i->id,'shipment_number'=>$i->note->number,'shipment_date'=>$i->note->sent_date->toDateString(),'quantity'=>$i->quantity,'unit_price'=>0]);return response()->json($items->values());
 }
}
