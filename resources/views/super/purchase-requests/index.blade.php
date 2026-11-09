@extends('layouts.admin')

@section('title', 'Purchase Request')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Purchasing</a></li>
            <li class="breadcrumb-item active" aria-current="page">Purchase Request</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="page-title mb-1">Purchase Request</h4>
                <span class="text-muted">Tinjau permintaan pembelian dan proses approval.</span>
            </div>
            @can('purchase-requests.create')
                <a href="{{ route('super.purchase-requests.create') }}" class="btn btn-primary btn-sm">
                    <i data-feather="plus" class="icon-sm me-1"></i> Buat Purchase Request
                </a>
            @endcan
        </div>

        <div class="col-12">
            @include('super.purchasing.date-filter')
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="purchase-requests-table" class="table table-bordered table-hover align-middle w-100">
                            <thead><tr>
                                <th width="50">No</th><th>Nomor PR</th><th>Tanggal Pengajuan</th><th>Nomor MR</th><th>Supplier</th>
                                <th>Pemohon</th><th>Total Estimasi</th><th>Status</th><th width="120">Action</th>
                            </tr></thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewPurchaseRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPurchaseRequestTitle">Detail Purchase Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="viewPurchaseRequestBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const viewModal = new bootstrap.Modal(document.getElementById('viewPurchaseRequestModal'));
            const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
            const safe = value => $('<div>').text(value ?? '').html();
            const table = $('#purchase-requests-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: @json(route('super.purchase-requests.dt')),
                    data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'number', name: 'number' },
                    { data: 'created_at', name: 'created_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
                    { data: 'material_request_number', name: 'material_request.number', orderable: false, searchable: false },
                    { data: 'supplier_name', name: 'supplier.name', orderable: false, searchable: false },
                    { data: 'requester_name', name: 'requester_name', orderable: false, searchable: false },
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

            $(document).on('click', '.btn-view-purchase-request', function() {
                $.get($(this).data('url')).done(function(request) {
                    const status = { pending: 'Menunggu Approval', approved: 'Disetujui', rejected: 'Ditolak' }[request.status];
                    const rows = request.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.product)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
                    $('#viewPurchaseRequestTitle').text(`Purchase Request ${request.number}`);
                    $('#viewPurchaseRequestBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-3">Material Request</dt><dd class="col-sm-9">${safe(request.material_request_number)}</dd>
                        <dt class="col-sm-3">Supplier</dt><dd class="col-sm-9">${safe(request.supplier)}</dd>
                        <dt class="col-sm-3">Pemohon</dt><dd class="col-sm-9">${safe(request.requester)}</dd>
                        <dt class="col-sm-3">Status</dt><dd class="col-sm-9">${status}</dd>
                        <dt class="col-sm-3">Catatan</dt><dd class="col-sm-9 text-break">${safe(request.notes)}</dd>
                        <dt class="col-sm-3">Ditinjau Oleh</dt><dd class="col-sm-9">${safe(request.reviewer)} (${safe(request.reviewed_at)})</dd>
                        <dt class="col-sm-3">Catatan Approval</dt><dd class="col-sm-9 text-break">${safe(request.review_note)}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Barang</th><th class="text-end">Jumlah</th><th class="text-end">Harga Satuan</th><th class="text-end">Subtotal</th></tr></thead><tbody>${rows}</tbody><tfoot><tr><th colspan="3" class="text-end">Total Estimasi</th><th class="text-end">${money(request.total_amount)}</th></tr></tfoot></table></div>`);
                    viewModal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail Purchase Request tidak dapat dimuat.' }));
            });

            $(document).on('click', '.btn-review-purchase-request', function() {
                const button = $(this);
                const rejecting = button.data('status') === 'rejected';
                Swal.fire({
                    icon: rejecting ? 'warning' : 'question',
                    title: rejecting ? 'Tolak Purchase Request?' : 'Setujui Purchase Request?',
                    input: 'textarea',
                    inputLabel: rejecting ? 'Alasan penolakan (wajib)' : 'Catatan approval (opsional)',
                    inputPlaceholder: 'Tulis catatan untuk pemohon',
                    showCancelButton: true,
                    confirmButtonText: rejecting ? 'Tolak' : 'Setujui',
                    cancelButtonText: 'Batal',
                    inputValidator: value => rejecting && !value?.trim() ? 'Alasan penolakan wajib diisi.' : undefined
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: button.data('url'), method: 'POST',
                        data: { _token: @json(csrf_token()), _method: 'PUT', note: result.value || '' },
                        success: response => { Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); table.ajax.reload(null, false); },
                        error: xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Status Purchase Request gagal diperbarui.' })
                    });
                });
            });
        });
    </script>
@endpush
