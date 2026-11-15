@extends('layouts.admin')
@section('title', __($translation.'.form'))
@section('content')
<div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">{{ __($translation.'.form') }}</h5><a class="btn btn-outline-secondary btn-sm" href="{{ route($routePrefix.'.index') }}">{{ __('ui.common.back') }}</a></div><div class="card-body">
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form method="POST" action="{{ $document ? route($routePrefix.'.update',$document) : route($routePrefix.'.store') }}" id="issue-form">@csrf @if($document) @method('PUT') @endif
<div class="row g-3 mb-4">
<div class="col-md-6"><label class="form-label">{{ __($translation.'.number') }}</label><input class="form-control form-control-sm" value="{{ $number }}" readonly><small class="text-muted">{{ __($translation.'.number_help') }}</small></div>
<div class="col-md-6"><label class="form-label" for="issue-date">{{ __($translation.'.date') }} *</label><input type="date" class="form-control form-control-sm" id="issue-date" name="issue_date" value="{{ old('issue_date',$document?->issue_date?->toDateString()??now()->toDateString()) }}" required></div>
@if($isReceipt)
<div class="col-md-6"><label class="form-label" for="manual-contract">{{ __($translation.'.contract') }} *</label><input class="form-control form-control-sm" id="manual-contract" name="contract_number" maxlength="100" value="{{ old('contract_number',$document?->contract_number ?? $document?->contract?->contract_number) }}" required></div>
<div class="col-md-6"><label class="form-label" for="receiver-id">{{ __($translation.'.receiver') }} *</label><input id="subcontractor-search" type="search" class="form-control form-control-sm mb-2" placeholder="{{ __($translation.'.search_subcontractor') }}" aria-label="{{ __($translation.'.search_subcontractor') }}"><select class="form-select form-select-sm" name="subcontractor_id" id="receiver-id" required><option value="">{{ __($translation.'.choose_subcontractor') }}</option>@foreach($subcontractors as $subcontractor)<option value="{{ $subcontractor->id }}" @selected(old('subcontractor_id',$document?->subcontractor_id)==$subcontractor->id)>{{ $subcontractor->name }}</option>@endforeach</select></div>
@else
<div class="col-md-6"><label class="form-label" for="contract">{{ __($translation.'.contract') }} *</label><select class="form-select form-select-sm" id="contract" name="contract_id" required><option value="">{{ __($translation.'.choose_contract') }}</option>@foreach($contracts as $contract)<option value="{{ $contract->id }}" data-receiver="{{ $contract->subcontractor_id }}" data-name="{{ $contract->subcontractor?->name }}" @selected(old('contract_id',$document?->contract_id)==$contract->id)>{{ $contract->contract_number }} — {{ $contract->subcontractor?->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label" for="receiver-name">{{ __($translation.'.receiver') }}</label><input class="form-control form-control-sm" id="receiver-name" readonly><input type="hidden" name="subcontractor_id" id="receiver-id"></div>
@endif
@if($isReceipt)
<div class="col-md-6"><label class="form-label" for="invoice-number">{{ __($translation.'.invoice_number') }}</label><input class="form-control form-control-sm" id="invoice-number" name="invoice_number" maxlength="100" value="{{ old('invoice_number',$document?->invoice_number) }}"></div>
<div class="col-md-6"><label class="form-label" for="invoice-date">{{ __($translation.'.invoice_date') }}</label><input class="form-control form-control-sm" type="date" id="invoice-date" name="invoice_date" value="{{ old('invoice_date',$document?->invoice_date?->toDateString()) }}"></div>
@else
<div class="col-md-6"><label class="form-label" for="vehicle">{{ __($translation.'.vehicle') }}</label><input class="form-control form-control-sm" id="vehicle" name="vehicle_number" maxlength="50" value="{{ old('vehicle_number',$document?->vehicle_number) }}"></div>
@endif
</div>
<div class="d-flex justify-content-between align-items-center mb-2"><h6>{{ __($translation.'.items') }}</h6><button type="button" class="btn btn-outline-primary btn-sm" id="add-item">{{ __($translation.'.choose_item') }}</button></div>
<div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr>@foreach(['code','name','unit','quantity','style','description'] as $key)<th>{{ __($translation.'.'.$key) }}</th>@endforeach<th>{{ __('ui.common.action') }}</th></tr></thead><tbody id="issue-items"></tbody></table></div>
<p class="small text-muted">{{ __($translation.'.notice') }}</p><button class="btn btn-primary btn-sm" id="save-issue">{{ __('ui.common.save') }}</button>
</form></div></div>
<div class="modal fade" id="product-modal" tabindex="-1" aria-labelledby="product-modal-title" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="product-modal-title">{{ __($translation.'.choose_item') }}</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><form id="product-search-form" class="d-flex gap-2 mb-3"><input id="product-search" class="form-control form-control-sm" maxlength="100" placeholder="{{ __($translation.'.product_search') }}" aria-label="{{ __($translation.'.product_search') }}"><button class="btn btn-primary btn-sm">{{ __('ui.common.search') }}</button></form><p class="small text-muted" id="product-status" role="status"></p><div class="table-responsive"><table class="table table-bordered"><thead><tr>@foreach(['code','name','unit'] as $key)<th>{{ __($translation.'.'.$key) }}</th>@endforeach<th>{{ __('ui.common.action') }}</th></tr></thead><tbody id="product-results"></tbody></table></div></div></div></div></div>
@endsection
@push('scripts')
<script>
(() => {
 const t=@json(__($translation));
 const products=@json($productOptions);
 const lines=@json($lines);
 const body=document.getElementById('issue-items');let index=0;
 function textCell(row,text){const cell=row.insertCell();cell.textContent=text??'';return cell;}
 function add(product,line={}){
  const row=body.insertRow();const i=index++;
  const cell=textCell(row,product.code);const hidden=document.createElement('input');hidden.type='hidden';hidden.name=`items[${i}][product_id]`;hidden.value=product.id;cell.append(hidden);
  textCell(row,product.name);textCell(row,product.unit);
  for(const field of ['quantity','style','description']){const cell=row.insertCell();const input=document.createElement('input');input.className='form-control form-control-sm';input.name=`items[${i}][${field}]`;input.value=line[field]??(field==='style'?product.style??'':'');if(field==='quantity'){input.type='number';input.min='0.0001';input.max='999999999999.9999';input.step='0.0001';input.required=true;}else input.maxLength=field==='style'?80:1000;input.setAttribute('aria-label',t[field]);cell.append(input);}
  const button=document.createElement('button');button.type='button';button.className='btn btn-outline-danger btn-sm';button.textContent=t.remove;button.onclick=()=>row.remove();row.insertCell().append(button);
 }
 for(const line of lines){const product=products[line.product_id];if(product)add(product,line);}
 const contract=document.getElementById('contract');function receiver(){const option=contract.selectedOptions[0];document.getElementById('receiver-id').value=option?.dataset.receiver??'';document.getElementById('receiver-name').value=option?.dataset.name??'';}if(contract){contract.addEventListener('change',receiver);receiver();}
 const subcontractorSearch=document.getElementById('subcontractor-search');if(subcontractorSearch)subcontractorSearch.addEventListener('input',function(){const term=this.value.toLocaleLowerCase();for(const option of document.getElementById('receiver-id').options)option.hidden=option.value!==''&&!option.text.toLocaleLowerCase().includes(term);});
 const modal=new bootstrap.Modal(document.getElementById('product-modal'));let pending;
 async function search(){pending?.abort();pending=new AbortController();const results=document.getElementById('product-results');results.replaceChildren();const status=document.getElementById('product-status');status.textContent=t.loading;
 try{const url=new URL(@json(route($routePrefix.'.products')));url.searchParams.set('search',document.getElementById('product-search').value);const response=await fetch(url,{headers:{Accept:'application/json'},signal:pending.signal});if(!response.ok)throw new Error();const data=await response.json();status.textContent=data.length?t.limit_notice:t.empty;for(const product of data){const row=results.insertRow();textCell(row,product.code);textCell(row,product.name);textCell(row,product.unit);const button=document.createElement('button');button.className='btn btn-primary btn-sm';button.textContent=t.select;button.onclick=()=>{add(product);modal.hide();};row.insertCell().append(button);}}catch(error){if(error.name!=='AbortError')status.textContent=t.load_failed;}}
 document.getElementById('add-item').onclick=()=>{modal.show();search();};document.getElementById('product-search-form').onsubmit=event=>{event.preventDefault();search();};
 document.getElementById('issue-form').addEventListener('submit',event=>{if(!body.rows.length){event.preventDefault();alert(t.items_required);}else document.getElementById('save-issue').disabled=true;});
})();
</script>
@endpush
