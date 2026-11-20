@extends('layouts.admin')
@section('title', __($translation.'.form'))
@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">{{ __($translation.'.form') }}</h5>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route($routePrefix.'.index') }}">{{ __('ui.common.back') }}</a>
    </div>
    <form method="POST" action="{{ $document ? route($routePrefix.'.update', $document) : route($routePrefix.'.store') }}" id="issue-form">
        @csrf
        @if($document)
            @method('PUT')
        @endif
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger" role="alert"><ul class="mb-0">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul></div>
            @endif
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="row g-3 g-xl-4">
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="issue-number">{{ __($translation.'.number') }}</label>
                        <div class="col-md-8">
                            <input id="issue-number" class="form-control form-control-sm" value="{{ $number }}" readonly><small class="text-muted">{{ __($translation.'.number_help') }}</small>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="issue-date">{{ __($translation.'.date') }} *</label>
                        <div class="col-md-8">
                            <input type="date" class="form-control form-control-sm" id="issue-date" name="issue_date" value="{{ old('issue_date',$document?->issue_date?->toDateString()??now()->toDateString()) }}" required>
                        </div>
                    </div>
                    @if($isReceipt)
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="manual-contract">{{ __($translation.'.contract') }} *</label>
                        <div class="col-md-8">
                            <input class="form-control form-control-sm" id="manual-contract" name="contract_number" maxlength="100" value="{{ old('contract_number',$document?->contract_number ?? $document?->contract?->contract_number ?? $selectedContract?->contract_number) }}" required>
                        </div>
                    </div>
                    @else
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="contract">{{ __($translation.'.contract') }} *</label>
                        <div class="col-md-8">
                            <select class="form-select form-select-sm" id="contract" name="contract_id" required><option value="">{{ __($translation.'.choose_contract') }}</option>@foreach($contracts as $contract)<option value="{{ $contract->id }}" data-receiver="{{ $contract->subcontractor_id }}" data-name="{{ $contract->subcontractor?->name }}" @selected(old('contract_id',$document?->contract_id ?? $selectedContract?->id)==$contract->id)>{{ $contract->contract_number }} — {{ $contract->subcontractor?->name }}</option>@endforeach</select>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="col-lg-6">
                    @if($isReceipt)
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="receiver-id">{{ __($translation.'.receiver') }} *</label>
                        <div class="col-md-8">
                            <input id="subcontractor-search" type="search" class="form-control form-control-sm mb-2" placeholder="{{ __($translation.'.search_subcontractor') }}" aria-label="{{ __($translation.'.search_subcontractor') }}"><select class="form-select form-select-sm" name="subcontractor_id" id="receiver-id" required><option value="">{{ __($translation.'.choose_subcontractor') }}</option>@foreach($subcontractors as $subcontractor)<option value="{{ $subcontractor->id }}" @selected(old('subcontractor_id',$document?->subcontractor_id ?? $selectedContract?->subcontractor_id)==$subcontractor->id)>{{ $subcontractor->name }}</option>@endforeach</select>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="invoice-number">{{ __($translation.'.invoice_number') }}</label>
                        <div class="col-md-8">
                            <input class="form-control form-control-sm" id="invoice-number" name="invoice_number" maxlength="100" value="{{ old('invoice_number',$document?->invoice_number) }}">
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="invoice-date">{{ __($translation.'.invoice_date') }}</label>
                        <div class="col-md-8">
                            <input class="form-control form-control-sm" type="date" id="invoice-date" name="invoice_date" value="{{ old('invoice_date',$document?->invoice_date?->toDateString()) }}">
                        </div>
                    </div>
                    @else
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="receiver-name">{{ __($translation.'.receiver') }}</label>
                        <div class="col-md-8">
                            <input class="form-control form-control-sm" id="receiver-name" readonly><input type="hidden" name="subcontractor_id" id="receiver-id">
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="vehicle">{{ __($translation.'.vehicle') }}</label>
                        <div class="col-md-8">
                            <input class="form-control form-control-sm" id="vehicle" name="vehicle_number" maxlength="50" value="{{ old('vehicle_number',$document?->vehicle_number) }}">
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <section class="border-top mt-3 pt-3" aria-labelledby="subcontract-items-title">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <h6 id="subcontract-items-title" class="text-primary mb-0">{{ __($translation.'.items') }}</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-item">
                        <i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __($translation.'.choose_item') }}
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle w-100">
                        <thead>
                            <tr>
                                @foreach(\App\Domain\Subcontract\Queries\SubcontractProductColumns::HEADINGS as $label)
                                    <th>{{ __($label) }}</th>
                                @endforeach
                                @foreach(['unit', 'quantity', 'style', 'description'] as $key)
                                    <th>{{ __($translation.'.'.$key) }}</th>
                                @endforeach
                                <th>{{ __('ui.common.action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="issue-items"></tbody>
                    </table>
                </div>
                <p class="small text-muted mb-0 mt-2">{{ __($translation.'.notice') }}</p>
            </section>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm" id="save-issue">
                <i data-feather="save" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.save') }}
            </button>
        </div>
    </form>
</div>
<div class="modal fade" id="product-modal" tabindex="-1" aria-labelledby="product-modal-title" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="product-modal-title">{{ __($translation.'.choose_item') }}</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="subcontract-products-table" class="table table-bordered" style="width: 100%"><thead><tr>@foreach(\App\Domain\Subcontract\Queries\SubcontractProductColumns::HEADINGS as $label)<th>{{ __($label) }}</th>@endforeach<th>{{ __($translation.'.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead><tbody id="product-results"></tbody></table></div></div></div></div></div>
@endsection
@push('scripts')
<script>
(() => {
 const t=@json(__($translation));
 const products=@json($productOptions);
 const lines=@json($lines);
 const itemFields = @json(array_keys(\App\Domain\Subcontract\Queries\SubcontractProductColumns::HEADINGS));
 const body=document.getElementById('issue-items');let index=0;
 function textCell(row,text){const cell=row.insertCell();cell.textContent=text??'';return cell;}
 function add(product,line={}){
  const row=body.insertRow();const i=index++;
  const cells=itemFields.map(field=>textCell(row,product[field] || '—'));const hidden=document.createElement('input');hidden.type='hidden';hidden.name=`items[${i}][product_id]`;hidden.value=product.id;cells[0].append(hidden);
  textCell(row,product.unit || '—');
  for(const field of ['quantity','style','description']){const cell=row.insertCell();const input=document.createElement('input');input.className='form-control form-control-sm';input.name=`items[${i}][${field}]`;input.value=line[field]??(field==='style'?product.style??'':'');if(field==='quantity'){input.type='number';input.min='0.0001';input.max='999999999999.9999';input.step='0.0001';input.required=true;}else input.maxLength=field==='style'?80:1000;input.setAttribute('aria-label',t[field]);cell.append(input);}
  const button=document.createElement('button');button.type='button';button.className='btn btn-outline-danger btn-sm';button.textContent=t.remove;button.onclick=()=>row.remove();row.insertCell().append(button);
 }
 for(const line of lines){const product=products[line.product_id];if(product)add(product,line);}
 const contract=document.getElementById('contract');function receiver(){const option=contract.selectedOptions[0];document.getElementById('receiver-id').value=option?.dataset.receiver??'';document.getElementById('receiver-name').value=option?.dataset.name??'';}if(contract){contract.addEventListener('change',receiver);receiver();}
 const subcontractorSearch=document.getElementById('subcontractor-search');if(subcontractorSearch)subcontractorSearch.addEventListener('input',function(){const term=this.value.toLocaleLowerCase();for(const option of document.getElementById('receiver-id').options)option.hidden=option.value!==''&&!option.text.toLocaleLowerCase().includes(term);});
 const modal=new bootstrap.Modal(document.getElementById('product-modal'));
 const productTable=$('#subcontract-products-table').DataTable({
  processing:true,serverSide:true,scrollX:true,searchDelay:500,lengthMenu:[10,25,50,100],
  ajax:@json(route($routePrefix.'.products')),
  columns:[
   ...itemFields.map(field => ({
    data: field, name: field === 'sku_code' ? 'code' : field,
    orderable: !['parent_code','category_name'].includes(field),
    defaultContent: '—', render: $.fn.dataTable.render.text()
   })),
   {data:'unit_label',orderable:false,searchable:false,defaultContent:'—',render:$.fn.dataTable.render.text()},
   {data:null,orderable:false,searchable:false,defaultContent:'<button type="button" class="btn btn-primary btn-sm js-select-subcontract-product"></button>'}
  ],order:[[0,'asc']],
  createdRow:function(row){$(row).find('.js-select-subcontract-product').text(t.select);}
 });
 $('#subcontract-products-table').on('click','.js-select-subcontract-product',function(){
  const product=productTable.row($(this).closest('tr')).data();
  add({...product,unit:product.unit_label,style:product.style_code});modal.hide();
 });
 document.getElementById('add-item').onclick=()=>modal.show();
 $('#product-modal').on('shown.bs.modal',()=>productTable.columns.adjust());
 document.getElementById('issue-form').addEventListener('submit',event=>{if(!body.rows.length){event.preventDefault();alert(t.items_required);}else document.getElementById('save-issue').disabled=true;});
})();
</script>
@endpush
