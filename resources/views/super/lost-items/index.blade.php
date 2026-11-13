@extends('layouts.admin')

@section('title', __('ui.lost.title'))

@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.warehouse_inventory') }}</li><li class="breadcrumb-item active">{{ __('ui.lost.title') }}</li></ol></nav>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="page-title mb-1">{{ __('ui.lost.title') }}</h4><span class="text-muted">{{ __('ui.lost.subtitle') }}</span></div>
    <div class="d-flex gap-2">@can('lost-items.view')<a href="{{ route('super.lost-items.export') }}" class="btn btn-outline-success btn-sm"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>@endcan @can('lost-items.create')<button class="btn btn-primary btn-sm" id="addLostItem"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.lost.add') }}</button>@endcan</div>
</div>
<div class="card"><div class="card-body"><div class="table-responsive"><table id="lostItemsTable" class="table table-bordered table-hover align-middle w-100">
    <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.lost.minutes_number') }}</th><th>{{ __('ui.lost.date') }}</th><th>{{ __('ui.lost.item_code') }}</th><th>{{ __('ui.lost.item_name') }}</th><th>{{ __('ui.lost.unit') }}</th><th>{{ __('ui.lost.quantity') }}</th><th>{{ __('ui.lost.description') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead>
</table></div></div></div>

<div class="modal fade" id="lostItemModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <form id="lostItemForm">@csrf<div class="modal-header"><h5 class="modal-title" id="lostItemModalTitle">{{ __('ui.lost.add') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
    <div class="modal-body"><input type="hidden" id="lostItemId"><div class="row g-3">
        <div class="col-md-7"><label class="form-label">{{ __('ui.lost.minutes_number') }}</label><input class="form-control" id="lostNumber" readonly placeholder="{{ __('ui.lost.number_generated') }}"></div>
        <div class="col-md-5"><label class="form-label">{{ __('ui.lost.date') }} <span class="text-danger">*</span></label><input type="date" class="form-control" name="document_date" id="lostDate" value="{{ now()->toDateString() }}" required></div>
        <div class="col-12"><label class="form-label">{{ __('ui.lost.item_code') }} <span class="text-danger">*</span></label><div class="input-group"><input class="form-control" id="lostProductLabel" readonly required placeholder="{{ __('ui.lost.select_item') }}"><button class="btn btn-outline-primary" type="button" id="chooseLostProduct">{{ __('ui.common.select') }}</button></div><input type="hidden" name="product_id" id="lostProductId"></div>
        <div class="col-md-4"><label class="form-label">{{ __('ui.lost.quantity') }} <span class="text-danger">*</span></label><input type="number" class="form-control" name="quantity" id="lostQuantity" min="0.0001" step="0.0001" required></div>
        <div class="col-md-8"><label class="form-label">{{ __('ui.lost.description') }}</label><input class="form-control" name="description" id="lostDescription" maxlength="5000"></div>
        <div class="col-12"><small class="text-muted">{{ __('ui.lost.stock_notice') }}</small></div>
    </div></div><div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button><button type="submit" class="btn btn-primary btn-sm" id="saveLostItem">{{ __('ui.common.save') }}</button></div></form>
</div></div></div>

<div class="modal fade" id="lostProductModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.lost.select_item') }}</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="lostProductsTable" class="table table-sm table-bordered align-middle w-100"><thead><tr><th>{{ __('ui.lost.item_code') }}</th><th>{{ __('ui.lost.item_name') }}</th><th>{{ __('ui.lost.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection

@push('scripts')
<script>
$(function(){
    const modal = new bootstrap.Modal(document.getElementById('lostItemModal'));
    const productModal = new bootstrap.Modal(document.getElementById('lostProductModal'));
    const table = $('#lostItemsTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:@json(route('super.lost-items.dt')),columns:[
        {data:'id',orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},
        {data:'number',name:'document.number'},{data:'document_date',name:'document.document_date',render:d=>d?new Date(d+'T00:00:00').toLocaleDateString():''},
        {data:'code',name:'product.code'},{data:'name',name:'product.name'},{data:'unit',orderable:false,searchable:false},
        {data:'quantity',name:'quantity',className:'text-end',render:d=>Number(d).toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:4})},
        {data:'description',name:'description',defaultContent:'—'},{data:'actions',orderable:false,searchable:false,className:'text-center'}
    ],order:[[2,'desc']],drawCallback:()=>window.feather&&feather.replace()});
    const products = $('#lostProductsTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:@json(route('super.lost-items.products.dt')),columns:[{data:'code',name:'code'},{data:'name',name:'name'},{data:'unit_label',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false,className:'text-center'}],drawCallback:()=>window.feather&&feather.replace()});
    function reset(){ $('#lostItemForm')[0].reset();$('#lostItemId').val('');$('#lostProductId').val('');$('#lostProductLabel').val('');$('#lostDate').val(@json(now()->toDateString()));$('#lostItemModalTitle').text(@json(__('ui.lost.add'))); }
    $('#addLostItem').on('click',()=>{reset();$('#lostNumber').val(@json(__('ui.lost.number_loading')));modal.show();$.get(@json(route('super.lost-items.number-preview'))).done(r=>$('#lostNumber').val(r.number)).fail(()=>$('#lostNumber').val(@json(__('ui.lost.number_unavailable'))))});
    $('#chooseLostProduct').on('click',()=>{products.ajax.reload();productModal.show()});
    $(document).on('click','.btn-select-lost-product',function(){const b=$(this);$('#lostProductId').val(b.data('id'));$('#lostProductLabel').val(`${b.data('code')} — ${b.data('name')} (${b.data('unit')})`);productModal.hide()});
    $(document).on('click','.btn-edit-lost-item',function(){const b=$(this);reset();$('#lostItemId').val(b.data('id'));$('#lostNumber').val(b.data('number'));$('#lostDate').val(b.data('date'));$('#lostProductId').val(b.data('product'));$('#lostProductLabel').val(`${b.data('code')} — ${b.data('name')} (${b.data('unit')})`);$('#lostQuantity').val(b.data('quantity'));$('#lostDescription').val(b.data('description'));$('#lostItemModalTitle').text(@json(__('ui.common.edit'))+' {{ __('ui.lost.title') }}');modal.show()});
    $(document).on('click','.btn-delete-lost-item',function(){const b=$(this);Swal.fire({icon:'warning',title:@json(__('ui.lost.delete_title')),text:@json(__('ui.lost.delete_help')),showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(result=>{if(!result.isConfirmed)return;$.ajax({url:b.data('url'),method:'DELETE',data:{_token:@json(csrf_token())},success:r=>{table.ajax.reload(null,false);Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:r.message})},error:x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))})})})});
    $('#lostItemForm').on('submit',function(e){e.preventDefault();const id=$('#lostItemId').val();const button=$('#saveLostItem').prop('disabled',true);$.ajax({url:id?@json(route('super.lost-items.update',['lostItemLine'=>'__ID__'])).replace('__ID__',id):@json(route('super.lost-items.store')),method:id?'PUT':'POST',data:$(this).serialize(),success:r=>{modal.hide();table.ajax.reload(null,false);Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:r.number?`${r.message} (${r.number})`:r.message})},error:x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))}),complete:()=>button.prop('disabled',false)});});
});
</script>
@endpush
