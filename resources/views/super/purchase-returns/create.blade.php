@extends('layouts.admin')

@section('title', __('ui.modules.purchase_return_create_title'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('super.purchase-returns.index') }}">{{ __('ui.common.purchase_return') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.record_return') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">{{ __('ui.modules.purchase_return_create_title') }}</h5>
            <a href="{{ route('super.purchase-returns.index') }}" class="btn btn-outline-secondary btn-sm">
                <i data-feather="arrow-left" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.back') }}
            </a>
        </div>

        <form id="purchaseReturnForm">
            @csrf
            <input type="hidden" name="goods_receipt_id" id="goods_receipt_id">
            <div class="card-body">
                <section aria-labelledby="return-source-title">
                    <h6 id="return-source-title" class="text-primary mb-3">{{ __('ui.common.source_receipt') }}</h6>
                    <div class="row g-3 g-xl-4">
                        <div class="col-lg-6">
                            <div class="row g-2 align-items-center mb-2">
                                <label class="col-md-4 col-form-label col-form-label-sm" for="selected_receipt_number">{{ __('ui.common.number') }}</label>
                                <div class="col-md-8">
                                    <div class="input-group input-group-sm">
                                        <input id="selected_receipt_number" class="form-control" readonly placeholder="{{ __('ui.modules.choose_receipt_placeholder') }}">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#selectReceiptModal">
                                            <i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.modules.search_receipt') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2 align-items-center mb-2">
                                <label class="col-md-4 col-form-label col-form-label-sm" for="returned_at">{{ __('ui.common.returned_date') }}</label>
                                <div class="col-md-8">
                                    <input type="datetime-local" name="returned_at" id="returned_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-2 align-items-center mb-2">
                                <label class="col-md-4 col-form-label col-form-label-sm" for="selected_purchase_order">{{ __('ui.common.purchase_order') }}</label>
                                <div class="col-md-8">
                                    <input id="selected_purchase_order" class="form-control form-control-sm" readonly placeholder="{{ __('ui.modules.auto_from_receipt') }}">
                                </div>
                            </div>
                            <div class="row g-2 align-items-center mb-2">
                                <label class="col-md-4 col-form-label col-form-label-sm" for="selected_supplier">{{ __('ui.common.supplier') }}</label>
                                <div class="col-md-8">
                                    <input id="selected_supplier" class="form-control form-control-sm" readonly placeholder="{{ __('ui.modules.auto_from_receipt') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="border-top mt-3 pt-3" aria-labelledby="return-items-title">
                    <h6 id="return-items-title" class="text-primary mb-2">{{ __('ui.common.returned_items') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.common.received') }}</th><th class="text-end">{{ __('ui.common.already_returned') }}</th><th class="text-end">{{ __('ui.common.returnable_remaining') }}</th><th style="width:180px">{{ __('ui.common.return_now') }}</th></tr></thead>
                            <tbody id="returnItems"><tr><td colspan="5" class="text-center text-muted py-2">{{ __('ui.modules.choose_receipt_items') }}</td></tr></tbody>
                        </table>
                    </div>
                    <div class="row g-3 g-xl-4 mt-1">
                        <div class="col-lg-6">
                            <div class="row g-2 align-items-start">
                                <label class="col-md-4 col-form-label col-form-label-sm" for="reason">{{ __('ui.common.return_reason') }}</label>
                                <div class="col-md-8">
                                    <textarea name="reason" id="reason" class="form-control form-control-sm" rows="2" maxlength="2000" required placeholder="{{ __('ui.modules.return_reason_example') }}"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-2 align-items-start">
                                <label class="col-md-4 col-form-label col-form-label-sm" for="notes">{{ __('ui.common.notes') }}</label>
                                <div class="col-md-8">
                                    <textarea name="notes" id="notes" class="form-control form-control-sm" rows="2" maxlength="5000" placeholder="{{ __('ui.modules.supplier_notes_placeholder') }}"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                <a href="{{ route('super.purchase-returns.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.cancel') }}</a>
                <button type="submit" id="submitReturn" class="btn btn-primary btn-sm" disabled>
                    <i data-feather="save" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.modules.save_return') }}
                </button>
            </div>
        </form>
    </div>

    <div class="modal fade" id="selectReceiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ __('ui.modules.choose_receipt') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
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
            const maxQuantityLabel = @json(__('ui.modules.max_quantity'));
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
                            <td><input type="hidden" name="items[${index}][goods_receipt_item_id]" value="${item.id}"><input type="number" class="form-control form-control-sm text-end return-quantity" name="items[${index}][quantity]" min="0" max="${item.available}" step="0.0001" placeholder="${safe(maxQuantityLabel.replace(':quantity', item.available))}" title="${safe(maxQuantityLabel.replace(':quantity', item.available))}" aria-label="{{ __('ui.common.return_now') }}"></td>
                        </tr>`).join('');
                    $('#returnItems').html(rows || '<tr><td colspan="5" class="text-center text-muted">{{ __('ui.common.no_returnable_items') }}</td></tr>');
                    $('#submitReturn').prop('disabled', !rows);
                    receiptModal.hide();
                }).fail(() => Swal.fire({ icon: 'error', title: '{{ __('ui.modules.failure') }}', text: '{{ __('ui.modules.receipt_detail_failed') }}' }));
            });

            $('#purchaseReturnForm').on('submit', function(event) {
                event.preventDefault();
                if (!$('.return-quantity').toArray().some(input => Number(input.value || 0) > 0)) {
                    Swal.fire({ icon: 'warning', title: '{{ __('ui.modules.refund_quantity_required_title') }}', text: '{{ __('ui.modules.refund_quantity_required') }}' });
                    return;
                }
                const button = $('#submitReturn').prop('disabled', true);
                $.ajax({
                    url: @json(route('super.purchase-returns.store')),
                    method: 'POST',
                    data: $(this).serializeArray().map(field => ({
                        ...field,
                        value: field.name.endsWith('[quantity]') && field.value === '' ? '0' : field.value,
                    })),
                    success: response => Swal.fire({ icon: 'success', title: '{{ __('ui.modules.success') }}', text: response.message }).then(() => window.location.href = @json(route('super.purchase-returns.index'))),
                    error: xhr => {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || '{{ __('ui.modules.return_save_failed') }}');
                        Swal.fire({ icon: 'error', title: '{{ __('ui.modules.failure') }}', text: message });
                        button.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
