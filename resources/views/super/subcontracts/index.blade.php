@extends('layouts.admin')

@section('title', __('ui.subcontract.title'))
@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.master') }}</li><li class="breadcrumb-item active">{{ __('ui.subcontract.title') }}</li></ol></nav>
@endsection

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">{{ __('ui.subcontract.title') }}</h5>
        @can('subcontractors.create')<button type="button" class="btn btn-primary btn-sm" id="addSubcontractor"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.subcontract.add_master') }}</button>@endcan
    </div>
    <div class="card-body">
        <div class="table-responsive"><table id="subcontractorsTable" class="table table-bordered table-hover align-middle w-100">
            <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.subcontract.subcontractor') }}</th><th>{{ __('ui.common.status') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead>
        </table></div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">{{ __('ui.subcontract.contracts_title') }}</h5>
        @can('subcontract-contracts.create')<button type="button" class="btn btn-primary btn-sm" id="addSubcontractContract"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.subcontract.add_contract') }}</button>@endcan
    </div>
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-sm-4 col-md-3"><label for="subcontractFrom" class="form-label mb-1">{{ __('ui.common.from_date') }}</label><input type="date" class="form-control form-control-sm" id="subcontractFrom" value="{{ now()->startOfMonth()->toDateString() }}"></div>
            <div class="col-sm-4 col-md-3"><label for="subcontractTo" class="form-label mb-1">{{ __('ui.common.to_date') }}</label><input type="date" class="form-control form-control-sm" id="subcontractTo" value="{{ now()->toDateString() }}"></div>
            <div class="col-auto"><button type="button" class="btn btn-primary btn-sm" id="showSubcontractContracts"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div>
        </div>
        <div class="table-responsive"><table id="subcontractContractsTable" class="table table-bordered table-hover align-middle w-100">
            <thead><tr>
                <th>{{ __('ui.common.no') }}</th><th>{{ __('ui.subcontract.subcontractor') }}</th><th>{{ __('ui.subcontract.contract_number') }}</th><th>{{ __('ui.subcontract.bc_decree_number') }}</th><th>{{ __('ui.common.description') }}</th><th>{{ __('ui.subcontract.starts_on') }}</th><th>{{ __('ui.subcontract.ends_on') }}</th><th class="text-end">{{ __('ui.subcontract.quota') }}</th><th>{{ __('ui.subcontract.outbound') }}</th><th>{{ __('ui.subcontract.inbound') }}</th><th>{{ __('ui.common.action') }}</th>
            </tr></thead>
        </table></div>
        <p class="small text-muted mb-0 mt-2">{{ __('ui.subcontract.transaction_notice') }}</p>
    </div>
</div>

<div class="modal fade" id="subcontractorModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-md modal-dialog-centered"><div class="modal-content">
    <form id="subcontractorForm">@csrf
        <div class="modal-header"><h5 class="modal-title" id="subcontractorModalTitle">{{ __('ui.subcontract.add_master') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
        <div class="modal-body"><input type="hidden" id="subcontractorId"><input type="hidden" name="is_active" value="0"><label class="form-label" for="subcontractorName">{{ __('ui.subcontract.subcontractor') }} *</label><input class="form-control form-control-sm" id="subcontractorName" name="name" maxlength="160" required>
            <div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" role="switch" id="subcontractorActive" name="is_active" value="1" checked><label class="form-check-label" for="subcontractorActive">{{ __('ui.common.active') }}</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button><button type="submit" class="btn btn-primary btn-sm" id="saveSubcontractor">{{ __('ui.common.save') }}</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="subcontractContractModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <form id="subcontractContractForm">@csrf
        <div class="modal-header"><h5 class="modal-title" id="subcontractContractModalTitle">{{ __('ui.subcontract.add_contract') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
        <div class="modal-body"><input type="hidden" id="subcontractContractId"><div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="contractSubcontractor">{{ __('ui.subcontract.subcontractor') }} *</label><select class="form-select form-select-sm" id="contractSubcontractor" name="subcontractor_id" required><option value="">{{ __('ui.common.select') }}</option>@foreach($subcontractors as $subcontractor)<option value="{{ $subcontractor->id }}">{{ $subcontractor->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label" for="contractNumber">{{ __('ui.subcontract.contract_number') }}</label><input class="form-control form-control-sm" id="contractNumber" readonly><div class="form-text">{{ __('ui.subcontract.number_help') }}</div></div>
            <div class="col-md-6"><label class="form-label" for="contractBcNumber">{{ __('ui.subcontract.bc_decree_number') }}</label><input class="form-control form-control-sm" id="contractBcNumber" name="bc_decree_number" maxlength="150"></div>
            <div class="col-md-3"><label class="form-label" for="contractStartsOn">{{ __('ui.subcontract.starts_on') }} *</label><input type="date" class="form-control form-control-sm" id="contractStartsOn" name="starts_on" required></div>
            <div class="col-md-3"><label class="form-label" for="contractEndsOn">{{ __('ui.subcontract.ends_on') }} *</label><input type="date" class="form-control form-control-sm" id="contractEndsOn" name="ends_on" required></div>
            <div class="col-md-4"><label class="form-label" for="contractQuota">{{ __('ui.subcontract.quota') }}</label><input type="number" min="0" step="0.0001" class="form-control form-control-sm text-end" id="contractQuota" name="quota"></div>
            <div class="col-12"><label class="form-label" for="contractDescription">{{ __('ui.common.description') }}</label><textarea class="form-control form-control-sm" id="contractDescription" name="description" rows="2" maxlength="5000"></textarea></div>
        </div></div>
        <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button><button type="submit" class="btn btn-primary btn-sm" id="saveSubcontractContract">{{ __('ui.common.save') }}</button></div>
    </form>
</div></div></div>
@endsection

@push('scripts')
<script>
$(function () {
    const subcontractorModal = new bootstrap.Modal(document.getElementById('subcontractorModal'));
    const contractModal = new bootstrap.Modal(document.getElementById('subcontractContractModal'));
    const subcontractorsTable = $('#subcontractorsTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: @json(route('super.subcontract.subcontractors.dt')),
        columns: [
            {data: 'id', orderable: false, searchable: false, render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1},
            {data: 'name', name: 'name'}, {data: 'status_label', name: 'is_active', orderable: false, searchable: false}, {data: 'actions', orderable: false, searchable: false, className: 'text-end text-nowrap'}
        ], order: [[1, 'asc']], drawCallback: () => window.feather && feather.replace()
    });
    const contractsTable = $('#subcontractContractsTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: {url: @json(route('super.subcontract.contracts.dt')), data: d => {d.from_date = $('#subcontractFrom').val(); d.to_date = $('#subcontractTo').val();}},
        columns: [
            {data: 'id', orderable: false, searchable: false, render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1},
            {data: 'subcontractor_name', orderable: false, searchable: false}, {data: 'contract_number', name: 'contract_number'}, {data: 'bc_decree_number', name: 'bc_decree_number', defaultContent: '—'},
            {data: 'description', name: 'description', defaultContent: '—'}, {data: 'starts_label', name: 'starts_on'}, {data: 'ends_label', name: 'ends_on'},
            {data: 'quota_label', name: 'quota', className: 'text-end'}, {data: 'outbound_label', orderable: false, searchable: false}, {data: 'inbound_label', orderable: false, searchable: false},
            {data: 'actions', orderable: false, searchable: false, className: 'text-end text-nowrap'}
        ], order: [[5, 'desc']], drawCallback: () => window.feather && feather.replace()
    });

    $('#showSubcontractContracts, #subcontractFrom, #subcontractTo').on('click change', () => contractsTable.ajax.reload());
    function resetSubcontractor() { $('#subcontractorForm')[0].reset(); $('#subcontractorId').val(''); $('#subcontractorActive').prop('checked', true); $('#subcontractorModalTitle').text(@json(__('ui.subcontract.add_master'))); }
    function resetContract() { $('#subcontractContractForm')[0].reset(); $('#subcontractContractId').val(''); $('#contractNumber').val(@json(__('ui.subcontract.number_loading'))); $('#subcontractContractModalTitle').text(@json(__('ui.subcontract.add_contract'))); }

    $('#addSubcontractor').on('click', () => { resetSubcontractor(); subcontractorModal.show(); });
    $('#addSubcontractContract').on('click', function () {
        resetContract(); contractModal.show();
        $.get(@json(route('super.subcontract.contracts.number-preview'))).done(r => $('#contractNumber').val(r.number)).fail(() => $('#contractNumber').val(@json(__('ui.subcontract.number_unavailable'))));
    });

    $(document).on('click', '.js-edit-subcontractor', function () {
        const id = $(this).data('id'); resetSubcontractor(); $('#subcontractorId').val(id);
        $.get(@json(route('super.subcontract.subcontractors.show', ['subcontractor' => '__ID__'])).replace('__ID__', id)).done(row => {
            $('#subcontractorName').val(row.name); $('#subcontractorActive').prop('checked', !!row.is_active); $('#subcontractorModalTitle').text(@json(__('ui.common.edit')) + ' ' + @json(__('ui.subcontract.subcontractor'))); subcontractorModal.show();
        });
    });
    $(document).on('click', '.js-edit-contract', function () {
        const id = $(this).data('id'); resetContract(); $('#subcontractContractId').val(id);
        $.get(@json(route('super.subcontract.contracts.show', ['contract' => '__ID__'])).replace('__ID__', id)).done(row => {
            $('#contractSubcontractor').val(row.subcontractor_id); $('#contractNumber').val(row.contract_number); $('#contractBcNumber').val(row.bc_decree_number); $('#contractStartsOn').val(row.starts_on); $('#contractEndsOn').val(row.ends_on); $('#contractQuota').val(row.quota); $('#contractDescription').val(row.description); $('#subcontractContractModalTitle').text(@json(__('ui.common.edit')) + ' ' + @json(__('ui.subcontract.contracts_title'))); contractModal.show();
        });
    });

    function submitForm(form, idSelector, storeUrl, updateUrl, modal, table, button) {
        const id = $(idSelector).val();
        $.ajax({url: id ? updateUrl.replace('__ID__', id) : storeUrl, method: id ? 'PUT' : 'POST', data: $(form).serialize()})
            .done(r => { modal.hide(); table.ajax.reload(null, false); Swal.fire({icon: 'success', title: @json(__('ui.workflow.success')), text: r.message + (r.number ? ` (${r.number})` : '')}); })
            .fail(x => Swal.fire({icon: 'error', title: @json(__('ui.workflow.failure')), text: Object.values(x.responseJSON?.errors || {}).flat().join('\n') || x.responseJSON?.message || @json(__('ui.workflow.save_failed'))}))
            .always(() => $(button).prop('disabled', false));
    }
    $('#subcontractorForm').on('submit', function (e) { e.preventDefault(); $('#saveSubcontractor').prop('disabled', true); submitForm(this, '#subcontractorId', @json(route('super.subcontract.subcontractors.store')), @json(route('super.subcontract.subcontractors.update', ['subcontractor' => '__ID__'])), subcontractorModal, subcontractorsTable, '#saveSubcontractor'); });
    $('#subcontractContractForm').on('submit', function (e) { e.preventDefault(); $('#saveSubcontractContract').prop('disabled', true); submitForm(this, '#subcontractContractId', @json(route('super.subcontract.contracts.store')), @json(route('super.subcontract.contracts.update', ['contract' => '__ID__'])), contractModal, contractsTable, '#saveSubcontractContract'); });

    function confirmDelete(button, url, table) {
        Swal.fire({icon: 'warning', title: @json(__('ui.common.confirm_delete')), showCancelButton: true, confirmButtonText: @json(__('ui.common.delete')), cancelButtonText: @json(__('ui.common.cancel'))}).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({url: url.replace('__ID__', $(button).data('id')), method: 'DELETE', data: {_token: @json(csrf_token())}})
                .done(r => { table.ajax.reload(null, false); Swal.fire({icon: 'success', title: @json(__('ui.workflow.success')), text: r.message}); })
                .fail(x => Swal.fire({icon: 'error', title: @json(__('ui.workflow.failure')), text: x.responseJSON?.message || @json(__('ui.workflow.save_failed'))}));
        });
    }
    $(document).on('click', '.js-delete-subcontractor', function () { confirmDelete(this, @json(route('super.subcontract.subcontractors.destroy', ['subcontractor' => '__ID__'])), subcontractorsTable); });
    $(document).on('click', '.js-delete-contract', function () { confirmDelete(this, @json(route('super.subcontract.contracts.destroy', ['contract' => '__ID__'])), contractsTable); });
});
</script>
@endpush
