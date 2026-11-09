@extends('layouts.admin')

@section('title', 'Catat Penerimaan Barang')

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('super.goods-receipts.index') }}">Penerimaan Barang</a></li>
        <li class="breadcrumb-item active" aria-current="page">Catat Penerimaan</li>
    </ol></nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="page-title mb-1">Catat Penerimaan Barang</h4><span class="text-muted">Catat jumlah yang benar-benar diterima. Penerimaan bisa dilakukan bertahap.</span></div>
        <a href="{{ route('super.goods-receipts.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i>Kembali</a>
    </div>

    <form id="goodsReceiptForm">
        @csrf
        <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Purchase Order</h6>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#selectPurchaseOrderModal"><i data-feather="search" class="icon-sm me-1"></i>Cari PO</button>
        </div><div class="card-body">
            <input type="hidden" name="purchase_order_id" id="purchase_order_id">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Nomor PO</label><input id="selected_po_number" class="form-control" readonly placeholder="Pilih PO yang akan diterima"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Supplier</label><input id="selected_supplier" class="form-control" readonly placeholder="Otomatis dari PO"></div>
                <div class="col-md-4 mb-3"><label class="form-label" for="received_at">Tanggal Penerimaan</label><input type="datetime-local" name="received_at" id="received_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-header"><h6 class="mb-0">Barang yang Diterima</h6></div><div class="card-body">
            <div class="table-responsive"><table class="table table-bordered align-middle">
                <thead><tr><th>Barang</th><th class="text-end">Dipesan</th><th class="text-end">Sudah Diterima</th><th class="text-end">Sisa</th><th style="width:180px">Diterima Sekarang</th></tr></thead>
                <tbody id="receiptItems"><tr><td colspan="5" class="text-center text-muted py-4">Pilih PO untuk memuat daftar barang.</td></tr></tbody>
            </table></div>
            <div class="mb-3"><label class="form-label" for="notes">Catatan</label><textarea name="notes" id="notes" class="form-control" rows="2" maxlength="5000" placeholder="Catatan kondisi barang atau dokumen penerimaan"></textarea></div>
        </div></div>
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('super.goods-receipts.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a><button type="submit" id="submitReceipt" class="btn btn-primary btn-sm" disabled><i data-feather="check" class="icon-sm me-1"></i>Simpan Penerimaan</button></div>
    </form>

    <div class="modal fade" id="selectPurchaseOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Pilih Purchase Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><div class="table-responsive"><table id="eligiblePurchaseOrdersTable" class="table table-bordered table-hover align-middle w-100">
                <thead><tr><th>No</th><th>Nomor PO</th><th>Supplier</th><th>Tanggal PO</th><th>Status</th><th>Jumlah Item</th><th>Action</th></tr></thead>
            </table></div></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const poModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('selectPurchaseOrderModal'));
            const safe = value => $('<div>').text(value ?? '').html();
            const table = $('#eligiblePurchaseOrdersTable').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: @json(route('super.goods-receipts.eligible')),
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'number', name: 'number' },
                    { data: 'supplier_name', orderable: false, searchable: false },
                    { data: 'order_date', name: 'order_date', render: data => data ? new Date(data).toLocaleDateString('id-ID') : '—' },
                    { data: 'status', render: data => data === 'partially_received' ? 'Diterima Sebagian' : 'Diterbitkan' },
                    { data: 'items_count', searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[3, 'desc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            $(document).on('click', '.btn-select-receipt-po', function() {
                const button = $(this);
                const url = @json(route('super.goods-receipts.source', ['purchaseOrderId' => '__ID__'])).replace('__ID__', button.data('id'));
                $.get(url).done(function(order) {
                    $('#purchase_order_id').val(order.id);
                    $('#selected_po_number').val(order.number);
                    $('#selected_supplier').val(order.supplier);
                    const rows = order.items.map((item, index) => `
                        <tr>
                            <td>${safe(item.code)} — ${safe(item.name)}${item.track_stock ? '' : '<div class="small text-muted">Barang non-stok</div>'}</td>
                            <td class="text-end">${item.ordered} ${safe(item.unit)}</td>
                            <td class="text-end">${item.received} ${safe(item.unit)}</td>
                            <td class="text-end">${item.remaining} ${safe(item.unit)}</td>
                            <td><input type="hidden" name="items[${index}][purchase_order_item_id]" value="${item.id}"><input type="number" class="form-control form-control-sm text-end receipt-quantity" name="items[${index}][quantity]" min="0" max="${item.remaining}" step="0.0001" value="0"><div class="small text-muted mt-1">Maks. ${item.remaining}</div></td>
                        </tr>`).join('');
                    $('#receiptItems').html(rows || '<tr><td colspan="5" class="text-center text-muted">Tidak ada sisa barang untuk diterima.</td></tr>');
                    $('#submitReceipt').prop('disabled', !rows);
                    poModal.hide();
                }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail PO tidak dapat dimuat.' }));
            });

            $('#goodsReceiptForm').on('submit', function(event) {
                event.preventDefault();
                const quantities = $('.receipt-quantity').toArray().map(input => Number(input.value || 0));
                if (!quantities.some(value => value > 0)) {
                    Swal.fire({ icon: 'warning', title: 'Jumlah belum diisi', text: 'Masukkan jumlah diterima untuk minimal satu barang.' });
                    return;
                }
                const submitButton = $('#submitReceipt').prop('disabled', true);
                $.ajax({
                    url: @json(route('super.goods-receipts.store')),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: response => Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }).then(() => window.location.href = @json(route('super.goods-receipts.index'))),
                    error: xhr => {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Penerimaan gagal disimpan.');
                        Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                        submitButton.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
