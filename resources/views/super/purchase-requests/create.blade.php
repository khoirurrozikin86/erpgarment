@extends('layouts.admin')

@section('title', 'Buat Purchase Request')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Purchasing</a></li>
            <li class="breadcrumb-item"><a href="{{ route('super.purchase-requests.index') }}">Purchase Request</a></li>
            <li class="breadcrumb-item active" aria-current="page">Buat</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="page-title mb-1">Buat Purchase Request</h4>
                <span class="text-muted">Pilih sebagian barang dari MR untuk satu supplier. Buat PR terpisah untuk supplier lain.</span>
            </div>
            <a href="{{ route('super.purchase-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                <i data-feather="arrow-left" class="icon-sm me-1"></i> Kembali
            </a>
        </div>

        <div class="col-12 col-xxl-10">
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">Informasi Pembelian</h6></div>
                <form id="purchaseRequestForm">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Material Request <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="hidden" id="pr_material_request_id" name="material_request_id">
                                    <input type="text" class="form-control" id="pr_material_request_number" placeholder="Pilih Material Request" readonly required>
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#materialRequestPicker">
                                        <i data-feather="search" class="icon-sm me-1"></i> Cari MR
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pr_supplier" class="form-label">Supplier untuk PR ini <span class="text-danger">*</span></label>
                                <select class="form-select" id="pr_supplier" name="supplier_id" required>
                                    <option value="">-- Pilih Supplier --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->code }} — {{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-info py-2">
                            Pilih hanya barang yang akan dibeli dari supplier ini. Jumlah tidak boleh melebihi sisa kebutuhan MR. Barang untuk supplier lain dibuat dalam PR terpisah.
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle">
                                <thead><tr><th style="width:55px" class="text-center">Pilih</th><th>Barang</th><th class="text-end">Sisa Kebutuhan MR</th><th style="min-width:140px">Jumlah PR</th><th style="min-width:170px">Harga Satuan</th><th class="text-end">Subtotal</th></tr></thead>
                                <tbody id="purchase-request-items">
                                    <tr><td colspan="6" class="text-center text-muted py-4">Cari dan pilih Material Request untuk memuat barangnya.</td></tr>
                                </tbody>
                                <tfoot><tr><th colspan="5" class="text-end">Total Estimasi</th><th class="text-end" id="purchase-request-total">Rp 0</th></tr></tfoot>
                            </table>
                        </div>

                        <div class="mb-0">
                            <label for="pr_notes" class="form-label">Catatan</label>
                            <textarea class="form-control" id="pr_notes" name="notes" rows="3" maxlength="5000" placeholder="Catatan tambahan untuk approval atau purchasing"></textarea>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('super.purchase-requests.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm" id="submitPurchaseRequest" disabled>
                            <i data-feather="send" class="icon-sm me-1"></i> Ajukan untuk Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="materialRequestPicker" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title">Pilih Material Request</h5><small class="text-muted">Hanya MR yang disetujui dan masih memiliki sisa kebutuhan.</small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="eligible-material-requests" class="table table-bordered table-hover align-middle w-100">
                        <thead><tr><th>Nomor MR</th><th>Pemohon</th><th>Departemen</th><th>Dibutuhkan</th><th>Jumlah Baris</th><th width="90">Action</th></tr></thead>
                    </table>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const pickerElement = document.getElementById('materialRequestPicker');
            const pickerModal = bootstrap.Modal.getOrCreateInstance(pickerElement);
            const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
            const safe = value => $('<div>').text(value ?? '').html();

            const pickerTable = $('#eligible-material-requests').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: @json(route('super.purchase-requests.eligible')),
                columns: [
                    { data: 'number', name: 'number' },
                    { data: 'requester_name', name: 'requester_name', orderable: false, searchable: false },
                    { data: 'department', name: 'department', defaultContent: '—' },
                    { data: 'needed_at', name: 'needed_at', defaultContent: '—', render: data => data ? new Date(data).toLocaleDateString('id-ID') : '—' },
                    { data: 'items_count', searchable: false, className: 'text-center' },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[0, 'desc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            function recalculateTotal() {
                let total = 0;
                $('#purchase-request-items tr[data-item-id]').each(function() {
                    const selected = $(this).find('.pr-item-select').is(':checked');
                    const quantity = selected ? Number($(this).find('.pr-quantity').val()) || 0 : 0;
                    const price = selected ? Number($(this).find('.pr-unit-price').val()) || 0 : 0;
                    const subtotal = Math.round(quantity * price * 100) / 100;
                    total += subtotal;
                    $(this).find('.pr-line-total').text(selected ? money(subtotal) : '—');
                    $(this).find('.pr-quantity, .pr-unit-price').prop('disabled', !selected);
                });
                $('#purchase-request-total').text(money(total));
                $('#submitPurchaseRequest').prop('disabled',
                    !$('#purchase-request-items .pr-item-select:checked').length ||
                    !$('#pr_material_request_id').val() ||
                    !$('#pr_supplier').val()
                );
            }

            function loadMaterialRequest(id, number) {
                $('#pr_material_request_id').val(id);
                $('#pr_material_request_number').val(number);
                $('#submitPurchaseRequest').prop('disabled', true);
                $('#purchase-request-items').html('<tr><td colspan="6" class="text-center text-muted">Memuat barang MR...</td></tr>');
                const sourceUrl = @json(route('super.purchase-requests.source', ['materialRequestId' => '__ID__']));
                $.get(sourceUrl.replace('__ID__', encodeURIComponent(id)))
                    .done(function(source) {
                        const body = $('#purchase-request-items').empty();
                        if (!source.items.length) {
                            body.html('<tr><td colspan="6" class="text-center text-muted">Semua jumlah barang MR sudah dialokasikan ke PR.</td></tr>');
                            recalculateTotal();
                            return;
                        }
                        source.items.forEach(item => {
                            const row = $('<tr>').attr('data-item-id', item.material_request_item_id);
                            const checkbox = $('<input>', { type: 'checkbox', class: 'form-check-input pr-item-select', 'aria-label': `Pilih ${item.name}` });
                            const quantityInput = $('<input>', {
                                type: 'number', class: 'form-control form-control-sm pr-quantity', min: '0.0001',
                                max: item.available_quantity, step: '0.0001', value: item.available_quantity, disabled: true
                            });
                            const priceInput = $('<input>', {
                                type: 'number', class: 'form-control form-control-sm text-end pr-unit-price', min: '0', step: '0.01', value: item.unit_price, disabled: true
                            });
                            row.append($('<td class="text-center">').append(checkbox));
                            row.append($('<td>').text(`${item.code} — ${item.name}`));
                            row.append($('<td class="text-end">').text(`${item.available_quantity} ${item.unit}`));
                            row.append($('<td>').append(quantityInput));
                            row.append($('<td>').append(priceInput));
                            row.append($('<td class="text-end pr-line-total">').text('—'));
                            row.data('source-item-id', item.material_request_item_id);
                            body.append(row);
                        });
                        recalculateTotal();
                    })
                    .fail(xhr => {
                        $('#submitPurchaseRequest').prop('disabled', true);
                        Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Material Request tidak lagi tersedia.' });
                    });
            }

            $(document).on('click', '.btn-select-material-request', function() {
                loadMaterialRequest($(this).data('id'), $(this).data('number'));
                pickerModal.hide();
            });
            $(document).on('change input', '.pr-item-select, .pr-quantity, .pr-unit-price', recalculateTotal);
            $('#pr_supplier').on('change', recalculateTotal);

            $('#purchaseRequestForm').on('submit', function(e) {
                e.preventDefault();
                const items = [];
                $('#purchase-request-items tr[data-item-id]').each(function() {
                    if (!$(this).find('.pr-item-select').is(':checked')) return;
                    items.push({
                        material_request_item_id: $(this).data('source-item-id'),
                        quantity: $(this).find('.pr-quantity').val(),
                        unit_price: $(this).find('.pr-unit-price').val(),
                    });
                });
                if (!items.length) {
                    Swal.fire({ icon: 'warning', title: 'Barang belum dipilih', text: 'Pilih setidaknya satu barang untuk PR ini.' });
                    return;
                }
                const button = $('#submitPurchaseRequest');
                button.prop('disabled', true).text('Mengirim...');
                $.ajax({
                    url: @json(route('super.purchase-requests.store')),
                    method: 'POST',
                    data: {
                        _token: @json(csrf_token()),
                        material_request_id: $('#pr_material_request_id').val(),
                        supplier_id: $('#pr_supplier').val(),
                        notes: $('#pr_notes').val(),
                        items: items,
                    },
                    success: function(response) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }).then(() => {
                            window.location.href = @json(route('super.purchase-requests.index'));
                        });
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Purchase Request gagal disimpan.');
                        Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                    },
                    complete: function() {
                        button.prop('disabled',
                            !$('#purchase-request-items .pr-item-select:checked').length ||
                            !$('#pr_material_request_id').val() ||
                            !$('#pr_supplier').val()
                        )
                            .html('<i data-feather="send" class="icon-sm me-1"></i> Ajukan untuk Approval');
                        if (window.feather) feather.replace();
                    }
                });
            });

            pickerElement.addEventListener('shown.bs.modal', () => pickerTable.columns.adjust().responsive.recalc());
        });
    </script>
@endpush
