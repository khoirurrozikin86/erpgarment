@extends('layouts.admin')

@section('title', 'Penerimaan Barang')

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Purchasing</a></li>
        <li class="breadcrumb-item active" aria-current="page">Penerimaan Barang</li>
    </ol></nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h4 class="page-title mb-1">Penerimaan Barang</h4><span class="text-muted">Catat barang yang diterima dari Purchase Order dan pantau riwayat penerimaan.</span></div>
            @can('goods-receipts.create')
                <a href="{{ route('super.goods-receipts.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>Catat Penerimaan</a>
            @endcan
        </div>
        <div class="col-12">
            @include('super.purchasing.date-filter')
            <div class="card"><div class="card-body"><div class="table-responsive">
            <table id="goods-receipts-table" class="table table-bordered table-hover align-middle w-100">
                <thead><tr><th>No</th><th>Nomor Penerimaan</th><th>Nomor PO</th><th>Supplier</th><th>Tanggal Terima</th><th>Jumlah Baris</th><th>Diterima Oleh</th><th>Action</th></tr></thead>
            </table>
            </div></div></div>
        </div>
    </div>

    <div class="modal fade" id="viewGoodsReceiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewGoodsReceiptTitle">Detail Penerimaan Barang</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body" id="viewGoodsReceiptBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const modal = new bootstrap.Modal(document.getElementById('viewGoodsReceiptModal'));
            const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
            const safe = value => $('<div>').text(value ?? '').html();
            const table = $('#goods-receipts-table').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: {
                    url: @json(route('super.goods-receipts.dt')),
                    data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'number', name: 'number' },
                    { data: 'purchase_order_number', orderable: false, searchable: false },
                    { data: 'supplier_name', orderable: false, searchable: false },
                    { data: 'received_at', name: 'received_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
                    { data: 'items_count', searchable: false },
                    { data: 'receiver_name', orderable: false, searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[4, 'desc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            $('#applyDateFilter').on('click', () => table.ajax.reload());
            $('#todayDateFilter').on('click', () => {
                const today = @json(now()->toDateString());
                $('#from_date, #to_date').val(today);
                table.ajax.reload();
            });

            $(document).on('click', '.btn-view-goods-receipt', function() {
                $.get($(this).data('url')).done(function(receipt) {
                    const rows = receipt.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.name)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
                    $('#viewGoodsReceiptTitle').text(`Penerimaan ${receipt.number}`);
                    $('#viewGoodsReceiptBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-3">Purchase Order</dt><dd class="col-sm-9">${safe(receipt.purchase_order)}</dd>
                        <dt class="col-sm-3">Supplier</dt><dd class="col-sm-9">${safe(receipt.supplier)}</dd>
                        <dt class="col-sm-3">Tanggal Terima</dt><dd class="col-sm-9">${safe(receipt.received_at)}</dd>
                        <dt class="col-sm-3">Diterima Oleh</dt><dd class="col-sm-9">${safe(receipt.receiver)}</dd>
                        <dt class="col-sm-3">Catatan</dt><dd class="col-sm-9 text-break">${safe(receipt.notes)}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Barang</th><th class="text-end">Diterima</th><th class="text-end">Harga Satuan</th><th class="text-end">Subtotal</th></tr></thead><tbody>${rows}</tbody></table></div>`);
                    modal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail penerimaan tidak dapat dimuat.' }));
            });
        });
    </script>
@endpush
