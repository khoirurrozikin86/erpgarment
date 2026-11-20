@extends('layouts.admin')

@section('title', __('ui.nav.stocks'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">{{ __('ui.common.inventory') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.nav.stocks') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="page-title mb-1">{{ __('ui.nav.stocks') }}</h4><span class="text-muted">{{ __('variants.stock_variant_balance_hint') }}</span></div>
        <div class="d-flex gap-2">
            <a href="{{ route('super.stocks.export') }}" class="btn btn-outline-success btn-sm"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>
            @can('stock-opnames.create')
                <a href="{{ route('super.stock-opnames.create') }}" class="btn btn-primary btn-sm"><i data-feather="clipboard" class="icon-sm me-1"></i>{{ __('ui.common.create_stock_count') }}</a>
            @endcan
        </div>
    </div>

    <div class="card"><div class="card-body"><div class="table-responsive">
        <table id="stock-balances-table" class="table table-bordered table-hover align-middle w-100">
            <thead>
                <tr>
                    <th>{{ __('ui.common.no') }}</th>
                    <th>{{ __('variants.stock_parent_code') }}</th>
                    <th>{{ __('ui.common.item_name') }}</th>
                    <th>{{ __('ui.common.category') }}</th>
                    <th>{{ __('ui.common.unit') }}</th>
                    <th class="text-end">{{ __('variants.global_stock') }}</th>
                    <th class="text-end">{{ __('ui.workflow.reorder_point') }}</th>
                    <th>{{ __('ui.common.status') }}</th>
                    <th>{{ __('variants.stock_sku') }}</th>
                </tr>
            </thead>
        </table>
    </div></div></div>

    <div class="modal fade" id="itemLotsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="itemLotsTitle">{{ __('ui.workflow.lot_balances') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body"><div class="table-responsive"><table class="table table-bordered table-sm"><thead><tr><th>{{ __('ui.workflow.lot_number') }}</th><th>{{ __('ui.common.supplier') }}</th><th>{{ __('ui.workflow.customs_document_number') }}</th><th>{{ __('ui.common.date') }}</th><th class="text-end">{{ __('ui.workflow.initial_quantity') }}</th><th class="text-end">{{ __('ui.workflow.current_quantity') }}</th></tr></thead><tbody id="itemLotsBody"></tbody></table></div></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const formatQuantity = new Intl.NumberFormat(@json(app()->getLocale()), {maximumFractionDigits: 4});
            const text = $.fn.dataTable.render.text();
            const statuses = {
                safe_stock: ['bg-success', @json(__('ui.modules.safe_stock'))],
                low_stock: ['bg-warning text-dark', @json(__('ui.modules.low_stock'))],
                out_of_stock: ['bg-danger', @json(__('ui.modules.out_of_stock'))],
            };
            const showDetails = @json(__('ui.common.detail'));
            const hideDetails = @json(__('variants.inventory_hide_skus'));
            const table = $('#stock-balances-table').DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                searchDelay: 350,
                ajax: @json(route('super.stocks.dt')),
                columns: [
                    {data: null, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1},
                    {data: 'code', render: text},
                    {data: 'name', render: text},
                    {data: 'category', defaultContent: '—', searchable: false, render: text},
                    {data: 'unit', defaultContent: '—', searchable: false, render: text},
                    {data: 'quantity', className: 'text-end fw-semibold', searchable: false, render: value => formatQuantity.format(value)},
                    {data: 'threshold', className: 'text-end', searchable: false, render: value => formatQuantity.format(value)},
                    {data: 'status', searchable: false, render: status => {
                        const [badge, label] = statuses[status];
                        return $('<span class="badge">').addClass(badge).text(label).prop('outerHTML');
                    }},
                    {data: null, searchable: false, render: () => $('<button type="button" class="btn btn-outline-secondary btn-sm stock-sku-toggle" aria-expanded="false">')
                        .text(showDetails).prop('outerHTML')},
                ],
                language: {
                    processing: @json(__('ui.workflow.data_processing')),
                    search: @json(__('ui.common.search')),
                    emptyTable: @json(__('ui.modules.no_tracked_items')),
                    zeroRecords: @json(__('ui.workflow.no_matching_data')),
                    lengthMenu: @json(__('ui.workflow.show_entries')),
                    info: @json(__('ui.workflow.showing_entries')),
                    infoEmpty: @json(__('ui.workflow.no_data')),
                    paginate: {previous: @json(__('ui.workflow.previous')), next: @json(__('ui.workflow.next'))}
                }
            });

            $('#stock-balances-table').on('click', '.stock-sku-toggle', function () {
                const row = table.row($(this).closest('tr'));
                const open = row.child.isShown();
                if (open) row.child.hide();
                else row.child(row.data().detail_html).show();
                $(this).attr('aria-expanded', String(!open)).text(open ? showDetails : hideDetails);
            });

            $(document).on('click', '.view-item-lots', function() {
                const button = $(this);
                const url = @json(route('super.stocks.lots', ['product' => '__ID__'])).replace('__ID__', button.data('id'));
                $('#itemLotsTitle').text(`{{ __('ui.workflow.lot_balances') }} — ${button.data('name')}`);
                $('#itemLotsBody').html(`<tr><td colspan="6" class="text-center">{{ __('ui.workflow.loading_lots') }}</td></tr>`);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('itemLotsModal')).show();
                $.get(url).done(lots => {
                    const rows = lots.map(lot => `<tr><td>${$('<div>').text(lot.lot_number).html()}</td><td>${$('<div>').text(lot.supplier).html()}</td><td>${$('<div>').text(lot.customs_documents).html()}</td><td>${$('<div>').text(lot.received_at).html()}</td><td class="text-end">${lot.initial_quantity}</td><td class="text-end">${lot.current_quantity}</td></tr>`).join('');
                    $('#itemLotsBody').html(rows || `<tr><td colspan="6" class="text-center text-muted">{{ __('ui.workflow.no_open_lots') }}</td></tr>`);
                }).fail(() => $('#itemLotsBody').html(`<tr><td colspan="6" class="text-center text-danger">{{ __('ui.workflow.lots_load_failed') }}</td></tr>`));
            });
        });
    </script>
@endpush
