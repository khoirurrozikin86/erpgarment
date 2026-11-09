@extends('layouts.admin')

@section('title', 'Buat Purchase Order')

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Purchasing</a></li>
        <li class="breadcrumb-item"><a href="{{ route('super.purchase-orders.index') }}">Purchase Order</a></li>
        <li class="breadcrumb-item active" aria-current="page">Buat</li>
    </ol></nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="page-title mb-1">Buat Purchase Order</h4>
                <span class="text-muted">PO dibuat dari Purchase Request yang sudah disetujui.</span>
            </div>
            <a href="{{ route('super.purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i> Kembali</a>
        </div>

        <div class="col-12 col-xxl-10">
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">Detail Pesanan</h6></div>
                <form id="purchaseOrderForm">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Purchase Request <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="hidden" id="po_purchase_request_id" name="purchase_request_id" value="{{ $initialPurchaseRequest?->id }}">
                                    <input type="text" class="form-control" id="po_purchase_request_number" placeholder="Pilih Purchase Request" value="{{ $initialPurchaseRequest?->number }}" readonly required>
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#purchaseRequestPicker"><i data-feather="search" class="icon-sm me-1"></i> Cari PR</button>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" class="form-control" id="po_supplier" value="" readonly placeholder="Terisi dari Purchase Request">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="po_expected_delivery" class="form-label">Perkiraan Tanggal Pengiriman</label>
                                <input type="date" class="form-control" id="po_expected_delivery" name="expected_delivery_at" min="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="po_payment_terms" class="form-label">Syarat Pembayaran</label>
                                <input type="text" class="form-control" id="po_payment_terms" name="payment_terms" maxlength="2000" placeholder="Contoh: Net 30 hari / COD">
                            </div>
                        </div>

                        <div class="alert alert-info py-2">Barang, jumlah, dan harga disalin dari PR yang disetujui agar nilai PO tetap sesuai approval.</div>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle">
                                <thead><tr><th>Barang</th><th class="text-end">Jumlah</th><th class="text-end">Harga Satuan</th><th class="text-end">Subtotal</th></tr></thead>
                                <tbody id="purchase-order-items"><tr><td colspan="4" class="text-center text-muted py-4">Pilih Purchase Request untuk memuat rincian barang.</td></tr></tbody>
                                <tfoot><tr><th colspan="3" class="text-end">Total Pesanan</th><th class="text-end" id="purchase-order-total">Rp 0</th></tr></tfoot>
                            </table>
                        </div>
                        <div class="mb-0">
                            <label for="po_notes" class="form-label">Catatan</label>
                            <textarea class="form-control" id="po_notes" name="notes" rows="3" maxlength="5000" placeholder="Catatan untuk supplier atau internal"></textarea>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('super.purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm" id="submitPurchaseOrder" disabled><i data-feather="save" class="icon-sm me-1"></i> Simpan sebagai Draft</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="purchaseRequestPicker" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title">Pilih Purchase Request</h5><small class="text-muted">Hanya PR yang disetujui dan belum memiliki PO.</small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body"><div class="table-responsive">
                <table id="eligible-purchase-requests" class="table table-bordered table-hover align-middle w-100">
                    <thead><tr><th>Nomor PR</th><th>Supplier</th><th>Pemohon</th><th>Jumlah Baris</th><th>Total</th><th width="90">Action</th></tr></thead>
                </table>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const pickerElement = document.getElementById('purchaseRequestPicker');
            const pickerModal = bootstrap.Modal.getOrCreateInstance(pickerElement);
            const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
            const table = $('#eligible-purchase-requests').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: @json(route('super.purchase-orders.eligible')),
                columns: [
                    { data: 'number', name: 'number' },
                    { data: 'supplier_name', name: 'supplier_name', orderable: false, searchable: false },
                    { data: 'requester_name', name: 'requester_name', orderable: false, searchable: false },
                    { data: 'items_count', searchable: false, className: 'text-center' },
                    { data: 'total_amount', name: 'total_amount', searchable: false, className: 'text-end', render: data => money(data) },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[0, 'desc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            function loadPurchaseRequest(id, number) {
                $('#po_purchase_request_id').val(id);
                $('#po_purchase_request_number').val(number);
                $('#submitPurchaseOrder').prop('disabled', true);
                $('#purchase-order-items').html('<tr><td colspan="4" class="text-center text-muted">Memuat rincian PR...</td></tr>');
                const sourceUrl = @json(route('super.purchase-orders.source', ['purchaseRequestId' => '__ID__']));
                $.get(sourceUrl.replace('__ID__', encodeURIComponent(id)))
                    .done(function(source) {
                        $('#po_supplier').val(source.supplier);
                        const body = $('#purchase-order-items').empty();
                        source.items.forEach(item => {
                            body.append($('<tr>').append(
                                $('<td>').text(`${item.code} — ${item.name}`),
                                $('<td class="text-end">').text(`${item.quantity} ${item.unit}`),
                                $('<td class="text-end">').text(money(item.unit_price)),
                                $('<td class="text-end">').text(money(item.line_total))
                            ));
                        });
                        $('#purchase-order-total').text(money(source.items.reduce((sum, item) => sum + Number(item.line_total || 0), 0)));
                        $('#submitPurchaseOrder').prop('disabled', !source.items.length);
                    })
                    .fail(xhr => {
                        $('#po_supplier').val('');
                        $('#purchase-order-items').html('<tr><td colspan="4" class="text-center text-danger">Purchase Request tidak tersedia.</td></tr>');
                        Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Purchase Request tidak lagi dapat dibuatkan PO.' });
                    });
            }

            $(document).on('click', '.btn-select-purchase-request', function() {
                loadPurchaseRequest($(this).data('id'), $(this).data('number'));
                pickerModal.hide();
            });

            $('#purchaseOrderForm').on('submit', function(e) {
                e.preventDefault();
                if (!$('#po_purchase_request_id').val()) return;
                const button = $('#submitPurchaseOrder');
                button.prop('disabled', true).text('Menyimpan...');
                $.ajax({
                    url: @json(route('super.purchase-orders.store')),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: response => Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }).then(() => {
                        window.location.href = @json(route('super.purchase-orders.index'));
                    }),
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Purchase Order gagal disimpan.');
                        Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                    },
                    complete: function() {
                        button.prop('disabled', !$('#po_purchase_request_id').val())
                            .html('<i data-feather="save" class="icon-sm me-1"></i> Simpan sebagai Draft');
                        if (window.feather) feather.replace();
                    }
                });
            });

            @if ($initialPurchaseRequest)
                loadPurchaseRequest(@json($initialPurchaseRequest->id), @json($initialPurchaseRequest->number));
            @endif
            pickerElement.addEventListener('shown.bs.modal', () => table.columns.adjust().responsive.recalc());
        });
    </script>
@endpush
