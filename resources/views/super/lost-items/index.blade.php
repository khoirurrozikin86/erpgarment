@extends('layouts.admin')

@section('title', __('ui.lost.title'))

@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.warehouse_inventory') }}</li><li class="breadcrumb-item active">{{ __('ui.lost.title') }}</li></ol></nav>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="page-title mb-1">{{ __('ui.lost.title') }}</h4><span class="text-muted">{{ __('ui.lost.subtitle') }}</span></div>
    <div class="d-flex gap-2">@can('lost-items.view')<a href="{{ route('super.lost-items.export') }}" class="btn btn-outline-success btn-sm"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>@endcan @can('lost-items.create')<a class="btn btn-primary btn-sm" href="{{ route('super.lost-items.create') }}"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.lost.add') }}</a>@endcan</div>
</div>
<div class="card"><div class="card-body"><div class="table-responsive"><table id="lostItemsTable" class="table table-bordered table-hover align-middle w-100">
    <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.lost.minutes_number') }}</th><th>{{ __('ui.lost.date') }}</th><th>{{ __('ui.lost.item_code') }}</th><th>{{ __('ui.lost.item_name') }}</th><th>{{ __('ui.lost.unit') }}</th><th>{{ __('ui.lost.quantity') }}</th><th>{{ __('ui.lost.description') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead>
</table></div></div></div>

@endsection

@push('scripts')
<script>
$(function(){
    const table = $('#lostItemsTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:@json(route('super.lost-items.dt')),columns:[
        {data:'id',orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},
        {data:'number',name:'document.number'},{data:'document_date',name:'document.document_date',render:d=>d?new Date(d+'T00:00:00').toLocaleDateString():''},
        {data:'code',name:'product.code'},{data:'name',name:'product.name'},{data:'unit',orderable:false,searchable:false},
        {data:'quantity',name:'quantity',className:'text-end',render:d=>Number(d).toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:4})},
        {data:'description',name:'description',defaultContent:'—'},{data:'actions',orderable:false,searchable:false,className:'text-center'}
    ],order:[[2,'desc']],drawCallback:()=>window.feather&&feather.replace()});
    $(document).on('click','.btn-delete-lost-item',function(){const b=$(this);Swal.fire({icon:'warning',title:@json(__('ui.lost.delete_title')),text:@json(__('ui.lost.delete_help')),showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(result=>{if(!result.isConfirmed)return;$.ajax({url:b.data('url'),method:'DELETE',data:{_token:@json(csrf_token())},success:r=>{table.ajax.reload(null,false);Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:r.message})},error:x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))})})})});
});
</script>
@endpush
