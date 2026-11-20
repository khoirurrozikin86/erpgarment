<?php
namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Subcontract\Actions\SaveSubcontractMaterialIssueAction;
use App\Domain\Subcontract\Queries\{SubcontractMaterialIssueQuery, SubcontractProductColumns};
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\SubcontractMaterialIssueExport;
use App\Http\Controllers\Controller;
use App\Models\{Product, SubcontractContract, SubcontractMaterialIssueDocument};
use Illuminate\Http\Request;
use Illuminate\Validation\{Rule, ValidationException};
use Maatwebsite\Excel\Facades\Excel;

class SubcontractMaterialIssueController extends Controller
{
    protected string $issueKind = 'raw';
    protected string $routePrefix = 'super.subcontract.material-issues';
    protected string $translation = 'subcontract_material_issue';
    protected string $permissionPrefix = 'subcontract-material-issues';
    protected string $numberingType = 'subcontract_material_issue';
    protected function query(): SubcontractMaterialIssueQuery { return app(SubcontractMaterialIssueQuery::class); }
    private function navigation(): array { return ['routePrefix'=>$this->routePrefix,'translation'=>$this->translation,'permissionPrefix'=>$this->permissionPrefix,'isReceipt'=>$this->issueKind==='receipt']; }

    private function filters(Request $request): array
    {
        $f=$request->validate(['from_date'=>['nullable','date_format:Y-m-d'],'to_date'=>['nullable','date_format:Y-m-d'],'search'=>['nullable','string','max:100'],'per_page'=>['nullable','integer','in:10,25,50,100']]);
        $f['from_date']??=now()->startOfMonth()->toDateString();$f['to_date']??=now()->toDateString();
        if($f['from_date']>$f['to_date'])throw ValidationException::withMessages(['to_date'=>__($this->translation.'.invalid_range')]);
        return $f;
    }
    public function index(Request $r,CompanyContext $c)
    {
        $filters=$this->filters(new Request($r->except(['search'])));
        if ($r->ajax()) return app(\App\Support\ServerSideTable::class)->response(
            $r, $filters, fn (array $filters) => $this->query()->builder($c->id(), $filters),
            \App\Domain\Subcontract\Queries\SubcontractDataTable::columns($this->issueKind),
            'super.subcontract.partials.rows.issues', $this->navigation()
        );
        return view('super.subcontract-material-issues.index',array_merge(compact('filters'),$this->navigation()));
    }
    public function export(Request $r,CompanyContext $c)
    {
        return Excel::download(new SubcontractMaterialIssueExport($c->id(),$this->filters($r),$this->issueKind),$this->issueKind.'-subcontract-issues.xlsx');
    }
    public function create(Request $request, CompanyContext $c, DocumentNumberService $numbers)
    {
        $filters = $request->validate(['contract_id' => ['nullable', 'integer', 'min:1']]);
        $selectedContract = isset($filters['contract_id'])
            ? SubcontractContract::where('company_id', $c->id())->findOrFail($filters['contract_id'])
            : null;

        return $this->form(null, $c, $numbers, $selectedContract);
    }
    public function edit(SubcontractMaterialIssueDocument $document,CompanyContext $c,DocumentNumberService $numbers)
    {
        abort_unless((int)$document->company_id===$c->id() && $document->issue_kind===$this->issueKind,404);
        if($document->status!=='draft')return redirect()->route($this->routePrefix.'.index')->withErrors(['document'=>__($this->translation.'.locked')]);
        return $this->form($document,$c,$numbers);
    }
    private function form(?SubcontractMaterialIssueDocument $document,CompanyContext $c,DocumentNumberService $numbers, ?SubcontractContract $selectedContract = null)
    {
        $contracts=SubcontractContract::where('company_id',$c->id())->whereHas('subcontractor',fn($q)=>$q->where('company_id',$c->id())->where('is_active',true))->with('subcontractor')->orderBy('contract_number')->get();
        $subcontractors=$this->issueKind==='receipt'?\App\Models\Subcontractor::where('company_id',$c->id())->where('is_active',true)->orderBy('name')->get(['id','name']):collect();
        $document?->load(['items.product.unit','contract']);
        $number=$document?->number??$numbers->preview($this->numberingType);
        $lines=array_values(old('items',$document?->items->map->only(['product_id','quantity','style','description'])->all()??[]));
        $products=Product::with(['unit', ...SubcontractProductColumns::RELATIONS])->whereIn('id',collect($lines)->pluck('product_id')->filter())->get()->keyBy('id');
        $productOptions=$products->map(fn($p)=>['id' => $p->id, ...SubcontractProductColumns::values($p), 'unit' => $p->unit?->symbol ?: $p->unit?->name]);
        return view('super.subcontract-material-issues.form',array_merge(compact('document','contracts','number','lines','productOptions','subcontractors','selectedContract'),$this->navigation()));
    }
    public function products(Request $r)
    {
        if ($r->has('draw')) {
            $table = \Yajra\DataTables\Facades\DataTables::eloquent($this->query()->products()->reorder())
                ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name);
            foreach (['parent_code', 'sku_code', 'category_name'] as $key) {
                $table->addColumn($key, fn (Product $product) => SubcontractProductColumns::values($product)[$key]);
            }

            $table->filterColumn('parent_code', fn ($query, $keyword) => $query->where(function ($query) use ($keyword) {
                    $query->whereHas('parentProduct', fn ($parent) => $parent->where('code', 'like', "%{$keyword}%"))
                        ->orWhere(fn ($main) => $main->whereNull('parent_product_id')->where('code', 'like', "%{$keyword}%"));
                }))
                ->filterColumn('category_name', fn ($query, $keyword) =>
                    $query->whereHas('category', fn ($category) => $category->where('name', 'like', "%{$keyword}%")));

            return $table->editColumn('color', fn (Product $product) => SubcontractProductColumns::values($product)['color'])
                ->editColumn('size', fn (Product $product) => SubcontractProductColumns::values($product)['size'])
                ->escapeColumns([])->toJson();
        }
        $f=$r->validate(['search'=>['nullable','string','max:100']]);$search=$f['search']??'';
        return response()->json($this->query()->products()->when($search,fn($q)=>$q->where(fn($q)=>$q->where('code','like',"%{$search}%")->orWhere('name','like',"%{$search}%")))->limit(50)->get()->map(fn($p)=>['id'=>$p->id,'code'=>$p->code,'name'=>$p->name,'unit'=>$p->unit?->symbol?:$p->unit?->name,'style'=>$p->style_code]));
    }
    private function validated(Request $r,CompanyContext $c): array
    {
        $data=$r->validate([
            'issue_date'=>['required','date_format:Y-m-d'],
            'contract_id'=>$this->issueKind==='receipt'?['prohibited']:['required','integer',Rule::exists('subcontract_contracts','id')->where('company_id',$c->id())->whereNull('deleted_at')],
            'contract_number'=>$this->issueKind==='receipt'?['required','string','max:100']:['prohibited'],
            'subcontractor_id'=>['required','integer',Rule::exists('subcontractors','id')->where('company_id',$c->id())->where('is_active',true)->whereNull('deleted_at')],
            'vehicle_number'=>['nullable','string','max:50'],
            'invoice_number'=>['nullable','string','max:100'],
            'invoice_date'=>['nullable','date_format:Y-m-d'],
            'items'=>['required','array','min:1','max:200'],
            'items.*.product_id'=>['required','integer','exists:products,id'],
            'items.*.quantity'=>['required','numeric','min:0.0001','max:999999999999.9999','decimal:0,4'],
            'items.*.style'=>['nullable','string','max:80'],
            'items.*.description'=>['nullable','string','max:1000'],
        ]);
        if($this->issueKind==='receipt'){$data['contract_id']=null;}
        else {
        $contract=SubcontractContract::where('company_id',$c->id())->findOrFail($data['contract_id']);
        if((int)$contract->subcontractor_id!==(int)$data['subcontractor_id'])throw ValidationException::withMessages(['subcontractor_id'=>__($this->translation.'.receiver_mismatch')]);
        }
        $ids=collect($data['items'])->pluck('product_id')->unique();
        if($this->query()->products()->whereIn('id',$ids)->count()!==$ids->count())throw ValidationException::withMessages(['items'=>__($this->translation.'.invalid_product')]);
        if($this->issueKind!=='receipt'){unset($data['invoice_number'],$data['invoice_date']);}
        else {$data['vehicle_number']=null;}
        $data['issue_kind']=$this->issueKind;
        return $data;
    }
    public function store(Request $r,CompanyContext $c,SaveSubcontractMaterialIssueAction $action)
    {
        $document=$action->save(null,$this->validated($r,$c),$c->id(),(int)$r->user()->id);
        return redirect()->route($this->routePrefix.'.index')->with('success',__($this->translation.'.saved'));
    }
    public function update(Request $r,SubcontractMaterialIssueDocument $document,CompanyContext $c,SaveSubcontractMaterialIssueAction $action)
    {
        abort_unless((int)$document->company_id===$c->id() && $document->issue_kind===$this->issueKind,404);
        $action->save($document,$this->validated($r,$c),$c->id(),(int)$r->user()->id);
        return back()->with('success',__($this->translation.'.saved'));
    }
    public function confirm(Request $r,SubcontractMaterialIssueDocument $document,CompanyContext $c,\App\Domain\Subcontract\Actions\ConfirmSubcontractMaterialIssueAction $action)
    {
        abort_unless((int)$document->company_id===$c->id() && $document->issue_kind===$this->issueKind,404);
        if($this->issueKind==='receipt')app(\App\Domain\Subcontract\Actions\ConfirmSubcontractReceiptAction::class)($document,$c->id(),(int)$r->user()->id);
        else $action($document,$c->id(),(int)$r->user()->id);
        return redirect()->route($this->routePrefix.'.index')->with('success',__($this->translation.'.confirmed_message'));
    }
    public function destroy(Request $r,SubcontractMaterialIssueDocument $document,CompanyContext $c,SaveSubcontractMaterialIssueAction $action)
    {
        abort_unless((int)$document->company_id===$c->id() && $document->issue_kind===$this->issueKind,404);
        $action->delete($document,$c->id(),(int)$r->user()->id);
        return redirect()->route($this->routePrefix.'.index')->with('success',__($this->translation.'.deleted'));
    }
}
