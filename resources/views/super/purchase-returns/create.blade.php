@extends('layouts.admin')

@section('title', 'Catat Purchase Return')

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('super.purchase-returns.index') }}">{{ __('ui.common.purchase_return') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.record_return') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="page-title mb-1">Catat Purchase Return</h4><span class="text-muted">Pilih penerimaan asal dan catat barang yang dikembalikan kepada supplier.</span></div>
        <a href="{{ route('super.purchase-returns.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i>{{ __('ui.common.back') }}</a>
    </div>

    <form id="purchaseReturnForm">
        @csrf
        <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">{{ __('ui.common.source_receipt') }}</h6>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#selectReceiptModal"><i data-feather="search" class="icon-sm me-1"></i>Cari Penerimaan</button>
        </div><div class="card-body">
            <input type="hidden" name="goods_receipt_id" id="goods_receipt_id">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('ui.common.number') }}</label><input id="selected_receipt_number" class="form-control" readonly placeholder="Pilih penerimaan asal"></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('ui.common.purchase_order') }}</label><input id="selected_purchase_order" class="form-control" readonly placeholder="Otomatis dari penerimaan"></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('ui.common.supplier') }}</label><input id="selected_supplier" class="form-control" readonly placeholder="Otomatis dari penerimaan"></div>
                <div class="col-md-4 mb-3"><label class="form-label" for="returned_at">{{ __('ui.common.returned_date') }}</label><input type="datetime-local" name="returned_at" id="returned_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-header"><h6 class="mb-0">{{ __('ui.common.returned_items') }}</h6></div><div class="card-body">
            <div class="table-responsive"><table class="table table-bordered align-middle">
                <thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.common.received') }}</th><th class="text-end">{{ __('ui.common.already_returned') }}</th><th class="text-end">{{ __('ui.common.returnable_remaining') }}</th><th style="width:180px">{{ __('ui.common.return_now') }}</th></tr></thead>
                <tbody id="returnItems"><tr><td colspan="5" class="text-center text-muted py-4">Pilih penerimaan untuk memuat daftar barang.</td></tr></tbody>
            </table></div>
            <div class="row"><div class="col-md-6 mb-3"><label class="form-label" for="reason">{{ __('ui.common.return_reason') }}</label><textarea name="reason" id="reason" class="form-control" rows="2" maxlength="2000" required placeholder="Contoh: barang rusak atau tidak sesuai pesanan"></textarea></div>
                <div class="col-md-6 mb-3"><label class="form-label" for="notes">{{ __('ui.common.notes') }}</label><textarea name="notes" id="notes" class="form-control" rows="2" maxlength="5000" placeholder="Catatan tambahan untuk supplier"></textarea></div></div>
        </div></div>
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('super.purchase-returns.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.cancel') }}</a><button type="submit" id="submitReturn" class="btn btn-primary btn-sm" disabled><i data-feather="corner-up-left" class="icon-sm me-1"></i>Simpan Retur</button></div>
    </form>

    <div class="modal fade" id="selectReceiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Pilih Penerimaan Barang</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body"><div class="table-responsive"><table id="eligibleReceiptsTable" class="table table-bordered table-hover align-middle w-100">
                <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.number') }}</th><th>{{ __('ui.common.po_number') }}</th><th>{{ __('ui.common.supplier') }}</th><th>{{ __('ui.common.received_date') }}</th><th>{{ __('ui.common.row_count') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead>
            </table></div></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const receiptModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('selectReceiptModal'));
            const safe = value => $('<div>').text(value ?? '').html();
            $('#eligibleReceiptsTable').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: @json(route('super.purchase-returns.eligible')),
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'number', name: 'number' },
                    { data: 'purchase_order_number', orderable: false, searchable: false },
                    { data: 'supplier_name', orderable: false, searchable: false },
                    { data: 'received_at', name: 'received_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
                    { data: 'items_count', searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[4, 'desc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            $(document).on('click', '.btn-select-return-receipt', function() {
                const url = @json(route('super.purchase-returns.source', ['goodsReceiptId' => '__ID__'])).replace('__ID__', $(this).data('id'));
                $.get(url).done(function(receipt) {
                    $('#goods_receipt_id').val(receipt.id);
                    $('#selected_receipt_number').val(receipt.number);
                    $('#selected_purchase_order').val(receipt.purchase_order);
                    $('#selected_supplier').val(receipt.supplier);
                    const rows = receipt.items.map((item, index) => `
                        <tr>
                            <td>${safe(item.code)} — ${safe(item.name)}${item.track_stock ? '' : '<div class="small text-muted">{{ __('ui.common.non_stock_item') }}</div>'}</td>
                            <td class="text-end">${item.received} ${safe(item.unit)}</td>
                            <td class="text-end">${item.returned} ${safe(item.unit)}</td>
                            <td class="text-end">${item.available} ${safe(item.unit)}</td>
                            <td><input type="hidden" name="items[${index}][goods_receipt_item_id]" value="${item.id}"><input type="number" class="form-control form-control-sm text-end return-quantity" name="items[${index}][quantity]" min="0" max="${item.available}" step="0.0001" value="0"><div class="small text-muted mt-1">Maks. ${item.available}</div></td>
                        </tr>`).join('');
                    $('#returnItems').html(rows || '<tr><td colspan="5" class="text-center text-muted">{{ __('ui.common.no_returnable_items') }}</td></tr>');
                    $('#submitReturn').prop('disabled', !rows);
                    receiptModal.hide();
                }).fail(() => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Detail penerimaan tidak dapat dimuat.' }));
            });

            $('#purchaseReturnForm').on('submit', function(event) {
                event.preventDefault();
                if (!$('.return-quantity').toArray().some(input => Number(input.value || 0) > 0)) {
                    Swal.fire({ icon: 'warning', title: 'Jumlah belum diisi', text: 'Masukkan jumlah retur untuk minimal satu barang.' });
                    return;
                }
                const button = $('#submitReturn').prop('disabled', true);
                $.ajax({
                    url: @json(route('super.purchase-returns.store')),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: response => Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }).then(() => window.location.href = @json(route('super.purchase-returns.index'))),
                    error: xhr => {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Purchase Return gagal disimpan.');
                        Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                        button.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
