@extends('layouts.admin')
@section('title', 'Buat Sales Invoice')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Sales</a></li><li class="breadcrumb-item"><a href="{{ route('super.sales-invoices.index') }}">Sales Invoice</a></li><li class="breadcrumb-item active">Buat</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Buat Sales Invoice</h4><span class="text-muted">Invoice dibuat dari Delivery yang sudah diposting.</span></div><a href="{{ route('super.sales-invoices.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i>Kembali</a></div>
    <form id="salesInvoiceForm"><div class="card"><div class="card-header"><h6 class="mb-0">Informasi Tagihan</h6></div><div class="card-body">
        <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Delivery <span class="text-danger">*</span></label><div class="input-group"><input type="hidden" id="delivery_id"><input type="text" class="form-control" id="delivery_number" placeholder="Pilih Delivery yang sudah diposting" readonly required><button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#deliveryPicker"><i data-feather="search" class="icon-sm me-1"></i>Cari Delivery</button></div></div><div class="col-md-3 mb-3"><label class="form-label" for="invoice_date">Tanggal Invoice</label><input type="date" id="invoice_date" class="form-control" value="{{ now()->toDateString() }}" required></div><div class="col-md-3 mb-3"><label class="form-label" for="due_date">Jatuh Tempo</label><input type="date" id="due_date" class="form-control" value="{{ now()->toDateString() }}"></div></div>
        <div id="invoiceSourceInfo" class="alert alert-info d-none py-2"></div>
        <div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th>Barang</th><th class="text-end">Jumlah Kirim</th><th class="text-end">Harga</th><th class="text-end">Diskon</th><th class="text-end">Pajak</th><th class="text-end">Total</th></tr></thead><tbody id="invoiceItems"><tr><td colspan="6" class="text-center text-muted py-4">Pilih Delivery untuk melihat barang yang ditagihkan.</td></tr></tbody><tfoot><tr><th colspan="5" class="text-end">Subtotal</th><th class="text-end" id="invoiceSubtotal">Rp 0</th></tr><tr><th colspan="5" class="text-end">Pajak</th><th class="text-end" id="invoiceTax">Rp 0</th></tr><tr><th colspan="5" class="text-end">Total Tagihan</th><th class="text-end fw-bold" id="invoiceTotal">Rp 0</th></tr></tfoot></table></div>
        <div class="mt-3"><label class="form-label" for="invoice_notes">Catatan</label><textarea id="invoice_notes" class="form-control" rows="3" maxlength="5000"></textarea></div>
    </div><div class="card-footer d-flex justify-content-between"><a href="{{ route('super.sales-invoices.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a><button type="submit" id="submitInvoice" class="btn btn-primary btn-sm" disabled><i data-feather="save" class="icon-sm me-1"></i>Simpan Draft Invoice</button></div></div></form>
    <div class="modal fade" id="deliveryPicker" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><h5 class="modal-title">Pilih Delivery</h5><small class="text-muted">Hanya Delivery yang sudah diposting dan belum memiliki invoice.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="table-responsive"><table id="eligibleDeliveries" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>Nomor Delivery</th><th>Sales Order</th><th>Customer</th><th>Tanggal Kirim</th><th>Jumlah Barang</th><th>Action</th></tr></thead></table></div></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const picker = bootstrap.Modal.getOrCreateInstance(document.getElementById('deliveryPicker'));
    const safe = value => $('<div>').text(value ?? '').html();
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    $('#eligibleDeliveries').DataTable({
        processing: true, serverSide: true, responsive: true, ajax: @json(route('super.sales-invoices.eligible')),
        columns: [
            { data: 'number', name: 'number' }, { data: 'sales_order_number', orderable: false, searchable: false },
            { data: 'customer_name', orderable: false, searchable: false },
            { data: 'delivered_at', name: 'delivered_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
            { data: 'items_count', searchable: false, className: 'text-center' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[3, 'desc']], drawCallback: function() { if (window.feather) feather.replace(); }
    });
    $(document).on('click', '.btn-select-invoice-delivery', function() {
        const button = $(this), id = button.data('id');
        $('#delivery_id').val(id); $('#delivery_number').val(button.data('number')); $('#submitInvoice').prop('disabled', true);
        $('#invoiceItems').html('<tr><td colspan="6" class="text-center text-muted">Memuat barang dan harga...</td></tr>'); $('#invoiceSourceInfo').addClass('d-none'); picker.hide();
        const sourceUrl = @json(route('super.sales-invoices.source', ['deliveryId' => '__ID__']));
        $.get(sourceUrl.replace('__ID__', encodeURIComponent(id))).done(source => {
            $('#invoiceSourceInfo').removeClass('d-none').text(`Customer: ${source.customer} · Delivery: ${source.number} · SO: ${source.sales_order}`);
            const rows = source.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.name)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.discount_amount)}</td><td class="text-end">${item.tax_rate}%</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
            $('#invoiceItems').html(rows); $('#invoiceSubtotal').text(money(source.subtotal)); $('#invoiceTax').text(money(source.tax_amount)); $('#invoiceTotal').text(money(source.total_amount)); $('#submitInvoice').prop('disabled', false);
        }).fail(xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Delivery tidak lagi tersedia untuk ditagihkan.' }));
    });
    $('#salesInvoiceForm').on('submit', function(event) {
        event.preventDefault(); const button = $('#submitInvoice').prop('disabled', true);
        $.ajax({
            url: @json(route('super.sales-invoices.store')), method: 'POST',
            data: { _token: @json(csrf_token()), delivery_id: $('#delivery_id').val(), invoice_date: $('#invoice_date').val(), due_date: $('#due_date').val(), notes: $('#invoice_notes').val() },
            success: response => Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }).then(() => window.location.href = response.url),
            error: xhr => { const errors = xhr.responseJSON?.errors; Swal.fire({ icon: 'error', title: 'Gagal', text: errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Invoice gagal dibuat.') }); button.prop('disabled', false); }
        });
    });
});
</script>
@endpush
