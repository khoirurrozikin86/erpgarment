@extends('layouts.admin')
@section('title', __('ui.common.sales_order'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">{{ __('ui.common.sales') }}</a></li><li class="breadcrumb-item active">{{ __('ui.common.sales_order') }}</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">{{ __('ui.common.sales_order') }}</h4><span class="text-muted">{{ __('ui.modules.sales_order_subtitle') }}</span></div>@can('sales-orders.create')<a href="{{ route('super.sales-orders.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.common.create_sales_order') }}</a>@endcan</div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-body"><div class="table-responsive"><table id="sales-orders-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.so_number') }}</th><th>{{ __('ui.common.quotation') }}</th><th>{{ __('ui.common.customer') }}</th><th>{{ __('ui.common.order_date') }}</th><th>{{ __('ui.common.requested_delivery') }}</th><th class="text-end">{{ __('ui.common.total') }}</th><th>{{ __('ui.common.status') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="salesOrderDetailModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="salesOrderDetailTitle">{{ __('ui.modules.order_detail') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body" id="salesOrderDetailBody"></div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const detailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('salesOrderDetailModal'));
    const safe = value => $('<div>').text(value ?? '').html();
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const table = $('#sales-orders-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: @json(route('super.sales-orders.dt')), data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); } },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'number', name: 'number' },
            { data: 'quotation_number', orderable: false, searchable: false },
            { data: 'customer_name', orderable: false, searchable: false },
            { data: 'order_date', name: 'order_date', render: data => data ? String(data).slice(0, 10).split('-').reverse().join('/') : '—' },
            { data: 'requested_delivery_at', name: 'requested_delivery_at', render: data => data ? String(data).slice(0, 10).split('-').reverse().join('/') : '—' },
            { data: 'total_amount', name: 'total_amount', className: 'text-end', render: data => money(data) },
            { data: 'status_label', name: 'status', searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[4, 'desc']], drawCallback: function() { if (window.feather) feather.replace(); }
    });
    $('#applyDateFilter').on('click', () => table.ajax.reload());
    $('#todayDateFilter').on('click', () => { const today = @json(now()->toDateString()); $('#from_date, #to_date').val(today); table.ajax.reload(); });

    $(document).on('click', '.btn-view-sales-order', function() {
        $.get($(this).data('url')).done(order => {
            const status = { draft: '{{ __('ui.modules.draft_status') }}', confirmed: '{{ __('ui.modules.order_confirmed') }}', partially_delivered: '{{ __('ui.modules.partially_delivered') }}', delivered: '{{ __('ui.modules.fully_delivered') }}', cancelled: '{{ __('ui.modules.cancelled_status') }}' }[order.status] || order.status;
            const rows = order.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.name)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${item.delivered_quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
            $('#salesOrderDetailTitle').text(`Sales Order ${order.number}`);
            $('#salesOrderDetailBody').html(`<dl class="row"><dt class="col-sm-3">{{ __('ui.common.quotation') }}</dt><dd class="col-sm-9">${safe(order.quotation)}</dd><dt class="col-sm-3">{{ __('ui.common.customer') }}</dt><dd class="col-sm-9">${safe(order.customer)}</dd><dt class="col-sm-3">{{ __('ui.common.order_date') }}</dt><dd class="col-sm-9">${safe(order.order_date)}</dd><dt class="col-sm-3">{{ __('ui.common.requested_delivery') }}</dt><dd class="col-sm-9">${safe(order.requested_delivery_at)}</dd><dt class="col-sm-3">{{ __('ui.common.status') }}</dt><dd class="col-sm-9">${status}</dd><dt class="col-sm-3">{{ __('ui.common.created_by') }}</dt><dd class="col-sm-9">${safe(order.creator)}</dd><dt class="col-sm-3">{{ __('ui.modules.order_confirmed') }} Oleh</dt><dd class="col-sm-9">${safe(order.confirmer)} · ${safe(order.confirmed_at)}</dd><dt class="col-sm-3">{{ __('ui.common.notes') }}</dt><dd class="col-sm-9">${safe(order.notes)}</dd></dl><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">Order</th><th class="text-end">{{ __('ui.common.sent') }}</th><th class="text-end">{{ __('ui.common.price') }}</th><th class="text-end">{{ __('ui.common.subtotal') }}</th></tr></thead><tbody>${rows}</tbody><tfoot><tr><th colspan="4" class="text-end">{{ __('ui.common.total') }}</th><th class="text-end">${money(order.total_amount)}</th></tr></tfoot></table></div>`);
            detailModal.show();
        }).fail(() => Swal.fire({ icon: 'error', title: '{{ __('ui.modules.failure') }}', text: '{{ __('ui.modules.detail_failed_order') }}' }));
    });
    $(document).on('click', '.btn-confirm-sales-order', function() {
        const button = $(this);
        Swal.fire({ icon: 'question', title: `{{ __('ui.modules.confirmation') }} {{ __('ui.common.sales_order') }} ${button.data('number')}?`, text: '{{ __('ui.modules.order_confirmation') }}', showCancelButton: true, confirmButtonText: '{{ __('ui.modules.confirmation') }}', cancelButtonText: '{{ __('ui.modules.cancel') }}' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'POST', data: { _token: @json(csrf_token()), _method: 'PUT' }, success: response => { Swal.fire({ icon: 'success', title: '{{ __('ui.modules.success') }}', text: response.message }); table.ajax.reload(null, false); }, error: xhr => Swal.fire({ icon: 'error', title: '{{ __('ui.modules.failure') }}', text: xhr.responseJSON?.message || '{{ __('ui.modules.sales_order_confirm_failed') }}' }) });
        });
    });
});
</script>
@endpush
