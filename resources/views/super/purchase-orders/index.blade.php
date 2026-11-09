@extends('layouts.admin')

@section('title', __('ui.common.purchase_order'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">{{ __('ui.common.purchasing') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.purchase_order') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h4 class="page-title mb-1">{{ __('ui.common.purchase_order') }}</h4><span class="text-muted">{{ __('ui.workflow.po_list_subtitle') }}</span></div>
            @can('purchase-orders.create')
                <a href="{{ route('super.purchase-orders.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.common.create_purchase_order') }}</a>
            @endcan
        </div>

        <div class="col-12">
            @include('super.purchasing.date-filter')
            <div class="card"><div class="card-body"><div class="table-responsive">
            <table id="purchase-orders-table" class="table table-bordered table-hover align-middle w-100">
                <thead><tr>
                    <th width="50">{{ __('ui.common.no') }}</th><th>{{ __('ui.common.po_number') }}</th><th>{{ __('ui.common.pr_number') }}</th><th>{{ __('ui.common.supplier') }}</th>
                    <th>{{ __('ui.common.po_date') }}</th><th>{{ __('ui.common.expected_arrival') }}</th><th>{{ __('ui.common.total') }}</th><th>{{ __('ui.common.status') }}</th><th width="170">{{ __('ui.common.action') }}</th>
                </tr></thead>
            </table>
            </div></div></div>
        </div>
    </div>

    <div class="modal fade" id="viewPurchaseOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewPurchaseOrderTitle">{{ __('ui.workflow.po_detail') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body" id="viewPurchaseOrderBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="purchaseOrderEmailHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="purchaseOrderEmailHistoryTitle">{{ __('ui.workflow.po_email_history') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body">
                <div class="table-responsive"><table class="table table-sm table-bordered align-middle">
                    <thead><tr><th>{{ __('ui.common.sent_date') }}</th><th>{{ __('ui.common.recipient') }}</th><th>{{ __('ui.common.subject') }}</th><th>{{ __('ui.common.sent_by') }}</th></tr></thead>
                    <tbody id="purchaseOrderEmailHistoryRows"><tr><td colspan="4" class="text-center text-muted">{{ __('ui.common.loading_history') }}</td></tr></tbody>
                </table></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const modal = new bootstrap.Modal(document.getElementById('viewPurchaseOrderModal'));
            const emailHistoryModal = new bootstrap.Modal(document.getElementById('purchaseOrderEmailHistoryModal'));
            const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
            const safe = value => $('<div>').text(value ?? '').html();
            const table = $('#purchase-orders-table').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: {
                    url: @json(route('super.purchase-orders.dt')),
                    data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'number', name: 'number' },
                    { data: 'purchase_request_number', name: 'purchase_request.number', orderable: false, searchable: false },
                    { data: 'supplier_name', name: 'supplier.name', orderable: false, searchable: false },
                    { data: 'order_date', name: 'order_date', render: data => data ? new Date(data).toLocaleDateString('id-ID') : '—' },
                    { data: 'expected_delivery_at', name: 'expected_delivery_at', defaultContent: '—', render: data => data ? new Date(data).toLocaleDateString('id-ID') : '—' },
                    { data: 'total_amount', name: 'total_amount', searchable: false, className: 'text-end', render: data => money(data) },
                    { data: 'status_label', name: 'status', searchable: false },
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

            $(document).on('click', '.btn-view-purchase-order', function() {
                $.get($(this).data('url')).done(function(order) {
                    const status = { draft: '{{ __('ui.workflow.draft') }}', issued: '{{ __('ui.workflow.issued') }}', partially_received: '{{ __('ui.workflow.receive_partial') }}', received: '{{ __('ui.workflow.received_complete') }}', cancelled: '{{ __('ui.common.cancelled') }}' }[order.status];
                    const rows = order.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.product)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
                    $('#viewPurchaseOrderTitle').text(`Purchase Order ${order.number}`);
                    $('#viewPurchaseOrderBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-3">{{ __('ui.common.purchase_request') }}</dt><dd class="col-sm-9">${safe(order.purchase_request_number)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.supplier') }}</dt><dd class="col-sm-9">${safe(order.supplier)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.created_by') }}</dt><dd class="col-sm-9">${safe(order.creator)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.po_date') }}</dt><dd class="col-sm-9">${safe(order.order_date)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.expected_arrival') }}</dt><dd class="col-sm-9">${safe(order.expected_delivery_at)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.status') }}</dt><dd class="col-sm-9">${status}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.issued') }}</dt><dd class="col-sm-9">${safe(order.issued_at)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.payment_terms') }}</dt><dd class="col-sm-9">${safe(order.payment_terms)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.notes') }}</dt><dd class="col-sm-9 text-break">${safe(order.notes)}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.common.quantity') }}</th><th class="text-end">{{ __('ui.common.unit_price') }}</th><th class="text-end">{{ __('ui.common.subtotal') }}</th></tr></thead><tbody>${rows}</tbody><tfoot><tr><th colspan="3" class="text-end">{{ __('ui.workflow.po_total') }}</th><th class="text-end">${money(order.total_amount)}</th></tr></tfoot></table></div>`);
                    modal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.po_detail_load_failed') }}' }));
            });

            $(document).on('click', '.btn-email-history', function() {
                const button = $(this);
                $('#purchaseOrderEmailHistoryRows').html('<tr><td colspan="4" class="text-center text-muted">{{ __('ui.common.loading_history') }}</td></tr>');
                emailHistoryModal.show();
                $.get(button.data('url')).done(function(response) {
                    $('#purchaseOrderEmailHistoryTitle').text(`Riwayat Email ${response.number}`);
                    if (!response.emails.length) {
                        $('#purchaseOrderEmailHistoryRows').html('<tr><td colspan="4" class="text-center text-muted">{{ __('ui.common.empty_history') }}</td></tr>');
                        return;
                    }
                    const rows = response.emails.map(item => `<tr><td>${safe(item.sent_at)}</td><td>${safe(item.recipient)}</td><td>${safe(item.subject)}</td><td>${safe(item.sender)}</td></tr>`).join('');
                    $('#purchaseOrderEmailHistoryRows').html(rows);
                }).fail(() => {
                    $('#purchaseOrderEmailHistoryRows').html('<tr><td colspan="4" class="text-center text-danger">{{ __('ui.common.history_failed') }}</td></tr>');
                });
            });

            $(document).on('click', '.btn-issue-purchase-order', function() {
                const button = $(this);
                Swal.fire({
                    icon: 'question', title: '{{ __('ui.workflow.issue_po_prompt') }}',
                    text: '{{ __('ui.workflow.po_issued_confirmation') }}',
                    showCancelButton: true, confirmButtonText: '{{ __('ui.workflow.issue_po') }}', cancelButtonText: '{{ __('ui.common.cancel') }}'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: button.data('url'), method: 'POST',
                        data: { _token: @json(csrf_token()), _method: 'PUT' },
                        success: response => { Swal.fire({ icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message }); table.ajax.reload(null, false); },
                        error: xhr => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: xhr.responseJSON?.message || '{{ __('ui.workflow.po_issue_failed') }}' })
                    });
                });
            });

            $(document).on('click', '.btn-email-purchase-order', function() {
                const button = $(this);
                Swal.fire({
                    icon: 'question',
                    title: '{{ __('ui.workflow.send_po_prompt') }}',
                    text: '{{ __('ui.workflow.email_pdf_info') }}',
                    input: 'email',
                    inputLabel: '{{ __('ui.workflow.supplier_email') }}',
                    inputValue: button.data('email') || '',
                    inputPlaceholder: 'supplier@example.com',
                    inputValidator: value => !value ? '{{ __('ui.workflow.supplier_email_required') }}' : undefined,
                    showCancelButton: true,
                    confirmButtonText: '{{ __('ui.workflow.send_email') }}',
                    cancelButtonText: '{{ __('ui.common.cancel') }}',
                    showLoaderOnConfirm: true,
                    preConfirm: email => $.ajax({
                        url: button.data('url'),
                        method: 'POST',
                        data: { _token: @json(csrf_token()), email }
                    }).catch(xhr => {
                        Swal.showValidationMessage(xhr.responseJSON?.message || '{{ __('ui.workflow.email_send_failed') }}');
                    })
                }).then(result => {
                    if (result.isConfirmed && result.value?.message) {
                        Swal.fire({ icon: 'success', title: '{{ __('ui.workflow.success') }}', text: result.value.message });
                    }
                });
            });
        });
    </script>
@endpush
