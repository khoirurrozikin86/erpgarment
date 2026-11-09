@extends('layouts.admin')
@section('title', 'Chart of Accounts')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">{{ __('ui.common.accounting') }}</a></li><li class="breadcrumb-item active">{{ __('ui.common.chart_of_accounts') }}</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">{{ __('ui.common.chart_of_accounts') }}</h4><span class="text-muted">Daftar akun akuntansi perusahaan untuk dasar pencatatan jurnal dan laporan.</span></div>@can('chart-of-accounts.create')<button type="button" id="createChartAccount" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#chartAccountModal"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.common.add') }}</button>@endcan</div>
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="chart-accounts-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.code') }}</th><th>{{ __('ui.common.account_name') }}</th><th>{{ __('ui.common.parent_account') }}</th><th>{{ __('ui.common.type') }}</th><th>{{ __('ui.common.normal_balance') }}</th><th>{{ __('ui.common.type') }}</th><th>Asal</th><th>{{ __('ui.common.status') }}</th><th>{{ __('ui.common.actions') }}</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="chartAccountModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><form id="chartAccountForm" class="modal-content"><div class="modal-header"><h5 class="modal-title" id="chartAccountTitle">{{ __('ui.common.add') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-4"><label class="form-label">{{ __('ui.common.account_code') }}</label><input name="code" class="form-control" maxlength="50" required></div><div class="col-md-8"><label class="form-label">{{ __('ui.common.account_name') }}</label><input name="name" class="form-control" maxlength="150" required></div><div class="col-md-6"><label class="form-label">{{ __('ui.common.account_type') }}</label><select name="account_type" id="chartAccountType" class="form-select" required><option value="asset">Aset</option><option value="liability">Kewajiban</option><option value="equity">Ekuitas</option><option value="revenue">Pendapatan</option><option value="expense">Beban</option></select></div><div class="col-md-6"><label class="form-label">{{ __('ui.common.normal_balance') }}</label><select name="normal_balance" id="chartNormalBalance" class="form-select" required><option value="debit">{{ __('ui.common.debit') }}</option><option value="credit">{{ __('ui.common.credit') }}</option></select></div><div class="col-md-6"><label class="form-label">{{ __('ui.common.parent_account') }}</label><select name="parent_id" id="chartParentAccount" class="form-select"><option value="">Tanpa akun induk</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" data-type="{{ $parent->account_type }}">{{ $parent->code }} · {{ $parent->name }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Struktur</label><select name="is_group" class="form-select" required><option value="0">Akun transaksi</option><option value="1">Akun grup</option></select></div><div class="col-md-3"><label class="form-label">{{ __('ui.common.status') }}</label><select name="is_active" class="form-select" required><option value="1">{{ __('ui.common.active') }}</option><option value="0">{{ __('ui.common.inactive') }}</option></select></div><div class="col-12"><label class="form-label">{{ __('ui.common.description_label') }}</label><textarea name="description" class="form-control" rows="2" maxlength="2000"></textarea></div></div><div class="small text-muted mt-3">Akun grup digunakan untuk mengelompokkan subakun dan bukan untuk pencatatan transaksi.</div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button><button type="submit" id="chartAccountSubmit" class="btn btn-primary btn-sm">{{ __('ui.common.save_account') }}</button></div></form></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const table = $('#chart-accounts-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: @json(route('super.chart-of-accounts.dt')),
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'code', name: 'code' }, { data: 'name', name: 'name' },
            { data: 'parent_label', orderable: false, searchable: false }, { data: 'type_label', orderable: false, searchable: false },
            { data: 'balance_label', name: 'normal_balance' },
            { data: 'is_group', orderable: false, searchable: false, render: value => Number(value) ? 'Grup' : 'Transaksi' },
            { data: 'system_label', orderable: false, searchable: false }, { data: 'status_label', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[1, 'asc']], drawCallback: () => window.feather && feather.replace()
    });
    const form = document.getElementById('chartAccountForm');
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('chartAccountModal'));
    const filterParents = () => {
        const type = form.elements.account_type.value;
        [...document.getElementById('chartParentAccount').options].forEach(option => {
            if (option.value) option.hidden = option.dataset.type !== type || option.value === form.dataset.accountId;
        });
        if (form.elements.parent_id.selectedOptions[0]?.hidden) form.elements.parent_id.value = '';
    };
    const resetForm = () => {
        form.reset(); delete form.dataset.url; delete form.dataset.accountId;
        $('#chartAccountTitle').text('{{ __('ui.common.add') }}'); $('#chartAccountSubmit').text('{{ __('ui.common.save_account') }}'); filterParents();
    };
    $('#createChartAccount').on('click', resetForm);
    $('#chartAccountType').on('change', function() {
        const defaults = { asset: 'debit', liability: 'credit', equity: 'credit', revenue: 'credit', expense: 'debit' };
        $('#chartNormalBalance').val(defaults[this.value]); filterParents();
    });
    $(document).on('click', '.btn-edit-chart-account', function() {
        const button = $(this); form.reset();
        form.dataset.url = button.data('url'); form.dataset.accountId = String(button.data('id') || '');
        form.elements.code.value = button.data('code'); form.elements.name.value = button.data('name');
        form.elements.account_type.value = button.data('type'); form.elements.normal_balance.value = button.data('normal');
        form.elements.parent_id.value = button.data('parent') || ''; form.elements.is_group.value = button.data('group');
        form.elements.is_active.value = button.data('active'); form.elements.description.value = button.data('description') || '';
        $('#chartAccountTitle').text('Edit Akun'); $('#chartAccountSubmit').text('Simpan Perubahan'); filterParents(); modal.show();
    });
    $('#chartAccountForm').on('submit', function(event) {
        event.preventDefault();
        const isEdit = Boolean(form.dataset.url);
        const data = Object.fromEntries(new FormData(form).entries());
        $.ajax({ url: form.dataset.url || @json(route('super.chart-of-accounts.store')), method: isEdit ? 'PUT' : 'POST', data: { ...data, _token: @json(csrf_token()) } })
            .done(response => { modal.hide(); resetForm(); table.ajax.reload(null, false); Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); })
            .fail(xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {}).flat()[0] || 'Akun gagal disimpan.' }));
    });
});
</script>
@endpush
