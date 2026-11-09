@extends('layouts.admin')
@section('title', 'Account Receivable')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Accounting</a></li><li class="breadcrumb-item active">Account Receivable</li></ol></nav>@endsection
@section('content')
    <div class="mb-3"><h4 class="page-title mb-1">Account Receivable</h4><span class="text-muted">Pantau saldo invoice terbit dan catat pembayaran customer.</span></div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="ar-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>No</th><th>Invoice</th><th>Delivery</th><th>Customer</th><th>Tanggal Invoice</th><th>Jatuh Tempo</th><th class="text-end">Total</th><th class="text-end">Terbayar</th><th class="text-end">Sisa Piutang</th><th>Status</th><th>Action</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="arHistoryModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="arHistoryTitle">Riwayat Pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div id="arHistorySummary" class="mb-3"></div><div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th>No Bukti</th><th>Tanggal</th><th>Metode</th><th>Referensi</th><th class="text-end">Jumlah</th><th>Catatan</th><th>Diterima Oleh</th></tr></thead><tbody id="arHistoryRows"></tbody></table></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const historyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('arHistoryModal'));
    const safe = value => $('<div>').text(value ?? '').html();
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const methodLabels = { cash: 'Tunai', bank_transfer: 'Transfer Bank', card: 'Kartu', other: 'Lainnya' };
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
            title: `Catat Pembayaran ${safe(button.data('number'))}`,
            html: `<div class="text-start"><div class="small text-muted mb-2">Sisa piutang: <strong>${money(button.data('outstanding'))}</strong></div><label class="form-label">Tanggal Pembayaran</label><input id="ar-payment-date" type="date" class="form-control mb-2" value="${@json(now()->toDateString())}"><label class="form-label">Jumlah Pembayaran</label><input id="ar-payment-amount" type="number" min="0.01" step="0.01" max="${Number(button.data('outstanding'))}" class="form-control mb-2" value="${Number(button.data('outstanding'))}"><label class="form-label">Metode</label><select id="ar-payment-method" class="form-select mb-2"><option value="cash">Tunai</option><option value="bank_transfer">Transfer Bank</option><option value="card">Kartu</option><option value="other">Lainnya</option></select><label class="form-label">Masuk ke Akun Kas/Bank</label><select id="ar-payment-account" class="form-select mb-2"><option value="">Pilih akun...</option>@foreach($cashBankAccounts as $account)<option value="{{ $account->id }}" data-type="{{ $account->type }}">{{ $account->code }} · {{ $account->name }} ({{ $account->type === 'cash' ? 'Kas' : 'Bank' }})</option>@endforeach</select><label class="form-label">Nomor Referensi</label><input id="ar-payment-reference" maxlength="150" class="form-control mb-2" placeholder="Opsional"><label class="form-label">Catatan</label><textarea id="ar-payment-notes" maxlength="2000" class="form-control" rows="2" placeholder="Opsional"></textarea></div>`,
            showCancelButton: true, confirmButtonText: 'Simpan Pembayaran', cancelButtonText: 'Batal', focusConfirm: false,
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
                if (!$('#ar-payment-date').val()) { Swal.showValidationMessage('Tanggal pembayaran wajib diisi.'); return false; }
                if (!$('#ar-payment-account').val()) { Swal.showValidationMessage('Pilih akun kas/bank untuk menerima pembayaran.'); return false; }
                if (!amount || amount <= 0 || amount > Number(button.data('outstanding'))) { Swal.showValidationMessage('Jumlah pembayaran harus lebih dari 0 dan tidak boleh melebihi sisa piutang.'); return false; }
                return $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), payment_date: $('#ar-payment-date').val(), cash_bank_account_id: $('#ar-payment-account').val(), amount, payment_method: $('#ar-payment-method').val(), reference_number: $('#ar-payment-reference').val(), notes: $('#ar-payment-notes').val() } }).catch(xhr => { Swal.showValidationMessage(xhr.responseJSON?.message || 'Pembayaran gagal disimpan.'); });
            }
        }).then(result => { if (result.isConfirmed && result.value?.message) { Swal.fire({ icon: 'success', title: 'Berhasil', text: result.value.message }); table.ajax.reload(null, false); } });
    });

    $(document).on('click', '.btn-payment-history', function() {
        const button = $(this);
        $('#arHistoryTitle').text(`Riwayat Pembayaran ${button.data('number')}`);
        $('#arHistoryRows').html('<tr><td colspan="7" class="text-center text-muted">Memuat riwayat...</td></tr>');
        $('#arHistorySummary').empty(); historyModal.show();
        $.get(button.data('url')).done(data => {
            $('#arHistorySummary').html(`<div><strong>${safe(data.customer)}</strong> · Total ${money(data.total_amount)} · Terbayar ${money(data.paid_amount)} · Sisa ${money(data.outstanding_amount)}</div>`);
            if (!data.payments.length) { $('#arHistoryRows').html('<tr><td colspan="7" class="text-center text-muted">Belum ada pembayaran.</td></tr>'); return; }
            $('#arHistoryRows').html(data.payments.map(item => `<tr><td>${safe(item.number)}</td><td>${safe(item.payment_date)}</td><td>${safe(methodLabels[item.payment_method] || item.payment_method)}</td><td>${safe(item.reference_number)}</td><td class="text-end">${money(item.amount)}</td><td>${safe(item.notes)}</td><td>${safe(item.receiver)}</td></tr>`).join(''));
        }).fail(() => $('#arHistoryRows').html('<tr><td colspan="7" class="text-center text-danger">Riwayat pembayaran gagal dimuat.</td></tr>'));
    });
});
</script>
@endpush
