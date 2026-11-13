@extends('layouts.admin')

@section('title', __('ui.damage.title'))

@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.warehouse_inventory') }}</li><li class="breadcrumb-item active">{{ __('ui.damage.title') }}</li></ol></nav>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="page-title mb-1">{{ __('ui.damage.title') }}</h4><span class="text-muted">{{ __('ui.damage.subtitle') }}</span></div>
    @can('damaged-items.create')<button class="btn btn-primary btn-sm" id="addDamagedItem"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.damage.add') }}</button>@endcan
</div>
<div class="card"><div class="card-body"><div class="table-responsive"><table id="damagedItemsTable" class="table table-bordered table-hover align-middle w-100">
    <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.damage.minutes_number') }}</th><th>{{ __('ui.damage.date') }}</th><th>{{ __('ui.damage.item_code') }}</th><th>{{ __('ui.damage.item_name') }}</th><th>{{ __('ui.damage.unit') }}</th><th>{{ __('ui.damage.quantity') }}</th><th>{{ __('ui.damage.description') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead>
</table></div></div></div>

<div class="modal fade" id="damagedItemModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <form id="damagedItemForm">@csrf<div class="modal-header"><h5 class="modal-title" id="damagedItemModalTitle">{{ __('ui.damage.add') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
    <div class="modal-body"><input type="hidden" id="damagedItemId"><div class="row g-3">
        <div class="col-md-7"><label class="form-label">{{ __('ui.damage.minutes_number') }}</label><input class="form-control" id="damagedNumber" readonly placeholder="{{ __('ui.damage.number_generated') }}"></div>
        <div class="col-md-5"><label class="form-label">{{ __('ui.damage.date') }} <span class="text-danger">*</span></label><input type="date" class="form-control" name="document_date" id="damagedDate" value="{{ now()->toDateString() }}" required></div>
        <div class="col-12"><label class="form-label">{{ __('ui.damage.item_code') }} <span class="text-danger">*</span></label><div class="input-group"><input class="form-control" id="damagedProductLabel" readonly required placeholder="{{ __('ui.damage.select_item') }}"><button class="btn btn-outline-primary" type="button" id="chooseDamagedProduct">{{ __('ui.common.select') }}</button></div><input type="hidden" name="product_id" id="damagedProductId"></div>
        <div class="col-md-4"><label class="form-label">{{ __('ui.damage.quantity') }} <span class="text-danger">*</span></label><input type="number" class="form-control" name="quantity" id="damagedQuantity" min="0.0001" step="0.0001" required></div>
        <div class="col-md-8"><label class="form-label">{{ __('ui.damage.description') }}</label><input class="form-control" name="description" id="damagedDescription" maxlength="5000"></div>
        <div class="col-12"><small class="text-muted">{{ __('ui.damage.stock_notice') }}</small></div>
    </div></div><div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button><button type="submit" class="btn btn-primary btn-sm" id="saveDamagedItem">{{ __('ui.common.save') }}</button></div></form>
</div></div></div>

<div class="modal fade" id="damagedProductModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.damage.select_item') }}</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="damagedProductsTable" class="table table-sm table-bordered align-middle w-100"><thead><tr><th>{{ __('ui.damage.item_code') }}</th><th>{{ __('ui.damage.item_name') }}</th><th>{{ __('ui.damage.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection

@push('scripts')
<script>
$(function(){
    const modal = new bootstrap.Modal(document.getElementById('damagedItemModal'));
    const productModal = new bootstrap.Modal(document.getElementById('damagedProductModal'));
    const table = $('#damagedItemsTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:@json(route('super.damaged-items.dt')),columns:[
        {data:'id',orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},
        {data:'number',name:'document.number'},{data:'document_date',name:'document.document_date',render:d=>d?new Date(d+'T00:00:00').toLocaleDateString():''},
        {data:'code',name:'product.code'},{data:'name',name:'product.name'},{data:'unit',orderable:false,searchable:false},
        {data:'quantity',name:'quantity',className:'text-end',render:d=>Number(d).toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:4})},
        {data:'description',name:'description',defaultContent:'—'},{data:'actions',orderable:false,searchable:false,className:'text-center'}
    ],order:[[2,'desc']],drawCallback:()=>window.feather&&feather.replace()});
    const products = $('#damagedProductsTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:@json(route('super.damaged-items.products.dt')),columns:[{data:'code',name:'code'},{data:'name',name:'name'},{data:'unit_label',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false,className:'text-center'}],drawCallback:()=>window.feather&&feather.replace()});
    function reset(){ $('#damagedItemForm')[0].reset();$('#damagedItemId').val('');$('#damagedProductId').val('');$('#damagedProductLabel').val('');$('#damagedDate').val(@json(now()->toDateString()));$('#damagedItemModalTitle').text(@json(__('ui.damage.add'))); }
    $('#addDamagedItem').on('click',()=>{reset();$('#damagedNumber').val(@json(__('ui.damage.number_loading')));modal.show();$.get(@json(route('super.damaged-items.number-preview'))).done(r=>$('#damagedNumber').val(r.number)).fail(()=>$('#damagedNumber').val(@json(__('ui.damage.number_unavailable'))))});
    $('#chooseDamagedProduct').on('click',()=>{products.ajax.reload();productModal.show()});
    $(document).on('click','.btn-select-damaged-product',function(){const b=$(this);$('#damagedProductId').val(b.data('id'));$('#damagedProductLabel').val(`${b.data('code')} — ${b.data('name')} (${b.data('unit')})`);productModal.hide()});
    $(document).on('click','.btn-edit-damaged-item',function(){const b=$(this);reset();$('#damagedItemId').val(b.data('id'));$('#damagedNumber').val(b.data('number'));$('#damagedDate').val(b.data('date'));$('#damagedProductId').val(b.data('product'));$('#damagedProductLabel').val(`${b.data('code')} — ${b.data('name')} (${b.data('unit')})`);$('#damagedQuantity').val(b.data('quantity'));$('#damagedDescription').val(b.data('description'));$('#damagedItemModalTitle').text(@json(__('ui.common.edit'))+' {{ __('ui.damage.title') }}');modal.show()});
    $(document).on('click','.btn-delete-damaged-item',function(){const b=$(this);Swal.fire({icon:'warning',title:@json(__('ui.damage.delete_title')),text:@json(__('ui.damage.delete_help')),showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(result=>{if(!result.isConfirmed)return;$.ajax({url:b.data('url'),method:'DELETE',data:{_token:@json(csrf_token())},success:r=>{table.ajax.reload(null,false);Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:r.message})},error:x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))})})})});
    $('#damagedItemForm').on('submit',function(e){e.preventDefault();const id=$('#damagedItemId').val();const button=$('#saveDamagedItem').prop('disabled',true);$.ajax({url:id?@json(route('super.damaged-items.update',['damagedItemLine'=>'__ID__'])).replace('__ID__',id):@json(route('super.damaged-items.store')),method:id?'PUT':'POST',data:$(this).serialize(),success:r=>{modal.hide();table.ajax.reload(null,false);Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:r.number?`${r.message} (${r.number})`:r.message})},error:x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))}),complete:()=>button.prop('disabled',false)});});
});
</script>
@endpush
