@extends('layouts.admin')
@section('title', __('ui.common.account_payable'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">{{ __('ui.common.accounting') }}</a></li><li class="breadcrumb-item active">{{ __('ui.common.account_payable') }}</li></ol></nav>@endsection
@section('content')
    <div class="mb-3"><h4 class="page-title mb-1">{{ __('ui.common.account_payable') }}</h4><span class="text-muted">{{ __('ui.modules.accounts_payable_subtitle') }}</span></div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="ap-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>Penerimaan</th><th>{{ __('ui.common.purchase_order') }}</th><th>{{ __('ui.common.supplier') }}</th><th>{{ __('ui.common.received_date') }}</th><th class="text-end">{{ __('ui.common.value') }}</th><th class="text-end">{{ __('ui.modules.returned') }}</th><th class="text-end">{{ __('ui.common.paid') }}</th><th class="text-end">{{ __('ui.modules.remaining_debt') }}</th><th>{{ __('ui.common.status') }}</th><th>{{ __('ui.common.actions') }}</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="apHistoryModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="apHistoryTitle">{{ __('ui.modules.supplier_payment_history') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div id="apHistorySummary" class="mb-3"></div><div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th>{{ __('ui.common.number') }}</th><th>{{ __('ui.common.date') }}</th><th>{{ __('ui.common.cash_bank_account') }}</th><th>{{ __('ui.common.reference') }}</th><th class="text-end">{{ __('ui.common.quantity') }}</th><th>{{ __('ui.common.notes') }}</th><th>{{ __('ui.common.recorded_by') }}</th></tr></thead><tbody id="apHistoryRows"></tbody></table></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('apHistoryModal'));
    const safe = value => $('<div>').text(value ?? '').html();
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const table = $('#ap-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: @json(route('super.account-payable.dt')), data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); } },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'number', name: 'number' }, { data: 'purchase_order_number', orderable: false, searchable: false },
            { data: 'supplier_name', orderable: false, searchable: false },
            { data: 'received_at', name: 'received_at', render: data => data ? String(data).slice(0,10).split('-').reverse().join('/') : '—' },
            { data: 'receipt_amount_value', orderable: false, searchable: false, className: 'text-end', render: money },
            { data: 'returned_amount_value', orderable: false, searchable: false, className: 'text-end', render: money },
            { data: 'paid_amount_value', orderable: false, searchable: false, className: 'text-end', render: money },
            { data: 'outstanding_amount', orderable: false, searchable: false, className: 'text-end', render: money },
            { data: 'payment_status', orderable: false, searchable: false }, { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[4, 'desc']], drawCallback: () => window.feather && feather.replace()
    });
    $('#applyDateFilter').on('click', () => table.ajax.reload());
    $('#todayDateFilter').on('click', () => { const today = @json(now()->toDateString()); $('#from_date, #to_date').val(today); table.ajax.reload(); });
    $(document).on('click', '.btn-pay-supplier', function() {
        const button = $(this);
        Swal.fire({
            title: `{{ __('ui.modules.pay_supplier_debt') }} ${safe(button.data('number'))}`,
            html: `<div class="text-start"><div class="small text-muted mb-2">{{ __('ui.modules.remaining_debt') }} <strong>${money(button.data('outstanding'))}</strong></div><label class="form-label">{{ __('ui.common.date') }}</label><input id="ap-payment-date" type="date" class="form-control mb-2" value="${@json(now()->toDateString())}"><label class="form-label">{{ __('ui.common.quantity') }}</label><input id="ap-payment-amount" type="number" min="0.01" step="0.01" max="${Number(button.data('outstanding'))}" class="form-control mb-2" value="${Number(button.data('outstanding'))}"><label class="form-label">{{ __('ui.modules.account_payment_from') }} {{ __('ui.modules.cash_account') }}/{{ __('ui.modules.bank_account') }}</label><select id="ap-payment-account" class="form-select mb-2"><option value="">{{ __('ui.common.choose_account') }}</option>@foreach($cashBankAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }} ({{ $account->type === 'cash' ? __('ui.modules.cash_account') : __('ui.modules.bank_account') }})</option>@endforeach</select><label class="form-label">{{ __('ui.modules.payment_reference') }}</label><input id="ap-payment-reference" maxlength="150" class="form-control mb-2" placeholder="{{ __('ui.modules.payment_proof_placeholder') }}"><label class="form-label">{{ __('ui.common.notes') }}</label><textarea id="ap-payment-notes" maxlength="2000" class="form-control" rows="2"></textarea></div>`,
            showCancelButton: true, confirmButtonText: '{{ __('ui.modules.save_payment') }}', cancelButtonText: '{{ __('ui.modules.cancel') }}', focusConfirm: false,
            preConfirm: () => {
                const amount = Number($('#ap-payment-amount').val());
                if (!$('#ap-payment-date').val()) { Swal.showValidationMessage('{{ __('ui.modules.payment_date_required') }}'); return false; }
                if (!$('#ap-payment-account').val()) { Swal.showValidationMessage('{{ __('ui.modules.select_cash_bank_account') }}.'); return false; }
                if (!amount || amount <= 0 || amount > Number(button.data('outstanding'))) { Swal.showValidationMessage('{{ __('ui.modules.amount_exceeds_balance') }}'); return false; }
                return $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), payment_date: $('#ap-payment-date').val(), cash_bank_account_id: $('#ap-payment-account').val(), amount, reference_number: $('#ap-payment-reference').val(), notes: $('#ap-payment-notes').val() } }).catch(xhr => { Swal.showValidationMessage(xhr.responseJSON?.message || '{{ __('ui.modules.receivable_payment_failed') }}'); });
            }
        }).then(result => { if (result.isConfirmed && result.value?.message) { Swal.fire({ icon: 'success', title: '{{ __('ui.modules.success') }}', text: result.value.message }); table.ajax.reload(null, false); } });
    });
    $(document).on('click', '.btn-supplier-payment-history', function() {
        const button = $(this); $('#apHistoryTitle').text(`{{ __('ui.modules.payment_history') }} ${button.data('number')}`);
        $('#apHistoryRows').html('<tr><td colspan="7" class="text-center text-muted">{{ __('ui.common.loading_history') }}</td></tr>'); $('#apHistorySummary').empty(); modal.show();
        $.get(button.data('url')).done(data => {
            $('#apHistorySummary').html(`<div><strong>${safe(data.supplier)}</strong> · Nilai ${money(data.total_amount)} · {{ __('ui.modules.returned') }} ${money(data.returned_amount)} · Dibayar ${money(data.paid_amount)} · {{ __('ui.modules.remaining_amount') }} ${money(data.outstanding_amount)}</div>`);
            if (!data.payments.length) { $('#apHistoryRows').html('<tr><td colspan="7" class="text-center text-muted">{{ __('ui.common.no_payments') }}</td></tr>'); return; }
            $('#apHistoryRows').html(data.payments.map(item => `<tr><td>${safe(item.number)}</td><td>${safe(item.payment_date)}</td><td>${safe(item.account)}</td><td>${safe(item.reference_number)}</td><td class="text-end">${money(item.amount)}</td><td>${safe(item.notes)}</td><td>${safe(item.payer)}</td></tr>`).join(''));
        }).fail(() => $('#apHistoryRows').html('<tr><td colspan="7" class="text-center text-danger">{{ __('ui.common.history_failed') }}</td></tr>'));
    });
});
</script>
@endpush
