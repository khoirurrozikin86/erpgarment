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
<th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.material_issue.number') }}</th><th>{{ __('ui.material_issue.date') }}</th><th>{{ __('ui.material_issue.request_number') }}</th><th>{{ __('ui.material_issue.mo_number') }}</th><th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.material_issue.sent_now') }}</th>
</tr></thead></table></div></div></div>
@endsection
@push('scripts')
<script>
$(function () {
    const indexUrl = @json(route('super.material-issues.requests.dt'));
    const historyUrl = @json(route('super.material-issues.history.dt'));
    const bulkIssueUrl = @json(route('super.material-issues.bulk.store'));
    const today = @json(now()->toDateString());
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
            {data:'number',name:'document.number'},{data:'issue_date',name:'document.issue_date',render:dateDisplay},
            {data:'request_number',orderable:false,searchable:false},{data:'mo_number',orderable:false,searchable:false},
            {data:'code',name:'product.code'},{data:'name',name:'product.name'},
            {data:'unit',orderable:false,searchable:false},{data:'quantity',name:'quantity',className:'text-end',render:fmt}
        ],order:[[3,'desc']],drawCallback:()=>window.feather&&feather.replace()
    });
    function fmt(value) { return Number(value||0).toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:4}); }
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

    $(document).on('click','.btn-open-material-issue',function(){
        $.get($(this).data('url')).done(request=>{
        const rows=request.items.map(line=>`<tr><td><small class="text-muted">${esc(line.request_number)}</small><br>${esc(line.code)}</td><td>${esc(line.name)}</td><td>${esc(line.unit)}</td><td class="text-end">${fmt(line.requested)}</td><td class="text-end">${fmt(line.sent_before)}</td><td><input type="number" class="form-control form-control-sm text-end mis-quantity" data-id="${line.id}" data-before="${line.sent_before}" data-max="${line.remaining}" min="0" max="${line.remaining}" step="0.0001" value="0" ${line.remaining<=0?'disabled':''}></td><td class="text-end mis-total">${fmt(line.sent_before)}</td></tr>`).join('');
            Swal.fire({
                title:`{{ __('ui.material_issue.send') }} · ${esc(request.request_number)}`, width:1050,
                html:`<div class="text-start mb-3"><div><b>{{ __('ui.material_issue.request_number') }}:</b> ${esc(request.order_number||request.request_number)} &nbsp; <b>{{ __('ui.material_issue.mo_number') }}:</b> ${esc(request.mo_number||'—')} &nbsp; <b>{{ __('ui.material_issue.style') }}:</b> ${esc(request.style||'—')}</div><label class="form-label mt-3">{{ __('ui.material_issue.date') }}</label><input id="misDate" type="date" class="form-control" value="${today}"></div><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.material_issue.request_quantity') }}</th><th>{{ __('ui.material_issue.sent_before') }}</th><th>{{ __('ui.material_issue.send_now') }}</th><th>{{ __('ui.material_issue.total_sent') }}</th></tr></thead><tbody>${rows}</tbody></table></div>`,
                showCancelButton:true, confirmButtonText:'{{ __('ui.material_issue.save') }}', cancelButtonText:'{{ __('ui.common.cancel') }}',
                didOpen:()=>$('.mis-quantity').on('input',function(){$(this).closest('tr').find('.mis-total').text(fmt(Number($(this).data('before'))+Number(this.value||0)));}),
                preConfirm:()=>{const items=$('.mis-quantity').map(function(){return {raw_material_order_item_id:Number($(this).data('id')),quantity:Number($(this).val()||0),max:Number($(this).data('max'))};}).get();if(!items.some(line=>line.quantity>0)){Swal.showValidationMessage('{{ __('ui.material_issue.quantity_invalid') }}');return false;}if(items.some(line=>!Number.isFinite(line.quantity)||line.quantity<0||line.quantity>line.max)){Swal.showValidationMessage('{{ __('ui.material_issue.quantity_invalid') }}');return false;}if(!$('#misDate').val()){Swal.showValidationMessage('{{ __('ui.material_issue.date_required') }}');return false;}return {items:items.filter(line=>line.quantity>0).map(({raw_material_order_item_id,quantity})=>({raw_material_order_item_id,quantity})),issue_date:$('#misDate').val()};}
            }).then(result=>{
                if(!result.isConfirmed)return;
                $.ajax({url:bulkIssueUrl,method:'POST',data:{_token:@json(csrf_token()),issue_date:result.value.issue_date,items:result.value.items}})
                    .done(response=>{requestTable.ajax.reload(null,false);historyTable.ajax.reload(null,false);Swal.fire({icon:'success',title:'{{ __('ui.workflow.success') }}',text:`${response.message} ${response.number}`});})
                    .fail(xhr=>Swal.fire({icon:'error',title:'{{ __('ui.workflow.failure') }}',text:Object.values(xhr.responseJSON?.errors||{}).flat().join('\n')||xhr.responseJSON?.message||'{{ __('ui.material_issue.failed') }}'}));
            });
        }).fail(xhr=>Swal.fire({icon:'error',title:'{{ __('ui.workflow.failure') }}',text:xhr.responseJSON?.message||'{{ __('ui.material_issue.failed') }}'}));
    });
    $(document).on('click','.btn-view-material-issue',function(){$.get($(this).data('url')).done(doc=>{const rows=doc.items.map(i=>`<tr><td>${esc(i.code)}</td><td>${esc(i.name)}</td><td>${esc(i.unit||'—')}</td><td class="text-end">${fmt(i.quantity)}</td></tr>`).join('');Swal.fire({title:esc(doc.number),html:`<p class="text-start">{{ __('ui.material_issue.date') }}: ${esc(doc.issue_date)} · {{ __('ui.material_issue.request_number') }}: ${esc(doc.request_number)} · {{ __('ui.material_issue.mo_number') }}: ${esc(doc.mo_number||'—')}</p><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.material_issue.sent_now') }}</th></tr></thead><tbody>${rows}</tbody></table></div>`,width:850});});});
});
</script>
@endpush
