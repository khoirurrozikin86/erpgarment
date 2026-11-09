@extends('layouts.admin')
@section('title', 'POS Session')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">POS</a></li><li class="breadcrumb-item active">Sesi Kasir</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Sesi Kasir</h4><span class="text-muted">Buka laci kas dengan saldo awal, lalu tutup berdasarkan hasil hitung kas fisik.</span></div>@can('pos-sessions.open')<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#openPosSessionModal"><i data-feather="unlock" class="icon-sm me-1"></i>Buka Sesi</button>@endcan</div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="pos-sessions-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>No</th><th>Nomor Sesi</th><th>Dibuka</th><th>Akun Kas</th><th>Kasir</th><th class="text-end">Saldo Awal</th><th class="text-end">Kas Seharusnya</th><th class="text-end">Kas Dihitung</th><th class="text-end">Selisih</th><th>Status</th><th>Aksi</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="openPosSessionModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form id="openPosSessionForm" class="modal-content"><div class="modal-header"><h5 class="modal-title">Buka Sesi Kasir</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Rekening Kas</label><select name="cash_bank_account_id" class="form-select" required><option value="">Pilih rekening kas...</option>@foreach($cashAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></div><div class="mb-3"><label class="form-label">Saldo Awal Laci</label><input name="opening_cash" type="number" min="0" step="0.01" value="0" class="form-control" required></div><div><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2" maxlength="2000" placeholder="Opsional"></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary btn-sm">Buka Sesi</button></div></form></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const table = $('#pos-sessions-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: @json(route('super.pos-sessions.dt')), data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); } },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'number', name: 'number' },
            { data: 'opened_at', name: 'opened_at', render: data => data ? String(data).replace('T', ' ').slice(0,16) : '—' },
            { data: 'account_name', orderable: false, searchable: false }, { data: 'opener.name', name: 'opener.name', defaultContent: '—' },
            { data: 'opening_cash', name: 'opening_cash', className: 'text-end', render: money },
            { data: 'expected_cash_current', orderable: false, searchable: false, className: 'text-end', render: money },
            { data: 'counted_cash', name: 'counted_cash', className: 'text-end', render: value => value == null ? '—' : money(value) },
            { data: 'variance', orderable: false, searchable: false, className: 'text-end', render: value => value == null ? '—' : `<span class="${Number(value) === 0 ? 'text-success' : 'text-danger'}">${money(value)}</span>` },
            { data: 'status_label', orderable: false, searchable: false }, { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[2, 'desc']], drawCallback: () => window.feather && feather.replace()
    });
    $('#applyDateFilter').on('click', () => table.ajax.reload());
    $('#todayDateFilter').on('click', () => { const today = @json(now()->toDateString()); $('#from_date, #to_date').val(today); table.ajax.reload(); });
    $('#openPosSessionForm').on('submit', function(event) {
        event.preventDefault(); const form = this;
        $.ajax({ url: @json(route('super.pos-sessions.open')), method: 'POST', data: { ...Object.fromEntries(new FormData(form).entries()), _token: @json(csrf_token()) } })
            .done(response => { bootstrap.Modal.getOrCreateInstance(document.getElementById('openPosSessionModal')).hide(); form.reset(); table.ajax.reload(); Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); })
            .fail(xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {}).flat()[0] || 'Sesi gagal dibuka.' }));
    });
    $(document).on('click', '.btn-close-pos-session', function() {
        const button = $(this); const expected = Number(button.data('expected'));
        Swal.fire({
            title: `Tutup Sesi ${button.data('number')}`,
            html: `<div class="text-start"><div class="small text-muted mb-2">Kas seharusnya: <strong>${money(expected)}</strong></div><label class="form-label">Kas Fisik Dihitung</label><input id="pos-counted-cash" type="number" min="0" step="0.01" class="form-control mb-2" value="${expected}"><label class="form-label">Catatan</label><textarea id="pos-close-notes" class="form-control" rows="2" maxlength="2000"></textarea></div>`,
            showCancelButton: true, confirmButtonText: 'Tutup Sesi', cancelButtonText: 'Batal',
            preConfirm: () => {
                const counted = Number($('#pos-counted-cash').val());
                if (!Number.isFinite(counted) || counted < 0) { Swal.showValidationMessage('Masukkan jumlah kas fisik yang valid.'); return false; }
                return $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), counted_cash: counted, notes: $('#pos-close-notes').val() } }).catch(xhr => { Swal.showValidationMessage(xhr.responseJSON?.message || 'Sesi gagal ditutup.'); });
            }
        }).then(result => {
            if (!result.isConfirmed || !result.value?.message) return;
            table.ajax.reload(null, false);
            const diff = Number(result.value.cash_difference);
            Swal.fire({ icon: diff === 0 ? 'success' : 'warning', title: 'Sesi Ditutup', html: `${result.value.message}<br>Kas seharusnya: <strong>${money(result.value.expected_cash)}</strong><br>Selisih: <strong>${money(diff)}</strong>` });
        });
    });
});
</script>
@endpush
