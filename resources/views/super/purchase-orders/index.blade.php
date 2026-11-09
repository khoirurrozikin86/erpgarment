@extends('layouts.admin')

@section('title', 'Purchase Order')

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Purchasing</a></li>
        <li class="breadcrumb-item active" aria-current="page">Purchase Order</li>
    </ol></nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h4 class="page-title mb-1">Purchase Order</h4><span class="text-muted">Buat dan terbitkan pesanan supplier dari PR yang sudah disetujui.</span></div>
            @can('purchase-orders.create')
                <a href="{{ route('super.purchase-orders.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i> Buat Purchase Order</a>
            @endcan
        </div>

        <div class="col-12">
            @include('super.purchasing.date-filter')
            <div class="card"><div class="card-body"><div class="table-responsive">
            <table id="purchase-orders-table" class="table table-bordered table-hover align-middle w-100">
                <thead><tr>
                    <th width="50">No</th><th>Nomor PO</th><th>Nomor PR</th><th>Supplier</th>
                    <th>Tanggal PO</th><th>Perkiraan Tiba</th><th>Total</th><th>Status</th><th width="170">Action</th>
                </tr></thead>
            </table>
            </div></div></div>
        </div>
    </div>

    <div class="modal fade" id="viewPurchaseOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewPurchaseOrderTitle">Detail Purchase Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body" id="viewPurchaseOrderBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="purchaseOrderEmailHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="purchaseOrderEmailHistoryTitle">Riwayat Email Purchase Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="table-responsive"><table class="table table-sm table-bordered align-middle">
                    <thead><tr><th>Tanggal Kirim</th><th>Penerima</th><th>Subjek</th><th>Dikirim Oleh</th></tr></thead>
                    <tbody id="purchaseOrderEmailHistoryRows"><tr><td colspan="4" class="text-center text-muted">Memuat riwayat...</td></tr></tbody>
                </table></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const modal = new bootstrap.Modal(document.getElementById('viewPurchaseOrderModal'));
            const emailHistoryModal = new bootstrap.Modal(document.getElementById('purchaseOrderEmailHistoryModal'));
            const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
            const safe = value => $('<div>').text(value ?? '').html();
            const table = $('#purchase-orders-table').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: {
                    url: @json(route('super.purchase-orders.dt')),
                    data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'number', name: 'number' },
                    { data: 'purchase_request_number', name: 'purchase_request.number', orderable: false, searchable: false },
                    { data: 'supplier_name', name: 'supplier.name', orderable: false, searchable: false },
                    { data: 'order_date', name: 'order_date', render: data => data ? new Date(data).toLocaleDateString('id-ID') : '—' },
                    { data: 'expected_delivery_at', name: 'expected_delivery_at', defaultContent: '—', render: data => data ? new Date(data).toLocaleDateString('id-ID') : '—' },
                    { data: 'total_amount', name: 'total_amount', searchable: false, className: 'text-end', render: data => money(data) },
                    { data: 'status_label', name: 'status', searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[1, 'desc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            $('#applyDateFilter').on('click', () => table.ajax.reload());
            $('#todayDateFilter').on('click', () => {
                const today = @json(now()->toDateString());
                $('#from_date, #to_date').val(today);
                table.ajax.reload();
            });

            $(document).on('click', '.btn-view-purchase-order', function() {
                $.get($(this).data('url')).done(function(order) {
                    const status = { draft: 'Draft', issued: 'Diterbitkan', partially_received: 'Diterima Sebagian', received: 'Selesai Diterima', cancelled: 'Dibatalkan' }[order.status];
                    const rows = order.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.product)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
                    $('#viewPurchaseOrderTitle').text(`Purchase Order ${order.number}`);
                    $('#viewPurchaseOrderBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-3">Purchase Request</dt><dd class="col-sm-9">${safe(order.purchase_request_number)}</dd>
                        <dt class="col-sm-3">Supplier</dt><dd class="col-sm-9">${safe(order.supplier)}</dd>
                        <dt class="col-sm-3">Dibuat Oleh</dt><dd class="col-sm-9">${safe(order.creator)}</dd>
                        <dt class="col-sm-3">Tanggal PO</dt><dd class="col-sm-9">${safe(order.order_date)}</dd>
                        <dt class="col-sm-3">Perkiraan Tiba</dt><dd class="col-sm-9">${safe(order.expected_delivery_at)}</dd>
                        <dt class="col-sm-3">Status</dt><dd class="col-sm-9">${status}</dd>
                        <dt class="col-sm-3">Diterbitkan</dt><dd class="col-sm-9">${safe(order.issued_at)}</dd>
                        <dt class="col-sm-3">Syarat Pembayaran</dt><dd class="col-sm-9">${safe(order.payment_terms)}</dd>
                        <dt class="col-sm-3">Catatan</dt><dd class="col-sm-9 text-break">${safe(order.notes)}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Barang</th><th class="text-end">Jumlah</th><th class="text-end">Harga Satuan</th><th class="text-end">Subtotal</th></tr></thead><tbody>${rows}</tbody><tfoot><tr><th colspan="3" class="text-end">Total PO</th><th class="text-end">${money(order.total_amount)}</th></tr></tfoot></table></div>`);
                    modal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail Purchase Order tidak dapat dimuat.' }));
            });

            $(document).on('click', '.btn-email-history', function() {
                const button = $(this);
                $('#purchaseOrderEmailHistoryRows').html('<tr><td colspan="4" class="text-center text-muted">Memuat riwayat...</td></tr>');
                emailHistoryModal.show();
                $.get(button.data('url')).done(function(response) {
                    $('#purchaseOrderEmailHistoryTitle').text(`Riwayat Email ${response.number}`);
                    if (!response.emails.length) {
                        $('#purchaseOrderEmailHistoryRows').html('<tr><td colspan="4" class="text-center text-muted">Belum ada email yang berhasil dikirim.</td></tr>');
                        return;
                    }
                    const rows = response.emails.map(item => `<tr><td>${safe(item.sent_at)}</td><td>${safe(item.recipient)}</td><td>${safe(item.subject)}</td><td>${safe(item.sender)}</td></tr>`).join('');
                    $('#purchaseOrderEmailHistoryRows').html(rows);
                }).fail(() => {
                    $('#purchaseOrderEmailHistoryRows').html('<tr><td colspan="4" class="text-center text-danger">Riwayat email gagal dimuat.</td></tr>');
                });
            });

            $(document).on('click', '.btn-issue-purchase-order', function() {
                const button = $(this);
                Swal.fire({
                    icon: 'question', title: 'Terbitkan Purchase Order?',
                    text: 'Setelah diterbitkan, dokumen PO siap dikirim ke supplier.',
                    showCancelButton: true, confirmButtonText: 'Terbitkan', cancelButtonText: 'Batal'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: button.data('url'), method: 'POST',
                        data: { _token: @json(csrf_token()), _method: 'PUT' },
                        success: response => { Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); table.ajax.reload(null, false); },
                        error: xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Purchase Order gagal diterbitkan.' })
                    });
                });
            });

            $(document).on('click', '.btn-email-purchase-order', function() {
                const button = $(this);
                Swal.fire({
                    icon: 'question',
                    title: 'Kirim Purchase Order?',
                    text: 'PDF PO akan dikirim sebagai lampiran menggunakan Email Setting perusahaan.',
                    input: 'email',
                    inputLabel: 'Email supplier',
                    inputValue: button.data('email') || '',
                    inputPlaceholder: 'supplier@example.com',
                    inputValidator: value => !value ? 'Alamat email supplier wajib diisi.' : undefined,
                    showCancelButton: true,
                    confirmButtonText: 'Kirim Email',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: email => $.ajax({
                        url: button.data('url'),
                        method: 'POST',
                        data: { _token: @json(csrf_token()), email }
                    }).catch(xhr => {
                        Swal.showValidationMessage(xhr.responseJSON?.message || 'Email gagal dikirim.');
                    })
                }).then(result => {
                    if (result.isConfirmed && result.value?.message) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: result.value.message });
                    }
                });
            });
        });
    </script>
@endpush
