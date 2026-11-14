@extends('layouts.admin')
@section('title', __('ui.delivery_note_approval.title'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.production') }}</li><li class="breadcrumb-item active">{{ __('ui.delivery_note_approval.title') }}</li></ol></nav>@endsection
@section('content')
<div class="card mb-4">
    <div class="card-header py-2">{{ __('ui.delivery_note_approval.title') }}</div>
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-sm-3"><label class="form-label">{{ __('ui.common.from_date') }}</label><input type="date" id="approvalFrom" class="form-control form-control-sm" value="{{ now()->startOfMonth()->toDateString() }}"></div>
            <div class="col-sm-3"><label class="form-label">{{ __('ui.common.to_date') }}</label><input type="date" id="approvalTo" class="form-control form-control-sm" value="{{ now()->toDateString() }}"></div>
            <div class="col-auto"><button type="button" class="btn btn-primary btn-sm" id="showApprovals"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div>
        </div>
        <div class="table-responsive"><table id="deliveryApprovalTable" class="table table-sm table-bordered table-hover align-middle w-100">
            <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.delivery_note_approval.approved_label') }}</th><th>{{ __('ui.gsn.number') }}</th><th>{{ __('ui.gsn.date') }}</th><th>{{ __('ui.gsn.customer') }}</th><th>{{ __('ui.delivery_note_approval.approved_by') }}</th><th>{{ __('ui.delivery_note_approval.approved_date') }}</th><th>{{ __('ui.gsn.po_buyer') }}</th></tr></thead>
        </table></div>
    </div>
</div>
<div class="card d-none" id="approvalDetailCard"><div class="card-header py-2">{{ __('ui.delivery_note_approval.detail_title') }}: <strong class="text-primary" id="approvalDetailNo"></strong></div><div class="card-body"><div class="table-responsive"><table class="table table-sm table-bordered align-middle w-100" id="approvalDetailTable"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.gsn.item_code') }}</th><th>{{ __('ui.gsn.item_name') }}</th><th>{{ __('ui.gsn.unit') }}</th><th>{{ __('ui.gsn.quantity') }}</th><th>{{ __('ui.gsn.style') }}</th><th>{{ __('ui.gsn.mo') }}</th></tr></thead><tbody></tbody></table></div></div></div>
@endsection
@push('scripts')
<script>
$(function(){
 const table=$('#deliveryApprovalTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.delivery-note-approvals.dt')),data:d=>{d.from_date=$('#approvalFrom').val();d.to_date=$('#approvalTo').val()}},columns:[{data:'DT_RowIndex',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false,className:'text-nowrap'},{data:'approved',orderable:false,searchable:false},{data:'number',name:'number'},{data:'sent_date',name:'sent_date',render:d=>d?new Date(d+'T00:00:00').toLocaleDateString():''},{data:'customer',orderable:false,searchable:false},{data:'approved_by_name',orderable:false,searchable:false},{data:'approved_date',orderable:false,searchable:false},{data:'po_buyer',orderable:false,searchable:false}],order:[[4,'desc']],drawCallback:()=>window.feather&&feather.replace()});
 $('#showApprovals').on('click',()=>table.ajax.reload());
 const detailUrl=@json(route('super.delivery-note-approvals.show',['note'=>'__ID__']));
 $(document).on('click','.js-delivery-detail',function(){const id=$(this).data('id');$.get(detailUrl.replace('__ID__',id)).done(note=>{const rows=(note.items||[]).map((line,i)=>{const p=line.production_order?.output_product||{};return `<tr><td>${i+1}</td><td>${esc(p.code)}</td><td>${esc(p.name)}</td><td>${esc(p.unit?.symbol||p.unit?.name)}</td><td class="text-end">${line.quantity}</td><td>${esc(p.style_code)}</td><td>${esc(line.production_order?.number)}</td></tr>`}).join('');$('#approvalDetailNo').text(note.number);$('#approvalDetailTable tbody').html(rows);$('#approvalDetailCard').removeClass('d-none');document.getElementById('approvalDetailCard').scrollIntoView({behavior:'smooth',block:'start'})}).fail(()=>Swal.fire({icon:'error',text:@json(__('ui.gsn.detail_failed'))}))});
 function send(id,action,title,text){Swal.fire({icon:'question',title,text,showCancelButton:true,confirmButtonText:action==='approve'?@json(__('ui.delivery_note_approval.approve')):@json(__('ui.delivery_note_approval.cancel')),cancelButtonText:@json(__('ui.common.cancel'))}).then(result=>{if(!result.isConfirmed)return;$.ajax({url:@json(route('super.delivery-note-approvals.approve',['note'=>'__ID__'])).replace('__ID__',id).replace(/approve$/,action),method:'POST',data:{_token:@json(csrf_token())}}).done(response=>{table.ajax.reload(null,false);Swal.fire({icon:'success',text:response.message})}).fail(xhr=>Swal.fire({icon:'error',text:Object.values(xhr.responseJSON?.errors||{}).flat().join('\n')||xhr.responseJSON?.message||'Error'}))})}
 $(document).on('click','.js-approve-delivery',function(){send($(this).data('id'),'approve',@json(__('ui.delivery_note_approval.confirm_approve_title')),@json(__('ui.delivery_note_approval.confirm_approve_help')))});
 $(document).on('click','.js-cancel-delivery',function(){send($(this).data('id'),'cancel',@json(__('ui.delivery_note_approval.confirm_cancel_title')),@json(__('ui.delivery_note_approval.confirm_cancel_help')))});
 function esc(v){return $('<div>').text(v||'—').html()}
});
</script>
@endpush
