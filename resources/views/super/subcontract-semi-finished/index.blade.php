@extends('layouts.admin')

@section('title', __('ui.subcontract_semi_finished.title'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('ui.nav.subcontract') }}</li>
        <li class="breadcrumb-item active">{{ __('ui.subcontract_semi_finished.title') }}</li>
    </ol></nav>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('ui.subcontract_semi_finished.title') }}</h5>
        @can('subcontract-semi-finished.create')
            <button class="btn btn-primary btn-sm" id="addSemiFinished"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.subcontract_semi_finished.add') }}</button>
        @endcan
    </div>
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-sm-4 col-md-3"><label for="semiFinishedFrom" class="form-label mb-1">{{ __('ui.common.from_date') }}</label><input type="date" id="semiFinishedFrom" class="form-control form-control-sm" value="{{ now()->startOfMonth()->toDateString() }}"></div>
            <div class="col-sm-4 col-md-3"><label for="semiFinishedTo" class="form-label mb-1">{{ __('ui.common.to_date') }}</label><input type="date" id="semiFinishedTo" class="form-control form-control-sm" value="{{ now()->toDateString() }}"></div>
            <div class="col-auto"><button id="showSemiFinished" class="btn btn-primary btn-sm"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div>
        </div>
        <div class="table-responsive"><table id="semiFinishedTable" class="table table-bordered table-hover align-middle w-100">
            <thead><tr>
                <th>{{ __('ui.common.no') }}</th><th>{{ __('ui.subcontract_semi_finished.number') }}</th><th>{{ __('ui.subcontract_semi_finished.date') }}</th><th>{{ __('ui.subcontract_semi_finished.description') }}</th><th>{{ __('ui.subcontract_semi_finished.buyer_po') }}</th><th>{{ __('ui.subcontract_semi_finished.style') }}</th><th>{{ __('ui.subcontract_semi_finished.product_code') }}</th><th>{{ __('ui.subcontract_semi_finished.product_name') }}</th><th>{{ __('ui.subcontract_semi_finished.unit') }}</th><th>{{ __('ui.subcontract_semi_finished.quantity') }}</th><th>{{ __('ui.subcontract_semi_finished.mo_number') }}</th><th>{{ __('ui.common.action') }}</th>
            </tr></thead>
        </table></div>
        <div class="small text-muted mt-2">{{ __('ui.subcontract_semi_finished.stock_notice') }}</div>
    </div>
</div>

<div class="modal fade" id="semiFinishedModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <form id="semiFinishedForm">@csrf
        <div class="modal-header"><h5 class="modal-title" id="semiFinishedModalTitle">{{ __('ui.subcontract_semi_finished.add') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
        <div class="modal-body">
            <input type="hidden" id="semiFinishedId">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">{{ __('ui.subcontract_semi_finished.number') }}</label><input id="semiFinishedNumber" class="form-control form-control-sm" readonly><div class="form-text">{{ __('ui.subcontract_semi_finished.number_help') }}</div></div>
                <div class="col-md-6"><label for="semiFinishedDate" class="form-label">{{ __('ui.subcontract_semi_finished.date') }} *</label><input type="date" name="production_date" id="semiFinishedDate" class="form-control form-control-sm" required></div>
                <div class="col-md-8"><label class="form-label">{{ __('ui.subcontract_semi_finished.product_code') }} *</label><div class="input-group input-group-sm"><input id="semiFinishedCode" class="form-control" readonly required><input type="hidden" name="product_id" id="semiFinishedProductId"><button type="button" id="chooseSemiFinishedProduct" class="btn btn-outline-primary">{{ __('ui.common.select') }}</button></div></div>
                <div class="col-md-4"><label class="form-label">{{ __('ui.subcontract_semi_finished.unit') }}</label><input id="semiFinishedUnit" class="form-control form-control-sm" readonly></div>
                <div class="col-md-8"><label class="form-label">{{ __('ui.subcontract_semi_finished.product_name') }}</label><input id="semiFinishedName" class="form-control form-control-sm" readonly></div>
                <div class="col-md-4"><label for="semiFinishedQuantity" class="form-label">{{ __('ui.subcontract_semi_finished.quantity') }} *</label><input type="number" min="0.0001" step="0.0001" name="quantity" id="semiFinishedQuantity" class="form-control form-control-sm" required></div>
                <div class="col-md-8"><label class="form-label">{{ __('ui.subcontract_semi_finished.mo_number') }}</label><div class="input-group input-group-sm"><input id="semiFinishedMoNumber" class="form-control" readonly><input type="hidden" name="production_order_id" id="semiFinishedMoId"><button type="button" id="chooseSemiFinishedMo" class="btn btn-outline-primary">{{ __('ui.subcontract_semi_finished.choose_mo') }}</button><button type="button" id="clearSemiFinishedMo" class="btn btn-outline-secondary" title="{{ __('ui.common.clear') }}"><i data-feather="x" class="icon-sm"></i></button></div></div>
                <div class="col-md-4"><label for="semiFinishedBuyerPo" class="form-label">{{ __('ui.subcontract_semi_finished.buyer_po') }}</label><input type="text" name="buyer_po_number" id="semiFinishedBuyerPo" maxlength="100" class="form-control form-control-sm"></div>
                <div class="col-12"><label for="semiFinishedDescription" class="form-label">{{ __('ui.subcontract_semi_finished.description') }}</label><textarea name="description" id="semiFinishedDescription" rows="2" maxlength="5000" class="form-control form-control-sm"></textarea></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button><button type="submit" class="btn btn-primary btn-sm"><i data-feather="save" class="icon-sm me-1"></i>{{ __('ui.common.save') }}</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="semiFinishedProductModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.subcontract_semi_finished.search_products') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="semiFinishedProductsTable" class="table table-sm table-bordered w-100"><thead><tr><th>{{ __('ui.subcontract_semi_finished.product_code') }}</th><th>{{ __('ui.subcontract_semi_finished.product_name') }}</th><th>{{ __('ui.subcontract_semi_finished.style') }}</th><th>{{ __('ui.subcontract_semi_finished.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>

<div class="modal fade" id="semiFinishedMoModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.subcontract_semi_finished.search_mo') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="semiFinishedMoTable" class="table table-sm table-bordered w-100"><thead><tr><th>{{ __('ui.subcontract_semi_finished.mo_number') }}</th><th>{{ __('ui.subcontract_semi_finished.product_code') }}</th><th>{{ __('ui.subcontract_semi_finished.buyer_po') }}</th><th>{{ __('ui.common.quantity') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection

@push('scripts')
<script>
$(function () {
    const entryModal = new bootstrap.Modal(document.getElementById('semiFinishedModal'));
    const productModal = new bootstrap.Modal(document.getElementById('semiFinishedProductModal'));
    const moModal = new bootstrap.Modal(document.getElementById('semiFinishedMoModal'));
    const showError = (xhr) => Swal.fire({icon:'error', title:@json(__('ui.workflow.failure')), text:Object.values(xhr.responseJSON?.errors || {}).flat().join('\n') || xhr.responseJSON?.message || @json(__('ui.workflow.save_failed'))});
    const table = $('#semiFinishedTable').DataTable({
        processing:true, serverSide:true, responsive:true,
        ajax:{url:@json(route('super.subcontract.semi-finished.dt')), data:d=>{d.from_date=$('#semiFinishedFrom').val();d.to_date=$('#semiFinishedTo').val()}},
        columns:[
            {data:'id',orderable:false,searchable:false,render:(v,t,r,m)=>m.row+m.settings._iDisplayStart+1},
            {data:'number_label',name:'document.number',orderable:false}, {data:'date_label',name:'document.production_date',orderable:false},
            {data:'description_label',name:'document.description',orderable:false}, {data:'buyer_po_label',name:'document.buyer_po_number',orderable:false},
            {data:'style_label',orderable:false,searchable:false}, {data:'product_code',orderable:false,searchable:false}, {data:'product_name',orderable:false,searchable:false},
            {data:'unit_label',orderable:false,searchable:false}, {data:'quantity_label',name:'quantity',orderable:false}, {data:'mo_label',orderable:false,searchable:false},
            {data:'actions',orderable:false,searchable:false}
        ], order:[[2,'desc']], drawCallback:()=>window.feather&&feather.replace()
    });
    const products = $('#semiFinishedProductsTable').DataTable({processing:true,serverSide:true,ajax:@json(route('super.subcontract.semi-finished.products.dt')),columns:[
        {data:'code',name:'code'},{data:'name',name:'name'},{data:'style_label',orderable:false,searchable:false},{data:'unit_label',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false}
    ], drawCallback:()=>window.feather&&feather.replace()});
    const mos = $('#semiFinishedMoTable').DataTable({processing:true,serverSide:true,ajax:@json(route('super.subcontract.semi-finished.production-orders.dt')),columns:[
        {data:'number',name:'number'},{data:'product_label',orderable:false,searchable:false},{data:'buyer_po_number',name:'buyer_po_number',defaultContent:'—'},{data:'planned_quantity',name:'planned_quantity'},{data:'actions',orderable:false,searchable:false}
    ], drawCallback:()=>window.feather&&feather.replace()});

    function resetForm(loadNumber = true) {
        $('#semiFinishedForm')[0].reset(); $('#semiFinishedId,#semiFinishedProductId,#semiFinishedMoId').val('');
        $('#semiFinishedCode,#semiFinishedName,#semiFinishedUnit,#semiFinishedMoNumber').val('');
        $('#semiFinishedDate').val(@json(now()->toDateString())); $('#semiFinishedModalTitle').text(@json(__('ui.subcontract_semi_finished.add')));
        $('#semiFinishedNumber').val(loadNumber ? @json(__('ui.subcontract_semi_finished.number_loading')) : '');
        if (loadNumber) $.get(@json(route('super.subcontract.semi-finished.number-preview'))).done(r=>$('#semiFinishedNumber').val(r.number)).fail(()=>$('#semiFinishedNumber').val(@json(__('ui.subcontract_semi_finished.number_unavailable'))));
    }
    $('#addSemiFinished').on('click',()=>{resetForm();entryModal.show()});
    $('#showSemiFinished').on('click',()=>table.ajax.reload());
    $('#semiFinishedFrom,#semiFinishedTo').on('change',()=>table.ajax.reload());
    $('#chooseSemiFinishedProduct').on('click',()=>{productModal.show();products.ajax.reload()});
    $('#chooseSemiFinishedMo').on('click',()=>{moModal.show();mos.ajax.reload()});
    $('#clearSemiFinishedMo').on('click',()=>{$('#semiFinishedMoId,#semiFinishedMoNumber').val('')});
    $(document).on('click','.js-select-semi-finished-product',function(){const b=$(this);$('#semiFinishedProductId').val(b.data('id'));$('#semiFinishedCode').val(b.data('code'));$('#semiFinishedName').val(b.data('name'));$('#semiFinishedUnit').val(b.data('unit'));productModal.hide()});
    $(document).on('click','.js-select-semi-finished-mo',function(){const b=$(this);$('#semiFinishedMoId').val(b.data('id'));$('#semiFinishedMoNumber').val(b.data('number'));if(b.data('buyer-po'))$('#semiFinishedBuyerPo').val(b.data('buyer-po'));moModal.hide()});
    $(document).on('click','.js-edit-semi-finished',function(){const id=$(this).data('id');$.get(@json(route('super.subcontract.semi-finished.show',['document'=>'__ID__'])).replace('__ID__',id)).done(d=>{resetForm(false);$('#semiFinishedId').val(d.id);$('#semiFinishedNumber').val(d.number);$('#semiFinishedDate').val(d.production_date);$('#semiFinishedMoId').val(d.production_order_id||'');$('#semiFinishedMoNumber').val(d.mo_number||'');$('#semiFinishedBuyerPo').val(d.buyer_po_number||'');$('#semiFinishedDescription').val(d.description||'');$('#semiFinishedProductId').val(d.product?.id||'');$('#semiFinishedCode').val(d.product?.code||'');$('#semiFinishedName').val(d.product?.name||'');$('#semiFinishedUnit').val(d.product?.unit||'');$('#semiFinishedQuantity').val(d.quantity||'');$('#semiFinishedModalTitle').text(@json(__('ui.subcontract_semi_finished.edit')));entryModal.show()}).fail(showError)});
    $(document).on('click','.js-delete-semi-finished',function(){const id=$(this).data('id');Swal.fire({icon:'warning',title:@json(__('ui.common.confirm_delete')),showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(r=>{if(r.isConfirmed)$.ajax({url:@json(route('super.subcontract.semi-finished.destroy',['document'=>'__ID__'])).replace('__ID__',id),method:'DELETE',data:{_token:@json(csrf_token())}}).done(x=>{table.ajax.reload(null,false);Swal.fire({icon:'success',text:x.message})}).fail(showError)})});
    $('#semiFinishedForm').on('submit',function(e){e.preventDefault();const id=$('#semiFinishedId').val();const url=id?@json(route('super.subcontract.semi-finished.update',['document'=>'__ID__'])).replace('__ID__',id):@json(route('super.subcontract.semi-finished.store'));$.ajax({url,method:id?'PUT':'POST',data:$(this).serialize()}).done(r=>{entryModal.hide();table.ajax.reload(null,false);Swal.fire({icon:'success',text:id?r.message:`${r.message} (${r.number})`})}).fail(showError)});
});
</script>
@endpush
