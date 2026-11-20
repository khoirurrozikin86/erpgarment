@extends('layouts.admin')
@section('title', __('ui.non_production_usage.title'))
@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.warehouse_inventory') }}</li><li class="breadcrumb-item active">{{ __('ui.non_production_usage.title') }}</li></ol></nav>
@endsection
@php($creating = request()->routeIs('super.non-production-material-usages.create'))
@section('content')
@if (!$creating)
<div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">{{ __('ui.non_production_usage.title') }}</h4><span class="text-muted">{{ __('ui.non_production_usage.subtitle') }}</span></div>@can('non-production-material-usages.create')<a class="btn btn-primary btn-sm" href="{{ route('super.non-production-material-usages.create') }}"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.non_production_usage.add') }}</a>@endcan</div>
<div class="card"><div class="card-body"><div class="row align-items-end g-2 mb-3"><div class="col-sm-4 col-md-3"><label for="from_date" class="form-label mb-1">{{ __('ui.common.from_date') }}</label><input type="date" id="from_date" class="form-control form-control-sm" value="{{ now()->startOfMonth()->toDateString() }}"></div><div class="col-sm-4 col-md-3"><label for="to_date" class="form-label mb-1">{{ __('ui.common.to_date') }}</label><input type="date" id="to_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}"></div><div class="col-auto"><button type="button" id="applyUsageFilter" class="btn btn-primary btn-sm"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div><div class="col-auto"><a href="{{ route('super.non-production-material-usages.export') }}" id="exportUsageExcel" class="btn btn-outline-success btn-sm"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a></div></div><div class="table-responsive"><table id="nonProductionUsageTable" class="table table-bordered table-hover align-middle w-100"><thead><tr>
<th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.non_production_usage.number') }}</th><th>{{ __('ui.non_production_usage.date') }}</th><th>{{ __('ui.non_production_usage.style') }}</th><th>{{ __('ui.non_production_usage.item_code') }}</th><th>{{ __('ui.non_production_usage.item_name') }}</th><th>{{ __('ui.non_production_usage.unit') }}</th><th class="text-end">{{ __('ui.non_production_usage.quantity') }}</th><th class="text-end">{{ __('ui.non_production_usage.price') }}</th><th class="text-end">{{ __('ui.non_production_usage.total_price') }}</th>
</tr></thead></table></div></div></div>

@endif

@if ($creating)
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center gap-2">
    <h5 class="mb-0">{{ __('ui.non_production_usage.add') }}</h5>
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('super.non-production-material-usages.index') }}">{{ __('ui.common.back') }}</a>
</div>
@else
    <div class="modal fade" id="nonProductionUsageModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
@endif
    <form id="nonProductionUsageForm">@csrf
        @unless ($creating)
            <div class="modal-header"><h5 class="modal-title" id="usageModalTitle">{{ __('ui.non_production_usage.add') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
        @endunless
        <div class="{{ $creating ? 'card-body' : 'modal-body' }}">
            <input type="hidden" id="usageItemId">
            <div class="row gx-lg-5 gy-2"><div class="col-lg-6">
<h6 class="text-primary mb-3">{{ __('ui.common.document_details') }}</h6>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="usageNumber">{{ __('ui.non_production_usage.number') }}</label><div class="col-sm-8"><input id="usageNumber" class="form-control form-control-sm" readonly placeholder="{{ __('ui.non_production_usage.number_generated') }}"></div></div>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="usageDate">{{ __('ui.non_production_usage.date') }} <span class="text-danger">*</span></label><div class="col-sm-8"><input type="date" name="transaction_date" id="usageDate" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required></div></div>
</div><div class="col-lg-6">
<h6 class="text-primary mb-3">{{ __('ui.common.item_details') }}</h6>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="usageProductLabel">{{ __('ui.non_production_usage.item_code') }} <span class="text-danger">*</span></label><div class="col-sm-8"><div class="input-group input-group-sm"><input id="usageProductLabel" class="form-control form-control-sm" readonly required placeholder="{{ __('ui.non_production_usage.select_item') }}"><button id="chooseUsageProduct" type="button" class="btn btn-outline-primary">{{ __('ui.common.select') }}</button></div><input type="hidden" name="product_id" id="usageProductId"></div></div>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="usageQuantity">{{ __('ui.non_production_usage.quantity') }} <span class="text-danger">*</span></label><div class="col-sm-8"><input type="number" name="quantity" id="usageQuantity" class="form-control form-control-sm text-end" min="0.0001" step="0.0001" required></div></div>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="usagePrice">{{ __('ui.non_production_usage.price') }} <span class="text-danger">*</span></label><div class="col-sm-8"><input type="number" name="unit_price" id="usagePrice" class="form-control form-control-sm text-end" min="0" step="0.0001" value="0" required></div></div>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="usageTotal">{{ __('ui.non_production_usage.total_price') }}</label><div class="col-sm-8"><input id="usageTotal" class="form-control form-control-sm text-end" value="0" readonly></div></div>
</div></div>
            <p class="small text-muted mb-0 mt-2">{{ __('ui.non_production_usage.stock_notice') }}</p>
        </div>
        <div class="{{ $creating ? 'card-footer d-flex justify-content-end gap-2' : 'modal-footer' }}">
            @if ($creating)
                <a class="btn btn-light btn-sm" href="{{ route('super.non-production-material-usages.index') }}">{{ __('ui.common.cancel') }}</a>
            @else
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
            @endif
            <button type="submit" id="saveUsage" class="btn btn-primary btn-sm">{{ __('ui.common.save') }}</button>
        </div>
    </form>
    </div>
@unless ($creating)
    </div></div>
@endunless

<div class="modal fade" id="usageProductModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.non_production_usage.select_item') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="usageProductsTable" class="table table-sm table-bordered align-middle w-100"><thead><tr><th>{{ __('ui.gsn.parent_code') }}</th><th>{{ __('ui.gsn.sku_code') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.wip_issue.category') }}</th><th>{{ __('ui.non_production_usage.item_name') }}</th><th>{{ __('ui.non_production_usage.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function(){
 const creating = @json($creating);

    const formModal=creating ? null : new bootstrap.Modal(document.getElementById('nonProductionUsageModal'));
    const productModal=new bootstrap.Modal(document.getElementById('usageProductModal'));
    const table=creating ? null : $('#nonProductionUsageTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.non-production-material-usages.dt')),data:d=>{d.from_date=$('#from_date').val();d.to_date=$('#to_date').val();}},columns:[
        {data:'id',orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},{data:'actions',orderable:false,searchable:false,className:'text-center'},
        {data:'number',name:'document.number'},{data:'transaction_date',name:'document.transaction_date',render:d=>d?new Date(d+'T00:00:00').toLocaleDateString():''},{data:'style',orderable:false,searchable:false},
        {data:'code',name:'product.code'},{data:'name',name:'product.name'},{data:'unit',orderable:false,searchable:false},
        {data:'quantity',name:'quantity',className:'text-end',render:format},{data:'unit_price',name:'unit_price',className:'text-end',render:format},{data:'total_price',name:'total_price',className:'text-end',render:format}
    ],order:[[3,'desc']],drawCallback:()=>window.feather&&feather.replace()});
    let products;
    const loadProducts = () => products ??= $('#usageProductsTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:@json(route('super.non-production-material-usages.products.dt')),columns:[{data: 'parent_code', orderable: false, searchable: false}, {data: 'sku_code', name: 'code', orderable: false}, {data: 'color', orderable: false, searchable: true}, {data: 'size', orderable: false, searchable: true}, {data: 'category_label', orderable: false, searchable: false},{data:'name',name:'name'},{data:'unit_label',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false,className:'text-center'}],drawCallback:()=>window.feather&&feather.replace()});
    function format(v){return Number(v||0).toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:4});}
    function calc(){const total=Number($('#usageQuantity').val()||0)*Number($('#usagePrice').val()||0);$('#usageTotal').val(total.toLocaleString(undefined,{minimumFractionDigits: 0,maximumFractionDigits:4}));}
    function reset(){const form=$('#nonProductionUsageForm')[0];form.reset();$('#usageItemId,#usageProductId').val('');$('#usageProductLabel').val('');$('#usageDate').val(@json(now()->toDateString()));$('#usagePrice').val(0);calc();$('#usageModalTitle').text(@json(__('ui.non_production_usage.add')));}
    function preview(){ $('#usageNumber').val(@json(__('ui.non_production_usage.number_loading')));$.get(@json(route('super.non-production-material-usages.number-preview'))).done(r=>$('#usageNumber').val(r.number)).fail(()=>$('#usageNumber').val(@json(__('ui.non_production_usage.number_unavailable')))); }
    if (creating) { reset();preview(); }

    $('#chooseUsageProduct').on('click',()=>{if (products) { products.ajax.reload(); } else { loadProducts(); } productModal.show();});
    $(document).on('click','.btn-select-np-usage-product',function(){const b=$(this);$('#usageProductId').val(b.data('id'));$('#usageProductLabel').val(`${b.data('code')} — ${b.data('name')} (${b.data('unit')})`);productModal.hide();});
    $('#usageQuantity,#usagePrice').on('input',calc);
    $(document).on('click','.btn-edit-np-usage',function(){const b=$(this);reset();$('#usageItemId').val(b.data('id'));$('#usageNumber').val(b.data('number'));$('#usageDate').val(b.data('date'));$('#usageProductId').val(b.data('product'));$('#usageProductLabel').val(`${b.data('code')} — ${b.data('name')} (${b.data('unit')})`);$('#usageQuantity').val(b.data('quantity'));$('#usagePrice').val(b.data('price'));calc();$('#usageModalTitle').text(@json(__('ui.common.edit'))+' {{ __('ui.non_production_usage.title') }}');formModal.show();});
    $('#applyUsageFilter').on('click',()=>table.ajax.reload());
    $('#exportUsageExcel').on('click',function(e){e.preventDefault();const params=new URLSearchParams();if($('#from_date').val())params.set('from_date',$('#from_date').val());if($('#to_date').val())params.set('to_date',$('#to_date').val());window.location.href=$(this).attr('href')+(params.size?'?'+params.toString():'');});
    $('#nonProductionUsageForm').on('submit',function(e){e.preventDefault();const id=$('#usageItemId').val(),button=$('#saveUsage').prop('disabled',true);const updateUrl=@json(route('super.non-production-material-usages.update',['usageItem'=>'__ID__'])).replace('__ID__',id);$.ajax({url:id?updateUrl:@json(route('super.non-production-material-usages.store')),method:id?'PUT':'POST',data:$(this).serialize()}).done(r=>{if (!creating) { formModal.hide();table.ajax.reload(null,false); }const feedback = Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:r.number?`${r.message} (${r.number})`:r.message}); if (creating) feedback.then(() => window.location.assign(@json(route('super.non-production-material-usages.index'))));}).fail(x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:Object.values(x.responseJSON?.errors||{}).flat().join('\n')||x.responseJSON?.message||@json(__('ui.workflow.save_failed'))})).always(()=>button.prop('disabled',false));});
    $(document).on('click','.btn-delete-np-usage',function(){const b=$(this);Swal.fire({icon:'warning',title:@json(__('ui.non_production_usage.delete_title')),text:`@json(__('ui.non_production_usage.delete_help')) · ${b.data('number')}`,showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(r=>{if(!r.isConfirmed)return;$.ajax({url:b.data('url'),method:'DELETE',data:{_token:@json(csrf_token())}}).done(x=>{table.ajax.reload(null,false);Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:x.message});}).fail(x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))}));});});
});
</script>
@endpush
