<?php
namespace App\Http\Controllers\Admin;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Customs\Actions\SaveBc41Action;
use App\Domain\Customs\Queries\Bc41Query;
use App\Exports\Bc41Export;
use App\Http\Controllers\Controller;
use App\Models\{CustomsDocument,Product,Customer,Exporter,GoodsSentNoteItem,GoodsReceiptItem,EquipmentReceiptItem};
use Illuminate\Http\Request;
use Illuminate\Validation\{Rule,ValidationException};
use Maatwebsite\Excel\Facades\Excel;
class Bc41Controller extends Controller {
 private function filters(Request $r):array {
  $f=$r->validate(['from_date'=>['nullable','date_format:Y-m-d'],'to_date'=>['nullable','date_format:Y-m-d'],'search'=>['nullable','string','max:100'],'per_page'=>['nullable','integer','in:10,25,50,100']]);$f['from_date']??=now()->startOfMonth()->toDateString();$f['to_date']??=now()->toDateString();if($f['from_date']>$f['to_date'])throw ValidationException::withMessages(['to_date'=>__('bc41.invalid_range')]);return $f;
 }
 public function index(Request $r,CompanyContext $c,Bc41Query $q){
  $filters=$this->filters(new Request($r->except(['search'])));
  if($r->ajax())return app(\App\Domain\Customs\Queries\BcInDataTable::class)->response($q->builder($c->id(),$filters), 'bc41', 'bc-out');
  return view('super.bc41.index',compact('filters'));
 }
 public function export(Request $r,CompanyContext $c){return Excel::download(new Bc41Export($c->id(),$this->filters($r)),'bc-4.1.xlsx');}
 public function create(){return $this->form(null);}
 public function edit(CustomsDocument $document,CompanyContext $c){$this->scope($document,$c);return $this->form($document);}
 private function scope(CustomsDocument $d,CompanyContext $c):void {abort_unless((int)$d->company_id===$c->id()&&$d->document_type==='BC 4.1',404);}
 private function form(?CustomsDocument $document){
  $document?->load('items');$customers=Customer::orderBy('name')->get(['id','name']);$exporters=Exporter::orderBy('name')->get(['id','name']);
  $lines=array_values(old('items',$document?->items->map->only(['product_id','goods_receipt_item_id','equipment_receipt_item_id','receipt_number','receipt_date','tax_invoice_number','tax_invoice_date','goods_sent_note_item_id','shipment_number','shipment_date','quantity','unit_price','hs_code'])->all()??[]));
  foreach($lines as &$line)foreach(['shipment_date','receipt_date','tax_invoice_date'] as $dateField)if(($line[$dateField]??null) instanceof \DateTimeInterface)$line[$dateField]=$line[$dateField]->format('Y-m-d');unset($line);
  $products=Product::with('unit')->whereIn('id',collect($lines)->pluck('product_id')->filter())->get()->mapWithKeys(fn($p)=>[$p->id=>['id'=>$p->id,'code'=>$p->code,'name'=>$p->name,'unit'=>$p->unit?->symbol?:$p->unit?->name]]);
  return view('super.bc41.form',compact('document','customers','exporters','lines','products'));
 }
 private function validated(Request $r,CompanyContext $c,?CustomsDocument $d):array {
  $all=$r->input('intent')==='all';if(!$all)$r->request->remove('items');
  return $r->validate([
   'intent'=>['required','in:header,all'],
   'submission_number'=>['required','string','max:100',Rule::unique('customs_documents','submission_number')->where('company_id',$c->id())->where('document_type','BC 4.1')->ignore($d?->id)],
   'document_number'=>['required','string','max:100'],'document_date'=>['required','date_format:Y-m-d'],
   'bl_awb_number'=>['nullable','string','max:100'],'bl_awb_date'=>['nullable','date_format:Y-m-d'],'invoice_number'=>['nullable','string','max:100'],'invoice_date'=>['nullable','date_format:Y-m-d'],
   'customer_id'=>['required','integer','exists:customers,id'],'exporter_id'=>['nullable','integer','exists:exporters,id'],'price_type'=>['nullable',Rule::in(['CIF','CNF','FOB'])],'currency'=>['required','string','regex:/^[A-Z]{3}$/'],
   'items'=>$all?['required','array','min:1','max:200']:['exclude'],
   'items.*.product_id'=>['required','integer','exists:products,id'],
   'items.*.goods_sent_note_item_id'=>['nullable','integer','exists:goods_sent_note_items,id'],
   'items.*.shipment_number'=>['required','string','max:100'],'items.*.shipment_date'=>['required','date_format:Y-m-d'],
   'items.*.quantity'=>['required','numeric','min:0.0001','max:999999999.9999','decimal:0,4'],'items.*.unit_price'=>['required','numeric','min:0','max:999999999.9999','decimal:0,4'],
   'items.*.goods_receipt_item_id'=>['nullable','integer','exists:goods_receipt_items,id'],'items.*.equipment_receipt_item_id'=>['nullable','integer','exists:equipment_receipt_items,id'],'items.*.receipt_number'=>['nullable','string','max:100'],'items.*.receipt_date'=>['nullable','date_format:Y-m-d'],'items.*.tax_invoice_number'=>['nullable','string','max:100'],'items.*.tax_invoice_date'=>['nullable','date_format:Y-m-d'],'items.*.hs_code'=>['nullable','string','max:30'],
  ]);
 }
 public function store(Request $r,CompanyContext $c,SaveBc41Action $a){$data=$this->validated($r,$c,null);$d=$a->save(null,$data,$c->id(),$r->user()->id,$r->input('intent')==='all');return redirect()->route('super.bc-out.bc41.edit',$d)->with('success',__('bc41.saved'));}
 public function update(Request $r,CustomsDocument $document,CompanyContext $c,SaveBc41Action $a){$this->scope($document,$c);$a->save($document,$this->validated($r,$c,$document),$c->id(),$r->user()->id,$r->input('intent')==='all');return back()->with('success',__('bc41.saved'));}
 public function destroy(Request $r,CustomsDocument $document,CompanyContext $c,SaveBc41Action $a){$this->scope($document,$c);$a->delete($document,$c->id(),$r->user()->id);return redirect()->route('super.bc-out.bc41.index')->with('success',__('bc41.deleted'));}
 public function products(Request $r){$f=$r->validate(['search'=>['nullable','string','max:100']]);$search=$f['search']??'';return response()->json(Product::where('is_active',true)->with('unit')->when($search,fn($q)=>$q->where(fn($q)=>$q->where('code','like',"%{$search}%")->orWhere('name','like',"%{$search}%")))->orderBy('code')->limit(50)->get()->map(fn($p)=>['id'=>$p->id,'code'=>$p->code,'name'=>$p->name,'unit'=>$p->unit?->symbol?:$p->unit?->name]));}
 public function gsns(Request $r,CompanyContext $c){
  $f=$r->validate(['customer_id'=>['required','integer','exists:customers,id'],'search'=>['nullable','string','max:100']]);$search=$f['search']??'';
  $items=GoodsSentNoteItem::with(['note','product.unit'])->where('customer_id',$f['customer_id'])->whereHas('note',fn($q)=>$q->where('company_id',$c->id())->whereNotNull('approved_at'))->when($search,fn($q)=>$q->where(fn($q)=>$q->whereHas('note',fn($q)=>$q->where('number','like',"%{$search}%"))->orWhereHas('product',fn($q)=>$q->where('code','like',"%{$search}%")->orWhere('name','like',"%{$search}%"))))->latest('id')->limit(50)->get()->filter(fn($i)=>$i->product)->map(fn($i)=>['id'=>$i->product_id,'code'=>$i->product->code,'name'=>$i->product->name,'unit'=>$i->product->unit?->symbol?:$i->product->unit?->name,'goods_sent_note_item_id'=>$i->id,'shipment_number'=>$i->note->number,'shipment_date'=>$i->note->sent_date->toDateString(),'quantity'=>$i->quantity,'unit_price'=>0]);return response()->json($items->values());
 }
 public function grns(Request $r,CompanyContext $c){
  $f=$r->validate(['search'=>['nullable','string','max:100']]);$search=$f['search']??'';
  $goods=GoodsReceiptItem::with(['goodsReceipt','product.unit'])->whereHas('goodsReceipt',fn($q)=>$q->where('status','approved'))->when($search,fn($q)=>$q->where(fn($q)=>$q->whereHas('goodsReceipt',fn($q)=>$q->where('number','like',"%{$search}%"))->orWhereHas('product',fn($q)=>$q->where('code','like',"%{$search}%")->orWhere('name','like',"%{$search}%"))))->latest('id')->limit(30)->get()->filter(fn($i)=>$i->product)->map(fn($i)=>['id'=>$i->product_id,'code'=>$i->product->code,'name'=>$i->product->name,'unit'=>$i->product->unit?->symbol?:$i->product->unit?->name,'goods_receipt_item_id'=>$i->id,'receipt_number'=>$i->goodsReceipt->number,'receipt_date'=>$i->goodsReceipt->received_at->toDateString(),'quantity'=>$i->quantity,'unit_price'=>$i->unit_price]);
  $equipment=EquipmentReceiptItem::with('document')->whereNotNull('product_id')->whereHas('document',fn($q)=>$q->where('company_id',$c->id()))->when($search,fn($q)=>$q->where(fn($q)=>$q->where('item_code','like',"%{$search}%")->orWhere('item_name','like',"%{$search}%")))->latest('id')->limit(20)->get()->map(fn($i)=>['id'=>$i->product_id,'code'=>$i->item_code,'name'=>$i->item_name,'unit'=>$i->unit,'equipment_receipt_item_id'=>$i->id,'receipt_number'=>$i->document->number,'receipt_date'=>$i->document->receipt_date->toDateString(),'quantity'=>$i->quantity,'unit_price'=>$i->unit_price]);return response()->json($goods->concat($equipment)->values());
 }
}
