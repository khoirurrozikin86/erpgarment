@extends('layouts.admin')

@section('title', 'Stock Card')

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Inventory</a></li>
        <li class="breadcrumb-item active" aria-current="page">Stock Card</li>
    </ol></nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="page-title mb-1">Stock Card</h4><span class="text-muted">Telusuri stok masuk dan keluar beserta saldo berjalan per barang.</span></div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <div class="row align-items-end g-2">
            <div class="col-md-7">
                <label class="form-label mb-1" for="selected_product">Barang</label>
                <div class="input-group input-group-sm">
                    <input type="hidden" id="product_id">
                    <input type="text" id="selected_product" class="form-control" readonly placeholder="Pilih barang untuk melihat kartu stok">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#selectProductModal"><i data-feather="search" class="icon-sm me-1"></i>Pilih Barang</button>
                </div>
            </div>
            <div class="col-md-5 d-flex justify-content-md-end align-items-end">
                <a href="#" id="exportStockCard" class="btn btn-sm btn-outline-success disabled" aria-disabled="true"><i data-feather="download" class="icon-sm me-1"></i>Export Excel</a>
            </div>
        </div>
    </div></div>

    <div class="row g-3 mb-3 d-none" id="stockSummary">
        <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Saldo Awal Periode</div><div class="h4 mb-0" id="openingBalance">0</div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Total Masuk</div><div class="h4 mb-0 text-success" id="periodIn">0</div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Total Keluar</div><div class="h4 mb-0 text-danger" id="periodOut">0</div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Saldo Saat Ini</div><div class="h4 mb-0" id="currentBalance">0</div></div></div></div>
    </div>

    @include('super.purchasing.date-filter')

    <div class="card"><div class="card-body"><div class="table-responsive">
        <table id="stock-card-table" class="table table-bordered table-hover align-middle w-100">
            <thead><tr><th>No</th><th>Waktu Pencatatan</th><th>No. Dokumen</th><th>Tipe</th><th class="text-end">Masuk</th><th class="text-end">Keluar</th><th class="text-end">Saldo</th><th>Satuan</th><th>Catatan</th></tr></thead>
        </table>
    </div></div></div>

    <div class="modal fade" id="selectProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Pilih Barang</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><div class="table-responsive"><table id="stockProductsTable" class="table table-bordered table-hover align-middle w-100">
                <thead><tr><th>No</th><th>Kode</th><th>Nama Barang</th><th>Satuan</th><th>Action</th></tr></thead>
            </table></div></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const productModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('selectProductModal'));
            const safe = value => $('<div>').text(value ?? '').html();
            const number = value => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(Number(value || 0));

            const table = $('#stock-card-table').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: {
                    url: @json(route('super.stock-card.dt')),
                    data: data => {
                        data.product_id = $('#product_id').val();
                        data.from_date = $('#from_date').val();
                        data.to_date = $('#to_date').val();
                    }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'created_at', name: 'created_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
                    { data: 'reference_number', orderable: false, searchable: false },
                    { data: 'movement_label', name: 'movement_type', searchable: false },
                    { data: 'quantity_in', orderable: false, searchable: false, className: 'text-end' },
                    { data: 'quantity_out', orderable: false, searchable: false, className: 'text-end' },
                    { data: 'balance_after', orderable: false, searchable: false, className: 'text-end fw-semibold' },
                    { data: 'unit_label', orderable: false, searchable: false },
                    { data: 'notes', name: 'notes', defaultContent: '—' }
                ],
                order: [[1, 'asc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            $('#stockProductsTable').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: @json(route('super.stock-card.products.dt')),
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'code', name: 'code' },
                    { data: 'name', name: 'name' },
                    { data: 'unit_label', orderable: false, searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[2, 'asc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            function loadSummary() {
                const productId = $('#product_id').val();
                if (!productId) {
                    $('#stockSummary').addClass('d-none');
                    return;
                }
                $.get(@json(route('super.stock-card.summary')), {
                    product_id: productId,
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val()
                }).done(function(summary) {
                    const unit = summary.unit ? ` ${safe(summary.unit)}` : '';
                    $('#openingBalance').html(`${number(summary.opening_balance)}${unit}`);
                    $('#periodIn').html(`${number(summary.quantity_in)}${unit}`);
                    $('#periodOut').html(`${number(summary.quantity_out)}${unit}`);
                    $('#currentBalance').html(`${number(summary.current_balance)}${unit}`);
                    $('#stockSummary').removeClass('d-none');
                }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Ringkasan saldo stok tidak dapat dimuat.' }));
            }

            function updateExportLink() {
                const productId = $('#product_id').val();
                const link = $('#exportStockCard');
                if (!productId) {
                    link.addClass('disabled').attr('aria-disabled', 'true').attr('href', '#');
                    return;
                }
                const params = new URLSearchParams({
                    product_id: productId,
                    from_date: $('#from_date').val() || '',
                    to_date: $('#to_date').val() || ''
                });
                link.removeClass('disabled').attr('aria-disabled', 'false')
                    .attr('href', `${@json(route('super.stock-card.export'))}?${params.toString()}`);
            }

            $(document).on('click', '.btn-select-stock-product', function() {
                const button = $(this);
                $('#product_id').val(button.data('id'));
                $('#selected_product').val(`${button.data('code')} — ${button.data('name')}`);
                productModal.hide();
                table.ajax.reload();
                loadSummary();
                updateExportLink();
            });

            $('#applyDateFilter').on('click', () => {
                table.ajax.reload();
                loadSummary();
                updateExportLink();
            });
            $('#todayDateFilter').on('click', () => {
                const today = @json(now()->toDateString());
                $('#from_date, #to_date').val(today);
                table.ajax.reload();
                loadSummary();
                updateExportLink();
            });

            $('#exportStockCard').on('click', function(event) {
                if (!$('#product_id').val()) event.preventDefault();
            });

            @if ($initialProduct)
                $('#product_id').val(@json($initialProduct->id));
                $('#selected_product').val(@json($initialProduct->code . ' — ' . $initialProduct->name));
                table.ajax.reload();
                loadSummary();
                updateExportLink();
            @endif
        });
    </script>
@endpush
