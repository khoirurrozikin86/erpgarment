@extends('layouts.admin')

@section('title', __('ui.subcontract.title'))
@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.master') }}</li><li class="breadcrumb-item active">{{ __('ui.subcontract.title') }}</li></ol></nav>
@endsection

@section('content')
@can('subcontract-contracts.view')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">{{ __('ui.subcontract.contracts_title') }}</h5>
        @can('subcontract-contracts.create')<a class="btn btn-primary btn-sm" href="{{ route('super.subcontract.contracts.create') }}"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.subcontract.add_contract') }}</a>@endcan
    </div>
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-sm-4 col-md-3"><label for="subcontractFrom" class="form-label mb-1">{{ __('ui.common.from_date') }}</label><input type="date" class="form-control form-control-sm" id="subcontractFrom" value="{{ request('from_date', now()->startOfMonth()->toDateString()) }}"></div>
            <div class="col-sm-4 col-md-3"><label for="subcontractTo" class="form-label mb-1">{{ __('ui.common.to_date') }}</label><input type="date" class="form-control form-control-sm" id="subcontractTo" value="{{ request('to_date', now()->toDateString()) }}"></div>
            <div class="col-auto"><button type="button" class="btn btn-primary btn-sm" id="showSubcontractContracts"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div>
        </div>
        <div class="table-responsive"><table id="subcontractContractsTable" class="table table-bordered table-hover align-middle" style="width: 100%">
            <thead><tr>
                <th>{{ __('ui.common.no') }}</th><th>{{ __('ui.subcontract.subcontractor') }}</th><th>{{ __('ui.subcontract.contract_number') }}</th><th>{{ __('ui.subcontract.bc_decree_number') }}</th><th>{{ __('ui.common.description') }}</th><th>{{ __('ui.subcontract.starts_on') }}</th><th>{{ __('ui.subcontract.ends_on') }}</th><th class="text-end">{{ __('ui.subcontract.quota') }}</th><th>{{ __('ui.subcontract.outbound') }}</th><th>{{ __('ui.subcontract.inbound') }}</th><th>{{ __('ui.common.action') }}</th>
            </tr></thead>
        </table></div>
    </div>
</div>

@endcan

@endsection

@push('scripts')
<script>
$(function () {
    @if(session('contract_success'))
        Swal.fire({icon: 'success', title: @json(__('ui.workflow.success')), text: @json(session('contract_success'))});
    @endif
    if (!document.getElementById('subcontractContractsTable')) return;

    const contractsTable = $('#subcontractContractsTable').DataTable({
        processing: true, serverSide: true, scrollX: true,
        ajax: {url: @json(route('super.subcontract.contracts.dt')), data: d => {d.from_date = $('#subcontractFrom').val(); d.to_date = $('#subcontractTo').val();}},
        columns: [
            {data: 'id', orderable: false, searchable: false, render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1},
            {data: 'subcontractor_name', orderable: false, searchable: false}, {data: 'contract_number', name: 'contract_number'}, {data: 'bc_decree_number', name: 'bc_decree_number', defaultContent: '—'},
            {data: 'description', name: 'description', defaultContent: '—'}, {data: 'starts_label', name: 'starts_on'}, {data: 'ends_label', name: 'ends_on'},
            {data: 'quota_label', name: 'quota', className: 'text-end'}, {data: 'outbound_label', orderable: false, searchable: false}, {data: 'inbound_label', orderable: false, searchable: false},
            {data: 'actions', orderable: false, searchable: false, className: 'text-end text-nowrap'}
        ], order: [[5, 'desc']], drawCallback: () => window.feather && feather.replace()
    });

    $('#showSubcontractContracts').on('click', () => contractsTable.ajax.reload());
    function confirmDelete(button, url, table) {
        Swal.fire({icon: 'warning', title: @json(__('ui.common.confirm_delete')), showCancelButton: true, confirmButtonText: @json(__('ui.common.delete')), cancelButtonText: @json(__('ui.common.cancel'))}).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({url: url.replace('__ID__', $(button).data('id')), method: 'DELETE', data: {_token: @json(csrf_token())}})
                .done(r => { table.ajax.reload(null, false); Swal.fire({icon: 'success', title: @json(__('ui.workflow.success')), text: r.message}); })
                .fail(x => Swal.fire({icon: 'error', title: @json(__('ui.workflow.failure')), text: x.responseJSON?.message || @json(__('ui.workflow.save_failed'))}));
        });
    }
    $(document).on('click', '.js-delete-contract', function () { confirmDelete(this, @json(route('super.subcontract.contracts.destroy', ['contract' => '__ID__'])), contractsTable); });
});
</script>
@endpush
