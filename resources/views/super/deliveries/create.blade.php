@extends('layouts.admin')
@section('title', 'Buat Delivery')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Sales</a></li><li class="breadcrumb-item"><a href="{{ route('super.deliveries.index') }}">Delivery</a></li><li class="breadcrumb-item active">Buat</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Buat Delivery</h4><span class="text-muted">Kirim barang dari Sales Order yang sudah dikonfirmasi. Stok dikurangi setelah Delivery diposting.</span></div><a href="{{ route('super.deliveries.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i>Kembali</a></div>
    <form id="deliveryForm"><div class="card"><div class="card-header"><h6 class="mb-0">Informasi Pengiriman</h6></div><div class="card-body">
        <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Sales Order <span class="text-danger">*</span></label><div class="input-group"><input type="hidden" id="sales_order_id"><input type="text" class="form-control" id="sales_order_number" placeholder="Pilih Sales Order terkonfirmasi" readonly required><button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#salesOrderPicker"><i data-feather="search" class="icon-sm me-1"></i>Cari SO</button></div></div><div class="col-md-6 mb-3"><label class="form-label" for="delivered_at">Tanggal Pengiriman</label><input type="datetime-local" id="delivered_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div></div>
        <div id="deliveryOrderInfo" class="alert alert-info d-none py-2"></div>
        <div class="alert alert-warning py-2"><strong>Perhatian:</strong> Sistem membatasi jumlah sesuai stok yang tersedia. Ketersediaan akan diperiksa kembali saat posting.</div>
        <div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th class="text-center" style="width:55px">Kirim</th><th>Barang</th><th class="text-end">Sisa SO</th><th class="text-end">Stok Tersedia</th><th style="width:150px">Jumlah Kirim</th></tr></thead><tbody id="deliveryItems"><tr><td colspan="5" class="text-center text-muted py-4">Pilih Sales Order untuk memuat barang.</td></tr></tbody></table></div>
        <div class="mt-3"><label class="form-label" for="delivery_notes">Catatan Pengiriman</label><textarea id="delivery_notes" class="form-control" rows="3" maxlength="5000"></textarea></div>
    </div><div class="card-footer d-flex justify-content-between"><a href="{{ route('super.deliveries.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a><button type="submit" id="submitDelivery" class="btn btn-primary btn-sm" disabled><i data-feather="save" class="icon-sm me-1"></i>Simpan Draft Delivery</button></div></div></form>
    <div class="modal fade" id="salesOrderPicker" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><h5 class="modal-title">Pilih Sales Order</h5><small class="text-muted">Menampilkan order yang sudah dikonfirmasi dan masih memiliki sisa pengiriman.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="table-responsive"><table id="eligibleSalesOrders" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>Nomor SO</th><th>Quotation</th><th>Customer</th><th>Tanggal Order</th><th>Jumlah Baris</th><th>Action</th></tr></thead></table></div></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const picker = bootstrap.Modal.getOrCreateInstance(document.getElementById('salesOrderPicker'));
    const safe = value => $('<div>').text(value ?? '').html();
    const ordersTable = $('#eligibleSalesOrders').DataTable({
        processing: true, serverSide: true, responsive: true, ajax: @json(route('super.deliveries.eligible')),
        columns: [
            { data: 'number', name: 'number' }, { data: 'quotation_number', orderable: false, searchable: false },
            { data: 'customer_name', orderable: false, searchable: false },
            { data: 'order_date', name: 'order_date', render: data => data ? String(data).slice(0, 10).split('-').reverse().join('/') : '—' },
            { data: 'items_count', searchable: false, className: 'text-center' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[3, 'desc']], drawCallback: function() { if (window.feather) feather.replace(); }
    });

    $(document).on('click', '.btn-select-delivery-order', function() {
        const button = $(this), id = button.data('id');
        $('#sales_order_id').val(id); $('#sales_order_number').val(button.data('number'));
        $('#submitDelivery').prop('disabled', true);
        $('#deliveryItems').html('<tr><td colspan="5" class="text-center text-muted">Memuat sisa barang dan stok...</td></tr>');
        $('#deliveryOrderInfo').addClass('d-none'); picker.hide();
        const sourceUrl = @json(route('super.deliveries.source', ['salesOrderId' => '__ID__']));
        $.get(sourceUrl.replace('__ID__', encodeURIComponent(id))).done(order => {
            $('#deliveryOrderInfo').removeClass('d-none').text(`SO ${order.number} · Customer: ${order.customer} · Quotation: ${order.quotation}`);
            const body = $('#deliveryItems').empty();
            if (!order.items.length) {
                body.html('<tr><td colspan="5" class="text-center text-muted py-4">Semua jumlah SO sudah terkirim atau sedang dialokasikan ke draft Delivery.</td></tr>');
                recalculate();
                return;
            }
            order.items.forEach(item => {
                const canShip = !item.track_stock || Number(item.available_stock) > 0;
                const max = item.track_stock ? Math.min(item.remaining_quantity, item.available_stock) : item.remaining_quantity;
                const row = $('<tr>').attr('data-sales-order-item-id', item.sales_order_item_id).attr('data-max', max);
                const checkbox = $('<input>', { type: 'checkbox', class: 'form-check-input delivery-item-select', disabled: !canShip, 'aria-label': `Kirim ${item.name}` });
                const input = $('<input>', { type: 'number', class: 'form-control form-control-sm text-end delivery-quantity', min: '0.0001', max, step: '0.0001', value: max > 0 ? max : '', disabled: true });
                row.append($('<td class="text-center">').append(checkbox));
                row.append($('<td>').html(`<strong>${safe(item.code)}</strong> — ${safe(item.name)} <span class="text-muted">(${safe(item.unit)})</span>`));
                row.append($('<td class="text-end">').text(`${item.remaining_quantity} ${item.unit}`));
                row.append($('<td class="text-end">').text(item.track_stock ? `${item.available_stock} ${item.unit}` : 'Tidak dilacak'));
                row.append($('<td>').append(input)); body.append(row);
            });
            recalculate();
        }).fail(xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Sales Order tidak lagi tersedia untuk Delivery.' }));
    });

    function recalculate() {
        let selectedCount = 0;
        $('#deliveryItems tr[data-sales-order-item-id]').each(function() {
            const row = $(this), selected = row.find('.delivery-item-select').is(':checked');
            row.find('.delivery-quantity').prop('disabled', !selected); if (selected) selectedCount++;
        });
        $('#submitDelivery').prop('disabled', selectedCount === 0 || !$('#sales_order_id').val());
    }
    $(document).on('change', '.delivery-item-select', recalculate);
    $(document).on('input', '.delivery-quantity', function() {
        const input = $(this), max = Number(input.attr('max'));
        if (Number(input.val()) > max) input.val(max);
    });

    $('#deliveryForm').on('submit', function(event) {
        event.preventDefault(); const items = [];
        $('#deliveryItems tr[data-sales-order-item-id]').each(function() {
            const row = $(this); if (!row.find('.delivery-item-select').is(':checked')) return;
            items.push({ sales_order_item_id: Number(row.data('sales-order-item-id')), quantity: row.find('.delivery-quantity').val() });
        });
        const button = $('#submitDelivery').prop('disabled', true);
        $.ajax({
            url: @json(route('super.deliveries.store')), method: 'POST',
            data: { _token: @json(csrf_token()), sales_order_id: $('#sales_order_id').val(), delivered_at: $('#delivered_at').val(), notes: $('#delivery_notes').val(), items },
            success: response => Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }).then(() => window.location.href = response.url),
            error: xhr => { const errors = xhr.responseJSON?.errors; Swal.fire({ icon: 'error', title: 'Gagal', text: errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Draft Delivery gagal dibuat.') }); button.prop('disabled', false); }
        });
    });
});
</script>
@endpush
