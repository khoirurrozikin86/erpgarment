@extends('layouts.admin')
@section('title', __('ui.common.cash_bank'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">{{ __('ui.common.accounting') }}</a></li><li class="breadcrumb-item active">{{ __('ui.common.cash_bank') }}</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">{{ __('ui.common.cash_bank') }}</h4><span class="text-muted">{{ __('ui.modules.cash_bank_subtitle') }}</span></div>@can('cash-bank.create')<div><button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#cashBankAccountModal"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.common.add') }}</button> <button type="button" id="createCashBankTransaction" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#cashBankTransactionModal"><i data-feather="repeat" class="icon-sm me-1"></i>{{ __('ui.modules.record_transaction') }}</button></div>@endcan</div>
    <div class="card mb-3"><div class="card-header"><h6 class="mb-0">{{ __('ui.modules.cash_bank_accounts') }}</h6></div><div class="card-body"><div class="table-responsive"><table id="cash-bank-accounts-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.code') }}</th><th>{{ __('ui.common.account_name') }}</th><th>{{ __('ui.common.type') }}</th><th>{{ __('ui.modules.information') }}</th><th>COA</th><th class="text-end">{{ __('ui.common.balance') }}</th><th>{{ __('ui.common.status') }}</th></tr></thead></table></div></div></div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-header"><h6 class="mb-0">{{ __('ui.modules.cash_bank_transactions') }}</h6></div><div class="card-body"><div class="table-responsive"><table id="cash-bank-transactions-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.date') }}</th><th>{{ __('ui.common.account') }}</th><th>{{ __('ui.common.type') }}</th><th>{{ __('ui.modules.source') }}</th><th>{{ __('ui.modules.counter_account') }}</th><th>{{ __('ui.common.description_label') }}</th><th>{{ __('ui.common.reference') }}</th><th class="text-end">{{ __('ui.common.quantity') }}</th><th>{{ __('ui.common.recorded_by') }}</th><th>{{ __('ui.common.actions') }}</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="cashBankAccountModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form id="cashBankAccountForm" class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.modules.add_cash_bank_account') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="mb-2"><label class="form-label">{{ __('ui.common.account_code') }}</label><input name="code" class="form-control" maxlength="50" required placeholder="Contoh: KAS-001"></div><div class="mb-2"><label class="form-label">{{ __('ui.common.account_name') }}</label><input name="name" class="form-control" maxlength="150" required placeholder="Contoh: {{ __('ui.modules.cash_account') }} Utama"></div><div class="mb-2"><label class="form-label">{{ __('ui.common.account_type') }}</label><select name="type" id="cashBankType" class="form-select" required><option value="cash">{{ __('ui.modules.cash_account') }}</option><option value="bank">{{ __('ui.modules.bank_account') }}</option></select></div><div class="mb-2"><label class="form-label">{{ __('ui.modules.account_origin') }}</label><select name="chart_of_account_id" id="cashBankCoa" class="form-select" required><option value="">{{ __('ui.modules.asset_account') }}</option>@foreach($cashBankChartAccounts as $chartAccount)<option value="{{ $chartAccount->id }}" data-code="{{ $chartAccount->code }}">{{ $chartAccount->code }} · {{ $chartAccount->name }}</option>@endforeach</select></div><div id="cashBankDetails" class="d-none"><div class="mb-2"><label class="form-label">{{ __('ui.modules.bank_name_label') }}</label><input name="bank_name" class="form-control" maxlength="100"></div><div class="mb-2"><label class="form-label">{{ __('ui.modules.bank_account_number') }}</label><input name="account_number" class="form-control" maxlength="100"></div><div class="mb-2"><label class="form-label">{{ __('ui.modules.account_owner') }}</label><input name="account_name" class="form-control" maxlength="150"></div></div><div class="mb-2"><label class="form-label">{{ __('ui.common.opening_balance') }}</label><input name="opening_balance" type="number" min="0" step="0.01" class="form-control" value="0" required></div><div class="small text-muted">{{ __('ui.modules.cash_coa_help') }}</div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button><button type="submit" class="btn btn-primary btn-sm">{{ __('ui.common.save_account') }}</button></div></form></div></div>
    <div class="modal fade" id="cashBankTransactionModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form id="cashBankTransactionForm" class="modal-content"><div class="modal-header"><h5 class="modal-title" id="cashBankTransactionTitle">{{ __('ui.modules.cash_bank_transaction_title') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="mb-2"><label class="form-label">{{ __('ui.common.cash_bank_account') }}</label><select name="cash_bank_account_id" class="form-select" required><option value="">{{ __('ui.common.choose_account') }}</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></div><div class="mb-2"><label class="form-label">{{ __('ui.modules.transaction_type') }}</label><select name="direction" class="form-select" required><option value="in">{{ __('ui.common.cash_in') }}</option><option value="out">{{ __('ui.common.cash_out') }}</option></select></div><div class="mb-2"><label class="form-label">{{ __('ui.modules.counter_account') }} (COA)</label><select name="counter_chart_account_id" class="form-select" required><option value="">{{ __('ui.modules.choose_counter_account') }}</option>@foreach($chartAccounts as $chartAccount)<option value="{{ $chartAccount->id }}">{{ $chartAccount->code }} · {{ $chartAccount->name }}</option>@endforeach</select><div class="form-text">{{ __('ui.modules.cash_in_guidance') }}</div></div><div class="mb-2"><label class="form-label">{{ __('ui.common.date') }}</label><input name="transaction_date" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div><div class="mb-2"><label class="form-label">{{ __('ui.common.quantity') }}</label><input name="amount" type="number" min="0.01" step="0.01" class="form-control" required></div><div class="mb-2"><label class="form-label">{{ __('ui.common.description_label') }}</label><input name="description" maxlength="255" class="form-control" required placeholder="Contoh: Pembelian perlengkapan kantor"></div><div class="mb-2"><label class="form-label">{{ __('ui.common.reference') }}</label><input name="reference_number" maxlength="150" class="form-control" placeholder="{{ __('ui.common.optional') }}"></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button><button type="submit" id="cashBankTransactionSubmit" class="btn btn-primary btn-sm">{{ __('ui.modules.save_transaction') }}</button></div></form></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const safe = value => $('<div>').text(value ?? '').html();
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const accounts = $('#cash-bank-accounts-table').DataTable({
        processing: true, serverSide: true, responsive: true, ajax: @json(route('super.cash-bank.accounts.dt')),
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'code', name: 'code' }, { data: 'name', name: 'name' }, { data: 'type_label', orderable: false, searchable: false },
            { data: 'account_info', orderable: false, searchable: false }, { data: 'chart_account_label', orderable: false, searchable: false }, { data: 'balance', orderable: false, searchable: false, className: 'text-end', render: money },
            { data: 'status_label', orderable: false, searchable: false }
        ], order: [[1, 'asc']], drawCallback: () => window.feather && feather.replace()
    });
    const transactions = $('#cash-bank-transactions-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: @json(route('super.cash-bank.transactions.dt')), data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); } },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'transaction_date', name: 'transaction_date', render: data => data ? String(data).slice(0,10).split('-').reverse().join('/') : '—' },
            { data: 'account_name', orderable: false, searchable: false }, { data: 'direction_label', orderable: false, searchable: false },
            { data: 'source_number', orderable: false, searchable: false }, { data: 'counter_account_name', orderable: false, searchable: false }, { data: 'description', name: 'description' },
            { data: 'reference_number', name: 'reference_number', defaultContent: '—' },
            { data: 'amount_signed', orderable: false, searchable: false, className: 'text-end', render: (value, type, row) => `<span class="${Number(value) < 0 ? 'text-danger' : 'text-success'}">${money(Math.abs(Number(value)))}</span>` },
            { data: 'creator_name', orderable: false, searchable: false }, { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[1, 'desc']], drawCallback: () => window.feather && feather.replace()
    });
    $('#applyDateFilter').on('click', () => transactions.ajax.reload());
    $('#todayDateFilter').on('click', () => { const today = @json(now()->toDateString()); $('#from_date, #to_date').val(today); transactions.ajax.reload(); });
    const filterCashBankCoa = () => {
        const prefix = $('#cashBankType').val() === 'cash' ? '111' : '112';
        $('#cashBankCoa option').each(function() { if (this.value) this.hidden = !String($(this).data('code')).startsWith(prefix); });
        if ($('#cashBankCoa option:selected').prop('hidden')) $('#cashBankCoa').val('');
    };
    $('#cashBankType').on('change', function() { $('#cashBankDetails').toggleClass('d-none', this.value !== 'bank'); filterCashBankCoa(); });
    filterCashBankCoa();
    $('#cashBankAccountForm').on('submit', function(event) {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());
        $.ajax({ url: @json(route('super.cash-bank.accounts.store')), method: 'POST', data: { ...data, _token: @json(csrf_token()) } })
            .done(response => { bootstrap.Modal.getOrCreateInstance(document.getElementById('cashBankAccountModal')).hide(); this.reset(); $('#cashBankDetails').addClass('d-none'); accounts.ajax.reload(); Swal.fire({ icon: 'success', title: '{{ __('ui.modules.success') }}', text: response.message }); })
            .fail(xhr => Swal.fire({ icon: 'error', title: '{{ __('ui.modules.failure') }}', text: xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {}).flat()[0] || '{{ __('ui.modules.account_save_failed') }}' }));
    });
    $('#cashBankTransactionForm').on('submit', function(event) {
        event.preventDefault();
        const form = this;
        const data = Object.fromEntries(new FormData(form).entries());
        const isEdit = Boolean(form.dataset.url);
        $.ajax({ url: form.dataset.url || @json(route('super.cash-bank.transactions.store')), method: isEdit ? 'PUT' : 'POST', data: { ...data, _token: @json(csrf_token()) } })
            .done(response => { bootstrap.Modal.getOrCreateInstance(document.getElementById('cashBankTransactionModal')).hide(); form.reset(); delete form.dataset.url; form.elements.transaction_date.value = @json(now()->toDateString()); $('#cashBankTransactionTitle').text('{{ __('ui.modules.cash_bank_transaction_title') }}'); $('#cashBankTransactionSubmit').text('{{ __('ui.modules.save_transaction') }}'); accounts.ajax.reload(); transactions.ajax.reload(null, false); Swal.fire({ icon: 'success', title: '{{ __('ui.modules.success') }}', text: response.message }); })
            .fail(xhr => Swal.fire({ icon: 'error', title: '{{ __('ui.modules.failure') }}', text: xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {}).flat()[0] || '{{ __('ui.modules.transaction_save_failed') }}' }));
    });
    $('#createCashBankTransaction').on('click', function() {
        const form = document.getElementById('cashBankTransactionForm'); form.reset(); delete form.dataset.url;
        form.elements.transaction_date.value = @json(now()->toDateString());
        $('#cashBankTransactionTitle').text('{{ __('ui.modules.cash_bank_transaction_title') }}'); $('#cashBankTransactionSubmit').text('{{ __('ui.modules.save_transaction') }}');
    });
    $(document).on('click', '.btn-edit-cash-transaction', function() {
        const button = $(this); const form = document.getElementById('cashBankTransactionForm');
        form.reset(); form.dataset.url = button.data('url');
        form.elements.cash_bank_account_id.value = button.data('account');
        form.elements.counter_chart_account_id.value = button.data('counter-account') || '';
        form.elements.transaction_date.value = button.data('date'); form.elements.direction.value = button.data('direction');
        form.elements.amount.value = button.data('amount'); form.elements.description.value = button.data('description');
        form.elements.reference_number.value = button.data('reference') || '';
        $('#cashBankTransactionTitle').text('{{ __('ui.modules.edit_transaction') }}'); $('#cashBankTransactionSubmit').text('{{ __('ui.modules.save_changes') }}');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('cashBankTransactionModal')).show();
    });
    $(document).on('click', '.btn-delete-cash-transaction', function() {
        const button = $(this);
        Swal.fire({ icon: 'warning', title: '{{ __('ui.modules.delete_transaction_title') }}', text: '{{ __('ui.modules.delete_transaction_help') }}', showCancelButton: true, confirmButtonText: '{{ __('ui.modules.delete') }}', cancelButtonText: '{{ __('ui.modules.cancel') }}' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'DELETE', data: { _token: @json(csrf_token()) } })
                .done(response => { accounts.ajax.reload(); transactions.ajax.reload(null, false); Swal.fire({ icon: 'success', title: '{{ __('ui.modules.success') }}', text: response.message }); })
                .fail(xhr => Swal.fire({ icon: 'error', title: '{{ __('ui.modules.failure') }}', text: xhr.responseJSON?.message || '{{ __('ui.modules.transaction_delete_failed') }}' }));
        });
    });
});
</script>
@endpush
