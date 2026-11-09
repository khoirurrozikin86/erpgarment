@extends('layouts.admin')
@section('title', 'Journal')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Accounting</a></li><li class="breadcrumb-item active">Journal</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Journal</h4><span class="text-muted">Kelola jurnal manual yang seimbang, lalu posting untuk menguncinya.</span></div>@can('journal.create')<a href="{{ route('super.journals.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>Buat Jurnal</a>@endcan</div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="journals-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>No</th><th>Nomor Jurnal</th><th>Tanggal</th><th>Keterangan</th><th>Referensi</th><th class="text-end">Debit</th><th class="text-end">Kredit</th><th>Baris</th><th>Status</th><th>Aksi</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="journalDetailModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="journalDetailTitle">Detail Journal</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body" id="journalDetailBody"></div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('journalDetailModal'));
    const safe = value => $('<div>').text(value ?? '').html();
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const table = $('#journals-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: @json(route('super.journals.dt')), data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); } },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'number', name: 'number' },
            { data: 'journal_date', name: 'journal_date', render: data => data ? String(data).slice(0,10).split('-').reverse().join('/') : '—' },
            { data: 'description', name: 'description' }, { data: 'reference_number', name: 'reference_number', defaultContent: '—' },
            { data: 'total_debit', name: 'total_debit', className: 'text-end', render: money },
            { data: 'total_credit', name: 'total_credit', className: 'text-end', render: money },
            { data: 'lines_count', orderable: false, searchable: false }, { data: 'status_label', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[2, 'desc']], drawCallback: () => window.feather && feather.replace()
    });
    $('#applyDateFilter').on('click', () => table.ajax.reload());
    $('#todayDateFilter').on('click', () => { const today = @json(now()->toDateString()); $('#from_date, #to_date').val(today); table.ajax.reload(); });
    $(document).on('click', '.btn-view-journal', function() {
        $.get($(this).data('url')).done(entry => {
            const rows = entry.lines.map(line => `<tr><td>${safe(line.code)} · ${safe(line.account)}</td><td>${safe(line.description)}</td><td class="text-end">${money(line.debit)}</td><td class="text-end">${money(line.credit)}</td></tr>`).join('');
            $('#journalDetailTitle').text(`Journal ${entry.number}`);
            $('#journalDetailBody').html(`<dl class="row"><dt class="col-sm-3">Tanggal</dt><dd class="col-sm-9">${safe(entry.journal_date)}</dd><dt class="col-sm-3">Keterangan</dt><dd class="col-sm-9">${safe(entry.description)}</dd><dt class="col-sm-3">Referensi</dt><dd class="col-sm-9">${safe(entry.reference_number)}</dd><dt class="col-sm-3">Status</dt><dd class="col-sm-9">${entry.status === 'posted' ? 'Posted' : 'Draft'}</dd><dt class="col-sm-3">Dibuat Oleh</dt><dd class="col-sm-9">${safe(entry.creator)}</dd><dt class="col-sm-3">Diposting Oleh</dt><dd class="col-sm-9">${safe(entry.poster)} · ${safe(entry.posted_at)}</dd></dl><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Akun</th><th>Keterangan Baris</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead><tbody>${rows}</tbody><tfoot><tr><th colspan="2" class="text-end">Total</th><th class="text-end">${money(entry.total_debit)}</th><th class="text-end">${money(entry.total_credit)}</th></tr></tfoot></table></div>`);
            modal.show();
        }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail jurnal tidak dapat dimuat.' }));
    });
    $(document).on('click', '.btn-post-journal', function() {
        const button = $(this);
        Swal.fire({ icon: 'question', title: `Posting ${button.data('number')}?`, text: 'Jurnal yang sudah diposting tidak dapat diedit atau dihapus.', showCancelButton: true, confirmButtonText: 'Posting', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), _method: 'PUT' } })
                .done(response => { table.ajax.reload(null, false); Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); })
                .fail(xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Jurnal gagal diposting.' }));
        });
    });
    $(document).on('click', '.btn-delete-journal', function() {
        const button = $(this);
        Swal.fire({ icon: 'warning', title: `Hapus draft ${button.data('number')}?`, showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'DELETE', data: { _token: @json(csrf_token()) } })
                .done(response => { table.ajax.reload(null, false); Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); })
                .fail(xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Draft jurnal gagal dihapus.' }));
        });
    });
});
</script>
@endpush
