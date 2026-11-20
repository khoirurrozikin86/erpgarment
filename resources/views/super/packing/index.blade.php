@extends('layouts.admin')
@section('title',__('ui.packing.title'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.production') }}</li><li class="breadcrumb-item active">{{ __('ui.packing.title') }}</li></ol></nav>@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="page-title mb-0">{{ __('ui.packing.title') }}</h4>@can('packing.create')<a href="{{ route('super.packing.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.packing.new') }}</a>@endcan</div>
<div class="card"><div class="card-body"><div class="row g-2 align-items-end mb-3"><div class="col-sm-3"><label class="form-label">{{ __('ui.common.from_date') }}</label><input type="date" id="packingFrom" class="form-control form-control-sm" value="{{ now()->startOfMonth()->toDateString() }}"></div><div class="col-sm-3"><label class="form-label">{{ __('ui.common.to_date') }}</label><input type="date" id="packingTo" class="form-control form-control-sm" value="{{ now()->toDateString() }}"></div><div class="col-auto"><button type="button" id="packingFilter" class="btn btn-primary btn-sm"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div><div class="col-auto"><a id="packingExport" href="{{ route('super.packing.export') }}" class="btn btn-outline-success btn-sm"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a></div></div>
    <div class="mb-4">
        <h6 class="mb-2">{{ __('variants.packing_progress_summary') }}</h6>
        <div class="table-responsive">
            <table id="packingSummaryTable" class="table table-sm table-bordered align-middle w-100">
                <thead class="table-light"><tr>
                    <th>{{ __('ui.packing.mo') }}</th>
                    <th>{{ __('ui.workflow.color') }}</th>
                    <th>{{ __('ui.workflow.size') }}</th>
                    <th class="text-end">{{ __('variants.sewing_order_target') }}</th>
                    <th class="text-end">{{ __('ui.packing.sewing_reception') }}</th>
                    <th class="text-end">{{ __('ui.packing.quantity') }}</th>
                    <th class="text-end">{{ __('ui.packing.send_to_finished_goods') }}</th>
                    <th class="text-end">{{ __('variants.sewing_wip_quantity') }}</th>
                    <th class="text-end">{{ __('variants.sewing_shortage_quantity') }}</th>
                    <th>{{ __('ui.common.status') }}</th>
                </tr></thead>
            </table>
        </div>
    </div>
    <h6 class="mb-2">{{ __('variants.packing_history') }}</h6>
    <div class="table-responsive"><table id="packingTable" class="table table-sm table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.packing.number') }}</th><th>{{ __('ui.packing.date') }}</th><th>{{ __('ui.packing.planning') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.workflow.order_quantity') }}</th></tr></thead></table></div></div></div>
@endsection
@push('scripts')<script>
$(function(){
 const table=$('#packingTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.packing.dt')),data:d=>{d.from_date=$('#packingFrom').val();d.to_date=$('#packingTo').val()}},columns:[{data:'DT_RowIndex',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false,className:'text-nowrap'},{data:'number',name:'packing_number'},{data:'date',name:'processed_at',render:d=>d?new Date(d+'T00:00:00').toLocaleDateString():''},{data:'planning',name:'productionOrder.no_cbd'},{data:'color',name:'productionOrder.outputProduct.color'},{data:'size',name:'productionOrder.outputProduct.size'},{data:'order_quantity',name:'productionOrder.planned_quantity',className:'text-end'}],order:[[3,'desc']],drawCallback:()=>window.feather&&feather.replace()});
 const formatQty = new Intl.NumberFormat(@json(app()->getLocale()), {maximumFractionDigits: 4});
 const progressTable = $('#packingSummaryTable').DataTable({
    processing: true,
    serverSide: true,
    ordering: false,
    pageLength: 10,
    ajax: {
        url: @json(route('super.packing.summary.dt')),
        data: data => {
            data.from_date = $('#packingFrom').val();
            data.to_date = $('#packingTo').val();
        },
    },
    columns: [
        {data: 'number', name: 'number'},
        {data: 'color', searchable: false},
        {data: 'size', searchable: false},
        ...['planned_quantity', 'incoming_quantity', 'processed_quantity', 'output_quantity', 'wip_quantity', 'shortage_quantity'].map(field => ({
            data: field, searchable: false, className: 'text-end', render: value => formatQty.format(value ?? 0),
        })),
        {data: null, searchable: false, render: (data, type, row) => {
            if (Number(row.shortage_quantity) === 0) {
                return '<span class="badge bg-success">' + @json(__('variants.sewing_target_met')) + '</span>';
            }
            if (Number(row.wip_quantity) > 0) {
                return '<span class="badge bg-warning text-dark">' + @json(__('variants.sewing_wip_open')) + '</span>';
            }
            return '<span class="badge bg-danger">' + @json(__('variants.sewing_target_short')) + '</span>';
        }},
    ],
 });
 const safe=v=>$('<div>').text(v??'—').html();
 function detailMarkup(d){return `<div class="p-3 bg-light-subtle"><div class="table-responsive"><table class="table table-sm table-bordered bg-white mb-0"><thead><tr><th>{{ __('ui.packing.style') }}</th><th>{{ __('ui.packing.product_code') }}</th><th>{{ __('ui.packing.product_name') }}</th><th>{{ __('ui.packing.product_code') }} /CUT</th><th>{{ __('ui.packing.sewing_reception') }}</th><th>{{ __('ui.packing.quantity') }}</th><th>{{ __('ui.packing.rejected') }}</th><th>{{ __('ui.packing.send_to_finished_goods') }}</th><th>{{ __('ui.packing.po_buyer') }}</th><th>{{ __('ui.packing.mo') }}</th></tr></thead><tbody><tr><td>${safe(d.style)}</td><td>${safe(d.product_code)}</td><td>${safe(d.product_name)}</td><td>${safe(d.cutting_product_code)}</td><td class="text-end">${safe(d.sewing_reception)}</td><td class="text-end">${safe(d.input_quantity)}</td><td class="text-end">${safe(d.rejected_quantity)}</td><td class="text-end">${safe(d.good_quantity)}</td><td>${safe(d.po_buyer)}</td><td>${safe(d.mo)}</td></tr></tbody></table></div></div>`}
 $('#packingTable').on('click','.btn-show-packing-detail',function(){const tr=$(this).closest('tr'),row=table.row(tr);if(row.child.isShown()){row.child.hide();tr.removeClass('shown');$(this).text(@json(__('ui.common.view_detail')))}else{row.child(detailMarkup(row.data())).show();tr.addClass('shown');$(this).text(@json(__('ui.common.close')))}});
 $(document).on('click','.btn-delete-packing',function(){const id=$(this).data('id');Swal.fire({icon:'warning',title:@json(__('ui.common.confirm_delete')),showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(x=>{if(x.isConfirmed)$.ajax({url:@json(route('super.packing.destroy',['entry'=>'__ID__'])).replace('__ID__',id),method:'DELETE',data:{_token:@json(csrf_token())}}).done(r=>{table.ajax.reload(null,false);progressTable.ajax.reload(null,false);Swal.fire({icon:'success',text:r.message})}).fail(x=>Swal.fire({icon:'error',text:Object.values(x.responseJSON?.errors||{}).flat().join('\n')||x.responseJSON?.message||'Error'}))})});
 $('#packingFilter').on('click',()=>{table.ajax.reload();progressTable.ajax.reload();});$('#packingExport').on('click',function(e){e.preventDefault();const q=new URLSearchParams();if($('#packingFrom').val())q.set('from_date',$('#packingFrom').val());if($('#packingTo').val())q.set('to_date',$('#packingTo').val());location.href=this.href+'?'+q.toString()});
});
</script>@endpush
