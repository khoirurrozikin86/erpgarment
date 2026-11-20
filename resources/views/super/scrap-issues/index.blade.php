@extends('layouts.admin')

@section('title', __('ui.scrap_issue.title'))
@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.scrap_materials') }}</li><li class="breadcrumb-item active">{{ __('ui.scrap_issue.title') }}</li></ol></nav>
@endsection

@section('content')
<div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">{{ __('ui.scrap_issue.title') }}</h5>@can('scrap-issues.create')<a class="btn btn-primary btn-sm" href="{{ route('super.scrap-issues.create') }}"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.scrap_issue.add') }}</a>@endcan</div>
<div class="card-body"><div class="row g-2 align-items-end mb-3">
    <div class="col-sm-4 col-md-3"><label class="form-label mb-1" for="scrapIssueFrom">{{ __('ui.common.from_date') }}</label><input type="date" id="scrapIssueFrom" class="form-control form-control-sm" value="{{ request('from_date', now()->startOfMonth()->toDateString()) }}"></div>
    <div class="col-sm-4 col-md-3"><label class="form-label mb-1" for="scrapIssueTo">{{ __('ui.common.to_date') }}</label><input type="date" id="scrapIssueTo" class="form-control form-control-sm" value="{{ request('to_date', now()->toDateString()) }}"></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm" id="showScrapIssues"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div>
    <div class="col-auto"><a class="btn btn-outline-success btn-sm" id="exportScrapIssues" href="{{ route('super.scrap-issues.export') }}"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a></div>
</div>
<div class="table-responsive"><table id="scrapIssuesTable" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.scrap_issue.number') }}</th><th>{{ __('ui.scrap_issue.date') }}</th><th>{{ __('ui.scrap_issue.customer') }}</th><th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.scrap_issue.quantity') }}</th><th>{{ __('ui.common.description') }}</th></tr></thead></table></div>
<div class="small text-muted mt-2">{{ __('ui.scrap_issue.stock_notice') }}</div></div></div>

@endsection

@push('scripts')
<script>
$(function(){
    @if(session('scrap_success'))
        Swal.fire({icon: 'success', text: @json(session('scrap_success'))});
    @endif
    const table=$('#scrapIssuesTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.scrap-issues.dt')),data:d=>{d.from_date=$('#scrapIssueFrom').val();d.to_date=$('#scrapIssueTo').val();}},columns:[
        {data:'id',orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},{data:'actions',orderable:false,searchable:false,className:'text-center'},{data:'number',name:'number'},{data:'date_label',name:'issue_date'},{data:'customer_name',name:'customer_name',orderable:false},{data:'scrap_code',name:'scrap_code',orderable:false},{data:'scrap_name',name:'scrap_name',orderable:false},{data:'unit_label',orderable:false,searchable:false},{data:'quantity',name:'quantity',className:'text-end',render:d=>Number(d).toLocaleString(undefined,{maximumFractionDigits:4})},{data:'description',name:'description',defaultContent:'—'}
    ],order:[[3,'desc']],drawCallback:()=>window.feather&&feather.replace()});
    $('#showScrapIssues').on('click',()=>table.ajax.reload());$('#scrapIssueFrom,#scrapIssueTo').on('change',()=>table.ajax.reload());
    $('#exportScrapIssues').on('click',function(e){e.preventDefault();window.location=this.href+'?'+$.param({from_date:$('#scrapIssueFrom').val(),to_date:$('#scrapIssueTo').val()});});
    $(document).on('click','.btn-delete-scrap-issue',function(){const b=$(this);Swal.fire({icon:'warning',title:@json(__('ui.common.confirm_delete')),text:@json(__('ui.scrap_issue.delete_help')),showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(r=>{if(!r.isConfirmed)return;$.ajax({url:b.data('url'),method:'DELETE',data:{_token:@json(csrf_token())}}).done(x=>{table.ajax.reload(null,false);Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:x.message});}).fail(x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))}));});});
});
</script>
@endpush
