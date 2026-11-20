@extends('layouts.admin')
@section('title', __('ui.wip_issue.title'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.production') }}</li><li class="breadcrumb-item active">{{ __('ui.wip_issue.title') }}</li></ol></nav>@endsection
@php($creating = request()->routeIs('super.wip-issues.create'))
@section('content')
@if (!$creating)
<div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">{{ __('ui.wip_issue.title') }}</h4><span class="text-muted">{{ __('ui.wip_issue.subtitle') }}</span></div>@can('wip-issues.create')<a class="btn btn-primary btn-sm" href="{{ route('super.wip-issues.create') }}"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.wip_issue.add') }}</a>@endcan</div>
<div class="card"><div class="card-body"><div class="row align-items-end g-2 mb-3"><div class="col-sm-4 col-md-3"><label class="form-label mb-1" for="wipFromDate">{{ __('ui.common.from_date') }}</label><input type="date" id="wipFromDate" class="form-control form-control-sm" value="{{ now()->startOfMonth()->toDateString() }}"></div><div class="col-sm-4 col-md-3"><label class="form-label mb-1" for="wipToDate">{{ __('ui.common.to_date') }}</label><input type="date" id="wipToDate" class="form-control form-control-sm" value="{{ now()->toDateString() }}"></div><div class="col-auto"><button class="btn btn-primary btn-sm" id="applyWipFilter"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div><div class="col-auto"><a class="btn btn-outline-success btn-sm" id="exportWipExcel" href="{{ route('super.wip-issues.export') }}"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a></div></div>
<div class="table-responsive"><table id="wipIssueTable" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.wip_issue.number') }}</th><th>{{ __('ui.wip_issue.date') }}</th><th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.wip_issue.style') }}</th><th>{{ __('ui.common.unit') }}</th><th class="text-end">{{ __('ui.wip_issue.quantity') }}</th><th>{{ __('ui.common.description') }}</th><th>{{ __('ui.common.recorded_by') }}</th></tr></thead></table></div></div></div>
@endif

@if ($creating)
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center gap-2">
    <h5 class="mb-0">{{ __('ui.wip_issue.add') }}</h5>
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('super.wip-issues.index') }}">{{ __('ui.common.back') }}</a>
</div>
@else
    <div class="modal fade" id="wipIssueModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
@endif
    <form id="wipIssueForm">@csrf
        @unless ($creating)
            <div class="modal-header"><h5 class="modal-title" id="wipIssueModalTitle">{{ __('ui.wip_issue.add') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
        @endunless
        <div class="{{ $creating ? 'card-body' : 'modal-body' }}">
            <input type="hidden" id="wipIssueLineId">
            <div class="row gx-lg-5 gy-2"><div class="col-lg-6">
<h6 class="text-primary mb-3">{{ __('ui.common.document_details') }}</h6>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="wipIssueNumber">{{ __('ui.wip_issue.number') }}</label><div class="col-sm-8"><input id="wipIssueNumber" class="form-control form-control-sm" readonly></div></div>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="wipIssueDate">{{ __('ui.wip_issue.date') }} <span class="text-danger">*</span></label><div class="col-sm-8"><input type="date" class="form-control form-control-sm" name="transaction_date" id="wipIssueDate" value="{{ now()->toDateString() }}" required></div></div>
</div><div class="col-lg-6">
<h6 class="text-primary mb-3">{{ __('ui.common.item_details') }}</h6>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="wipProductLabel">{{ __('ui.common.item_code') }} <span class="text-danger">*</span></label><div class="col-sm-8"><div class="input-group input-group-sm"><input id="wipProductLabel" class="form-control form-control-sm" readonly required placeholder="{{ __('ui.wip_issue.select_item') }}"><button type="button" id="chooseWipProduct" class="btn btn-outline-primary">{{ __('ui.common.select') }}</button></div><input type="hidden" name="product_id" id="wipProductId"></div></div>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="wipIssueQuantity">{{ __('ui.wip_issue.quantity') }} <span class="text-danger">*</span></label><div class="col-sm-8"><input type="number" name="quantity" id="wipIssueQuantity" class="form-control form-control-sm text-end" min="0.0001" step="0.0001" required></div></div>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="wipIssueDescription">{{ __('ui.common.description') }}</label><div class="col-sm-8"><textarea name="description" id="wipIssueDescription" class="form-control form-control-sm" rows="2" maxlength="2000"></textarea></div></div>
</div></div>
            <p class="small text-muted mb-0 mt-2">{{ __('ui.wip_issue.stock_notice') }}</p>
        </div>
        <div class="{{ $creating ? 'card-footer d-flex justify-content-end gap-2' : 'modal-footer' }}">
            @if ($creating)
                <a class="btn btn-light btn-sm" href="{{ route('super.wip-issues.index') }}">{{ __('ui.common.cancel') }}</a>
            @else
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
            @endif
            <button type="submit" id="saveWipIssue" class="btn btn-primary btn-sm">{{ __('ui.common.save') }}</button>
        </div>
    </form>
    </div>
@unless ($creating)
    </div></div>
@endunless

<div class="modal fade" id="wipProductModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.wip_issue.select_item') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="wipProductsTable" class="table table-sm table-bordered align-middle w-100"><thead><tr><th>{{ __('ui.gsn.parent_code') }}</th><th>{{ __('ui.gsn.sku_code') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.wip_issue.category') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function(){
 const creating = @json($creating);

 const issueModal=creating ? null : new bootstrap.Modal(document.getElementById('wipIssueModal'));
 const productModal=new bootstrap.Modal(document.getElementById('wipProductModal'));
 const table=creating ? null : $('#wipIssueTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.wip-issues.dt')),data:d=>{d.from_date=$('#wipFromDate').val();d.to_date=$('#wipToDate').val();}},columns:[
  {data:'id',orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},{data:'actions',orderable:false,searchable:false,className:'text-center'},
  {data:'number',name:'document.number'},{data:'transaction_date',name:'document.transaction_date'},{data:'code',name:'product.code'},{data:'name',name:'product.name'},
  {data:'style',orderable:false,searchable:false},{data:'unit',orderable:false,searchable:false},{data:'quantity',name:'quantity',className:'text-end',render:fmt},{data:'description',name:'description'},{data:'creator',orderable:false,searchable:false}
 ],order:[[3,'desc']],drawCallback:()=>window.feather&&feather.replace()});
 let products;
    const loadProducts = () => products ??= $('#wipProductsTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:@json(route('super.wip-issues.products.dt')),columns:[{data: 'parent_code', orderable: false, searchable: false}, {data: 'sku_code', name: 'code', orderable: false}, {data: 'color', orderable: false, searchable: true}, {data: 'size', orderable: false, searchable: true},{data:'name',name:'name'},{data:'category_label',orderable:false,searchable:false},{data:'unit_label',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false,className:'text-center'}],drawCallback:()=>window.feather&&feather.replace()});
 function fmt(v){return Number(v||0).toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:4});}
 function reset(){document.getElementById('wipIssueForm').reset();$('#wipIssueLineId,#wipProductId').val('');$('#wipProductLabel').val('');$('#wipIssueDate').val(@json(now()->toDateString()));$('#wipIssueModalTitle').text(@json(__('ui.wip_issue.add')));}
 function preview(){ $('#wipIssueNumber').val(@json(__('ui.wip_issue.number_loading')));$.get(@json(route('super.wip-issues.number-preview'))).done(r=>$('#wipIssueNumber').val(r.number)).fail(()=>$('#wipIssueNumber').val(@json(__('ui.wip_issue.number_unavailable')))); }
 if (creating) { reset();preview(); }

 $('#chooseWipProduct').on('click',()=>{if (products) { products.ajax.reload(); } else { loadProducts(); } productModal.show();});
 $(document).on('click','.btn-select-wip-product',function(){const b=$(this);$('#wipProductId').val(b.data('id'));$('#wipProductLabel').val(`${b.data('code')} — ${b.data('name')} (${b.data('unit')})`);productModal.hide();});
 $(document).on('click','.btn-edit-wip-issue',function(){const b=$(this);reset();$('#wipIssueLineId').val(b.data('id'));$('#wipIssueNumber').val(b.data('number'));$('#wipIssueDate').val(b.data('date'));$('#wipProductId').val(b.data('product'));$('#wipProductLabel').val(`${b.data('code')} — ${b.data('name')} (${b.data('unit')})`);$('#wipIssueQuantity').val(b.data('quantity'));$('#wipIssueDescription').val(b.data('description'));$('#wipIssueModalTitle').text(@json(__('ui.common.edit'))+' {{ __('ui.wip_issue.title') }}');issueModal.show();});
 $('#applyWipFilter').on('click',()=>table.ajax.reload());
 $('#exportWipExcel').on('click',function(e){e.preventDefault();const p=new URLSearchParams();if($('#wipFromDate').val())p.set('from_date',$('#wipFromDate').val());if($('#wipToDate').val())p.set('to_date',$('#wipToDate').val());location.href=$(this).attr('href')+(p.size?'?'+p.toString():'');});
 $('#wipIssueForm').on('submit',function(e){e.preventDefault();const id=$('#wipIssueLineId').val(),button=$('#saveWipIssue').prop('disabled',true),url=@json(route('super.wip-issues.update',['wipIssueLine'=>'__ID__'])).replace('__ID__',id);$.ajax({url:id?url:@json(route('super.wip-issues.store')),method:id?'PUT':'POST',data:$(this).serialize()}).done(r=>{if (!creating) { issueModal.hide();table.ajax.reload(null,false); }const feedback = Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:r.number?`${r.message} (${r.number})`:r.message}); if (creating) feedback.then(() => window.location.assign(@json(route('super.wip-issues.index'))));}).fail(x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:Object.values(x.responseJSON?.errors||{}).flat().join('\n')||x.responseJSON?.message||@json(__('ui.workflow.save_failed'))})).always(()=>button.prop('disabled',false));});
 $(document).on('click','.btn-delete-wip-issue',function(){const b=$(this);Swal.fire({icon:'warning',title:@json(__('ui.wip_issue.delete_title')),text:`@json(__('ui.wip_issue.delete_help')) · ${b.data('number')}`,showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(r=>{if(!r.isConfirmed)return;$.ajax({url:b.data('url'),method:'DELETE',data:{_token:@json(csrf_token())}}).done(x=>{table.ajax.reload(null,false);Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:x.message});}).fail(x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:Object.values(x.responseJSON?.errors||{}).flat().join('\n')||x.responseJSON?.message||@json(__('ui.workflow.save_failed'))}));});});
});
</script>
@endpush
