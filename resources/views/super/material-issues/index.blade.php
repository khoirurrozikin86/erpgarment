@extends('layouts.admin')
@section('title', __('ui.material_issue.title'))
@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.warehouse_inventory') }}</li><li class="breadcrumb-item active">{{ __('ui.material_issue.title') }}</li></ol></nav>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">{{ __('ui.material_issue.title') }}</h4><span class="text-muted">{{ __('ui.material_issue.subtitle') }}</span></div>@can('material-issues.view')<a href="{{ route('super.material-issues.export') }}" id="exportMaterialIssues" class="btn btn-outline-success btn-sm"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>@endcan</div>
<div class="card mb-4"><div class="card-header bg-white"><h6 class="mb-0">{{ __('ui.material_issue.requests_title') }}</h6></div><div class="card-body"><div class="table-responsive"><table id="productionRequestsTable" class="table table-bordered table-hover align-middle w-100"><thead><tr>
<th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.material_issue.mo_number') }}</th><th>{{ __('ui.material_issue.request_number') }}</th><th>{{ __('ui.material_issue.request_date') }}</th><th>{{ __('ui.material_issue.number') }}</th><th>{{ __('ui.material_issue.date') }}</th>
</tr></thead></table></div></div></div>
@include('super.purchasing.date-filter')
<div class="card"><div class="card-header bg-white"><h6 class="mb-0">{{ __('ui.material_issue.history_title') }}</h6></div><div class="card-body"><div class="table-responsive"><table id="materialIssuesTable" class="table table-bordered table-hover align-middle w-100"><thead><tr>
<th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.material_issue.number') }}</th><th>{{ __('ui.material_issue.date') }}</th><th>{{ __('ui.material_issue.request_number') }}</th><th>{{ __('ui.material_issue.mo_number') }}</th>
</tr></thead></table></div></div></div>
@endsection
@push('scripts')
<script>
$(function () {
    const indexUrl = @json(route('super.material-issues.requests.dt'));
    const historyUrl = @json(route('super.material-issues.history.dt'));
    const requestTable = $('#productionRequestsTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: {url: indexUrl},
        columns: [
            {data:'id', orderable:false, searchable:false, render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},
            {data:'actions', orderable:false, searchable:false, className:'text-center'},
            {data:'mo_number', orderable:false, searchable:false},
            {data:'request_number', name:'number'}, {data:'request_date', name:'order_date', render:dateDisplay},
            {data:'mis_number', orderable:false, searchable:false}, {data:'mis_date', orderable:false, searchable:false, render:dateDisplay}
        ], order:[[4,'desc']], drawCallback:()=>window.feather&&feather.replace()
    });
    const historyTable = $('#materialIssuesTable').DataTable({
        processing:true, serverSide:true, responsive:true,
        ajax:{url:historyUrl,data:d=>{d.from_date=$('#from_date').val();d.to_date=$('#to_date').val();}},
        columns:[
            {data:'id',orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},
            {data:'actions',orderable:false,searchable:false,className:'text-center'},
            {data:'number',name:'number'},{data:'issue_date',name:'issue_date',render:dateDisplay},
            {data:'request_number',orderable:false,searchable:false},{data:'mo_number',orderable:false,searchable:false}
        ],order:[[3,'desc']],drawCallback:()=>window.feather&&feather.replace()
    });
    function dateDisplay(value) { if(!value)return '—'; const parts=String(value).slice(0,10).split('-'); return parts.length===3?`${parts[2]}-${parts[1]}-${parts[0]}`:value; }
    function esc(value) { return $('<div>').text(value??'').html(); }
    $('#applyDateFilter,#todayDateFilter').on('click',()=>historyTable.ajax.reload());
    $('#exportMaterialIssues').on('click',function(e){e.preventDefault();const p=new URLSearchParams();if($('#from_date').val())p.set('from_date',$('#from_date').val());if($('#to_date').val())p.set('to_date',$('#to_date').val());location.href=$(this).attr('href')+(p.size?'?'+p.toString():'');});

    $(document).on('click','.btn-edit-material-issue',function(){
        const button=$(this);
        Swal.fire({
            title:`{{ __('ui.material_issue.edit_title') }} · ${esc(button.data('number'))}`,
            html:`<div class="text-start"><label class="form-label">{{ __('ui.material_issue.date') }}</label><input id="editMisDate" type="date" class="form-control mb-3" value="${esc(button.data('date'))}"><label class="form-label">{{ __('ui.material_issue.send_now') }}</label><input id="editMisQuantity" type="number" min="0.0001" step="0.0001" class="form-control" value="${Number(button.data('quantity')||0)}"><small class="text-muted">{{ __('ui.material_issue.edit_help') }}</small></div>`,
            showCancelButton:true,confirmButtonText:'{{ __('ui.common.save') }}',cancelButtonText:'{{ __('ui.common.cancel') }}',
            preConfirm:()=>{const issue_date=$('#editMisDate').val(),quantity=Number($('#editMisQuantity').val());if(!issue_date||!Number.isFinite(quantity)||quantity<=0){Swal.showValidationMessage('{{ __('ui.material_issue.quantity_invalid') }}');return false;}return {issue_date,quantity};}
        }).then(result=>{
            if(!result.isConfirmed)return;
            $.ajax({url:button.data('url'),method:'PUT',data:{_token:@json(csrf_token()),...result.value}})
                .done(response=>{requestTable.ajax.reload(null,false);historyTable.ajax.reload(null,false);Swal.fire({icon:'success',title:'{{ __('ui.modules.success') }}',text:response.message});})
                .fail(xhr=>Swal.fire({icon:'error',title:'{{ __('ui.modules.failure') }}',text:Object.values(xhr.responseJSON?.errors||{}).flat().join('\n')||xhr.responseJSON?.message||'{{ __('ui.material_issue.failed') }}'}));
        });
    });
    $(document).on('click','.btn-delete-material-issue',function(){
        const button=$(this);
        Swal.fire({icon:'warning',title:'{{ __('ui.material_issue.delete_title') }}',text:`{{ __('ui.material_issue.delete_help') }} · ${esc(button.data('number'))}`,showCancelButton:true,confirmButtonText:'{{ __('ui.common.confirm_delete') }}',cancelButtonText:'{{ __('ui.common.cancel') }}'})
            .then(result=>{if(!result.isConfirmed)return;$.ajax({url:button.data('url'),method:'DELETE',data:{_token:@json(csrf_token())}})
                .done(response=>{requestTable.ajax.reload(null,false);historyTable.ajax.reload(null,false);Swal.fire({icon:'success',title:'{{ __('ui.modules.success') }}',text:response.message});})
                .fail(xhr=>Swal.fire({icon:'error',title:'{{ __('ui.modules.failure') }}',text:Object.values(xhr.responseJSON?.errors||{}).flat().join('\n')||xhr.responseJSON?.message||'{{ __('ui.material_issue.failed') }}'}));});
    });


});
</script>
@endpush
