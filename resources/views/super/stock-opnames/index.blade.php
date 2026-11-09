@extends('layouts.admin')

@section('title', 'Stock Opname')

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Inventory</a></li>
        <li class="breadcrumb-item active" aria-current="page">Stock Opname</li>
    </ol></nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h4 class="page-title mb-1">Stock Opname</h4><span class="text-muted">Bandingkan hasil hitung fisik dengan saldo sistem dan posting selisih stok.</span></div>
            @can('stock-opnames.create')
                <a href="{{ route('super.stock-opnames.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>Buat Opname</a>
            @endcan
        </div>
        <div class="col-12">
            @include('super.purchasing.date-filter')
            <div class="card"><div class="card-body"><div class="table-responsive">
                <table id="stock-opnames-table" class="table table-bordered table-hover align-middle w-100">
                    <thead><tr><th>No</th><th>Nomor Opname</th><th>Waktu Mulai</th><th>Jumlah Barang</th><th>Status</th><th>Dibuat Oleh</th><th>Dihitung Oleh</th><th>Diposting Oleh</th><th>Action</th></tr></thead>
                </table>
            </div></div></div>
        </div>
    </div>

    <div class="modal fade" id="viewStockOpnameModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewStockOpnameTitle">Detail Stock Opname</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body" id="viewStockOpnameBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const modal = new bootstrap.Modal(document.getElementById('viewStockOpnameModal'));
            const safe = value => $('<div>').text(value ?? '').html();
            const table = $('#stock-opnames-table').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: {
                    url: @json(route('super.stock-opnames.dt')),
                    data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'number', name: 'number' },
                    { data: 'counted_at', name: 'counted_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
                    { data: 'items_count', searchable: false },
                    { data: 'status_label', name: 'status', searchable: false },
                    { data: 'starter_name', orderable: false, searchable: false },
                    { data: 'counter_name', orderable: false, searchable: false },
                    { data: 'poster_name', orderable: false, searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[2, 'desc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            $('#applyDateFilter').on('click', () => table.ajax.reload());
            $('#todayDateFilter').on('click', () => {
                const today = @json(now()->toDateString());
                $('#from_date, #to_date').val(today);
                table.ajax.reload();
            });

            $(document).on('click', '.btn-view-stock-opname', function() {
                $.get($(this).data('url')).done(function(opname) {
                    const rows = opname.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.name)}</td><td class="text-end">${item.system_quantity} ${safe(item.unit)}</td><td class="text-end">${item.counted_quantity} ${safe(item.unit)}</td><td class="text-end">${item.difference}</td></tr>`).join('');
                    $('#viewStockOpnameTitle').text(`Stock Opname ${opname.number}`);
                    $('#viewStockOpnameBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-3">Waktu Mulai</dt><dd class="col-sm-9">${safe(opname.counted_at)}</dd>
                        <dt class="col-sm-3">Status</dt><dd class="col-sm-9">${{ draft: 'Draft', counting: 'Menunggu Posting', posted: 'Diposting' }[opname.status]}</dd>
                        <dt class="col-sm-3">Dibuat Oleh</dt><dd class="col-sm-9">${safe(opname.starter)}</dd>
                        <dt class="col-sm-3">Dihitung Oleh</dt><dd class="col-sm-9">${safe(opname.counter)}</dd>
                        <dt class="col-sm-3">Diposting Oleh</dt><dd class="col-sm-9">${safe(opname.poster)} (${safe(opname.posted_at)})</dd>
                        <dt class="col-sm-3">Catatan</dt><dd class="col-sm-9 text-break">${safe(opname.notes)}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Barang</th><th class="text-end">Saldo Sistem</th><th class="text-end">Fisik</th><th class="text-end">Selisih</th></tr></thead><tbody>${rows}</tbody></table></div>`);
                    modal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail Stock Opname tidak dapat dimuat.' }));
            });

            $(document).on('click', '.btn-post-stock-opname', function() {
                const button = $(this);
                Swal.fire({
                    icon: 'warning', title: `Posting ${button.data('number')}?`,
                    text: 'Selisih hitung akan langsung memperbarui saldo stok dan tercatat di Stock Card.',
                    showCancelButton: true, confirmButtonText: 'Posting', cancelButtonText: 'Batal'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: button.data('url'), method: 'POST',
                        data: { _token: @json(csrf_token()), _method: 'PUT' },
                        success: response => { Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); table.ajax.reload(null, false); },
                        error: xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Stock Opname gagal diposting.' })
                    });
                });
            });
        });
    </script>
@endpush
