@extends('layouts.admin')
@section('title', 'Sales Quotation')
@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Sales</a></li><li class="breadcrumb-item active">Quotation</li></ol></nav>
@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Sales Quotation</h4><span class="text-muted">Buat penawaran harga untuk customer dan catat hasil tindak lanjutnya.</span></div>@can('sales-quotations.create')<a href="{{ route('super.sales-quotations.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>Buat Quotation</a>@endcan</div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="sales-quotations-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>No</th><th>Nomor Quotation</th><th>Tanggal</th><th>Customer</th><th class="text-end">Total</th><th>Status</th><th>Dibuat Oleh</th><th>Action</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="quotationDetailModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="quotationDetailTitle">Detail Quotation</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body" id="quotationDetailBody"></div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
    <div class="modal fade" id="quotationEmailHistoryModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="quotationEmailHistoryTitle">Riwayat Email Quotation</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th>Tanggal Kirim</th><th>Penerima</th><th>Subjek</th><th>Dikirim Oleh</th></tr></thead><tbody id="quotationEmailHistoryRows"><tr><td colspan="4" class="text-center text-muted">Memuat riwayat...</td></tr></tbody></table></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const detailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('quotationDetailModal'));
    const emailHistoryModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('quotationEmailHistoryModal'));
    const safe = value => $('<div>').text(value ?? '').html();
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const table = $('#sales-quotations-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: @json(route('super.sales-quotations.dt')), data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); } },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'number', name: 'number' },
            { data: 'quote_date', name: 'quote_date', render: data => {
                if (!data) return '—';
                const date = String(data).slice(0, 10);
                return /^\d{4}-\d{2}-\d{2}$/.test(date) ? date.split('-').reverse().join('/') : '—';
            } },
            { data: 'customer_name', orderable: false, searchable: false },
            { data: 'total_amount', name: 'total_amount', className: 'text-end', render: data => money(data) },
            { data: 'status_label', name: 'status', searchable: false },
            { data: 'creator_name', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[2, 'desc']], drawCallback: function() { if (window.feather) feather.replace(); }
    });
    $('#applyDateFilter').on('click', () => table.ajax.reload());
    $('#todayDateFilter').on('click', () => { const today = @json(now()->toDateString()); $('#from_date, #to_date').val(today); table.ajax.reload(); });

    $(document).on('click', '.btn-quotation-email-history', function() {
        const button = $(this);
        $('#quotationEmailHistoryRows').html('<tr><td colspan="4" class="text-center text-muted">Memuat riwayat...</td></tr>');
        emailHistoryModal.show();
        $.get(button.data('url')).done(response => {
            $('#quotationEmailHistoryTitle').text(`Riwayat Email ${response.number}`);
            if (!response.emails.length) {
                $('#quotationEmailHistoryRows').html('<tr><td colspan="4" class="text-center text-muted">Belum ada email yang berhasil dikirim.</td></tr>');
                return;
            }
            const rows = response.emails.map(item => `<tr><td>${safe(item.sent_at)}</td><td>${safe(item.recipient)}</td><td>${safe(item.subject)}</td><td>${safe(item.sender)}</td></tr>`).join('');
            $('#quotationEmailHistoryRows').html(rows);
        }).fail(() => $('#quotationEmailHistoryRows').html('<tr><td colspan="4" class="text-center text-danger">Riwayat email gagal dimuat.</td></tr>'));
    });

    $(document).on('click', '.btn-view-quotation', function() {
        $.get($(this).data('url')).done(q => {
            const rows = q.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.name)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.discount_amount)}</td><td class="text-end">${item.tax_rate}%</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
            const status = { draft: 'Draft', sent: 'Diterbitkan', accepted: 'Disetujui', rejected: 'Ditolak' }[q.status] || q.status;
            $('#quotationDetailTitle').text(`Quotation ${q.number}`);
            $('#quotationDetailBody').html(`<dl class="row"><dt class="col-sm-3">Customer</dt><dd class="col-sm-9">${safe(q.customer)} (${safe(q.customer_email)})</dd><dt class="col-sm-3">Tanggal</dt><dd class="col-sm-9">${safe(q.quote_date)} · Berlaku sampai ${safe(q.valid_until)}</dd><dt class="col-sm-3">Status</dt><dd class="col-sm-9">${status}</dd><dt class="col-sm-3">Pembuat</dt><dd class="col-sm-9">${safe(q.creator)}</dd><dt class="col-sm-3">Catatan</dt><dd class="col-sm-9">${safe(q.notes)}</dd><dt class="col-sm-3">Catatan Tindak Lanjut</dt><dd class="col-sm-9">${safe(q.review_note)}</dd></dl><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Barang</th><th class="text-end">Jumlah</th><th class="text-end">Harga</th><th class="text-end">Diskon</th><th class="text-end">Pajak</th><th class="text-end">Total</th></tr></thead><tbody>${rows}</tbody><tfoot><tr><th colspan="5" class="text-end">Subtotal</th><th class="text-end">${money(q.subtotal)}</th></tr><tr><th colspan="5" class="text-end">Pajak</th><th class="text-end">${money(q.tax_amount)}</th></tr><tr><th colspan="5" class="text-end">Total</th><th class="text-end">${money(q.total_amount)}</th></tr></tfoot></table></div>`);
            detailModal.show();
        }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail quotation tidak dapat dimuat.' }));
    });
    $(document).on('click', '.btn-issue-quotation', function() {
        const button = $(this);
        Swal.fire({ icon: 'question', title: `Terbitkan ${button.data('number')}?`, text: 'Status quotation berubah menjadi diterbitkan dan siap dikirim ke customer.', showCancelButton: true, confirmButtonText: 'Terbitkan', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), _method: 'PUT' }, success: response => { Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); table.ajax.reload(null, false); }, error: xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Quotation gagal diterbitkan.' }) });
        });
    });
    $(document).on('click', '.btn-email-quotation', function() {
        const button = $(this);
        Swal.fire({
            icon: 'question', title: 'Kirim Sales Quotation?',
            text: 'PDF quotation akan dikirim sebagai lampiran menggunakan Email Setting perusahaan.',
            input: 'email', inputLabel: 'Email customer', inputValue: button.data('email') || '', inputPlaceholder: 'customer@example.com',
            inputValidator: value => !value ? 'Alamat email customer wajib diisi.' : undefined,
            showCancelButton: true, confirmButtonText: 'Kirim Email', cancelButtonText: 'Batal', showLoaderOnConfirm: true,
            preConfirm: email => $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), email } }).catch(xhr => Swal.showValidationMessage(xhr.responseJSON?.message || 'Email gagal dikirim.'))
        }).then(result => {
            if (result.isConfirmed && result.value?.message) Swal.fire({ icon: 'success', title: 'Berhasil', text: result.value.message });
        });
    });
    $(document).on('click', '.btn-review-quotation', function() {
        const button = $(this), accepted = button.data('status') === 'accepted';
        Swal.fire({ icon: accepted ? 'question' : 'warning', title: `${accepted ? 'Setujui' : 'Tolak'} ${button.data('number')}?`, input: 'textarea', inputLabel: 'Catatan (opsional)', inputPlaceholder: 'Catatan customer', showCancelButton: true, confirmButtonText: accepted ? 'Disetujui' : 'Ditolak', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), _method: 'PUT', status: button.data('status'), note: result.value || '' }, success: response => { Swal.fire({ icon: 'success', title: 'Tersimpan', text: response.message }); table.ajax.reload(null, false); }, error: xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Status quotation gagal diperbarui.' }) });
        });
    });
});
</script>
@endpush
