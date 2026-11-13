@extends('layouts.admin')

@section('title', __('ui.common.material_request'))

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">{{ __('ui.common.purchasing') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.material_request') }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    <style>
        #createRequestModal .modal-content { max-height: 100%; overflow: hidden; }
        #createRequestModal .material-request-form { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; overflow: hidden; }
        #createRequestModal .material-request-form .modal-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; }
        #createRequestModal .material-request-form .modal-header,
        #createRequestModal .material-request-form .modal-footer { flex-shrink: 0; }
    </style>
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="page-title mb-1">{{ __('ui.common.material_request') }}</h4>
                <span class="text-muted">{{ __('ui.workflow.mr_subtitle') }}</span>
            </div>
            @can('material-requests.create')
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                    <i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.workflow.create_request') }}</button>
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
                                    <th width="50">{{ __('ui.common.no') }}</th>
                                    <th>{{ __('ui.common.number') }}</th>
                                    <th>{{ __('ui.common.request_date') }}</th>
                                    <th>{{ __('ui.workflow.buyer_po_no') }}</th>
                                    <th>{{ __('ui.workflow.bom_product') }}</th>
                                    <th class="text-end">{{ __('ui.workflow.production_quantity') }}</th>
                                    <th>{{ __('ui.common.requester') }}</th>
                                    <th>{{ __('ui.common.department') }}</th>
                                    <th>{{ __('ui.common.needed_date') }}</th>
                                    <th>{{ __('ui.common.item_count') }}</th>
                                    <th>{{ __('ui.workflow.approval') }}</th>
                                    <th>{{ __('ui.common.purchasing_progress') }}</th>
                                    <th width="130" class="text-end">{{ __('ui.common.action') }}</th>
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
                    <form id="materialRequestForm" class="material-request-form">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title">{{ __('ui.workflow.material_request_title') }}</h5>
                                <small class="text-muted">{{ __('ui.workflow.request_sent_for_approval') }}</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="bill_of_material_id" id="request_bom_id">
                            <input type="hidden" name="production_order_id" id="request_production_order_id">
                            <input type="hidden" name="output_product_id" id="request_output_product_id">
                            <input type="hidden" name="bom_version" id="request_bom_version">
                            <input type="hidden" name="production_quantity" id="request_production_quantity">
                            <div class="border rounded p-3 mb-3 bg-light">
                                <div class="row align-items-end mb-3">
                                    <div class="col-md-8">
                                        <label for="source_production_order" class="form-label">{{ __('ui.workflow.source_production_order') }}</label>
                                        <select id="source_production_order" class="form-select">
                                            <option value="">{{ __('ui.workflow.choose_production_order') }}</option>
                                            @foreach ($productionOrders as $productionOrder)
                                                <option value="{{ $productionOrder['id'] }}">{{ $productionOrder['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4"><button type="button" class="btn btn-outline-success btn-sm" id="load-production-order-materials">{{ __('ui.workflow.load_order_materials') }}</button></div>
                                </div>
                                <h6 class="fw-bold">{{ __('ui.workflow.generate_from_bom') }}</h6>
                                <div class="row align-items-end">
                                    <div class="col-md-5 mb-2">
                                        <label for="bom_output_product" class="form-label">{{ __('ui.workflow.bom_product') }}</label>
                                        <select class="form-select" id="bom_output_product">
                                            <option value="">{{ __('ui.workflow.bom_choose_product') }}</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product['id'] }}">{{ $product['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label for="production_quantity_input" class="form-label">{{ __('ui.workflow.production_quantity') }}</label>
                                        <input type="number" class="form-control" id="production_quantity_input" min="0.0001" step="0.0001">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="generate-bom-requirements">{{ __('ui.workflow.calculate_bom_requirements') }}</button>
                                    </div>
                                </div>
                                <small id="bom-generation-summary" class="text-muted"></small>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="request_department" class="form-label">{{ __('ui.common.department') }}</label>
                                    <input type="text" class="form-control" id="request_department" name="department" maxlength="120" placeholder="{{ __('ui.workflow.department_example') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="request_needed_at" class="form-label">{{ __('ui.common.needed_date') }}</label>
                                    <input type="date" class="form-control" id="request_needed_at" name="needed_at">
                                </div>
                            </div>
                            <label class="form-label">{{ __('ui.workflow.items_needed') }}<span class="text-danger">*</span></label>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead><tr><th style="min-width:260px">{{ __('ui.common.item') }}</th><th style="width:150px">{{ __('ui.common.quantity') }}</th><th style="min-width:170px">{{ __('ui.common.notes') }}</th><th style="width:45px"></th></tr></thead>
                                    <tbody id="request-items"></tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-request-item">
                                <i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.common.add_item') }}</button>
                            <div class="mb-0">
                                <label for="request_reason" class="form-label">{{ __('ui.common.request_reason') }}<span class="text-danger">*</span></label>
                                <textarea class="form-control" id="request_reason" name="reason" rows="3" maxlength="5000" required placeholder="{{ __('ui.common.explain_need') }}"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="submitMaterialRequest"><i data-feather="send" class="icon-sm me-1"></i>{{ __('ui.workflow.submit_request') }}</button>
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
                    <h5 class="modal-title" id="viewRequestTitle">{{ __('ui.workflow.request_detail') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                </div>
                <div class="modal-body" id="viewRequestBody"></div>
                <div class="modal-footer">
                    @can('material-requests.issue')
                        <button type="button" class="btn btn-primary btn-sm d-none" id="issueMaterialsButton">{{ __('ui.workflow.issue_materials') }}</button>
                    @endcan
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button>
                </div>
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
            let currentIssueUrl = null;
            let currentRequest = null;
            let bomGenerated = false;
            let itemIndex = 0;

            function parseQuantity(value) {
                let normalized = String(value ?? '').trim().replace(/\s/g, '');
                if (!normalized) return NaN;
                const comma = normalized.lastIndexOf(',');
                const dot = normalized.lastIndexOf('.');
                if (comma >= 0 && dot >= 0) {
                    normalized = comma > dot
                        ? normalized.replace(/\./g, '').replace(',', '.')
                        : normalized.replace(/,/g, '');
                } else if (comma >= 0) {
                    normalized = normalized.replace(',', '.');
                }
                return Number(normalized);
            }

            function addItemRow(item = {}) {
                const index = itemIndex++;
                const row = $('<tr>');
                const select = $('<select>', { class: 'form-select form-select-sm', name: `items[${index}][product_id]`, required: true })
                    .append($('<option>', { value: '', text: '{{ __('ui.workflow.choose_item_option') }}' }));
                products.forEach(product => select.append($('<option>', { value: product.id, text: product.label })));
                if (item.product_id) select.val(String(item.product_id));
                row.append($('<td>').append(select));
                row.append($('<td>').append($('<input>', { type: 'text', inputmode: 'decimal', autocomplete: 'off', class: 'form-control form-control-sm', name: `items[${index}][quantity]`, required: true, placeholder: '{{ __('ui.common.quantity') }}', value: item.quantity ?? '' })));
                row.append($('<td>').append($('<input>', { type: 'text', class: 'form-control form-control-sm', name: `items[${index}][note]`, maxlength: 255, placeholder: '{{ __('ui.common.optional') }}', value: item.note ?? '' })));
                row.append($('<td class="text-center">').append($('<button>', { type: 'button', class: 'btn btn-sm btn-outline-danger remove-request-item', html: '<i data-feather="x"></i>' })));
                $('#request-items').append(row);
                if (window.feather) feather.replace();
            }

            @can('material-requests.create')
                addItemRow();
                function clearBomSnapshot() {
                    $('#request_bom_id, #request_production_order_id, #request_output_product_id, #request_bom_version, #request_production_quantity').val('');
                    $('#bom-generation-summary').text('');
                    if (bomGenerated) { $('#request-items').empty(); itemIndex = 0; addItemRow(); bomGenerated = false; }
                }
                $('#bom_output_product, #production_quantity_input, #request_needed_at, #source_production_order').on('change input', clearBomSnapshot);
                $('#generate-bom-requirements').on('click', function() {
                    const button = $(this).prop('disabled', true);
                    $.get(@json(route('super.material-requests.bom-requirements')), {
                        product_id: $('#bom_output_product').val(),
                        production_quantity: $('#production_quantity_input').val(),
                        needed_at: $('#request_needed_at').val()
                    }).done(function(result) {
                        $('#request-items').empty(); itemIndex = 0;
                        result.items.forEach(item => addItemRow(item));
                        $('#request_bom_id').val(result.bill_of_material_id);
                        $('#request_production_order_id').val('');
                        $('#request_output_product_id').val(result.output_product_id);
                        $('#request_bom_version').val(result.bom_version);
                        $('#request_production_quantity').val(result.production_quantity);
                        $('#bom-generation-summary').text(`${@json(__('ui.workflow.bom_snapshot_summary'))}: ${result.output_product} × ${Number(result.production_quantity).toLocaleString()} — BOM v${result.bom_version}`);
                        bomGenerated = true;
                    }).fail(xhr => Swal.fire({icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {}).flat().join('\n') || '{{ __('ui.workflow.active_bom_not_found') }}'}))
                    .always(() => button.prop('disabled', false));
                });
                $('#load-production-order-materials').on('click', function() {
                    const orderId = $('#source_production_order').val();
                    if (!orderId) { Swal.fire({icon: 'warning', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.choose_production_order') }}'}); return; }
                    const button = $(this).prop('disabled', true);
                    $.get(@json(route('super.material-requests.index')) + `/material-orders/${orderId}/requirements`)
                        .done(result => {
                            $('#request-items').empty(); itemIndex = 0;
                            result.items.forEach(item => addItemRow(item));
                            $('#request_production_order_id').val(result.production_order_id);
                            $('#request_bom_id').val(result.bill_of_material_id);
                            $('#request_output_product_id').val(result.output_product_id);
                            $('#request_bom_version').val(result.bom_version);
                            $('#request_production_quantity').val(result.production_quantity);
                            $('#bom_output_product').val(result.output_product_id);
                            $('#production_quantity_input').val(result.production_quantity);
                            $('#bom-generation-summary').text(`${@json(__('ui.workflow.material_order'))} ${result.production_order_number} — ${result.output_product} — BOM v${result.bom_version}`);
                            bomGenerated = true;
                        })
                        .fail(xhr => Swal.fire({icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: xhr.responseJSON?.message || '{{ __('ui.workflow.production_order_materials_failed') }}'}))
                        .always(() => button.prop('disabled', false));
                });
                $('#add-request-item').on('click', addItemRow);
                $(document).on('click', '.remove-request-item', function() {
                    if ($('#request-items tr').length > 1) $(this).closest('tr').remove();
                });
                $('#materialRequestForm').on('submit', function(e) {
                    e.preventDefault();
                    let quantitiesValid = true;
                    $('#request-items input[name$="[quantity]"]').each(function() {
                        const quantity = parseQuantity(this.value);
                        if (!Number.isFinite(quantity) || quantity <= 0) {
                            quantitiesValid = false;
                            return false;
                        }
                        this.value = quantity.toFixed(4);
                    });
                    if (!quantitiesValid) {
                        Swal.fire({ icon: 'warning', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.quantity_input_invalid') }}' });
                        return;
                    }
                    const button = $('#submitMaterialRequest');
                    button.prop('disabled', true).text('{{ __('ui.workflow.sending') }}');
                    $.ajax({
                        url: @json(route('super.material-requests.store')),
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(response) {
                            bootstrap.Modal.getOrCreateInstance(createModal).hide();
                            Swal.fire({ icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message });
                            $('#material-requests-table').DataTable().ajax.reload(null, false);
                            $('#materialRequestForm')[0].reset();
                            $('#request_bom_id, #request_production_order_id, #request_output_product_id, #request_bom_version, #request_production_quantity').val('');
                            $('#bom-generation-summary').text(''); bomGenerated = false;
                            $('#bom_output_product, #production_quantity_input, #source_production_order').val('');
                            $('#request-items').empty(); itemIndex = 0; addItemRow();
                        },
                        error: function(xhr) {
                            const errors = xhr.responseJSON?.errors;
                            const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || '{{ __('ui.workflow.request_save_failed') }}');
                            Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: message });
                        },
                        complete: function() {
                            button.prop('disabled', false).html('<i data-feather="send" class="icon-sm me-1"></i> ' + @json(__('ui.workflow.submit_request')));
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
                    { data: 'buyer_po_number', orderable: false, searchable: false, defaultContent: '—' },
                    { data: 'product_label', orderable: false, searchable: false, defaultContent: '—' },
                    { data: 'production_quantity_display', orderable: false, searchable: false, className: 'text-end', defaultContent: '—' },
                    { data: 'requester_name', name: 'requester_name', orderable: false, searchable: false },
                    { data: 'department', name: 'department', defaultContent: '—' },
                    { data: 'needed_at', name: 'needed_at', defaultContent: '—', render: data => data ? new Date(data).toLocaleDateString('id-ID') : '—' },
                    { data: 'items_count', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'status_label', name: 'status', searchable: false },
                    { data: 'purchase_status_label', name: 'purchase_status', searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-end' }
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
                const openIssueDialog = $(this).hasClass('btn-issue-request');
                $.get($(this).data('url')).done(function(request) {
                    currentRequest = request;
                    currentIssueUrl = request.issue_url;
                    const status = { pending: '{{ __('ui.workflow.approval_pending') }}', approved: '{{ __('ui.workflow.approval_approved') }}', rejected: '{{ __('ui.workflow.approval_rejected') }}' }[request.status];
                    const purchaseStatus = { not_processed: '{{ __('ui.workflow.purchasing_not_processed') }}', partially_processed: '{{ __('ui.workflow.purchasing_partially_processed') }}', processed: '{{ __('ui.workflow.purchasing_fully_processed') }}' }[request.purchase_status] || '—';
                    const bomSummary = request.bom_version ? `<dt class="col-sm-4">{{ __('ui.workflow.bom_used') }}</dt><dd class="col-sm-8">${$('<div>').text(request.output_product).html()} × ${Number(request.production_quantity).toLocaleString()} — BOM v${$('<div>').text(request.bom_version).html()}${request.production_order_number ? ` — ${$('<div>').text(request.production_order_number).html()}` : ''}</dd>` : '';
                    const rows = request.items.map(item => `<tr><td>${$('<div>').text(item.code).html()} — ${$('<div>').text(item.product).html()}</td><td class="text-end">${item.quantity} ${$('<div>').text(item.unit).html()}</td><td class="text-end">${Number(item.issued_quantity).toLocaleString()}</td><td>${$('<div>').text(item.note).html()}</td></tr>`).join('');
                    $('#viewRequestTitle').text(`Material Request ${request.number}`);
                    $('#viewRequestBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-4">{{ __('ui.common.requester') }}</dt><dd class="col-sm-8">${$('<div>').text(request.requester).html()}</dd>
                        <dt class="col-sm-4">{{ __('ui.common.department') }}</dt><dd class="col-sm-8">${$('<div>').text(request.department).html()}</dd>
                        <dt class="col-sm-4">{{ __('ui.common.needed_date') }}</dt><dd class="col-sm-8">${$('<div>').text(request.needed_at).html()}</dd>${bomSummary}
                        <dt class="col-sm-4">{{ __('ui.common.approval_status') }}</dt><dd class="col-sm-8">${status}</dd>
                        <dt class="col-sm-4">{{ __('ui.common.purchasing_progress') }}</dt><dd class="col-sm-8">${purchaseStatus}</dd>
                        <dt class="col-sm-4">{{ __('ui.common.reason') }}</dt><dd class="col-sm-8 text-break">${$('<div>').text(request.reason).html()}</dd>
                        <dt class="col-sm-4">{{ __('ui.common.reviewed_by') }}</dt><dd class="col-sm-8">${$('<div>').text(request.reviewer).html()} (${request.reviewed_at})</dd>
                        <dt class="col-sm-4">{{ __('ui.common.approval_notes') }}</dt><dd class="col-sm-8 text-break">${$('<div>').text(request.review_note).html()}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.common.quantity') }}</th><th class="text-end">{{ __('ui.workflow.issued_quantity') }}</th><th>{{ __('ui.common.notes') }}</th></tr></thead><tbody>${rows}</tbody></table></div>`);
                    @can('material-requests.issue')
                        const canIssue = request.status === 'approved' && request.items.some(item => item.remaining_quantity > 0);
                        $('#issueMaterialsButton').toggleClass('d-none', !canIssue);
                    @endcan
                    if (openIssueDialog && $('#issueMaterialsButton').is(':visible')) {
                        $('#issueMaterialsButton').trigger('click');
                    } else {
                        viewModal.show();
                    }
                }).fail(() => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.request_detail_load_failed') }}' }));
            });

            @can('material-requests.issue')
                $('#issueMaterialsButton').on('click', function() {
                    const remaining = currentRequest.items.filter(item => item.remaining_quantity > 0);
                    const inputs = remaining.map((item, index) => `
                        <div class="row align-items-center mb-2">
                            <div class="col-7">${$('<div>').text(item.code + ' — ' + item.product).html()}<small class="d-block text-muted">{{ __('ui.workflow.remaining_quantity') }}: ${Number(item.remaining_quantity).toLocaleString()}</small></div>
                            <div class="col-5"><input class="form-control form-control-sm material-issue-quantity" data-item-id="${item.id}" data-max="${item.remaining_quantity}" type="text" inputmode="decimal" autocomplete="off" placeholder="0"></div>
                        </div>`).join('');
                    const openIssueDialog = () => Swal.fire({
                        title: '{{ __('ui.workflow.issue_materials') }}',
                        html: `<div class="text-start">${inputs}</div>`,
                        showCancelButton: true,
                        confirmButtonText: '{{ __('ui.workflow.confirm_issue') }}',
                        cancelButtonText: '{{ __('ui.common.cancel') }}',
                        preConfirm: () => {
                            const items = [];
                            $('.material-issue-quantity').each(function() {
                                const quantity = this.value.trim() === '' ? 0 : parseQuantity(this.value);
                                const maximum = Number($(this).data('max'));
                                if (!Number.isFinite(quantity) || quantity < 0 || quantity > maximum) {
                                    Swal.showValidationMessage('{{ __('ui.workflow.quantity_input_invalid') }}');
                                    return false;
                                }
                                if (quantity > 0) items.push({material_request_item_id: Number($(this).data('item-id')), quantity});
                            });
                            if (!items.length) { Swal.showValidationMessage('{{ __('ui.workflow.enter_issue_quantity') }}'); return false; }
                            return items;
                        }
                    }).then(result => {
                        if (!result.isConfirmed) {
                            viewModal.show();
                            return;
                        }
                        $.ajax({url: currentIssueUrl, method: 'PUT', data: {_token: @json(csrf_token()), items: result.value}})
                            .done(response => {
                                Swal.fire({icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message});
                                $('#material-requests-table').DataTable().ajax.reload(null, false);
                                viewModal.hide();
                            })
                            .fail(xhr => Swal.fire({icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: Object.values(xhr.responseJSON?.errors || {}).flat().join('\n') || xhr.responseJSON?.message || '{{ __('ui.workflow.material_issue_failed') }}'}));
                    });
                    const requestModal = document.getElementById('viewRequestModal');
                    if (requestModal.classList.contains('show')) {
                        $('#viewRequestModal').one('hidden.bs.modal', openIssueDialog);
                        viewModal.hide();
                    } else {
                        openIssueDialog();
                    }
                });
            @endcan

            $(document).on('click', '.btn-review-request', function() {
                const button = $(this);
                const rejecting = button.data('status') === 'rejected';
                Swal.fire({
                    icon: rejecting ? 'warning' : 'question',
                    title: rejecting ? '{{ __('ui.workflow.reject_request_prompt') }}' : '{{ __('ui.workflow.approve_request_prompt') }}',
                    input: 'textarea',
                    inputLabel: rejecting ? '{{ __('ui.workflow.rejection_reason_required') }}' : '{{ __('ui.workflow.optional_note') }}',
                    inputPlaceholder: '{{ __('ui.workflow.note_for_requester') }}',
                    showCancelButton: true,
                    confirmButtonText: rejecting ? '{{ __('ui.workflow.reject') }}' : '{{ __('ui.workflow.approve') }}',
                    cancelButtonText: '{{ __('ui.common.cancel') }}',
                    inputValidator: value => rejecting && !value?.trim() ? '{{ __('ui.workflow.rejection_reason_missing') }}' : undefined
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: button.data('url'), method: 'POST',
                        data: { _token: @json(csrf_token()), _method: 'PUT', note: result.value || '' },
                        success: response => { Swal.fire({ icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message }); table.ajax.reload(null, false); },
                        error: xhr => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: xhr.responseJSON?.message || '{{ __('ui.workflow.request_status_update_failed') }}' })
                    });
                });
            });
        });
    </script>
@endpush
