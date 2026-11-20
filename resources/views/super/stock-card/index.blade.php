@extends('layouts.admin')

@section('title', __( 'ui.common.stock_card' ))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">{{ __('ui.common.inventory') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.stock_card') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="page-title mb-1">{{ __('ui.common.stock_card') }}</h4><span class="text-muted">{{ __('ui.workflow.stock_card_subtitle') }}</span></div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <div class="row align-items-end g-2">
            <div class="col-md-7">
                <label class="form-label mb-1" for="selected_product">{{ __('ui.common.item') }}</label>
                <div class="input-group input-group-sm">
                    <input type="hidden" id="product_id" value="{{ $initialProduct?->id }}">
                    <input type="hidden" id="stock_card_family" value="0">
                    <input type="text" id="selected_product" class="form-control" readonly placeholder="{{ __('ui.workflow.choose_product_stock_card') }}">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#selectProductModal"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.choose_item') }}</button>
                </div>
            </div>
            <div class="col-md-5 d-flex justify-content-md-end align-items-end">
                <a href="#" id="exportStockCard" class="btn btn-sm btn-outline-success disabled" aria-disabled="true"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>
            </div>
        </div>
    </div></div>

    <div class="row g-3 mb-3 d-none" id="stockSummary">
        <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('ui.common.opening_period_balance') }}</div><div class="h4 mb-0" id="openingBalance">0</div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('ui.workflow.total_in') }}</div><div class="h4 mb-0 text-success" id="periodIn">0</div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('ui.workflow.total_out') }}</div><div class="h4 mb-0 text-danger" id="periodOut">0</div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('ui.workflow.current_balance') }}</div><div class="h4 mb-0" id="currentBalance">0</div></div></div></div>
    </div>

    @include('super.purchasing.date-filter')

    <div id="stockCardFamilyDetails" class="card mb-3 d-none">
        <div class="card-header"><h6 class="mb-0">{{ __('variants.stock_card_all_skus') }}</h6></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead><tr>
                    <th>{{ __('variants.stock_sku') }}</th>
                    <th>{{ __('ui.workflow.color') }}</th>
                    <th>{{ __('ui.workflow.size') }}</th>
                    <th class="text-end">{{ __('ui.common.opening_period_balance') }}</th>
                    <th class="text-end">{{ __('ui.workflow.total_in') }}</th>
                    <th class="text-end">{{ __('ui.workflow.total_out') }}</th>
                    <th class="text-end">{{ __('ui.workflow.current_balance') }}</th>
                </tr></thead>
                <tbody id="stockCardFamilyRows"></tbody>
            </table>
        </div>
    </div>

    <div class="card"><div class="card-body"><div class="table-responsive">
        <table id="stock-card-table" class="table table-bordered table-hover align-middle w-100">
            <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.workflow.recorded_at') }}</th><th>{{ __('variants.stock_sku') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.workflow.document_number') }}</th><th>{{ __('ui.workflow.lot_roll') }}</th><th>{{ __('ui.common.type') }}</th><th class="text-end">{{ __('ui.common.enter') }}</th><th class="text-end">{{ __('ui.common.exit') }}</th><th class="text-end">{{ __('ui.common.balance') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.notes') }}</th></tr></thead>
        </table>
    </div></div></div>

    <div class="modal fade" id="selectProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ __('ui.common.choose_item') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body"><div class="table-responsive"><table id="stockProductsTable" class="table table-bordered table-hover align-middle w-100">
                <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('variants.stock_parent_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead>
            </table></div></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const productModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('selectProductModal'));
            const safe = value => $('<div>').text(value ?? '').html();
            const number = value => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(Number(value || 0));

            const table = $('#stock-card-table').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: {
                    url: @json(route('super.stock-card.dt')),
                    data: data => {
                        data.product_id = $('#product_id').val();
                        data.family = $('#stock_card_family').val();
                        data.from_date = $('#from_date').val();
                        data.to_date = $('#to_date').val();
                    }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'created_at', name: 'created_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
                    { data: 'sku_code', orderable: false, searchable: false },
                    { data: 'sku_color', orderable: false, searchable: false },
                    { data: 'sku_size', orderable: false, searchable: false },
                    { data: 'reference_number', orderable: false, searchable: false },
                    { data: 'lot_numbers', orderable: false, searchable: false },
                    { data: 'movement_label', name: 'movement_type', searchable: false },
                    { data: 'quantity_in', orderable: false, searchable: false, className: 'text-end' },
                    { data: 'quantity_out', orderable: false, searchable: false, className: 'text-end' },
                    { data: 'balance_after', orderable: false, searchable: false, className: 'text-end fw-semibold' },
                    { data: 'unit_label', orderable: false, searchable: false },
                    { data: 'notes', name: 'notes', defaultContent: '—' }
                ],
                order: [[1, 'asc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            const picker = $('#stockProductsTable');
            const text = $.fn.dataTable.render.text();
            const showDetails = @json(__('ui.common.detail'));
            const hideDetails = @json(__('variants.inventory_hide_skus'));
            const selectParent = @json(__('variants.stock_card_select_parent'));
            const allSkus = @json(__('variants.stock_card_all_skus'));
            const productTable = picker.DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                searchDelay: 350,
                ajax: @json(route('super.stock-card.products.dt')),
                columns: [
                    {data: null, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1},
                    {data: 'code', render: text},
                    {data: 'name', render: text},
                    {data: 'unit', defaultContent: '—', searchable: false, render: text},
                    {data: null, searchable: false, render: () => $('<div class="d-flex flex-wrap gap-1">').append(
                        $('<button type="button" class="btn btn-sm btn-primary select-stock-card-parent">').text(selectParent),
                        $('<button type="button" class="btn btn-sm btn-outline-secondary stock-card-sku-toggle" aria-expanded="false">').text(showDetails)
                    ).prop('outerHTML')},
                ],
                language: {
                    processing: @json(__('ui.workflow.data_processing')),
                    search: @json(__('ui.common.search')),
                    emptyTable: @json(__('ui.modules.no_tracked_items')),
                    zeroRecords: @json(__('ui.workflow.no_matching_data')),
                    lengthMenu: @json(__('ui.workflow.show_entries')),
                    info: @json(__('ui.workflow.showing_entries')),
                    infoEmpty: @json(__('ui.workflow.no_data')),
                    paginate: {previous: @json(__('ui.workflow.previous')), next: @json(__('ui.workflow.next'))},
                },
            });
            $('#selectProductModal').on('shown.bs.modal', () => productTable.columns.adjust());

            picker.on('click', '.stock-card-sku-toggle', function () {
                const row = productTable.row($(this).closest('tr'));
                const open = row.child.isShown();
                if (open) row.child.hide();
                else row.child(row.data().detail_html).show();
                $(this).attr('aria-expanded', String(!open)).text(open ? showDetails : hideDetails);
            });

            function showSelectedProduct(parentCode, sku, name, color, size) {
                const labels = [parentCode];
                if (sku !== parentCode) labels.push(sku);
                labels.push(name);
                const dimensions = [color, size].filter(Boolean).join(' / ');
                if (dimensions) labels.push(dimensions);
                $('#selected_product').val(labels.join(' — '));
            }

            let pendingSummary;
            function loadSummary() {
                pendingSummary?.abort();
                $('#stockCardFamilyDetails').addClass('d-none');
                const productId = $('#product_id').val();
                if (!productId) {
                    $('#stockSummary').addClass('d-none');
                    return;
                }
                pendingSummary = $.get(@json(route('super.stock-card.summary')), {
                    product_id: productId,
                    family: $('#stock_card_family').val(),
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val()
                }).done(function(summary) {
                    const unit = summary.unit ? ` ${safe(summary.unit)}` : '';
                    $('#openingBalance').html(`${number(summary.opening_balance)}${unit}`);
                    $('#periodIn').html(`${number(summary.quantity_in)}${unit}`);
                    $('#periodOut').html(`${number(summary.quantity_out)}${unit}`);
                    $('#currentBalance').html(`${number(summary.current_balance)}${unit}`);
                    $('#stockSummary').removeClass('d-none');

                    if (summary.details) {
                        const rows = summary.details.map(sku => `<tr>
                            <td>${safe(sku.code)}</td>
                            <td>${safe(sku.color ?? '—')}</td>
                            <td>${safe(sku.size ?? '—')}</td>
                            <td class="text-end">${number(sku.opening_balance)}${unit}</td>
                            <td class="text-end">${number(sku.quantity_in)}${unit}</td>
                            <td class="text-end">${number(sku.quantity_out)}${unit}</td>
                            <td class="text-end fw-semibold">${number(sku.current_balance)}${unit}</td>
                        </tr>`).join('');
                        $('#stockCardFamilyRows').html(rows);
                        $('#stockCardFamilyDetails').removeClass('d-none');
                    }
                }).fail((xhr, status) => {
                    if (status !== 'abort') Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.stock_summary_load_failed') }}' });
                });
            }

            function updateExportLink() {
                const productId = $('#product_id').val();
                const link = $('#exportStockCard');
                if (!productId) {
                    link.addClass('disabled').attr('aria-disabled', 'true').attr('href', '#');
                    return;
                }
                const params = new URLSearchParams({
                    product_id: productId,
                    family: $('#stock_card_family').val(),
                    from_date: $('#from_date').val() || '',
                    to_date: $('#to_date').val() || ''
                });
                link.removeClass('disabled').attr('aria-disabled', 'false')
                    .attr('href', `${@json(route('super.stock-card.export'))}?${params.toString()}`);
            }

            function refreshCard() {
                productModal.hide();
                table.ajax.reload();
                loadSummary();
                updateExportLink();
            }

            picker.on('click', '.select-stock-card-parent', function () {
                const parent = productTable.row($(this).closest('tr')).data();
                $('#product_id').val(parent.id);
                $('#stock_card_family').val('1');
                $('#selected_product').val(`${parent.code} — ${parent.name} — ${allSkus}`);
                refreshCard();
            });

            picker.on('click', '.btn-select-stock-sku', function () {
                const button = $(this);
                $('#product_id').val(button.attr('data-id'));
                $('#stock_card_family').val('0');
                showSelectedProduct(button.attr('data-parent-code'), button.attr('data-code'),
                    button.attr('data-name'), button.attr('data-color'), button.attr('data-size'));
                refreshCard();
            });

            $('#applyDateFilter').on('click', () => {
                table.ajax.reload();
                loadSummary();
                updateExportLink();
            });
            $('#todayDateFilter').on('click', () => {
                const today = @json(now()->toDateString());
                $('#from_date, #to_date').val(today);
                table.ajax.reload();
                loadSummary();
                updateExportLink();
            });

            $('#exportStockCard').on('click', function(event) {
                if (!$('#product_id').val()) event.preventDefault();
            });

            @if ($initialProduct)
                showSelectedProduct(@json($initialProduct->parentProduct?->code ?? $initialProduct->code),
                    @json($initialProduct->code), @json($initialProduct->name), @json($initialProduct->color), @json($initialProduct->size));
                loadSummary();
                updateExportLink();
            @endif
        });
    </script>
@endpush
