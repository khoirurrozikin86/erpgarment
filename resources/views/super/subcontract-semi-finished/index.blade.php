@extends('layouts.admin')

@section('title', __('ui.subcontract_semi_finished.title'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('ui.nav.subcontract') }}</li>
        <li class="breadcrumb-item active">{{ __('ui.subcontract_semi_finished.title') }}</li>
    </ol></nav>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('ui.subcontract_semi_finished.title') }}</h5>
        @can('subcontract-semi-finished.create')
            <a class="btn btn-primary btn-sm" href="{{ route('super.subcontract.semi-finished.create') }}"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.subcontract_semi_finished.add') }}</a>
        @endcan
    </div>
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-sm-4 col-md-3"><label for="semiFinishedFrom" class="form-label mb-1">{{ __('ui.common.from_date') }}</label><input type="date" id="semiFinishedFrom" class="form-control form-control-sm" value="{{ request('from_date', now()->startOfMonth()->toDateString()) }}"></div>
            <div class="col-sm-4 col-md-3"><label for="semiFinishedTo" class="form-label mb-1">{{ __('ui.common.to_date') }}</label><input type="date" id="semiFinishedTo" class="form-control form-control-sm" value="{{ request('to_date', now()->toDateString()) }}"></div>
            <div class="col-auto"><button id="showSemiFinished" class="btn btn-primary btn-sm"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div>
        </div>
        <div class="table-responsive"><table id="semiFinishedTable" class="table table-bordered table-hover align-middle" style="width: 100%">
            <thead><tr>
                <th>{{ __('ui.common.no') }}</th><th>{{ __('ui.subcontract_semi_finished.number') }}</th><th>{{ __('ui.subcontract_semi_finished.date') }}</th><th>{{ __('ui.subcontract_semi_finished.description') }}</th><th>{{ __('ui.subcontract_semi_finished.buyer_po') }}</th><th>{{ __('ui.subcontract_semi_finished.style') }}</th>@foreach(\App\Domain\Subcontract\Queries\SubcontractProductColumns::HEADINGS as $label)<th>{{ __($label) }}</th>@endforeach<th>{{ __('ui.subcontract_semi_finished.unit') }}</th><th>{{ __('ui.subcontract_semi_finished.quantity') }}</th><th>{{ __('ui.subcontract_semi_finished.mo_number') }}</th><th>{{ __('ui.common.action') }}</th>
            </tr></thead>
        </table></div>
        <div class="small text-muted mt-2">{{ __('ui.subcontract_semi_finished.stock_notice') }}</div>
    </div>
</div>


@endsection

@push('scripts')
<script>
$(function () {
    @if(session('semi_finished_success'))
        Swal.fire({icon: 'success', text: @json(session('semi_finished_success'))});
    @endif
    const itemColumns = @json(array_keys(\App\Domain\Subcontract\Queries\SubcontractProductColumns::HEADINGS))
        .map(field => ({data: 'item_' + field, defaultContent: '—', orderable: false, searchable: false}));
    const table = $('#semiFinishedTable').DataTable({
        processing:true, serverSide:true, scrollX: true,
        ajax:{url:@json(route('super.subcontract.semi-finished.dt')), data:d=>{d.from_date=$('#semiFinishedFrom').val();d.to_date=$('#semiFinishedTo').val()}},
        columns:[
            {data:'id',orderable:false,searchable:false,render:(v,t,r,m)=>m.row+m.settings._iDisplayStart+1},
            {data:'number_label',name:'document.number',orderable:false}, {data:'date_label',name:'document.production_date',orderable:false},
            {data:'description_label',name:'document.description',orderable:false}, {data:'buyer_po_label',name:'document.buyer_po_number',orderable:false},
            {data:'style_label',orderable:false,searchable:false}, ...itemColumns,
            {data:'unit_label',orderable:false,searchable:false}, {data:'quantity_label',name:'quantity',orderable:false}, {data:'mo_label',orderable:false,searchable:false},
            {data:'actions',orderable:false,searchable:false}
        ], order:[[2,'desc']], drawCallback:()=>window.feather&&feather.replace()
    });
    $('#showSemiFinished').on('click', () => table.ajax.reload());
    $('#semiFinishedFrom,#semiFinishedTo').on('change', () => table.ajax.reload());
    $(document).on('click','.js-delete-semi-finished',function(){const id=$(this).data('id');Swal.fire({icon:'warning',title:@json(__('ui.common.confirm_delete')),showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(r=>{if(r.isConfirmed)$.ajax({url:@json(route('super.subcontract.semi-finished.destroy',['document'=>'__ID__'])).replace('__ID__',id),method:'DELETE',data:{_token:@json(csrf_token())}}).done(x=>{table.ajax.reload(null,false);Swal.fire({icon:'success',text:x.message})}).fail(showError)})});
});
</script>
@endpush
