@extends('layouts.admin')

@section('title', __('ui.scrap_receipt.title'))

@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.scrap_materials') }}</li><li class="breadcrumb-item active">{{ __('ui.scrap_receipt.title') }}</li></ol></nav>
@endsection

@section('content')
<div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">{{ __('ui.scrap_receipt.title') }}</h5>@can('scrap-receipts.create')<button type="button" id="addScrapReceipt" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.scrap_receipt.add') }}</button>@endcan</div>
<div class="card-body"><div class="row g-2 align-items-end mb-3">
    <div class="col-sm-4 col-md-3"><label class="form-label mb-1" for="scrapFromDate">{{ __('ui.common.from_date') }}</label><input type="date" id="scrapFromDate" class="form-control form-control-sm" value="{{ now()->subDays(30)->toDateString() }}"></div>
    <div class="col-sm-4 col-md-3"><label class="form-label mb-1" for="scrapToDate">{{ __('ui.common.to_date') }}</label><input type="date" id="scrapToDate" class="form-control form-control-sm" value="{{ now()->toDateString() }}"></div>
    <div class="col-auto"><button type="button" class="btn btn-primary btn-sm" id="showScrapData"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div>
    <div class="col-auto"><a class="btn btn-outline-success btn-sm" id="exportScrapReceipts" href="{{ route('super.scrap-receipts.export') }}"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a></div>
</div>
<div class="table-responsive"><table id="scrapReceiptTable" class="table table-bordered table-hover align-middle w-100"><thead><tr>
    <th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.scrap_receipt.number') }}</th><th>{{ __('ui.scrap_receipt.date') }}</th><th>{{ __('ui.scrap_receipt.scrap_code') }}</th><th>{{ __('ui.scrap_receipt.scrap_name') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.scrap_receipt.quantity') }}</th><th>{{ __('ui.scrap_receipt.source_code') }}</th><th>{{ __('ui.scrap_receipt.source_name') }}</th><th>{{ __('ui.scrap_receipt.production_number') }}</th><th>{{ __('ui.common.description') }}</th>
</tr></thead></table></div><div class="small text-muted mt-2">{{ __('ui.scrap_receipt.stock_notice') }} {{ __('ui.scrap_receipt.confirmation_note') }}</div></div></div>

<div class="modal fade" id="scrapReceiptModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form id="scrapReceiptForm">@csrf
    <div class="modal-header"><h5 class="modal-title" id="scrapReceiptModalTitle">{{ __('ui.scrap_receipt.add') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
    <div class="modal-body"><input type="hidden" id="scrapReceiptId"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">{{ __('ui.scrap_receipt.number') }}</label><input class="form-control form-control-sm" id="scrapReceiptNumber" readonly></div>
        <div class="col-md-6"><label class="form-label">{{ __('ui.scrap_receipt.date') }} *</label><input type="date" class="form-control form-control-sm" id="scrapReceiptDate" name="receipt_date" value="{{ now()->toDateString() }}" required></div>
        <div class="col-md-6"><label class="form-label">{{ __('ui.scrap_receipt.scrap_code') }} *</label><div class="input-group input-group-sm"><input class="form-control" id="scrapProductLabel" readonly required placeholder="{{ __('ui.scrap_receipt.select_scrap') }}"><button class="btn btn-outline-primary" type="button" id="chooseScrapProduct">{{ __('ui.common.select') }}</button></div><input type="hidden" name="scrap_product_id" id="scrapProductId"></div>
        <div class="col-md-6"><label class="form-label">{{ __('ui.scrap_receipt.quantity') }} *</label><input type="number" class="form-control form-control-sm text-end" name="quantity" id="scrapReceiptQuantity" min="0.0001" step="0.0001" required></div>
        <div class="col-md-6"><label class="form-label">{{ __('ui.scrap_receipt.source_code') }} *</label><div class="input-group input-group-sm"><input class="form-control" id="sourceProductLabel" readonly required placeholder="{{ __('ui.scrap_receipt.select_source') }}"><button class="btn btn-outline-primary" type="button" id="chooseSourceProduct">{{ __('ui.common.select') }}</button></div><input type="hidden" name="source_product_id" id="sourceProductId"></div>
        <div class="col-md-6"><label class="form-label">{{ __('ui.scrap_receipt.production_number') }}</label><input class="form-control form-control-sm" name="production_number" id="scrapProductionNumber" maxlength="100"></div>
        <div class="col-12"><label class="form-label">{{ __('ui.common.description') }}</label><textarea class="form-control form-control-sm" name="description" id="scrapDescription" rows="2" maxlength="5000"></textarea></div>
        <div class="col-12 small text-muted">{{ __('ui.scrap_receipt.stock_notice') }} {{ __('ui.scrap_receipt.confirmation_note') }}</div>
    </div></div><div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button><button type="submit" class="btn btn-primary btn-sm" id="saveScrapReceipt">{{ __('ui.common.save') }}</button></div>
</form></div></div></div>

<div class="modal fade" id="scrapProductModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="scrapProductModalTitle"></h5><button class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="scrapProductsTable" class="table table-sm table-bordered align-middle w-100"><thead><tr><th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection

@push('scripts')
<script>
$(function(){
    const receiptModal=new bootstrap.Modal(document.getElementById('scrapReceiptModal'));
    const productModal=new bootstrap.Modal(document.getElementById('scrapProductModal'));
    let pickerTarget='scrap';
    const table=$('#scrapReceiptTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.scrap-receipts.dt')),data:d=>{d.from_date=$('#scrapFromDate').val();d.to_date=$('#scrapToDate').val();}},columns:[
        {data:'id',orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},{data:'actions',orderable:false,searchable:false,className:'text-center'},
        {data:'number',name:'number'},{data:'date_label',name:'receipt_date',render:d=>d?new Date(d+'T00:00:00').toLocaleDateString():''},{data:'scrap_code',name:'scrapProduct.code'},{data:'scrap_name',name:'scrapProduct.name'},{data:'unit_label',orderable:false,searchable:false},
        {data:'quantity',name:'quantity',className:'text-end',render:d=>Number(d).toLocaleString(undefined,{maximumFractionDigits:4})},{data:'source_code',name:'sourceProduct.code'},{data:'source_name',name:'sourceProduct.name'},{data:'production_number',name:'production_number',defaultContent:'—'},{data:'description',name:'description',defaultContent:'—'}
    ],order:[[3,'desc']],drawCallback:()=>window.feather&&feather.replace()});
    const products=$('#scrapProductsTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.scrap-receipts.products.dt')),data:d=>{d.type=pickerTarget;}},columns:[{data:'code',name:'code'},{data:'name',name:'name'},{data:'unit_label',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false,className:'text-center'}],drawCallback:()=>window.feather&&feather.replace()});
    function setPicker(type){pickerTarget=type;$('#scrapProductModalTitle').text(type==='scrap'?@json(__('ui.scrap_receipt.select_scrap')):@json(__('ui.scrap_receipt.select_source')));products.ajax.reload();productModal.show();}
    function reset(){ $('#scrapReceiptForm')[0].reset();$('#scrapReceiptId,#scrapProductId,#sourceProductId').val('');$('#scrapProductLabel,#sourceProductLabel').val('');$('#scrapReceiptDate').val(@json(now()->toDateString()));$('#scrapReceiptModalTitle').text(@json(__('ui.scrap_receipt.add'))); }
    $('#showScrapData').on('click',()=>table.ajax.reload());
    $('#scrapFromDate,#scrapToDate').on('change',()=>table.ajax.reload());
    $('#exportScrapReceipts').on('click',function(e){e.preventDefault();const params=$.param({from_date:$('#scrapFromDate').val(),to_date:$('#scrapToDate').val()});window.location=this.href+'?'+params;});
    $('#addScrapReceipt').on('click',()=>{reset();$('#scrapReceiptNumber').val(@json(__('ui.scrap_receipt.number_loading')));receiptModal.show();$.get(@json(route('super.scrap-receipts.number-preview'))).done(r=>$('#scrapReceiptNumber').val(r.number)).fail(()=>$('#scrapReceiptNumber').val(@json(__('ui.scrap_receipt.number_unavailable'))));});
    $('#chooseScrapProduct').on('click',()=>setPicker('scrap'));$('#chooseSourceProduct').on('click',()=>setPicker('source'));
    $(document).on('click','.btn-select-scrap-product',function(){const b=$(this),scrap=pickerTarget==='scrap';$(scrap?'#scrapProductId':'#sourceProductId').val(b.data('id'));$(scrap?'#scrapProductLabel':'#sourceProductLabel').val(`${b.data('code')} — ${b.data('name')} (${b.data('unit')})`);productModal.hide();});
    $(document).on('click','.btn-edit-scrap-receipt',function(){const b=$(this);reset();$('#scrapReceiptId').val(b.data('id'));$('#scrapReceiptNumber').val(b.data('number'));$('#scrapReceiptDate').val(b.data('date'));$('#scrapProductId').val(b.data('scrap'));$('#scrapProductLabel').val(`${b.data('scrap-code')} — ${b.data('scrap-name')} (${b.data('scrap-unit')})`);$('#sourceProductId').val(b.data('source'));$('#sourceProductLabel').val(`${b.data('source-code')} — ${b.data('source-name')} (${b.data('source-unit')})`);$('#scrapProductionNumber').val(b.data('production'));$('#scrapReceiptQuantity').val(b.data('quantity'));$('#scrapDescription').val(b.data('description'));$('#scrapReceiptModalTitle').text(@json(__('ui.common.edit'))+' {{ __('ui.scrap_receipt.title') }}');receiptModal.show();});
    $(document).on('click','.btn-delete-scrap-receipt',function(){const b=$(this);Swal.fire({icon:'warning',title:@json(__('ui.common.confirm_delete')),text:@json(__('ui.scrap_receipt.delete_help')),showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(r=>{if(!r.isConfirmed)return;$.ajax({url:b.data('url'),method:'DELETE',data:{_token:@json(csrf_token())}}).done(x=>{table.ajax.reload(null,false);Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:x.message});}).fail(x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))}));});});
    $('#scrapReceiptForm').on('submit',function(e){e.preventDefault();const id=$('#scrapReceiptId').val(),url=id?@json(route('super.scrap-receipts.update',['scrapReceipt'=>'__ID__'])).replace('__ID__',id):@json(route('super.scrap-receipts.store')),button=$('#saveScrapReceipt').prop('disabled',true);$.ajax({url,method:id?'PUT':'POST',data:$(this).serialize()}).done(r=>{receiptModal.hide();table.ajax.reload(null,false);Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:r.number?`${r.message} (${r.number})`:r.message});}).fail(x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:Object.values(x.responseJSON?.errors||{}).flat().join('\n')||x.responseJSON?.message||@json(__('ui.workflow.save_failed'))})).always(()=>button.prop('disabled',false));});
});
</script>
@endpush
