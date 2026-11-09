@extends('layouts.admin')
@section('title', 'Account Payable')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Accounting</a></li><li class="breadcrumb-item active">Account Payable</li></ol></nav>@endsection
@section('content')
    <div class="mb-3"><h4 class="page-title mb-1">Account Payable</h4><span class="text-muted">Pantau utang dari penerimaan barang dan catat pembayaran supplier melalui kas/bank.</span></div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="ap-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>No</th><th>Penerimaan</th><th>Purchase Order</th><th>Supplier</th><th>Tanggal Terima</th><th class="text-end">Nilai</th><th class="text-end">Retur</th><th class="text-end">Terbayar</th><th class="text-end">Sisa Utang</th><th>Status</th><th>Aksi</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="apHistoryModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="apHistoryTitle">Riwayat Pembayaran Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div id="apHistorySummary" class="mb-3"></div><div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th>No Bukti</th><th>Tanggal</th><th>Akun Kas/Bank</th><th>Referensi</th><th class="text-end">Jumlah</th><th>Catatan</th><th>Dicatat Oleh</th></tr></thead><tbody id="apHistoryRows"></tbody></table></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
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
            title: `Bayar Utang ${safe(button.data('number'))}`,
            html: `<div class="text-start"><div class="small text-muted mb-2">Sisa utang: <strong>${money(button.data('outstanding'))}</strong></div><label class="form-label">Tanggal Pembayaran</label><input id="ap-payment-date" type="date" class="form-control mb-2" value="${@json(now()->toDateString())}"><label class="form-label">Jumlah Pembayaran</label><input id="ap-payment-amount" type="number" min="0.01" step="0.01" max="${Number(button.data('outstanding'))}" class="form-control mb-2" value="${Number(button.data('outstanding'))}"><label class="form-label">Bayar dari Akun Kas/Bank</label><select id="ap-payment-account" class="form-select mb-2"><option value="">Pilih akun...</option>@foreach($cashBankAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }} ({{ $account->type === 'cash' ? 'Kas' : 'Bank' }})</option>@endforeach</select><label class="form-label">Referensi / No. Bukti</label><input id="ap-payment-reference" maxlength="150" class="form-control mb-2" placeholder="Nomor bukti transfer atau pembayaran"><label class="form-label">Catatan</label><textarea id="ap-payment-notes" maxlength="2000" class="form-control" rows="2"></textarea></div>`,
            showCancelButton: true, confirmButtonText: 'Simpan Pembayaran', cancelButtonText: 'Batal', focusConfirm: false,
            preConfirm: () => {
                const amount = Number($('#ap-payment-amount').val());
                if (!$('#ap-payment-date').val()) { Swal.showValidationMessage('Tanggal pembayaran wajib diisi.'); return false; }
                if (!$('#ap-payment-account').val()) { Swal.showValidationMessage('Pilih akun kas/bank.'); return false; }
                if (!amount || amount <= 0 || amount > Number(button.data('outstanding'))) { Swal.showValidationMessage('Jumlah harus lebih dari 0 dan tidak melebihi sisa utang.'); return false; }
                return $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), payment_date: $('#ap-payment-date').val(), cash_bank_account_id: $('#ap-payment-account').val(), amount, reference_number: $('#ap-payment-reference').val(), notes: $('#ap-payment-notes').val() } }).catch(xhr => { Swal.showValidationMessage(xhr.responseJSON?.message || 'Pembayaran gagal disimpan.'); });
            }
        }).then(result => { if (result.isConfirmed && result.value?.message) { Swal.fire({ icon: 'success', title: 'Berhasil', text: result.value.message }); table.ajax.reload(null, false); } });
    });
    $(document).on('click', '.btn-supplier-payment-history', function() {
        const button = $(this); $('#apHistoryTitle').text(`Riwayat Pembayaran ${button.data('number')}`);
        $('#apHistoryRows').html('<tr><td colspan="7" class="text-center text-muted">Memuat riwayat...</td></tr>'); $('#apHistorySummary').empty(); modal.show();
        $.get(button.data('url')).done(data => {
            $('#apHistorySummary').html(`<div><strong>${safe(data.supplier)}</strong> · Nilai ${money(data.total_amount)} · Retur ${money(data.returned_amount)} · Dibayar ${money(data.paid_amount)} · Sisa ${money(data.outstanding_amount)}</div>`);
            if (!data.payments.length) { $('#apHistoryRows').html('<tr><td colspan="7" class="text-center text-muted">Belum ada pembayaran.</td></tr>'); return; }
            $('#apHistoryRows').html(data.payments.map(item => `<tr><td>${safe(item.number)}</td><td>${safe(item.payment_date)}</td><td>${safe(item.account)}</td><td>${safe(item.reference_number)}</td><td class="text-end">${money(item.amount)}</td><td>${safe(item.notes)}</td><td>${safe(item.payer)}</td></tr>`).join(''));
        }).fail(() => $('#apHistoryRows').html('<tr><td colspan="7" class="text-center text-danger">Riwayat pembayaran gagal dimuat.</td></tr>'));
    });
});
</script>
@endpush
