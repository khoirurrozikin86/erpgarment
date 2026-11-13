@extends('layouts.admin')

@section('title', __('ui.raw_material_order.title'))

@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.production') }}</li><li class="breadcrumb-item active">{{ __('ui.raw_material_order.title') }}</li></ol></nav>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="page-title mb-1">{{ __('ui.raw_material_order.title') }}</h4><span class="text-muted">{{ __('ui.raw_material_order.subtitle') }}</span></div>
    <div class="d-flex gap-2">
        @can('raw-material-orders.view')<a href="{{ route('super.raw-material-orders.export') }}" id="exportRawMaterialOrders" class="btn btn-outline-success btn-sm"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>@endcan
        @can('raw-material-orders.create')<a href="{{ route('super.raw-material-orders.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.raw_material_order.create') }}</a>@endcan
    </div>
</div>
@include('super.purchasing.date-filter')
<div class="card"><div class="card-body"><div class="table-responsive"><table id="rawMaterialOrdersTable" class="table table-bordered table-hover align-middle w-100">
    <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.raw_material_order.number') }}</th><th>{{ __('ui.raw_material_order.date') }}</th><th>{{ __('ui.raw_material_order.style') }}</th><th>{{ __('ui.raw_material_order.mo_number') }}</th><th>{{ __('ui.raw_material_order.item_code') }}</th><th>{{ __('ui.raw_material_order.item_name') }}</th><th>{{ __('ui.raw_material_order.unit') }}</th><th>{{ __('ui.raw_material_order.quantity') }}</th><th>{{ __('ui.raw_material_order.sent_quantity') }}</th></tr></thead>
</table></div></div></div>

<div class="modal fade" id="rawMoPickerModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">{{ __('ui.raw_material_order.select_mo') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
    <div class="modal-body"><div class="table-responsive"><table id="rawMoPickerTable" class="table table-sm table-bordered align-middle w-100"><thead><tr><th>{{ __('ui.raw_material_order.mo_number') }}</th><th>{{ __('ui.common.date') }}</th><th>{{ __('ui.raw_material_order.style') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div>
</div></div></div>
@endsection

@push('scripts')
<script>
$(function(){
    const table=$('#rawMaterialOrdersTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.raw-material-orders.dt')),data:d=>{d.from_date=$('#from_date').val();d.to_date=$('#to_date').val()}},columns:[
        {data:'id',orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},
        {data:'actions',orderable:false,searchable:false,className:'text-center'},
        {data:'number',name:'number'},{data:'order_date',name:'document.order_date',render:d=>d?new Date(d+'T00:00:00').toLocaleDateString():'—'},
        {data:'style_code',name:'style_code',defaultContent:'—'},{data:'mo_number',name:'document.production_order_number'},
        {data:'code',name:'product.code'},{data:'name',name:'product.name'},{data:'unit',orderable:false,searchable:false},
        {data:'quantity',name:'quantity',className:'text-end',render:d=>Number(d).toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:4})},
        {data:'issued_quantity',name:'issued_quantity',className:'text-end',render:d=>Number(d||0).toLocaleString(undefined,{minimumFractionDigits:0,maximumFractionDigits:4})}
    ],order:[[3,'desc']],drawCallback:()=>window.feather&&feather.replace()});
    $('#applyDateFilter').on('click',()=>table.ajax.reload());
    $('#todayDateFilter').on('click',()=>{const today=@json(now()->toDateString());$('#from_date,#to_date').val(today);table.ajax.reload()});
    $('#exportRawMaterialOrders').on('click',function(e){e.preventDefault();const params=new URLSearchParams();if($('#from_date').val())params.set('from_date',$('#from_date').val());if($('#to_date').val())params.set('to_date',$('#to_date').val());window.location.href=$(this).attr('href')+(params.size?'?'+params.toString():'')});
    $(document).on('click','.btn-delete-raw-material-order',function(){const button=$(this);Swal.fire({icon:'warning',title:@json(__('ui.raw_material_order.delete_title')),text:@json(__('ui.raw_material_order.delete_help')),showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(r=>{if(!r.isConfirmed)return;$.ajax({url:button.data('url'),method:'DELETE',data:{_token:@json(csrf_token())},success:r=>{table.ajax.reload(null,false);Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:r.message})},error:x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))})})})});
});
</script>
@endpush
