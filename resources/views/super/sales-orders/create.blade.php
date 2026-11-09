@extends('layouts.admin')
@section('title', 'Buat Sales Order')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Sales</a></li><li class="breadcrumb-item"><a href="{{ route('super.sales-orders.index') }}">Sales Order</a></li><li class="breadcrumb-item active">Buat</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Buat Sales Order</h4><span class="text-muted">Sales Order dibuat dari quotation yang sudah disetujui customer.</span></div><a href="{{ route('super.sales-orders.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i>Kembali</a></div>
    <form id="salesOrderForm"><div class="card"><div class="card-header"><h6 class="mb-0">Sumber Pesanan</h6></div><div class="card-body">
        <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Quotation Disetujui <span class="text-danger">*</span></label><div class="input-group"><input type="hidden" id="sales_quotation_id"><input type="text" id="sales_quotation_number" class="form-control" placeholder="Pilih quotation yang disetujui" readonly required><button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quotationPicker"><i data-feather="search" class="icon-sm me-1"></i>Cari Quotation</button></div></div><div class="col-md-3 mb-3"><label class="form-label" for="order_date">Tanggal Order</label><input type="date" id="order_date" class="form-control" value="{{ now()->toDateString() }}" required></div><div class="col-md-3 mb-3"><label class="form-label" for="requested_delivery_at">Pengiriman Diminta</label><input type="date" id="requested_delivery_at" class="form-control"></div></div>
        <div id="quotationPreview" class="d-none"><div class="alert alert-info py-2" id="quotationPreviewInfo"></div><div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th>Barang</th><th class="text-end">Jumlah</th><th class="text-end">Harga</th><th class="text-end">Diskon</th><th class="text-end">Pajak</th><th class="text-end">Subtotal</th></tr></thead><tbody id="quotationPreviewItems"></tbody><tfoot><tr><th colspan="5" class="text-end">Subtotal</th><th class="text-end" id="previewSubtotal"></th></tr><tr><th colspan="5" class="text-end">Pajak</th><th class="text-end" id="previewTax"></th></tr><tr><th colspan="5" class="text-end">Total</th><th class="text-end" id="previewTotal"></th></tr></tfoot></table></div></div>
        <div class="mt-3"><label class="form-label" for="sales_order_notes">Catatan Order</label><textarea id="sales_order_notes" class="form-control" rows="3" maxlength="5000" placeholder="Catatan untuk pemenuhan pesanan"></textarea></div>
    </div><div class="card-footer d-flex justify-content-between"><a href="{{ route('super.sales-orders.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a><button id="submitSalesOrder" class="btn btn-primary btn-sm" type="submit" disabled><i data-feather="save" class="icon-sm me-1"></i>Buat Sales Order</button></div></div></form>
    <div class="modal fade" id="quotationPicker" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><h5 class="modal-title">Pilih Quotation Disetujui</h5><small class="text-muted">Quotation yang sudah memiliki Sales Order tidak ditampilkan.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="table-responsive"><table id="eligibleQuotations" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>Nomor Quotation</th><th>Tanggal</th><th>Customer</th><th class="text-end">Total</th><th class="text-center">Barang</th><th>Action</th></tr></thead></table></div></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const picker = bootstrap.Modal.getOrCreateInstance(document.getElementById('quotationPicker'));
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const safe = value => $('<div>').text(value ?? '').html();
    $('#eligibleQuotations').DataTable({
        processing: true, serverSide: true, responsive: true, ajax: @json(route('super.sales-orders.eligible')),
        columns: [
            { data: 'number', name: 'number' },
            { data: 'quote_date', name: 'quote_date', render: data => data ? String(data).slice(0, 10).split('-').reverse().join('/') : '—' },
            { data: 'customer_name', orderable: false, searchable: false },
            { data: 'total_amount', name: 'total_amount', className: 'text-end', render: data => money(data) },
            { data: 'items_count', searchable: false, className: 'text-center' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[1, 'desc']], drawCallback: function() { if (window.feather) feather.replace(); }
    });

    $(document).on('click', '.btn-select-sales-quotation', function() {
        const button = $(this), id = button.data('id');
        $('#sales_quotation_id').val(id); $('#sales_quotation_number').val(button.data('number'));
        $('#submitSalesOrder').prop('disabled', true); $('#quotationPreview').removeClass('d-none');
        $('#quotationPreviewItems').html('<tr><td colspan="6" class="text-center text-muted">Memuat quotation...</td></tr>'); picker.hide();
        const url = @json(route('super.sales-orders.source', ['quotationId' => '__ID__'])).replace('__ID__', encodeURIComponent(id));
        $.get(url).done(quotation => {
            $('#quotationPreviewInfo').text(`Customer: ${quotation.customer} · Quotation: ${quotation.number} · Berlaku sampai: ${quotation.valid_until}`);
            const rows = quotation.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.name)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.discount_amount)}</td><td class="text-end">${item.tax_rate}%</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
            $('#quotationPreviewItems').html(rows); $('#previewSubtotal').text(money(quotation.subtotal)); $('#previewTax').text(money(quotation.tax_amount)); $('#previewTotal').text(money(quotation.total_amount)); $('#submitSalesOrder').prop('disabled', false);
        }).fail(xhr => { $('#submitSalesOrder').prop('disabled', true); Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Quotation tidak lagi tersedia.' }); });
    });

    $('#salesOrderForm').on('submit', function(event) {
        event.preventDefault(); const button = $('#submitSalesOrder').prop('disabled', true);
        $.ajax({
            url: @json(route('super.sales-orders.store')), method: 'POST',
            data: { _token: @json(csrf_token()), sales_quotation_id: $('#sales_quotation_id').val(), order_date: $('#order_date').val(), requested_delivery_at: $('#requested_delivery_at').val(), notes: $('#sales_order_notes').val() },
            success: response => Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }).then(() => window.location.href = response.url),
            error: xhr => { const errors = xhr.responseJSON?.errors; Swal.fire({ icon: 'error', title: 'Gagal', text: errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Sales Order gagal dibuat.') }); button.prop('disabled', false); }
        });
    });
});
</script>
@endpush
