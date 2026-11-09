@extends('layouts.admin')

@section('title', 'Material Request')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Purchasing</a></li>
            <li class="breadcrumb-item active" aria-current="page">Material Request</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="page-title mb-1">Material Request</h4>
                <span class="text-muted">Ajukan kebutuhan barang internal dan pantau proses approval.</span>
            </div>
            @can('material-requests.create')
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                    <i data-feather="plus" class="icon-sm me-1"></i> Buat Permintaan
                </button>
            @endcan
        </div>

        <div class="col-12">
            @include('super.purchasing.date-filter')
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="material-requests-table" class="table table-bordered table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>Nomor</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Pemohon</th>
                                    <th>Departemen</th>
                                    <th>Tanggal Dibutuhkan</th>
                                    <th>Jumlah Barang</th>
                                    <th>Approval</th>
                                    <th>Progress Purchasing</th>
                                    <th width="120">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('material-requests.create')
        <div class="modal fade" id="createRequestModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form id="materialRequestForm">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title">Buat Material Request</h5>
                                <small class="text-muted">Permintaan akan langsung dikirim untuk approval.</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="request_department" class="form-label">Departemen</label>
                                    <input type="text" class="form-control" id="request_department" name="department" maxlength="120" placeholder="Contoh: Produksi">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="request_needed_at" class="form-label">Tanggal Dibutuhkan</label>
                                    <input type="date" class="form-control" id="request_needed_at" name="needed_at">
                                </div>
                            </div>
                            <label class="form-label">Barang yang Dibutuhkan <span class="text-danger">*</span></label>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead><tr><th style="min-width:260px">Barang</th><th style="width:150px">Jumlah</th><th style="min-width:170px">Catatan</th><th style="width:45px"></th></tr></thead>
                                    <tbody id="request-items"></tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-request-item">
                                <i data-feather="plus" class="icon-sm me-1"></i> Tambah Barang
                            </button>
                            <div class="mb-0">
                                <label for="request_reason" class="form-label">Alasan Permintaan <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="request_reason" name="reason" rows="3" maxlength="5000" required placeholder="Jelaskan kebutuhan barang ini"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="submitMaterialRequest"><i data-feather="send" class="icon-sm me-1"></i> Ajukan Permintaan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <div class="modal fade" id="viewRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewRequestTitle">Detail Material Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="viewRequestBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const products = @json($products);
            const createModal = document.getElementById('createRequestModal');
            const viewModal = new bootstrap.Modal(document.getElementById('viewRequestModal'));
            let itemIndex = 0;

            function addItemRow() {
                const index = itemIndex++;
                const row = $('<tr>');
                const select = $('<select>', { class: 'form-select form-select-sm', name: `items[${index}][product_id]`, required: true })
                    .append($('<option>', { value: '', text: '-- Pilih Barang --' }));
                products.forEach(product => select.append($('<option>', { value: product.id, text: product.label })));
                row.append($('<td>').append(select));
                row.append($('<td>').append($('<input>', { type: 'number', class: 'form-control form-control-sm', name: `items[${index}][quantity]`, min: '0.0001', step: '0.0001', required: true, placeholder: 'Jumlah' })));
                row.append($('<td>').append($('<input>', { type: 'text', class: 'form-control form-control-sm', name: `items[${index}][note]`, maxlength: 255, placeholder: 'Opsional' })));
                row.append($('<td class="text-center">').append($('<button>', { type: 'button', class: 'btn btn-sm btn-outline-danger remove-request-item', html: '<i data-feather="x"></i>' })));
                $('#request-items').append(row);
                if (window.feather) feather.replace();
            }

            @can('material-requests.create')
                addItemRow();
                $('#add-request-item').on('click', addItemRow);
                $(document).on('click', '.remove-request-item', function() {
                    if ($('#request-items tr').length > 1) $(this).closest('tr').remove();
                });
                $('#materialRequestForm').on('submit', function(e) {
                    e.preventDefault();
                    const button = $('#submitMaterialRequest');
                    button.prop('disabled', true).text('Mengirim...');
                    $.ajax({
                        url: @json(route('super.material-requests.store')),
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(response) {
                            bootstrap.Modal.getOrCreateInstance(createModal).hide();
                            Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message });
                            $('#material-requests-table').DataTable().ajax.reload(null, false);
                            $('#materialRequestForm')[0].reset();
                            $('#request-items').empty(); itemIndex = 0; addItemRow();
                        },
                        error: function(xhr) {
                            const errors = xhr.responseJSON?.errors;
                            const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Permintaan gagal disimpan.');
                            Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                        },
                        complete: function() {
                            button.prop('disabled', false).html('<i data-feather="send" class="icon-sm me-1"></i> Ajukan Permintaan');
                            if (window.feather) feather.replace();
                        }
                    });
                });
            @endcan

            const table = $('#material-requests-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: @json(route('super.material-requests.dt')),
                    data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'number', name: 'number' },
                    { data: 'created_at', name: 'created_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
                    { data: 'requester_name', name: 'requester_name', orderable: false, searchable: false },
                    { data: 'department', name: 'department', defaultContent: '—' },
                    { data: 'needed_at', name: 'needed_at', defaultContent: '—', render: data => data ? new Date(data).toLocaleDateString('id-ID') : '—' },
                    { data: 'items_count', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'status_label', name: 'status', searchable: false },
                    { data: 'purchase_status_label', name: 'purchase_status', searchable: false },
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

            $(document).on('click', '.btn-view-request', function() {
                $.get($(this).data('url')).done(function(request) {
                    const status = { pending: 'Menunggu Approval', approved: 'Disetujui', rejected: 'Ditolak' }[request.status];
                    const purchaseStatus = { not_processed: 'Belum Diproses Purchasing', partially_processed: 'Sebagian Dialokasikan ke PR', processed: 'Seluruh kebutuhan dialokasikan ke PR' }[request.purchase_status] || '—';
                    const rows = request.items.map(item => `<tr><td>${$('<div>').text(item.code).html()} — ${$('<div>').text(item.product).html()}</td><td class="text-end">${item.quantity} ${$('<div>').text(item.unit).html()}</td><td>${$('<div>').text(item.note).html()}</td></tr>`).join('');
                    $('#viewRequestTitle').text(`Material Request ${request.number}`);
                    $('#viewRequestBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-4">Pemohon</dt><dd class="col-sm-8">${$('<div>').text(request.requester).html()}</dd>
                        <dt class="col-sm-4">Departemen</dt><dd class="col-sm-8">${$('<div>').text(request.department).html()}</dd>
                        <dt class="col-sm-4">Tanggal Dibutuhkan</dt><dd class="col-sm-8">${$('<div>').text(request.needed_at).html()}</dd>
                        <dt class="col-sm-4">Status Approval</dt><dd class="col-sm-8">${status}</dd>
                        <dt class="col-sm-4">Progress Purchasing</dt><dd class="col-sm-8">${purchaseStatus}</dd>
                        <dt class="col-sm-4">Alasan</dt><dd class="col-sm-8 text-break">${$('<div>').text(request.reason).html()}</dd>
                        <dt class="col-sm-4">Ditinjau Oleh</dt><dd class="col-sm-8">${$('<div>').text(request.reviewer).html()} (${request.reviewed_at})</dd>
                        <dt class="col-sm-4">Catatan Approval</dt><dd class="col-sm-8 text-break">${$('<div>').text(request.review_note).html()}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Barang</th><th class="text-end">Jumlah</th><th>Catatan</th></tr></thead><tbody>${rows}</tbody></table></div>`);
                    viewModal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail permintaan tidak dapat dimuat.' }));
            });

            $(document).on('click', '.btn-review-request', function() {
                const button = $(this);
                const rejecting = button.data('status') === 'rejected';
                Swal.fire({
                    icon: rejecting ? 'warning' : 'question',
                    title: rejecting ? 'Tolak permintaan?' : 'Setujui permintaan?',
                    input: 'textarea',
                    inputLabel: rejecting ? 'Alasan penolakan (wajib)' : 'Catatan (opsional)',
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
                        error: xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Status permintaan gagal diperbarui.' })
                    });
                });
            });
        });
    </script>
@endpush
