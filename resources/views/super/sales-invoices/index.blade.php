@extends('layouts.admin')
@section('title', 'Sales Invoice')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Sales</a></li><li class="breadcrumb-item active">Sales Invoice</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Sales Invoice</h4><span class="text-muted">Tagihkan barang yang sudah dikirim dan pantau tanggal jatuh tempo.</span></div>@can('sales-invoices.create')<a href="{{ route('super.sales-invoices.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>Buat Invoice</a>@endcan</div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="sales-invoices-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>No</th><th>Nomor Invoice</th><th>Nomor Delivery</th><th>Customer</th><th>Tanggal Invoice</th><th>Jatuh Tempo</th><th class="text-end">Total</th><th>Status</th><th>Action</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="salesInvoiceDetailModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="salesInvoiceDetailTitle">Detail Sales Invoice</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body" id="salesInvoiceDetailBody"></div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
    <div class="modal fade" id="salesInvoiceEmailHistoryModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="salesInvoiceEmailHistoryTitle">Riwayat Email Invoice</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th>Tanggal Kirim</th><th>Penerima</th><th>Subjek</th><th>Dikirim Oleh</th></tr></thead><tbody id="salesInvoiceEmailHistoryRows"></tbody></table></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const detailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('salesInvoiceDetailModal'));
    const historyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('salesInvoiceEmailHistoryModal'));
    const safe = value => $('<div>').text(value ?? '').html();
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const table = $('#sales-invoices-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: @json(route('super.sales-invoices.dt')), data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); } },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'number', name: 'number' }, { data: 'delivery_number', orderable: false, searchable: false },
            { data: 'customer_name', orderable: false, searchable: false },
            { data: 'invoice_date', name: 'invoice_date', render: data => data ? String(data).slice(0,10).split('-').reverse().join('/') : '—' },
            { data: 'due_date', name: 'due_date', render: data => data ? String(data).slice(0,10).split('-').reverse().join('/') : '—' },
            { data: 'total_amount', name: 'total_amount', className: 'text-end', render: data => money(data) },
            { data: 'status_label', name: 'status', searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[4, 'desc']], drawCallback: function() { if (window.feather) feather.replace(); }
    });
    $('#applyDateFilter').on('click', () => table.ajax.reload());
    $('#todayDateFilter').on('click', () => { const today = @json(now()->toDateString()); $('#from_date, #to_date').val(today); table.ajax.reload(); });

    $(document).on('click', '.btn-view-sales-invoice', function() {
        $.get($(this).data('url')).done(invoice => {
            const rows = invoice.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.name)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.discount_amount)}</td><td class="text-end">${item.tax_rate}%</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
            $('#salesInvoiceDetailTitle').text(`Sales Invoice ${invoice.number}`);
            $('#salesInvoiceDetailBody').html(`<dl class="row"><dt class="col-sm-3">Delivery</dt><dd class="col-sm-9">${safe(invoice.delivery)}</dd><dt class="col-sm-3">Sales Order</dt><dd class="col-sm-9">${safe(invoice.sales_order)}</dd><dt class="col-sm-3">Customer</dt><dd class="col-sm-9">${safe(invoice.customer)} · ${safe(invoice.email)}</dd><dt class="col-sm-3">Tanggal Invoice</dt><dd class="col-sm-9">${safe(invoice.invoice_date)}</dd><dt class="col-sm-3">Jatuh Tempo</dt><dd class="col-sm-9">${safe(invoice.due_date)}</dd><dt class="col-sm-3">Status</dt><dd class="col-sm-9">${invoice.status === 'issued' ? 'Diterbitkan' : 'Draft'}</dd><dt class="col-sm-3">Dibuat Oleh</dt><dd class="col-sm-9">${safe(invoice.creator)}</dd><dt class="col-sm-3">Diterbitkan Oleh</dt><dd class="col-sm-9">${safe(invoice.issuer)} · ${safe(invoice.issued_at)}</dd><dt class="col-sm-3">Catatan</dt><dd class="col-sm-9">${safe(invoice.notes)}</dd></dl><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Barang</th><th class="text-end">Jumlah</th><th class="text-end">Harga</th><th class="text-end">Diskon</th><th class="text-end">Pajak</th><th class="text-end">Total</th></tr></thead><tbody>${rows}</tbody><tfoot><tr><th colspan="5" class="text-end">Subtotal</th><th class="text-end">${money(invoice.subtotal)}</th></tr><tr><th colspan="5" class="text-end">Pajak</th><th class="text-end">${money(invoice.tax_amount)}</th></tr><tr><th colspan="5" class="text-end">Total</th><th class="text-end">${money(invoice.total_amount)}</th></tr></tfoot></table></div>`);
            detailModal.show();
        }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail invoice tidak dapat dimuat.' }));
    });

    $(document).on('click', '.btn-issue-sales-invoice', function() {
        const button = $(this);
        Swal.fire({ icon: 'question', title: `Terbitkan ${button.data('number')}?`, text: 'Invoice menjadi dokumen tagihan resmi untuk customer.', showCancelButton: true, confirmButtonText: 'Terbitkan', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), _method: 'PUT' }, success: response => { Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); table.ajax.reload(null, false); }, error: xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Invoice gagal diterbitkan.' }) });
        });
    });
    $(document).on('click', '.btn-sales-invoice-history', function() {
        const button = $(this); $('#salesInvoiceEmailHistoryRows').html('<tr><td colspan="4" class="text-center text-muted">Memuat riwayat...</td></tr>'); historyModal.show();
        $.get(button.data('url')).done(response => {
            $('#salesInvoiceEmailHistoryTitle').text(`Riwayat Email ${response.number}`);
            if (!response.emails.length) { $('#salesInvoiceEmailHistoryRows').html('<tr><td colspan="4" class="text-center text-muted">Belum ada email yang berhasil dikirim.</td></tr>'); return; }
            $('#salesInvoiceEmailHistoryRows').html(response.emails.map(item => `<tr><td>${safe(item.sent_at)}</td><td>${safe(item.recipient)}</td><td>${safe(item.subject)}</td><td>${safe(item.sender)}</td></tr>`).join(''));
        }).fail(() => $('#salesInvoiceEmailHistoryRows').html('<tr><td colspan="4" class="text-center text-danger">Riwayat email gagal dimuat.</td></tr>'));
    });
    $(document).on('click', '.btn-email-sales-invoice', function() {
        const button = $(this);
        Swal.fire({ icon: 'question', title: 'Kirim Sales Invoice?', text: 'PDF invoice dikirim sebagai lampiran menggunakan Email Setting perusahaan.', input: 'email', inputLabel: 'Email customer', inputValue: button.data('email') || '', inputPlaceholder: 'customer@example.com', inputValidator: value => !value ? 'Alamat email customer wajib diisi.' : undefined, showCancelButton: true, confirmButtonText: 'Kirim Email', cancelButtonText: 'Batal', showLoaderOnConfirm: true,
            preConfirm: email => $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), email } }).catch(xhr => Swal.showValidationMessage(xhr.responseJSON?.message || 'Email gagal dikirim.'))
        }).then(result => { if (result.isConfirmed && result.value?.message) Swal.fire({ icon: 'success', title: 'Berhasil', text: result.value.message }); });
    });
});
</script>
@endpush
