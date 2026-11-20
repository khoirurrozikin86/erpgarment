@extends('layouts.admin')
@section('title',__('ui.cutting.title'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.production') }}</li><li class="breadcrumb-item active">{{ __('ui.cutting.title') }}</li></ol></nav>@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="page-title mb-0">{{ __('ui.cutting.title') }}</h4>@can('cutting.create')<a href="{{ route('super.cutting.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.cutting.new') }}</a>@endcan</div>
<div class="card"><div class="card-body"><div class="row g-2 align-items-end mb-3"><div class="col-sm-3"><label class="form-label">{{ __('ui.common.from_date') }}</label><input type="date" id="cuttingFrom" class="form-control form-control-sm" value="{{ now()->startOfMonth()->toDateString() }}"></div><div class="col-sm-3"><label class="form-label">{{ __('ui.common.to_date') }}</label><input type="date" id="cuttingTo" class="form-control form-control-sm" value="{{ now()->toDateString() }}"></div><div class="col-auto"><button type="button" id="cuttingFilter" class="btn btn-primary btn-sm"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div><div class="col-auto"><a id="cuttingExport" href="{{ route('super.cutting.export') }}" class="btn btn-outline-success btn-sm"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a></div></div><div class="table-responsive"><table id="cuttingTable" class="table table-sm table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.cutting.mo') }}</th><th>{{ __('ui.common.recorded_by') }}</th><th>{{ __('ui.cutting.number') }}</th><th>{{ __('ui.cutting.date') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.workflow.order_quantity') }} (PCS)</th><th>{{ __('ui.cutting.quantity') }} (PCS)</th></tr></thead></table></div></div></div>
@endsection
@push('scripts')<script>
$(function(){
 const table=$('#cuttingTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.cutting.dt')),data:d=>{d.from_date=$('#cuttingFrom').val();d.to_date=$('#cuttingTo').val()}},columns:[{data:'DT_RowIndex',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false,className:'text-nowrap'},{data:'mo',name:'productionOrder.number'},{data:'user',orderable:false,searchable:false},{data:'number',name:'cutting_number'},{data:'date',name:'processed_at',render:d=>d?new Date(d+'T00:00:00').toLocaleDateString():''},{data:'color',name:'productionOrder.outputProduct.color'},{data:'size',name:'productionOrder.outputProduct.size'},{data:'order_quantity',name:'productionOrder.planned_quantity',className:'text-end'},{data:'cut_quantity',orderable:false,searchable:false,className:'text-end'}],order:[[5,'desc']],drawCallback:()=>window.feather&&feather.replace()});
 function safe(value){return $('<div>').text(value??'—').html()}
 function detailMarkup(d) {
    const withUnit = (value, unit) => value == null ? '—' : safe(unit && unit !== '—' ? value + ' ' + unit : value);
    return `<div class="p-3 bg-light-subtle">
        <div class="table-responsive">
            <table class="table table-sm table-bordered bg-white mb-2">
                <thead><tr>
                    <th>{{ __('ui.cutting.style') }}</th>
                    <th>{{ __('ui.cutting.product_code') }}</th>
                    <th>{{ __('ui.cutting.product_name') }}</th>
                    <th>{{ __('ui.workflow.color') }}</th>
                    <th>{{ __('ui.workflow.size') }}</th>
                    <th>{{ __('ui.cutting.po_buyer') }}</th>
                    <th>{{ __('ui.cutting.mo') }}</th>
                    <th>{{ __('ui.cutting.quantity') }}</th>
                    <th>{{ __('ui.cutting.rejected') }}</th>
                    <th>{{ __('ui.cutting.send_to_sewing') }}</th>
                </tr></thead>
                <tbody><tr>
                    <td>${safe(d.style)}</td><td>${safe(d.product_code)}</td><td>${safe(d.product_name)}</td>
                    <td>${safe(d.color)}</td><td>${safe(d.size)}</td><td>${safe(d.po_buyer)}</td><td>${safe(d.mo)}</td>
                    <td class="text-end text-nowrap">${withUnit(d.cut_quantity, 'PCS')}</td>
                    <td class="text-end text-nowrap">${withUnit(d.rejected_quantity, 'PCS')}</td>
                    <td class="text-end text-nowrap">${withUnit(d.good_quantity, 'PCS')}</td>
                </tr></tbody>
            </table>
        </div>
    </div>`;
 }
 $('#cuttingTable').on('click','.btn-show-cutting-detail',function(){const tr=$(this).closest('tr');const row=table.row(tr);if(row.child.isShown()){row.child.hide();tr.removeClass('shown');$(this).text(@json(__('ui.common.detail')))}else{row.child(detailMarkup(row.data())).show();tr.addClass('shown');$(this).text(@json(__('ui.common.close')))}});
  $(document).on('click','.btn-delete-cutting',function(){const id=$(this).data('id');Swal.fire({icon:'warning',title:@json(__('ui.common.confirm_delete')),showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(x=>{if(x.isConfirmed)$.ajax({url:@json(route('super.cutting.destroy',['entry'=>'__ID__'])).replace('__ID__',id),method:'DELETE',data:{_token:@json(csrf_token())}}).done(r=>{table.ajax.reload(null,false);Swal.fire({icon:'success',text:r.message})}).fail(x=>Swal.fire({icon:'error',text:Object.values(x.responseJSON?.errors||{}).flat().join('\n')||x.responseJSON?.message||'Error'}))})});
 $('#cuttingFilter').on('click',()=>table.ajax.reload());$('#cuttingExport').on('click',function(e){e.preventDefault();let q=new URLSearchParams();if($('#cuttingFrom').val())q.set('from_date',$('#cuttingFrom').val());if($('#cuttingTo').val())q.set('to_date',$('#cuttingTo').val());location.href=this.href+'?'+q.toString()});
});
</script>@endpush
