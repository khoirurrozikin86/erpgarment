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
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="mb-0">{{ __('ui.workflow.create_purchase_request') }}</h5>
            <a href="{{ route('super.purchase-requests.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.back') }}</a>
        </div>
        <form id="purchaseRequestForm">
            @csrf
            <div class="card-body">
                <section aria-labelledby="pr-document-title">
                    <h6 id="pr-document-title" class="text-primary mb-3">{{ __('ui.workflow.purchase_information') }}</h6>
                    <div class="row g-3 g-xl-4">
                        <div class="col-lg-6">
                            <div class="row g-2 align-items-center mb-2">
                                <label for="pr_material_request_number" class="col-md-4 col-form-label col-form-label-sm">{{ __('ui.common.material_request') }}<span class="text-danger">*</span></label>
                                <div class="col-md-8">
                                    <div class="input-group input-group-sm">
                                        <input type="hidden" id="pr_material_request_id" name="material_request_id">
                                        <input type="text" class="form-control" id="pr_material_request_number" placeholder="{{ __('ui.workflow.choose_material_request') }}" readonly required>
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#materialRequestPicker">
                                            <i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.workflow.search_mr') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-2 align-items-center mb-2">
                                <label for="pr_supplier_label" class="col-md-4 col-form-label col-form-label-sm">{{ __('ui.workflow.supplier_for_pr') }}<span class="text-danger">*</span></label>
                                <div class="col-md-8">
                                    <input type="hidden" id="pr_supplier" name="supplier_id">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="pr_supplier_label" readonly placeholder="{{ __('ui.workflow.choose_supplier') }}">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#pr-supplier-picker">
                                            <i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.search') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="border-top mt-3 pt-3" aria-labelledby="pr-items-title">
                    <h6 id="pr-items-title" class="text-primary mb-2">{{ __('ui.raw_material_order.item_details') }}</h6>
                        <div class="alert alert-info py-2">{{ __('ui.workflow.pr_supplier_guidance') }}</div>
                        <div id="pr-production-summary" class="alert alert-light border py-2 d-none"></div>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle">
                                <thead><tr><th style="width:55px" class="text-center">{{ __('ui.workflow.select') }}</th><th>{{ __('ui.common.item') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th class="text-end">{{ __('ui.workflow.remaining_mr_need') }}</th><th style="min-width:140px">{{ __('ui.workflow.pr_quantity') }}</th><th style="min-width:170px">{{ __('ui.common.unit_price') }}</th><th class="text-end">{{ __('ui.common.subtotal') }}</th></tr></thead>
                                <tbody id="purchase-request-items">
                                    <tr><td colspan="8" class="text-center text-muted py-2">{{ __('ui.workflow.load_mr_items_prompt') }}</td></tr>
                                </tbody>
                                <tfoot><tr><th colspan="7" class="text-end">{{ __('ui.common.estimated_total') }}</th><th class="text-end" id="purchase-request-total">Rp 0</th></tr></tfoot>
                            </table>
                        </div>

                    <div class="row g-2 align-items-start mt-2">
                        <label for="pr_notes" class="col-md-2 col-form-label col-form-label-sm">{{ __('ui.common.notes') }}</label>
                        <div class="col-md-10">
                            <textarea class="form-control form-control-sm" id="pr_notes" name="notes" rows="2" maxlength="5000" placeholder="{{ __('ui.workflow.approval_or_purchasing_notes') }}"></textarea>
                        </div>
                    </div>
                </section>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                <a href="{{ route('super.purchase-requests.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.cancel') }}</a>
                <button type="submit" class="btn btn-primary btn-sm" id="submitPurchaseRequest" disabled>
                    <i data-feather="send" class="icon-sm me-1"></i>{{ __('ui.workflow.submit_for_approval') }}</button>
            </div>
        </form>
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
    <div class="modal fade" id="pr-supplier-picker" tabindex="-1" aria-labelledby="pr-supplier-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pr-supplier-title">{{ __('ui.workflow.choose_supplier') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <table id="pr-supplier-table" class="table table-bordered align-middle" style="width: 100%">
                        <thead><tr><th>{{ __('ui.common.code') }}</th><th>{{ __('ui.common.name') }}</th><th>{{ __('ui.common.actions') }}</th></tr></thead>
                        <tbody>
                            @foreach($suppliers as $supplier)
                                <tr>
                                    <td>{{ $supplier->code }}</td>
                                    <td>{{ $supplier->name }}</td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm pr-select-supplier"
                                            data-id="{{ $supplier->id }}" data-label="{{ $supplier->code }} — {{ $supplier->name }}">
                                            {{ __('ui.common.select') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const supplierPicker = document.getElementById('pr-supplier-picker');
            const supplierTable = $('#pr-supplier-table');
            let supplierDataTable;

            supplierPicker.addEventListener('shown.bs.modal', () => {
                supplierDataTable ??= supplierTable.DataTable({
                    columnDefs: [{targets: 2, orderable: false, searchable: false}],
                    order: [[1, 'asc']],
                });
                supplierDataTable.columns.adjust();
            });

            supplierTable.on('click', '.pr-select-supplier', function () {
                $('#pr_supplier_label').val(this.dataset.label);
                $('#pr_supplier').val(this.dataset.id).trigger('change');
                bootstrap.Modal.getInstance(supplierPicker).hide();
            });

            const pickerElement = document.getElementById('materialRequestPicker');
            const pickerModal = bootstrap.Modal.getOrCreateInstance(pickerElement);
            const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(Number(value || 0));
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
                $('#purchase-request-items').html('<tr><td colspan="8" class="text-center text-muted">{{ __('ui.workflow.loading_mr_items') }}</td></tr>');
                const sourceUrl = @json(route('super.purchase-requests.source', ['materialRequestId' => '__ID__']));
                $.get(sourceUrl.replace('__ID__', encodeURIComponent(id)))
                    .done(function(source) {
                        const productionRows = (source.production_details || []).map(detail => {
                            const quantity = detail.quantity === null ? '—' : Number(detail.quantity).toLocaleString('id-ID', { maximumFractionDigits: 4 });
                            return `<span class="text-nowrap me-3"><strong>${$('<div>').text(detail.style || '—').html()}</strong> / ${$('<div>').text(detail.color || '—').html()} / ${$('<div>').text(detail.size || '—').html()} / ${quantity}</span>`;
                        }).join('');
                        $('#pr-production-summary').toggleClass('d-none', !productionRows).html(productionRows);
                        const body = $('#purchase-request-items').empty();
                        if (!source.items.length) {
                            body.html('<tr><td colspan="8" class="text-center text-muted">{{ __('ui.workflow.mr_fully_allocated') }}</td></tr>');
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
                            row.append($('<td>').text(item.color || '—'));
                            row.append($('<td>').text(item.size || '—'));
                            row.append($('<td class="text-end">').text(`${Number(item.available_quantity).toLocaleString('id-ID', { maximumFractionDigits: 4 })} ${item.unit}`));
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
                if (!$('#pr_supplier').val()) {
                    Swal.fire({icon: 'warning', text: @json(__('ui.workflow.choose_supplier'))});
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
