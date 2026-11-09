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
                                    <th>{{ __('ui.common.requester') }}</th>
                                    <th>{{ __('ui.common.department') }}</th>
                                    <th>{{ __('ui.common.needed_date') }}</th>
                                    <th>{{ __('ui.common.item_count') }}</th>
                                    <th>{{ __('ui.workflow.approval') }}</th>
                                    <th>{{ __('ui.common.purchasing_progress') }}</th>
                                    <th width="120">{{ __('ui.common.action') }}</th>
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
                    <form id="materialRequestForm">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title">{{ __('ui.workflow.material_request_title') }}</h5>
                                <small class="text-muted">{{ __('ui.workflow.request_sent_for_approval') }}</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                        </div>
                        <div class="modal-body">
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
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div>
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
            let itemIndex = 0;

            function addItemRow() {
                const index = itemIndex++;
                const row = $('<tr>');
                const select = $('<select>', { class: 'form-select form-select-sm', name: `items[${index}][product_id]`, required: true })
                    .append($('<option>', { value: '', text: '{{ __('ui.workflow.choose_item_option') }}' }));
                products.forEach(product => select.append($('<option>', { value: product.id, text: product.label })));
                row.append($('<td>').append(select));
                row.append($('<td>').append($('<input>', { type: 'number', class: 'form-control form-control-sm', name: `items[${index}][quantity]`, min: '0.0001', step: '0.0001', required: true, placeholder: '{{ __('ui.common.quantity') }}' })));
                row.append($('<td>').append($('<input>', { type: 'text', class: 'form-control form-control-sm', name: `items[${index}][note]`, maxlength: 255, placeholder: '{{ __('ui.common.optional') }}' })));
                row.append($('<td class="text-center">').append($('<button>', { type: 'button', class: 'btn btn-sm btn-outline-danger remove-request-item', html: '<i data-feather="x"></i>' })));
                $('#request-items').append(row);
                if (window.feather) feather.replace();
            }

            @can('material-requests.create')
                addItemRow();
                $('#add-request-item').on('click', addItemRow);
                $(document).on('click', '.remove-request-item', function() {
                    if ($('#request-items tr').length > 1) $(this).closest('tr').remove();
                });
                $('#materialRequestForm').on('submit', function(e) {
                    e.preventDefault();
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
                    { data: 'requester_name', name: 'requester_name', orderable: false, searchable: false },
                    { data: 'department', name: 'department', defaultContent: '—' },
                    { data: 'needed_at', name: 'needed_at', defaultContent: '—', render: data => data ? new Date(data).toLocaleDateString('id-ID') : '—' },
                    { data: 'items_count', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'status_label', name: 'status', searchable: false },
                    { data: 'purchase_status_label', name: 'purchase_status', searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
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
                $.get($(this).data('url')).done(function(request) {
                    const status = { pending: '{{ __('ui.workflow.approval_pending') }}', approved: '{{ __('ui.workflow.approval_approved') }}', rejected: '{{ __('ui.workflow.approval_rejected') }}' }[request.status];
                    const purchaseStatus = { not_processed: '{{ __('ui.workflow.purchasing_not_processed') }}', partially_processed: '{{ __('ui.workflow.purchasing_partially_processed') }}', processed: '{{ __('ui.workflow.purchasing_fully_processed') }}' }[request.purchase_status] || '—';
                    const rows = request.items.map(item => `<tr><td>${$('<div>').text(item.code).html()} — ${$('<div>').text(item.product).html()}</td><td class="text-end">${item.quantity} ${$('<div>').text(item.unit).html()}</td><td>${$('<div>').text(item.note).html()}</td></tr>`).join('');
                    $('#viewRequestTitle').text(`Material Request ${request.number}`);
                    $('#viewRequestBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-4">{{ __('ui.common.requester') }}</dt><dd class="col-sm-8">${$('<div>').text(request.requester).html()}</dd>
                        <dt class="col-sm-4">{{ __('ui.common.department') }}</dt><dd class="col-sm-8">${$('<div>').text(request.department).html()}</dd>
                        <dt class="col-sm-4">{{ __('ui.common.needed_date') }}</dt><dd class="col-sm-8">${$('<div>').text(request.needed_at).html()}</dd>
                        <dt class="col-sm-4">{{ __('ui.common.approval_status') }}</dt><dd class="col-sm-8">${status}</dd>
                        <dt class="col-sm-4">{{ __('ui.common.purchasing_progress') }}</dt><dd class="col-sm-8">${purchaseStatus}</dd>
                        <dt class="col-sm-4">{{ __('ui.common.reason') }}</dt><dd class="col-sm-8 text-break">${$('<div>').text(request.reason).html()}</dd>
                        <dt class="col-sm-4">{{ __('ui.common.reviewed_by') }}</dt><dd class="col-sm-8">${$('<div>').text(request.reviewer).html()} (${request.reviewed_at})</dd>
                        <dt class="col-sm-4">{{ __('ui.common.approval_notes') }}</dt><dd class="col-sm-8 text-break">${$('<div>').text(request.review_note).html()}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.common.quantity') }}</th><th>{{ __('ui.common.notes') }}</th></tr></thead><tbody>${rows}</tbody></table></div>`);
                    viewModal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.request_detail_load_failed') }}' }));
            });

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
