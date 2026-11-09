@extends('layouts.admin')

@section('title', __('ui.workflow.create_purchase_request'))

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">{{ __('ui.common.purchasing') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('super.purchase-requests.index') }}">{{ __('ui.common.purchase_request') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.create') }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="page-title mb-1">{{ __('ui.workflow.create_purchase_request') }}</h4>
                <span class="text-muted">{{ __('ui.workflow.select_items_from_mr') }}</span>
            </div>
            <a href="{{ route('super.purchase-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                <i data-feather="arrow-left" class="icon-sm me-1"></i>{{ __('ui.common.back') }}</a>
        </div>

        <div class="col-12 col-xxl-10">
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">{{ __('ui.workflow.purchase_information') }}</h6></div>
                <form id="purchaseRequestForm">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('ui.common.material_request') }}<span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="hidden" id="pr_material_request_id" name="material_request_id">
                                    <input type="text" class="form-control" id="pr_material_request_number" placeholder="{{ __('ui.workflow.choose_material_request') }}" readonly required>
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#materialRequestPicker">
                                        <i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.workflow.search_mr') }}</button>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pr_supplier" class="form-label">{{ __('ui.workflow.supplier_for_pr') }}<span class="text-danger">*</span></label>
                                <select class="form-select" id="pr_supplier" name="supplier_id" required>
                                    <option value="">{{ __('ui.workflow.choose_supplier') }}</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->code }} — {{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-info py-2">{{ __('ui.workflow.pr_supplier_guidance') }}</div>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle">
                                <thead><tr><th style="width:55px" class="text-center">{{ __('ui.workflow.select') }}</th><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.workflow.remaining_mr_need') }}</th><th style="min-width:140px">{{ __('ui.workflow.pr_quantity') }}</th><th style="min-width:170px">{{ __('ui.common.unit_price') }}</th><th class="text-end">{{ __('ui.common.subtotal') }}</th></tr></thead>
                                <tbody id="purchase-request-items">
                                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('ui.workflow.load_mr_items_prompt') }}</td></tr>
                                </tbody>
                                <tfoot><tr><th colspan="5" class="text-end">{{ __('ui.common.estimated_total') }}</th><th class="text-end" id="purchase-request-total">Rp 0</th></tr></tfoot>
                            </table>
                        </div>

                        <div class="mb-0">
                            <label for="pr_notes" class="form-label">{{ __('ui.common.notes') }}</label>
                            <textarea class="form-control" id="pr_notes" name="notes" rows="3" maxlength="5000" placeholder="{{ __('ui.workflow.approval_or_purchasing_notes') }}"></textarea>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('super.purchase-requests.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.cancel') }}</a>
                        <button type="submit" class="btn btn-primary btn-sm" id="submitPurchaseRequest" disabled>
                            <i data-feather="send" class="icon-sm me-1"></i>{{ __('ui.workflow.submit_for_approval') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="materialRequestPicker" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title">{{ __('ui.workflow.choose_material_request') }}</h5><small class="text-muted">{{ __('ui.workflow.approved_mr_with_remaining') }}</small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="eligible-material-requests" class="table table-bordered table-hover align-middle w-100">
                        <thead><tr><th>{{ __('ui.common.mr_number') }}</th><th>{{ __('ui.common.requester') }}</th><th>{{ __('ui.common.department') }}</th><th>{{ __('ui.workflow.needed') }}</th><th>{{ __('ui.common.row_count') }}</th><th width="90">{{ __('ui.common.action') }}</th></tr></thead>
                    </table>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div>
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
                $('#purchase-request-items').html('<tr><td colspan="6" class="text-center text-muted">{{ __('ui.workflow.loading_mr_items') }}</td></tr>');
                const sourceUrl = @json(route('super.purchase-requests.source', ['materialRequestId' => '__ID__']));
                $.get(sourceUrl.replace('__ID__', encodeURIComponent(id)))
                    .done(function(source) {
                        const body = $('#purchase-request-items').empty();
                        if (!source.items.length) {
                            body.html('<tr><td colspan="6" class="text-center text-muted">{{ __('ui.workflow.mr_fully_allocated') }}</td></tr>');
                            recalculateTotal();
                            return;
                        }
                        source.items.forEach(item => {
                            const row = $('<tr>').attr('data-item-id', item.material_request_item_id);
                            const checkbox = $('<input>', { type: 'checkbox', class: 'form-check-input pr-item-select', 'aria-label': `{{ __('ui.workflow.select') }} ${item.name}` });
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
                        Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: xhr.responseJSON?.message || '{{ __('ui.workflow.mr_unavailable') }}' });
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
                    Swal.fire({ icon: 'warning', title: '{{ __('ui.workflow.items_not_selected') }}', text: '{{ __('ui.workflow.choose_at_least_one_item') }}' });
                    return;
                }
                const button = $('#submitPurchaseRequest');
                button.prop('disabled', true).text('{{ __('ui.workflow.sending') }}');
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
                        Swal.fire({ icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message }).then(() => {
                            window.location.href = @json(route('super.purchase-requests.index'));
                        });
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || '{{ __('ui.workflow.pr_save_failed') }}');
                        Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: message });
                    },
                    complete: function() {
                        button.prop('disabled',
                            !$('#purchase-request-items .pr-item-select:checked').length ||
                            !$('#pr_material_request_id').val() ||
                            !$('#pr_supplier').val()
                        )
                            .html('<i data-feather="send" class="icon-sm me-1"></i> ' + @json(__('ui.workflow.submit_for_approval')));
                        if (window.feather) feather.replace();
                    }
                });
            });

            pickerElement.addEventListener('shown.bs.modal', () => pickerTable.columns.adjust().responsive.recalc());
        });
    </script>
@endpush
