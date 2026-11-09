@extends('layouts.admin')

@section('title', __('ui.workflow.receipt_create_title'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('super.goods-receipts.index') }}">{{ __('ui.common.goods_receipt') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.record_receipt') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="page-title mb-1">{{ __('ui.workflow.receipt_create_title') }}</h4><span class="text-muted">{{ __('ui.workflow.receipt_create_subtitle') }}</span></div>
        <a href="{{ route('super.goods-receipts.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i>{{ __('ui.common.back') }}</a>
    </div>

    <form id="goodsReceiptForm">
        @csrf
        <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">{{ __('ui.common.purchase_order') }}</h6>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#selectPurchaseOrderModal"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.workflow.search_po') }}</button>
        </div><div class="card-body">
            <input type="hidden" name="purchase_order_id" id="purchase_order_id">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('ui.common.po_number') }}</label><input id="selected_po_number" class="form-control" readonly placeholder="{{ __('ui.workflow.choose_po_to_receive') }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('ui.common.supplier') }}</label><input id="selected_supplier" class="form-control" readonly placeholder="{{ __('ui.workflow.auto_from_po') }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label" for="received_at">{{ __('ui.common.receipt_date') }}</label><input type="datetime-local" name="received_at" id="received_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-header"><h6 class="mb-0">{{ __('ui.workflow.received_items') }}</h6></div><div class="card-body">
            <div class="table-responsive"><table class="table table-bordered align-middle">
                <thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.workflow.ordered') }}</th><th class="text-end">{{ __('ui.workflow.received_previously') }}</th><th class="text-end">{{ __('ui.common.remaining') }}</th><th style="width:180px">{{ __('ui.workflow.receive_now') }}</th></tr></thead>
                <tbody id="receiptItems"><tr><td colspan="5" class="text-center text-muted py-4">{{ __('ui.workflow.choose_po_load_items') }}</td></tr></tbody>
            </table></div>
            <div class="mb-3"><label class="form-label" for="notes">{{ __('ui.common.notes') }}</label><textarea name="notes" id="notes" class="form-control" rows="2" maxlength="5000" placeholder="{{ __('ui.workflow.receipt_notes_placeholder') }}"></textarea></div>
        </div></div>
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('super.goods-receipts.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.cancel') }}</a><button type="submit" id="submitReceipt" class="btn btn-primary btn-sm" disabled><i data-feather="check" class="icon-sm me-1"></i>{{ __('ui.workflow.save_receipt') }}</button></div>
    </form>

    <div class="modal fade" id="selectPurchaseOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ __('ui.workflow.choose_purchase_order') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body"><div class="table-responsive"><table id="eligiblePurchaseOrdersTable" class="table table-bordered table-hover align-middle w-100">
                <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.po_number') }}</th><th>{{ __('ui.common.supplier') }}</th><th>{{ __('ui.common.po_date') }}</th><th>{{ __('ui.common.status') }}</th><th>{{ __('ui.workflow.item_count') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead>
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
                    { data: 'status', render: data => data === 'partially_received' ? '{{ __('ui.workflow.receive_partial') }}' : '{{ __('ui.workflow.issued') }}' },
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
                            <td>${safe(item.code)} — ${safe(item.name)}${item.track_stock ? '' : '<div class="small text-muted">{{ __('ui.common.non_stock_item') }}</div>'}</td>
                            <td class="text-end">${item.ordered} ${safe(item.unit)}</td>
                            <td class="text-end">${item.received} ${safe(item.unit)}</td>
                            <td class="text-end">${item.remaining} ${safe(item.unit)}</td>
                            <td><input type="hidden" name="items[${index}][purchase_order_item_id]" value="${item.id}"><input type="number" class="form-control form-control-sm text-end receipt-quantity" name="items[${index}][quantity]" min="0" max="${item.remaining}" step="0.0001" value="0"><div class="small text-muted mt-1">Maks. ${item.remaining}</div></td>
                        </tr>`).join('');
                    $('#receiptItems').html(rows || '<tr><td colspan="5" class="text-center text-muted">{{ __('ui.workflow.no_remaining_receipts') }}</td></tr>');
                    $('#submitReceipt').prop('disabled', !rows);
                    poModal.hide();
                }).fail(() => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.detail_po_load_failed') }}' }));
            });

            $('#goodsReceiptForm').on('submit', function(event) {
                event.preventDefault();
                const quantities = $('.receipt-quantity').toArray().map(input => Number(input.value || 0));
                if (!quantities.some(value => value > 0)) {
                    Swal.fire({ icon: 'warning', title: '{{ __('ui.workflow.receive_quantity_required_title') }}', text: '{{ __('ui.workflow.receive_quantity_required_text') }}' });
                    return;
                }
                const submitButton = $('#submitReceipt').prop('disabled', true);
                $.ajax({
                    url: @json(route('super.goods-receipts.store')),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: response => Swal.fire({ icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message }).then(() => window.location.href = @json(route('super.goods-receipts.index'))),
                    error: xhr => {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || '{{ __('ui.workflow.receipt_save_failed') }}');
                        Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: message });
                        submitButton.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
