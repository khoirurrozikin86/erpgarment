@extends('layouts.admin')

@section('title', __( 'ui.common.receipt' ))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">{{ __('ui.common.purchasing') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.goods_receipt') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h4 class="page-title mb-1">{{ __('ui.common.goods_receipt') }}</h4><span class="text-muted">{{ __('ui.workflow.receipt_list_subtitle') }}</span></div>
            <div class="d-flex gap-2">
                @can('goods-receipts.view')<a href="{{ route('super.goods-receipts.export') }}" id="exportGoodsReceipts" class="btn btn-outline-success btn-sm"><i data-feather="file-text" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>@endcan
                @can('goods-receipts.create')<a href="{{ route('super.goods-receipts.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.common.record_receipt') }}</a>@endcan
            </div>
        </div>
        <div class="col-12">
            @include('super.purchasing.date-filter')
            <div class="card"><div class="card-body"><div class="table-responsive">
            <table id="goods-receipts-table" class="table table-bordered table-hover align-middle w-100">
                <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.common.number') }}</th><th>{{ __('ui.common.received_date') }}</th><th>{{ __('ui.common.supplier') }}</th><th>{{ __('ui.common.invoice_number') }}</th><th>{{ __('ui.common.invoice_date') }}</th><th>{{ __('ui.common.material_request') }}</th><th>{{ __('ui.common.po_number') }}</th><th>{{ __('ui.common.status') }}</th><th>{{ __('ui.common.notes') }}</th></tr></thead>
            </table>
            </div></div></div>
        </div>
    </div>

    <div class="modal fade" id="viewGoodsReceiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewGoodsReceiptTitle">{{ __('ui.workflow.receipt_detail') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body" id="viewGoodsReceiptBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const modal = new bootstrap.Modal(document.getElementById('viewGoodsReceiptModal'));
            const safe = value => $('<div>').text(value ?? '').html();
            const table = $('#goods-receipts-table').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: {
                    url: @json(route('super.goods-receipts.dt')),
                    data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-end text-nowrap' },
                    { data: 'number', name: 'number' },
                    { data: 'received_at', name: 'received_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
                    { data: 'supplier_name', orderable: false, searchable: false },
                    { data: 'invoice_number', name: 'invoice_number', defaultContent: '—' },
                    { data: 'invoice_date', name: 'invoice_date', render: data => data ? new Date(data).toLocaleDateString('id-ID') : '—' },
                    { data: 'material_request_number', orderable: false, searchable: false },
                    { data: 'purchase_order_number', orderable: false },
                    { data: 'approval_status', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'notes', name: 'notes', defaultContent: '—' }
                ],
                order: [[3, 'desc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            $('#applyDateFilter').on('click', () => table.ajax.reload());
            $('#todayDateFilter').on('click', () => {
                const today = @json(now()->toDateString());
                $('#from_date, #to_date').val(today);
                table.ajax.reload();
            });
            const updateExportUrl = () => {
                const url = new URL(@json(route('super.goods-receipts.export')), window.location.origin);
                if ($('#from_date').val()) url.searchParams.set('from_date', $('#from_date').val());
                if ($('#to_date').val()) url.searchParams.set('to_date', $('#to_date').val());
                $('#exportGoodsReceipts').attr('href', url.toString());
            };
            $('#from_date, #to_date').on('change', updateExportUrl);
            $('#applyDateFilter, #todayDateFilter').on('click', updateExportUrl);
            updateExportUrl();

            $(document).on('click', '.btn-view-goods-receipt', function() {
                $.get($(this).data('url')).done(function(receipt) {
                    const rows = receipt.items.map(item => `<tr><td>${safe(item.code)}</td><td>${safe(item.name)}</td><td>${safe(item.lot_number)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${Number(item.unit_price).toLocaleString('id-ID')}</td><td class="text-end">${Number(item.line_total).toLocaleString('id-ID')}</td></tr>`).join('');
                    $('#viewGoodsReceiptTitle').text(`Penerimaan ${receipt.number}`);
                    $('#viewGoodsReceiptBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-3">{{ __('ui.common.purchase_order') }}</dt><dd class="col-sm-9">${safe(receipt.purchase_order)}</dd>
                        <dt class="col-sm-3">{{ __('ui.workflow.material_order') }}</dt><dd class="col-sm-9">${safe(receipt.material_order)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.invoice_number') }}</dt><dd class="col-sm-9">${safe(receipt.invoice_number)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.invoice_date') }}</dt><dd class="col-sm-9">${safe(receipt.invoice_date)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.currency') }}</dt><dd class="col-sm-9">${safe(receipt.currency)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.supplier') }}</dt><dd class="col-sm-9">${safe(receipt.supplier)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.received_date') }}</dt><dd class="col-sm-9">${safe(receipt.received_at)}</dd>
                        <dt class="col-sm-3">{{ __('ui.workflow.customs_document_code') }}</dt><dd class="col-sm-9">${safe(receipt.customs_document_code)}</dd>
                        <dt class="col-sm-3">{{ __('ui.workflow.customs_document_number') }}</dt><dd class="col-sm-9">${safe(receipt.customs_document_number)} (${safe(receipt.customs_document_date)})</dd>
                        <dt class="col-sm-3">{{ __('ui.common.received_by') }}</dt><dd class="col-sm-9">${safe(receipt.receiver)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.status') }}</dt><dd class="col-sm-9">${safe(receipt.status_label)}</dd>
                        <dt class="col-sm-3">{{ __('ui.workflow.approved_by') }}</dt><dd class="col-sm-9">${safe(receipt.approver)}${receipt.approved_at !== '—' ? ' · ' + safe(receipt.approved_at) : ''}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.notes') }}</dt><dd class="col-sm-9 text-break">${safe(receipt.notes)}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.workflow.item_code') }}</th><th>{{ __('ui.common.item') }}</th><th>{{ __('ui.workflow.lot_roll') }}</th><th class="text-end">{{ __('ui.common.received') }}</th><th class="text-end">{{ __('ui.common.unit_price') }}</th><th class="text-end">{{ __('ui.common.subtotal') }}</th></tr></thead><tbody>${rows}</tbody></table></div>`);
                    modal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.receipt_detail_load_failed') }}' }));
            });

            $(document).on('click', '.btn-delete-goods-receipt', function() {
                const button = $(this);
                Swal.fire({
                    icon: 'warning',
                    title: '{{ __('ui.workflow.delete_receipt_confirm') }}',
                    text: button.data('number'),
                    showCancelButton: true,
                    confirmButtonText: '{{ __('ui.common.delete') }}',
                    cancelButtonText: '{{ __('ui.common.cancel') }}',
                    confirmButtonColor: '#dc3545'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({ url: button.data('url'), method: 'DELETE', data: { _token: @json(csrf_token()) } })
                        .done(response => {
                            Swal.fire({ icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message });
                            table.ajax.reload(null, false);
                        })
                        .fail(xhr => {
                            const message = Object.values(xhr.responseJSON?.errors || {}).flat().join('\n') || xhr.responseJSON?.message || '{{ __('ui.workflow.receipt_delete_failed') }}';
                            Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: message });
                        });
                });
            });

            $(document).on('click', '.btn-approve-goods-receipt', function() {
                const button = $(this);
                Swal.fire({
                    icon: 'question',
                    title: '{{ __('ui.workflow.approve_receipt_confirm') }}',
                    text: button.data('number'),
                    showCancelButton: true,
                    confirmButtonText: '{{ __('ui.workflow.approve') }}',
                    cancelButtonText: '{{ __('ui.common.cancel') }}'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({ url: button.data('url'), method: 'PUT', data: { _token: @json(csrf_token()) } })
                        .done(response => {
                            Swal.fire({ icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message });
                            table.ajax.reload(null, false);
                        })
                        .fail(xhr => {
                            const message = Object.values(xhr.responseJSON?.errors || {}).flat().join('\n') || xhr.responseJSON?.message || '{{ __('ui.workflow.receipt_approval_failed') }}';
                            Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: message });
                        });
                });
            });
        });
    </script>
@endpush
