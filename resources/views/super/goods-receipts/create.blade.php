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
            <h6 class="mb-0">{{ __('ui.workflow.receipt_document') }}</h6>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#selectPurchaseOrderModal"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.workflow.search_po') }}</button>
        </div><div class="card-body">
            <input type="hidden" name="purchase_order_id" id="purchase_order_id">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('ui.common.po_number') }}</label><input id="selected_po_number" class="form-control" readonly placeholder="{{ __('ui.workflow.choose_po_to_receive') }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label" for="received_at">{{ __('ui.common.receipt_date') }}</label><input type="datetime-local" name="received_at" id="received_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('ui.common.supplier') }}</label><input id="selected_supplier" class="form-control" readonly placeholder="{{ __('ui.workflow.auto_from_po') }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label" for="invoice_number">{{ __('ui.common.invoice_number') }}</label><input name="invoice_number" id="invoice_number" class="form-control" maxlength="100"></div>
                <div class="col-md-4 mb-3"><label class="form-label" for="invoice_date">{{ __('ui.common.invoice_date') }}</label><input type="date" name="invoice_date" id="invoice_date" class="form-control" value="{{ now()->toDateString() }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label" for="currency">{{ __('ui.common.currency') }}</label><input name="currency" id="currency" class="form-control text-uppercase" value="USD" maxlength="10" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('ui.workflow.material_order') }}</label><input id="selected_mo_number" class="form-control" readonly placeholder="—"></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('ui.workflow.style') }}</label><input id="selected_style" class="form-control" readonly placeholder="—"></div>
                <div class="col-12"><hr class="my-1"><div class="small fw-semibold text-muted mb-2">{{ __('ui.workflow.customs_document') }}</div></div>
                <div class="col-md-4 mb-3"><label class="form-label" for="customs_document_code">{{ __('ui.workflow.customs_document_code') }}</label><select name="customs_document_code" id="customs_document_code" class="form-select"><option value="">{{ __('ui.common.optional') }}</option>@foreach (['2.3', '2.7', '2.6.2', '4.0'] as $documentCode)<option value="{{ $documentCode }}">{{ $documentCode }}</option>@endforeach</select></div>
                <div class="col-md-4 mb-3"><label class="form-label" for="customs_document_number">{{ __('ui.workflow.customs_document_number') }}</label><input name="customs_document_number" id="customs_document_number" class="form-control" maxlength="100"></div>
                <div class="col-md-4 mb-3"><label class="form-label" for="customs_document_date">{{ __('ui.workflow.customs_document_date') }}</label><input type="date" name="customs_document_date" id="customs_document_date" class="form-control"></div>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-header"><h6 class="mb-0">{{ __('ui.workflow.received_items') }}</h6></div><div class="card-body">
            <div class="table-responsive"><table class="table table-bordered align-middle">
                <thead><tr><th>{{ __('ui.workflow.item_code') }} / {{ __('ui.common.item') }}</th><th>{{ __('ui.workflow.style') }}</th><th class="text-end">{{ __('ui.workflow.ordered') }}</th><th class="text-end">{{ __('ui.workflow.received_previously') }}</th><th class="text-end">{{ __('ui.common.remaining') }}</th><th class="text-end">{{ __('ui.common.unit_price') }}</th><th style="min-width:330px">{{ __('ui.workflow.receive_now') }} / {{ __('ui.workflow.lot_roll') }}</th><th class="text-end">{{ __('ui.common.total') }}</th></tr></thead>
                <tbody id="receiptItems"><tr><td colspan="8" class="text-center text-muted py-4">{{ __('ui.workflow.choose_po_load_items') }}</td></tr></tbody>
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
            let receiptLineIndex = 0;
            function receiptLine(itemId, quantity, trackStock) {
                const index = receiptLineIndex++;
                return `<div class="row g-1 align-items-center mb-1 receipt-lot-row"><input type="hidden" name="items[${index}][purchase_order_item_id]" value="${itemId}"><div class="${trackStock ? 'col-6' : 'd-none'}"><input type="text" class="form-control form-control-sm" name="items[${index}][lot_number]" maxlength="100" placeholder="{{ __('ui.workflow.lot_number') }}"></div><div class="${trackStock ? 'col-5' : 'col-10'}"><input type="number" class="form-control form-control-sm text-end receipt-quantity" name="items[${index}][quantity]" min="0" max="${quantity}" step="0.0001" value="0" placeholder="{{ __('ui.workflow.receive_now') }}"></div><div class="col-1">${trackStock ? '<button type="button" class="btn btn-sm btn-outline-danger remove-lot-row" aria-label="{{ __('ui.common.delete') }}">×</button>' : ''}</div></div>`;
            }
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
                    $('#selected_mo_number').val(order.material_order_number || '—');
                    $('#selected_style').val(order.style_code || '—');
                    if (order.currency) $('#currency').val(order.currency);
                    receiptLineIndex = 0;
                    const rows = order.items.map(item => `
                        <tr>
                            <td><span class="text-nowrap fw-semibold">${safe(item.code)}</span><div class="small text-muted text-truncate" style="max-width:340px" title="${safe(item.name)}">${safe(item.name)}</div>${item.track_stock ? '' : '<div class="small text-muted">{{ __('ui.common.non_stock_item') }}</div>'}</td>
                            <td>${safe(order.style_code || '—')}</td>
                            <td class="text-end">${item.ordered} ${safe(item.unit)}</td>
                            <td class="text-end">${item.received} ${safe(item.unit)}</td>
                            <td class="text-end">${item.remaining} ${safe(item.unit)}</td>
                            <td class="text-end">${Number(item.unit_price).toLocaleString('id-ID')}</td>
                            <td><div class="receipt-lot-entries" data-item-id="${item.id}" data-remaining="${item.remaining}">${receiptLine(item.id, item.remaining, item.track_stock)}</div>${item.track_stock ? `<button type="button" class="btn btn-link btn-sm p-0 add-receipt-lot" data-item-id="${item.id}" data-remaining="${item.remaining}">{{ __('ui.workflow.add_lot_roll') }}</button>` : ''}<div class="small text-muted mt-1">{{ __('ui.workflow.maximum_receive') }} ${item.remaining}</div></td>
                            <td class="text-end receipt-item-total" data-unit-price="${item.unit_price}">0,00</td>
                        </tr>`).join('');
                    $('#receiptItems').html(rows || '<tr><td colspan="8" class="text-center text-muted">{{ __('ui.workflow.no_remaining_receipts') }}</td></tr>');
                    $('#submitReceipt').prop('disabled', !rows);
                    poModal.hide();
                }).fail(() => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.detail_po_load_failed') }}' }));
            });

            $(document).on('click', '.add-receipt-lot', function() {
                const button = $(this);
                button.siblings('.receipt-lot-entries').append(receiptLine(button.data('item-id'), button.data('remaining'), true));
            });
            $(document).on('click', '.remove-lot-row', function() {
                if ($(this).closest('.receipt-lot-entries').find('.receipt-lot-row').length > 1) $(this).closest('.receipt-lot-row').remove();
                recalculateItemTotal($(this).closest('tr'));
            });
            function recalculateItemTotal(row) {
                const quantity = row.find('.receipt-quantity').toArray().reduce((total, input) => total + Number(input.value || 0), 0);
                const price = Number(row.find('.receipt-item-total').data('unit-price') || 0);
                row.find('.receipt-item-total').text((quantity * price).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }
            $(document).on('input', '.receipt-quantity', function() { recalculateItemTotal($(this).closest('tr')); });

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
