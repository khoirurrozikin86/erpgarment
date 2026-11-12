@extends('layouts.admin')
@section('title', __('ui.common.account_receivable'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">{{ __('ui.common.accounting') }}</a></li><li class="breadcrumb-item active">{{ __('ui.common.account_receivable') }}</li></ol></nav>@endsection
@section('content')
    <div class="mb-3"><h4 class="page-title mb-1">{{ __('ui.common.account_receivable') }}</h4><span class="text-muted">{{ __('ui.modules.accounts_receivable_subtitle') }}</span></div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="ar-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.invoice') }}</th><th>{{ __('ui.common.delivery') }}</th><th>{{ __('ui.common.customer') }}</th><th>{{ __('ui.common.invoice_date') }}</th><th>{{ __('ui.common.due_date') }}</th><th class="text-end">{{ __('ui.common.total') }}</th><th class="text-end">{{ __('ui.common.paid') }}</th><th class="text-end">{{ __('ui.common.remaining_receivable') }}</th><th>{{ __('ui.common.status') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="arHistoryModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="arHistoryTitle">{{ __('ui.modules.payment_history') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div id="arHistorySummary" class="mb-3"></div><div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th>{{ __('ui.common.number') }}</th><th>{{ __('ui.common.date') }}</th><th>{{ __('ui.common.method') }}</th><th>{{ __('ui.common.reference') }}</th><th class="text-end">{{ __('ui.common.quantity') }}</th><th>{{ __('ui.common.notes') }}</th><th>{{ __('ui.common.received_by') }}</th></tr></thead><tbody id="arHistoryRows"></tbody></table></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const historyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('arHistoryModal'));
    const safe = value => $('<div>').text(value ?? '').html();
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const methodLabels = { cash: '{{ __('ui.modules.cash') }}', bank_transfer: '{{ __('ui.modules.bank_transfer') }}', card: '{{ __('ui.modules.card') }}', other: '{{ __('ui.modules.other') }}' };
    const table = $('#ar-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: @json(route('super.account-receivable.dt')), data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); } },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'number', name: 'number' }, { data: 'delivery_number', orderable: false, searchable: false },
            { data: 'customer_name', orderable: false, searchable: false },
            { data: 'invoice_date', name: 'invoice_date', render: data => data ? String(data).slice(0,10).split('-').reverse().join('/') : '—' },
            { data: 'due_date', name: 'due_date', render: data => data ? String(data).slice(0,10).split('-').reverse().join('/') : '—' },
            { data: 'total_amount', name: 'total_amount', className: 'text-end', render: money },
            { data: 'paid_amount', orderable: false, searchable: false, className: 'text-end', render: money },
            { data: 'outstanding_amount', orderable: false, searchable: false, className: 'text-end', render: money },
            { data: 'payment_status', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[4, 'desc']], drawCallback: function() { if (window.feather) feather.replace(); }
    });
    $('#applyDateFilter').on('click', () => table.ajax.reload());
    $('#todayDateFilter').on('click', () => { const today = @json(now()->toDateString()); $('#from_date, #to_date').val(today); table.ajax.reload(); });

    $(document).on('click', '.btn-receive-payment', function() {
        const button = $(this);
        Swal.fire({
            title: `{{ __('ui.modules.save_payment') }} ${safe(button.data('number'))}`,
            html: `<div class="text-start"><div class="small text-muted mb-2">{{ __('ui.modules.remaining_amount') }} <strong>${money(button.data('outstanding'))}</strong></div><label class="form-label">{{ __('ui.common.date') }}</label><input id="ar-payment-date" type="date" class="form-control mb-2" value="${@json(now()->toDateString())}"><label class="form-label">{{ __('ui.common.quantity') }}</label><input id="ar-payment-amount" type="number" min="0.01" step="0.01" max="${Number(button.data('outstanding'))}" class="form-control mb-2" value="${Number(button.data('outstanding'))}"><label class="form-label">{{ __('ui.common.method') }}</label><select id="ar-payment-method" class="form-select mb-2"><option value="cash">{{ __('ui.modules.cash') }}</option><option value="bank_transfer">{{ __('ui.modules.bank_transfer') }}</option><option value="card">{{ __('ui.modules.card') }}</option><option value="other">{{ __('ui.modules.other') }}</option></select><label class="form-label">{{ __('ui.modules.account_payment_to') }} {{ __('ui.modules.cash_account') }}/{{ __('ui.modules.bank_account') }}</label><select id="ar-payment-account" class="form-select mb-2"><option value="">{{ __('ui.common.choose_account') }}</option>@foreach($cashBankAccounts as $account)<option value="{{ $account->id }}" data-type="{{ $account->type }}">{{ $account->code }} · {{ $account->name }} ({{ $account->type === 'cash' ? __('ui.modules.cash_account') : __('ui.modules.bank_account') }})</option>@endforeach</select><label class="form-label">{{ __('ui.common.reference') }}</label><input id="ar-payment-reference" maxlength="150" class="form-control mb-2" placeholder="{{ __('ui.common.optional') }}"><label class="form-label">{{ __('ui.common.notes') }}</label><textarea id="ar-payment-notes" maxlength="2000" class="form-control" rows="2" placeholder="{{ __('ui.common.optional') }}"></textarea></div>`,
            showCancelButton: true, confirmButtonText: '{{ __('ui.modules.save_payment') }}', cancelButtonText: '{{ __('ui.modules.cancel') }}', focusConfirm: false,
            didOpen: () => {
                const method = document.getElementById('ar-payment-method');
                const account = document.getElementById('ar-payment-account');
                const filterAccounts = () => {
                    [...account.options].forEach(option => {
                        const type = option.dataset.type;
                        option.hidden = Boolean(type && method.value !== 'other' && type !== (method.value === 'cash' ? 'cash' : 'bank'));
                    });
                    if (account.selectedOptions[0]?.hidden) account.value = '';
                };
                method.addEventListener('change', filterAccounts);
                filterAccounts();
            },
            preConfirm: () => {
                const amount = Number($('#ar-payment-amount').val());
                if (!$('#ar-payment-date').val()) { Swal.showValidationMessage('{{ __('ui.modules.payment_date_required') }}'); return false; }
                if (!$('#ar-payment-account').val()) { Swal.showValidationMessage('{{ __('ui.modules.receive_payment_account') }}'); return false; }
                if (!amount || amount <= 0 || amount > Number(button.data('outstanding'))) { Swal.showValidationMessage('{{ __('ui.modules.amount_exceeds_balance') }}'); return false; }
                return $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), payment_date: $('#ar-payment-date').val(), cash_bank_account_id: $('#ar-payment-account').val(), amount, payment_method: $('#ar-payment-method').val(), reference_number: $('#ar-payment-reference').val(), notes: $('#ar-payment-notes').val() } }).catch(xhr => { Swal.showValidationMessage(xhr.responseJSON?.message || '{{ __('ui.modules.receivable_payment_failed') }}'); });
            }
        }).then(result => { if (result.isConfirmed && result.value?.message) { Swal.fire({ icon: 'success', title: '{{ __('ui.modules.success') }}', text: result.value.message }); table.ajax.reload(null, false); } });
    });

    $(document).on('click', '.btn-payment-history', function() {
        const button = $(this);
        $('#arHistoryTitle').text(`{{ __('ui.modules.payment_history') }} ${button.data('number')}`);
        $('#arHistoryRows').html('<tr><td colspan="7" class="text-center text-muted">{{ __('ui.common.loading_history') }}</td></tr>');
        $('#arHistorySummary').empty(); historyModal.show();
        $.get(button.data('url')).done(data => {
            $('#arHistorySummary').html(`<div><strong>${safe(data.customer)}</strong> · Total ${money(data.total_amount)} · {{ __('ui.modules.paid_amount') }} ${money(data.paid_amount)} · {{ __('ui.modules.remaining_amount') }} ${money(data.outstanding_amount)}</div>`);
            if (!data.payments.length) { $('#arHistoryRows').html('<tr><td colspan="7" class="text-center text-muted">{{ __('ui.common.no_payments') }}</td></tr>'); return; }
            $('#arHistoryRows').html(data.payments.map(item => `<tr><td>${safe(item.number)}</td><td>${safe(item.payment_date)}</td><td>${safe(methodLabels[item.payment_method] || item.payment_method)}</td><td>${safe(item.reference_number)}</td><td class="text-end">${money(item.amount)}</td><td>${safe(item.notes)}</td><td>${safe(item.receiver)}</td></tr>`).join(''));
        }).fail(() => $('#arHistoryRows').html('<tr><td colspan="7" class="text-center text-danger">{{ __('ui.common.history_failed') }}</td></tr>'));
    });
});
</script>
@endpush
