@extends('layouts.admin')

@section('title', 'Purchase Return')

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Purchasing</a></li>
        <li class="breadcrumb-item active" aria-current="page">Purchase Return</li>
    </ol></nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h4 class="page-title mb-1">Purchase Return</h4><span class="text-muted">Catat barang yang dikembalikan kepada supplier dari penerimaan.</span></div>
            @can('purchase-returns.create')
                <a href="{{ route('super.purchase-returns.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>Catat Retur</a>
            @endcan
        </div>
        <div class="col-12">
            @include('super.purchasing.date-filter')
            <div class="card"><div class="card-body"><div class="table-responsive">
                <table id="purchase-returns-table" class="table table-bordered table-hover align-middle w-100">
                    <thead><tr><th>No</th><th>Nomor Return</th><th>Nomor GR</th><th>Supplier</th><th>Tanggal Retur</th><th>Total Nilai</th><th>Dicatat Oleh</th><th>Action</th></tr></thead>
                </table>
            </div></div></div>
        </div>
    </div>

    <div class="modal fade" id="viewPurchaseReturnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewPurchaseReturnTitle">Detail Purchase Return</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body" id="viewPurchaseReturnBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const modal = new bootstrap.Modal(document.getElementById('viewPurchaseReturnModal'));
            const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
            const safe = value => $('<div>').text(value ?? '').html();
            const table = $('#purchase-returns-table').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: {
                    url: @json(route('super.purchase-returns.dt')),
                    data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'number', name: 'number' },
                    { data: 'receipt_number', orderable: false, searchable: false },
                    { data: 'supplier_name', orderable: false, searchable: false },
                    { data: 'returned_at', name: 'returned_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
                    { data: 'total_amount', name: 'total_amount', searchable: false, className: 'text-end', render: data => money(data) },
                    { data: 'returner_name', orderable: false, searchable: false },
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

            $(document).on('click', '.btn-view-purchase-return', function() {
                $.get($(this).data('url')).done(function(item) {
                    const rows = item.items.map(row => `<tr><td>${safe(row.code)} — ${safe(row.name)}</td><td class="text-end">${row.quantity} ${safe(row.unit)}</td><td class="text-end">${money(row.unit_price)}</td><td class="text-end">${money(row.line_total)}</td></tr>`).join('');
                    $('#viewPurchaseReturnTitle').text(`Purchase Return ${item.number}`);
                    $('#viewPurchaseReturnBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-3">Penerimaan Asal</dt><dd class="col-sm-9">${safe(item.receipt)}</dd>
                        <dt class="col-sm-3">Purchase Order</dt><dd class="col-sm-9">${safe(item.purchase_order)}</dd>
                        <dt class="col-sm-3">Supplier</dt><dd class="col-sm-9">${safe(item.supplier)}</dd>
                        <dt class="col-sm-3">Tanggal Retur</dt><dd class="col-sm-9">${safe(item.returned_at)}</dd>
                        <dt class="col-sm-3">Dicatat Oleh</dt><dd class="col-sm-9">${safe(item.returner)}</dd>
                        <dt class="col-sm-3">Alasan</dt><dd class="col-sm-9 text-break">${safe(item.reason)}</dd>
                        <dt class="col-sm-3">Catatan</dt><dd class="col-sm-9 text-break">${safe(item.notes)}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Barang</th><th class="text-end">Jumlah Retur</th><th class="text-end">Harga Satuan</th><th class="text-end">Subtotal</th></tr></thead><tbody>${rows}</tbody><tfoot><tr><th colspan="3" class="text-end">Total Retur</th><th class="text-end">${money(item.total_amount)}</th></tr></tfoot></table></div>`);
                    modal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail purchase return tidak dapat dimuat.' }));
            });
        });
    </script>
@endpush
