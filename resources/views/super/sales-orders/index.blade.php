@extends('layouts.admin')
@section('title', 'Sales Order')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Sales</a></li><li class="breadcrumb-item active">Sales Order</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Sales Order</h4><span class="text-muted">Proses quotation yang disetujui menjadi pesanan customer. Stok berubah saat Delivery.</span></div>@can('sales-orders.create')<a href="{{ route('super.sales-orders.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>Buat Sales Order</a>@endcan</div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="sales-orders-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>No</th><th>Nomor SO</th><th>Quotation</th><th>Customer</th><th>Tanggal Order</th><th>Pengiriman Diminta</th><th class="text-end">Total</th><th>Status</th><th>Action</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="salesOrderDetailModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="salesOrderDetailTitle">Detail Sales Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body" id="salesOrderDetailBody"></div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const detailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('salesOrderDetailModal'));
    const safe = value => $('<div>').text(value ?? '').html();
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const table = $('#sales-orders-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: @json(route('super.sales-orders.dt')), data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); } },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'number', name: 'number' },
            { data: 'quotation_number', orderable: false, searchable: false },
            { data: 'customer_name', orderable: false, searchable: false },
            { data: 'order_date', name: 'order_date', render: data => data ? String(data).slice(0, 10).split('-').reverse().join('/') : '—' },
            { data: 'requested_delivery_at', name: 'requested_delivery_at', render: data => data ? String(data).slice(0, 10).split('-').reverse().join('/') : '—' },
            { data: 'total_amount', name: 'total_amount', className: 'text-end', render: data => money(data) },
            { data: 'status_label', name: 'status', searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[4, 'desc']], drawCallback: function() { if (window.feather) feather.replace(); }
    });
    $('#applyDateFilter').on('click', () => table.ajax.reload());
    $('#todayDateFilter').on('click', () => { const today = @json(now()->toDateString()); $('#from_date, #to_date').val(today); table.ajax.reload(); });

    $(document).on('click', '.btn-view-sales-order', function() {
        $.get($(this).data('url')).done(order => {
            const status = { draft: 'Draft', confirmed: 'Dikonfirmasi', partially_delivered: 'Dikirim Sebagian', delivered: 'Selesai Dikirim', cancelled: 'Dibatalkan' }[order.status] || order.status;
            const rows = order.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.name)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${item.delivered_quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
            $('#salesOrderDetailTitle').text(`Sales Order ${order.number}`);
            $('#salesOrderDetailBody').html(`<dl class="row"><dt class="col-sm-3">Quotation</dt><dd class="col-sm-9">${safe(order.quotation)}</dd><dt class="col-sm-3">Customer</dt><dd class="col-sm-9">${safe(order.customer)}</dd><dt class="col-sm-3">Tanggal Order</dt><dd class="col-sm-9">${safe(order.order_date)}</dd><dt class="col-sm-3">Pengiriman Diminta</dt><dd class="col-sm-9">${safe(order.requested_delivery_at)}</dd><dt class="col-sm-3">Status</dt><dd class="col-sm-9">${status}</dd><dt class="col-sm-3">Dibuat Oleh</dt><dd class="col-sm-9">${safe(order.creator)}</dd><dt class="col-sm-3">Dikonfirmasi Oleh</dt><dd class="col-sm-9">${safe(order.confirmer)} · ${safe(order.confirmed_at)}</dd><dt class="col-sm-3">Catatan</dt><dd class="col-sm-9">${safe(order.notes)}</dd></dl><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Barang</th><th class="text-end">Order</th><th class="text-end">Terkirim</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr></thead><tbody>${rows}</tbody><tfoot><tr><th colspan="4" class="text-end">Total</th><th class="text-end">${money(order.total_amount)}</th></tr></tfoot></table></div>`);
            detailModal.show();
        }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail Sales Order gagal dimuat.' }));
    });
    $(document).on('click', '.btn-confirm-sales-order', function() {
        const button = $(this);
        Swal.fire({ icon: 'question', title: `Konfirmasi ${button.data('number')}?`, text: 'Konfirmasi tidak mengubah stok. Barang keluar saat Delivery dibuat dan diposting.', showCancelButton: true, confirmButtonText: 'Konfirmasi', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), _method: 'PUT' }, success: response => { Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); table.ajax.reload(null, false); }, error: xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Sales Order gagal dikonfirmasi.' }) });
        });
    });
});
</script>
@endpush
