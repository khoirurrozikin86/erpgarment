@extends('layouts.admin')
@section('title', __('ui.modules.reports_inventory_title'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">{{ __('ui.common.report') }}</a></li><li class="breadcrumb-item active">{{ __('ui.common.inventory') }}</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">{{ __('ui.modules.reports_inventory_title') }}</h4><span class="text-muted">{{ __('ui.modules.reports_inventory_subtitle') }}</span></div></div>
    <form method="GET" class="card mb-3"><div class="card-body py-3"><div class="row align-items-end g-2"><div class="col-sm-5 col-md-3"><label for="from_date" class="form-label mb-1">{{ __('ui.common.from_date') }}</label><input id="from_date" name="from_date" type="date" class="form-control form-control-sm" value="{{ $fromDate }}" required></div><div class="col-sm-5 col-md-3"><label for="to_date" class="form-label mb-1">{{ __('ui.common.to_date') }}</label><input id="to_date" name="to_date" type="date" class="form-control form-control-sm" value="{{ $toDate }}" required></div><div class="col-sm-2 col-md-auto"><button class="btn btn-sm btn-primary" type="submit"><i data-feather="filter" class="icon-sm me-1"></i>{{ __('ui.common.show') }}</button> <button class="btn btn-sm btn-outline-success" type="submit" formaction="{{ route('super.reports.inventory.export') }}"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</button></div></div></div></form>
    <div class="row g-3 mb-3"><div class="col-md"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('ui.modules.tracked_items') }}</div><div class="fs-5 fw-semibold">{{ number_format($summary['products']) }}</div></div></div></div><div class="col-md"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('ui.modules.items_with_movements') }}</div><div class="fs-5 fw-semibold">{{ number_format($summary['moved_products']) }}</div></div></div></div><div class="col-md"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('ui.modules.safe_stock') }}</div><div class="fs-5 fw-semibold text-success">{{ number_format($summary['safe_stock']) }}</div></div></div></div><div class="col-md"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('ui.modules.low_stock') }}</div><div class="fs-5 fw-semibold text-warning">{{ number_format($summary['low_stock']) }}</div></div></div></div><div class="col-md"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('ui.modules.out_of_stock') }}</div><div class="fs-5 fw-semibold text-danger">{{ number_format($summary['out_of_stock']) }}</div></div></div></div></div>
    <div class="card">
        <div class="card-body table-responsive">
            <table id="inventory-report-table" class="table table-bordered table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>{{ __('variants.stock_parent_code') }}</th>
                        <th>{{ __('ui.common.item_name') }}</th>
                        <th>{{ __('ui.common.category') }}</th>
                        <th>{{ __('ui.common.unit') }}</th>
                        @foreach (['opening_balance', 'enter', 'exit', 'closing_balance'] as $label)
                            <th class="text-end">{{ __('ui.common.'.$label) }}</th>
                        @endforeach
                        <th>{{ __('ui.common.status') }}</th>
                        <th>{{ __('variants.stock_sku') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            const formatQuantity = new Intl.NumberFormat('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 4});
            const text = $.fn.dataTable.render.text();
            const statuses = {
                safe_stock: ['bg-success', @json(__('ui.modules.safe_stock'))],
                low_stock: ['bg-warning text-dark', @json(__('ui.modules.low_stock'))],
                out_of_stock: ['bg-danger', @json(__('ui.modules.out_of_stock'))],
            };
            const showDetails = @json(__('ui.common.detail'));
            const hideDetails = @json(__('variants.inventory_hide_skus'));
            const table = $('#inventory-report-table').DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                searchDelay: 350,
                ajax: {
                    url: @json(route('super.reports.inventory.dt')),
                    data: {from_date: @json($fromDate), to_date: @json($toDate)}
                },
                columns: [
                    {data: 'code', render: text},
                    {data: 'name', render: text},
                    {data: 'category', defaultContent: '—', searchable: false, render: text},
                    {data: 'unit', defaultContent: '—', searchable: false, render: text},
                    ...['opening', 'incoming', 'outgoing', 'closing'].map(balance => ({
                        data: balance, className: 'text-end', searchable: false, render: value => formatQuantity.format(value)
                    })),
                    {data: 'status', searchable: false, render: status => {
                        const [badge, label] = statuses[status];
                        return $('<span class="badge">').addClass(badge).text(label).prop('outerHTML');
                    }},
                    {data: null, searchable: false, render: () => $('<button type="button" class="btn btn-outline-secondary btn-sm inventory-sku-toggle" aria-expanded="false">')
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

            $('#inventory-report-table').on('click', '.inventory-sku-toggle', function () {
                const row = table.row($(this).closest('tr'));
                const open = row.child.isShown();
                if (open) row.child.hide();
                else row.child(row.data().detail_html).show();
                $(this).attr('aria-expanded', String(!open)).text(open ? showDetails : hideDetails);
            });
        });
    </script>
@endpush
