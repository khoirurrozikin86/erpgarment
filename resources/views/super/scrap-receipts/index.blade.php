@extends('layouts.admin')

@section('title', __('ui.scrap_receipt.title'))

@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.scrap_materials') }}</li><li class="breadcrumb-item active">{{ __('ui.scrap_receipt.title') }}</li></ol></nav>
@endsection

@section('content')
<div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">{{ __('ui.scrap_receipt.title') }}</h5>@can('scrap-receipts.create')<a class="btn btn-primary btn-sm" href="{{ route('super.scrap-receipts.create') }}"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.scrap_receipt.add') }}</a>@endcan</div>
<div class="card-body"><div class="row g-2 align-items-end mb-3">
    <div class="col-sm-4 col-md-3"><label class="form-label mb-1" for="scrapFromDate">{{ __('ui.common.from_date') }}</label><input type="date" id="scrapFromDate" class="form-control form-control-sm" value="{{ request('from_date', now()->startOfMonth()->toDateString()) }}"></div>
    <div class="col-sm-4 col-md-3"><label class="form-label mb-1" for="scrapToDate">{{ __('ui.common.to_date') }}</label><input type="date" id="scrapToDate" class="form-control form-control-sm" value="{{ request('to_date', now()->toDateString()) }}"></div>
    <div class="col-auto"><button type="button" class="btn btn-primary btn-sm" id="showScrapData"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div>
    <div class="col-auto"><a class="btn btn-outline-success btn-sm" id="exportScrapReceipts" href="{{ route('super.scrap-receipts.export') }}"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a></div>
</div>
<div class="table-responsive"><table id="scrapReceiptTable" class="table table-bordered table-hover align-middle w-100"><thead><tr>
    <th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.scrap_receipt.number') }}</th><th>{{ __('ui.scrap_receipt.date') }}</th><th>{{ __('ui.scrap_receipt.scrap_code') }}</th><th>{{ __('ui.scrap_receipt.scrap_name') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.scrap_receipt.quantity') }}</th><th>{{ __('ui.scrap_receipt.source_code') }}</th><th>{{ __('ui.scrap_receipt.source_name') }}</th><th>{{ __('ui.scrap_receipt.production_number') }}</th><th>{{ __('ui.common.description') }}</th>
</tr></thead></table></div><div class="small text-muted mt-2">{{ __('ui.scrap_receipt.stock_notice') }} {{ __('ui.scrap_receipt.confirmation_note') }}</div></div></div>

@endsection

@push('scripts')
<script>
$(function(){
    @if(session('scrap_success'))
        Swal.fire({icon: 'success', text: @json(session('scrap_success'))});
    @endif
    const table=$('#scrapReceiptTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.scrap-receipts.dt')),data:d=>{d.from_date=$('#scrapFromDate').val();d.to_date=$('#scrapToDate').val();}},columns:[
        {data:'id',orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},{data:'actions',orderable:false,searchable:false,className:'text-center'},
        {data:'number',name:'number'},{data:'date_label',name:'receipt_date',render:d=>d?new Date(d+'T00:00:00').toLocaleDateString():''},{data:'scrap_code',name:'scrap_code',orderable:false},{data:'scrap_name',name:'scrap_name',orderable:false},{data:'unit_label',orderable:false,searchable:false},
        {data:'quantity',name:'quantity',className:'text-end',render:d=>Number(d).toLocaleString(undefined,{maximumFractionDigits:4})},{data:'source_code',name:'source_code',orderable:false},{data:'source_name',name:'source_name',orderable:false},{data:'production_number',name:'production_number',defaultContent:'—'},{data:'description',name:'description',defaultContent:'—'}
    ],order:[[3,'desc']],drawCallback:()=>window.feather&&feather.replace()});
    $('#showScrapData').on('click',()=>table.ajax.reload());
    $('#scrapFromDate,#scrapToDate').on('change',()=>table.ajax.reload());
    $('#exportScrapReceipts').on('click',function(e){e.preventDefault();const params=$.param({from_date:$('#scrapFromDate').val(),to_date:$('#scrapToDate').val()});window.location=this.href+'?'+params;});
    $(document).on('click','.btn-delete-scrap-receipt',function(){const b=$(this);Swal.fire({icon:'warning',title:@json(__('ui.common.confirm_delete')),text:@json(__('ui.scrap_receipt.delete_help')),showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(r=>{if(!r.isConfirmed)return;$.ajax({url:b.data('url'),method:'DELETE',data:{_token:@json(csrf_token())}}).done(x=>{table.ajax.reload(null,false);Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:x.message});}).fail(x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))}));});});
});
</script>
@endpush
