@extends('layouts.admin')
@section('title', __('ui.common.create_sales_order'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">{{ __('ui.common.sales') }}</a></li><li class="breadcrumb-item"><a href="{{ route('super.sales-orders.index') }}">{{ __('ui.common.sales_order') }}</a></li><li class="breadcrumb-item active">{{ __('ui.common.create') }}</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">{{ __('ui.common.create_sales_order') }}</h4><span class="text-muted">{{ __('ui.modules.sales_order_create_help') }}</span></div><a href="{{ route('super.sales-orders.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i>{{ __('ui.common.back') }}</a></div>
    <form id="salesOrderForm"><div class="card"><div class="card-header"><h6 class="mb-0">{{ __('ui.modules.order_source') }}</h6></div><div class="card-body">
        <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Quotation {{ __('ui.modules.quotation_accepted') }} <span class="text-danger">*</span></label><div class="input-group"><input type="hidden" id="sales_quotation_id"><input type="text" id="sales_quotation_number" class="form-control" placeholder="{{ __('ui.modules.select_approved_quotation') }}" readonly required><button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quotationPicker"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.modules.search_quotation') }}</button></div></div><div class="col-md-3 mb-3"><label class="form-label" for="order_date">{{ __('ui.common.order_date') }}</label><input type="date" id="order_date" class="form-control" value="{{ now()->toDateString() }}" required></div><div class="col-md-3 mb-3"><label class="form-label" for="requested_delivery_at">{{ __('ui.common.requested_delivery') }}</label><input type="date" id="requested_delivery_at" class="form-control"></div></div>
        <div id="quotationPreview" class="d-none"><div class="alert alert-info py-2" id="quotationPreviewInfo"></div><div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.common.quantity') }}</th><th class="text-end">{{ __('ui.common.price') }}</th><th class="text-end">{{ __('ui.common.discount') }}</th><th class="text-end">{{ __('ui.common.tax') }}</th><th class="text-end">{{ __('ui.common.subtotal') }}</th></tr></thead><tbody id="quotationPreviewItems"></tbody><tfoot><tr><th colspan="5" class="text-end">{{ __('ui.common.subtotal') }}</th><th class="text-end" id="previewSubtotal"></th></tr><tr><th colspan="5" class="text-end">{{ __('ui.common.tax') }}</th><th class="text-end" id="previewTax"></th></tr><tr><th colspan="5" class="text-end">{{ __('ui.common.total') }}</th><th class="text-end" id="previewTotal"></th></tr></tfoot></table></div></div>
        <div class="mt-3"><label class="form-label" for="sales_order_notes">{{ __('ui.modules.order_notes') }}</label><textarea id="sales_order_notes" class="form-control" rows="3" maxlength="5000" placeholder="{{ __('ui.modules.fulfillment_notes_placeholder') }}"></textarea></div>
    </div><div class="card-footer d-flex justify-content-between"><a href="{{ route('super.sales-orders.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.cancel') }}</a><button id="submitSalesOrder" class="btn btn-primary btn-sm" type="submit" disabled><i data-feather="save" class="icon-sm me-1"></i>{{ __('ui.common.create_sales_order') }}</button></div></div></form>
    <div class="modal fade" id="quotationPicker" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><h5 class="modal-title">{{ __('ui.modules.search_quotation') }} {{ __('ui.modules.quotation_accepted') }}</h5><small class="text-muted">{{ __('ui.modules.quotation_already_ordered') }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="eligibleQuotations" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.quotation_number') }}</th><th>{{ __('ui.common.date') }}</th><th>{{ __('ui.common.customer') }}</th><th class="text-end">{{ __('ui.common.total') }}</th><th class="text-center">{{ __('ui.common.item') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const picker = bootstrap.Modal.getOrCreateInstance(document.getElementById('quotationPicker'));
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const safe = value => $('<div>').text(value ?? '').html();
    $('#eligibleQuotations').DataTable({
        processing: true, serverSide: true, responsive: true, ajax: @json(route('super.sales-orders.eligible')),
        columns: [
            { data: 'number', name: 'number' },
            { data: 'quote_date', name: 'quote_date', render: data => data ? String(data).slice(0, 10).split('-').reverse().join('/') : '—' },
            { data: 'customer_name', orderable: false, searchable: false },
            { data: 'total_amount', name: 'total_amount', className: 'text-end', render: data => money(data) },
            { data: 'items_count', searchable: false, className: 'text-center' },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[1, 'desc']], drawCallback: function() { if (window.feather) feather.replace(); }
    });

    $(document).on('click', '.btn-select-sales-quotation', function() {
        const button = $(this), id = button.data('id');
        $('#sales_quotation_id').val(id); $('#sales_quotation_number').val(button.data('number'));
        $('#submitSalesOrder').prop('disabled', true); $('#quotationPreview').removeClass('d-none');
        $('#quotationPreviewItems').html('<tr><td colspan="6" class="text-center text-muted">{{ __('ui.modules.loading_quotation') }}</td></tr>'); picker.hide();
        const url = @json(route('super.sales-orders.source', ['quotationId' => '__ID__'])).replace('__ID__', encodeURIComponent(id));
        $.get(url).done(quotation => {
            $('#quotationPreviewInfo').text(`Customer: ${quotation.customer} · Quotation: ${quotation.number} · {{ __('ui.modules.quotation_preview_until') }}: ${quotation.valid_until}`);
            const rows = quotation.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.name)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.discount_amount)}</td><td class="text-end">${item.tax_rate}%</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
            $('#quotationPreviewItems').html(rows); $('#previewSubtotal').text(money(quotation.subtotal)); $('#previewTax').text(money(quotation.tax_amount)); $('#previewTotal').text(money(quotation.total_amount)); $('#submitSalesOrder').prop('disabled', false);
        }).fail(xhr => { $('#submitSalesOrder').prop('disabled', true); Swal.fire({ icon: 'error', title: '{{ __('ui.modules.failure') }}', text: xhr.responseJSON?.message || '{{ __('ui.modules.quotation_unavailable') }}' }); });
    });

    $('#salesOrderForm').on('submit', function(event) {
        event.preventDefault(); const button = $('#submitSalesOrder').prop('disabled', true);
        $.ajax({
            url: @json(route('super.sales-orders.store')), method: 'POST',
            data: { _token: @json(csrf_token()), sales_quotation_id: $('#sales_quotation_id').val(), order_date: $('#order_date').val(), requested_delivery_at: $('#requested_delivery_at').val(), notes: $('#sales_order_notes').val() },
            success: response => Swal.fire({ icon: 'success', title: '{{ __('ui.modules.success') }}', text: response.message }).then(() => window.location.href = response.url),
            error: xhr => { const errors = xhr.responseJSON?.errors; Swal.fire({ icon: 'error', title: '{{ __('ui.modules.failure') }}', text: errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || '{{ __('ui.modules.sales_order_create_failed') }}') }); button.prop('disabled', false); }
        });
    });
});
</script>
@endpush
