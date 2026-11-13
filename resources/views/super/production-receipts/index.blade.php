@extends('layouts.admin')
@section('title', __('ui.production_receipt.title'))
@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.production') }}</li><li class="breadcrumb-item active">{{ __('ui.production_receipt.title') }}</li></ol></nav>
@endsection
@section('content')
<div class="mb-3"><h4 class="page-title mb-1">{{ __('ui.production_receipt.title') }}</h4><span class="text-muted">{{ __('ui.production_receipt.subtitle') }}</span></div>
@include('super.purchasing.date-filter')
<div class="card mb-4"><div class="card-body"><div class="table-responsive"><table id="productionReceiptsTable" class="table table-bordered table-hover align-middle w-100"><thead><tr>
<th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.production_receipt.approval_status') }}</th><th>{{ __('ui.production_receipt.delivery_number') }}</th><th>{{ __('ui.production_receipt.date') }}</th><th>{{ __('ui.production_receipt.order_number') }}</th>
</tr></thead></table></div></div></div>
<div class="card"><div class="card-header bg-white"><h6 id="receiptDetailTitle" class="mb-0">{{ __('ui.production_receipt.details_title') }}</h6></div><div class="card-body"><div id="receiptApprovalMeta" class="small text-muted mb-3"></div><div class="table-responsive"><table id="productionReceiptItemsTable" class="table table-sm table-bordered align-middle w-100"><thead><tr>
<th>{{ __('ui.common.no') }}</th><th>{{ __('ui.production_receipt.po_supplier') }}</th><th>{{ __('ui.production_receipt.item_code') }}</th><th>{{ __('ui.production_receipt.item_name') }}</th><th>{{ __('ui.production_receipt.unit') }}</th><th>{{ __('ui.production_receipt.style') }}</th><th class="text-end">{{ __('ui.production_receipt.quantity_received') }}</th>
</tr></thead><tbody><tr><td colspan="7" class="text-center text-muted">{{ __('ui.common.select') }}</td></tr></tbody></table></div></div></div>
@endsection
@push('scripts')
<script>
$(function(){
    const table=$('#productionReceiptsTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.production-receipts.dt')),data:d=>{d.from_date=$('#from_date').val();d.to_date=$('#to_date').val();}},columns:[
        {data:'id',orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},
        {data:'actions',orderable:false,searchable:false,className:'text-end text-nowrap'},
        {data:'approval_status',name:'production_receipt_status'},
        {data:'number',name:'number'},
        {data:'issue_date',name:'issue_date'},
        {data:'order_number',orderable:false,searchable:false}
    ],order:[[4,'desc']],drawCallback:()=>window.feather&&feather.replace()});
    function esc(v){return $('<div>').text(v??'').html();}
    function qty(v){return Number(v||0).toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:4});}
    $('#applyDateFilter,#todayDateFilter').on('click',()=>table.ajax.reload());
    $(document).on('click','.btn-show-production-receipt',function(){
        $.get($(this).data('url')).done(doc=>{
            $('#receiptDetailTitle').text(`{{ __('ui.production_receipt.details_title') }}: ${doc.number}`);
            $('#receiptApprovalMeta').text(doc.status==='received'?`{{ __('ui.production_receipt.received_by') }}: ${doc.received_by||'—'} · {{ __('ui.production_receipt.received_at') }}: ${doc.received_at||'—'}`:'');
            const rows=doc.items.map((item,index)=>`<tr><td>${index+1}</td><td>${esc(item.purchase_order||'—')}</td><td>${esc(item.code)}</td><td>${esc(item.name)}</td><td>${esc(item.unit)}</td><td>${esc(item.style)}</td><td class="text-end">${qty(item.quantity)}</td></tr>`).join('');
            $('#productionReceiptItemsTable tbody').html(rows||'<tr><td colspan="7" class="text-center text-muted">{{ __('ui.common.no_matching_records') }}</td></tr>');
            document.getElementById('receiptDetailTitle').scrollIntoView({behavior:'smooth',block:'center'});
        });
    });
    $(document).on('click','.btn-approve-production-receipt',function(){
        const button=$(this);
        Swal.fire({icon:'question',title:'{{ __('ui.production_receipt.approve_title') }}',text:'{{ __('ui.production_receipt.approve_help') }}',showCancelButton:true,confirmButtonText:'{{ __('ui.workflow.approve') }}',cancelButtonText:'{{ __('ui.common.cancel') }}'})
            .then(result=>{if(!result.isConfirmed)return;$.ajax({url:button.data('url'),method:'PUT',data:{_token:@json(csrf_token())}})
                .done(response=>{table.ajax.reload(null,false);Swal.fire({icon:'success',title:'{{ __('ui.modules.success') }}',text:response.message});})
                .fail(xhr=>Swal.fire({icon:'error',title:'{{ __('ui.modules.failure') }}',text:Object.values(xhr.responseJSON?.errors||{}).flat().join('\n')||xhr.responseJSON?.message||'{{ __('ui.production_receipt.invalid_source') }}'}));});
    });
});
</script>
@endpush
