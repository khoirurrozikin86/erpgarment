@extends('layouts.admin')
@section('title', 'Delivery')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Sales</a></li><li class="breadcrumb-item active">Delivery</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Delivery</h4><span class="text-muted">Catat pengiriman penuh atau sebagian dari Sales Order. Posting mengurangi stok.</span></div>@can('deliveries.create')<a href="{{ route('super.deliveries.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>Buat Delivery</a>@endcan</div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="deliveries-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>No</th><th>Nomor Delivery</th><th>Nomor SO</th><th>Customer</th><th>Tanggal Kirim</th><th>Jumlah Barang</th><th>Status</th><th>Dikirim Oleh</th><th>Action</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="deliveryDetailModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="deliveryDetailTitle">Detail Delivery</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body" id="deliveryDetailBody"></div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deliveryDetailModal'));
    const safe = value => $('<div>').text(value ?? '').html();
    const table = $('#deliveries-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: @json(route('super.deliveries.dt')), data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); } },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'number', name: 'number' }, { data: 'sales_order_number', orderable: false, searchable: false },
            { data: 'customer_name', orderable: false, searchable: false },
            { data: 'delivered_at', name: 'delivered_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
            { data: 'items_count', searchable: false, className: 'text-center' },
            { data: 'status_label', name: 'status', searchable: false },
            { data: 'deliverer_name', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[4, 'desc']], drawCallback: function() { if (window.feather) feather.replace(); }
    });
    $('#applyDateFilter').on('click', () => table.ajax.reload());
    $('#todayDateFilter').on('click', () => { const today = @json(now()->toDateString()); $('#from_date, #to_date').val(today); table.ajax.reload(); });

    $(document).on('click', '.btn-view-delivery', function() {
        $.get($(this).data('url')).done(delivery => {
            const rows = delivery.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.name)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td></tr>`).join('');
            $('#deliveryDetailTitle').text(`Delivery ${delivery.number}`);
            $('#deliveryDetailBody').html(`<dl class="row"><dt class="col-sm-3">Sales Order</dt><dd class="col-sm-9">${safe(delivery.sales_order)}</dd><dt class="col-sm-3">Customer</dt><dd class="col-sm-9">${safe(delivery.customer)}</dd><dt class="col-sm-3">Tanggal Kirim</dt><dd class="col-sm-9">${safe(delivery.delivered_at)}</dd><dt class="col-sm-3">Status</dt><dd class="col-sm-9">${delivery.status === 'posted' ? 'Diposting' : 'Draft'}</dd><dt class="col-sm-3">Dikirim Oleh</dt><dd class="col-sm-9">${safe(delivery.deliverer)}</dd><dt class="col-sm-3">Waktu Posting</dt><dd class="col-sm-9">${safe(delivery.posted_at)}</dd><dt class="col-sm-3">Catatan</dt><dd class="col-sm-9">${safe(delivery.notes)}</dd></dl><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Barang</th><th class="text-end">Jumlah Kirim</th></tr></thead><tbody>${rows}</tbody></table></div>`);
            modal.show();
        }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail Delivery tidak dapat dimuat.' }));
    });
    $(document).on('click', '.btn-post-delivery', function() {
        const button = $(this);
        Swal.fire({ icon: 'warning', title: `Posting ${button.data('number')}?`, text: 'Stok tersedia akan diperiksa ulang. Jika cukup, stok akan dikurangi dan mutasi dicatat di Stock Card.', showCancelButton: true, confirmButtonText: 'Posting', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), _method: 'PUT' }, success: response => { Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); table.ajax.reload(null, false); }, error: xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join('\n') : (xhr.responseJSON?.message || 'Delivery gagal diposting.') }) });
        });
    });
    $(document).on('click', '.btn-delete-delivery', function() {
        const button = $(this);
        Swal.fire({ icon: 'warning', title: `Hapus draft ${button.data('number')}?`, text: 'Jumlah barang pada draft ini akan dilepas agar bisa dibuatkan Delivery baru.', showCancelButton: true, confirmButtonText: 'Hapus Draft', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), _method: 'DELETE' }, success: response => { Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); table.ajax.reload(null, false); }, error: xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Draft Delivery gagal dihapus.' }) });
        });
    });
});
</script>
@endpush
