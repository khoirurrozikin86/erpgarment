@extends('layouts.admin')

@section('title', __('ui.common.purchase_request'))

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">{{ __('ui.common.purchasing') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.purchase_request') }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="page-title mb-1">{{ __('ui.common.purchase_request') }}</h4>
                <span class="text-muted">{{ __('ui.workflow.pr_subtitle') }}</span>
            </div>
            @can('purchase-requests.create')
                <a href="{{ route('super.purchase-requests.create') }}" class="btn btn-primary btn-sm">
                    <i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.workflow.create_purchase_request') }}</a>
            @endcan
        </div>

        <div class="col-12">
            @include('super.purchasing.date-filter')
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="purchase-requests-table" class="table table-bordered table-hover align-middle w-100">
                            <thead><tr>
                                <th width="50">{{ __('ui.common.no') }}</th><th>{{ __('ui.common.pr_number') }}</th><th>{{ __('ui.common.request_date') }}</th><th>{{ __('ui.common.mr_number') }}</th><th>{{ __('ui.common.supplier') }}</th>
                                <th>{{ __('ui.common.requester') }}</th><th>{{ __('ui.common.estimated_total') }}</th><th>{{ __('ui.common.status') }}</th><th width="120">{{ __('ui.common.action') }}</th>
                            </tr></thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewPurchaseRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPurchaseRequestTitle">{{ __('ui.workflow.pr_detail') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
            </div>
            <div class="modal-body" id="viewPurchaseRequestBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const viewModal = new bootstrap.Modal(document.getElementById('viewPurchaseRequestModal'));
            const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
            const safe = value => $('<div>').text(value ?? '').html();
            const table = $('#purchase-requests-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: @json(route('super.purchase-requests.dt')),
                    data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'number', name: 'number' },
                    { data: 'created_at', name: 'created_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
                    { data: 'material_request_number', name: 'material_request.number', orderable: false, searchable: false },
                    { data: 'supplier_name', name: 'supplier.name', orderable: false, searchable: false },
                    { data: 'requester_name', name: 'requester_name', orderable: false, searchable: false },
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

            $(document).on('click', '.btn-view-purchase-request', function() {
                $.get($(this).data('url')).done(function(request) {
                    const status = { pending: '{{ __('ui.workflow.approval_pending') }}', approved: '{{ __('ui.workflow.approval_approved') }}', rejected: '{{ __('ui.workflow.approval_rejected') }}' }[request.status];
                    const rows = request.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.product)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
                    $('#viewPurchaseRequestTitle').text(`Purchase Request ${request.number}`);
                    $('#viewPurchaseRequestBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-3">{{ __('ui.common.material_request') }}</dt><dd class="col-sm-9">${safe(request.material_request_number)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.supplier') }}</dt><dd class="col-sm-9">${safe(request.supplier)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.requester') }}</dt><dd class="col-sm-9">${safe(request.requester)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.status') }}</dt><dd class="col-sm-9">${status}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.notes') }}</dt><dd class="col-sm-9 text-break">${safe(request.notes)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.reviewed_by') }}</dt><dd class="col-sm-9">${safe(request.reviewer)} (${safe(request.reviewed_at)})</dd>
                        <dt class="col-sm-3">{{ __('ui.common.approval_notes') }}</dt><dd class="col-sm-9 text-break">${safe(request.review_note)}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.common.quantity') }}</th><th class="text-end">{{ __('ui.common.unit_price') }}</th><th class="text-end">{{ __('ui.common.subtotal') }}</th></tr></thead><tbody>${rows}</tbody><tfoot><tr><th colspan="3" class="text-end">{{ __('ui.common.estimated_total') }}</th><th class="text-end">${money(request.total_amount)}</th></tr></tfoot></table></div>`);
                    viewModal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.pr_detail_load_failed') }}' }));
            });

            $(document).on('click', '.btn-review-purchase-request', function() {
                const button = $(this);
                const rejecting = button.data('status') === 'rejected';
                Swal.fire({
                    icon: rejecting ? 'warning' : 'question',
                    title: rejecting ? '{{ __('ui.workflow.reject_pr_prompt') }}' : '{{ __('ui.workflow.approve_pr_prompt') }}',
                    input: 'textarea',
                    inputLabel: rejecting ? '{{ __('ui.workflow.rejection_reason_required') }}' : '{{ __('ui.workflow.approval_note') }}',
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
                        error: xhr => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: xhr.responseJSON?.message || '{{ __('ui.workflow.pr_status_update_failed') }}' })
                    });
                });
            });
        });
    </script>
@endpush
