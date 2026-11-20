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
    @php($isCreatePage = request()->routeIs('super.material-requests.create'))
    <style>
        #mrSourcePickerTable td { vertical-align: top; }
        #mrSourcePickerTable .mr-source-detail-line { height: 36px; display: flex; align-items: center; white-space: nowrap; border-bottom: 1px solid #e8edf3; }
        #mrSourcePickerTable .mr-source-detail-line:last-child { border-bottom: 0; }
    </style>
    @if(!$isCreatePage)
    <div class="row">
        <div class="mobile-list-header d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="page-title mb-1">{{ __('ui.common.material_request') }}</h4>
                <span class="text-muted">{{ __('ui.workflow.mr_subtitle') }}</span>
            </div>
            <div class="mobile-list-actions d-flex flex-wrap gap-2">
                @can('material-requests.view')
                    <a href="{{ route('super.material-requests.export') }}" id="exportMaterialRequests" class="btn btn-outline-success btn-sm">
                        <i data-feather="download" class="icon-sm me-1"></i>
                        <span class="mobile-list-action-label">{{ __('ui.common.export_excel') }}</span></a>
                @endcan
                @can('material-requests.create')
                    <a class="btn btn-primary btn-sm" href="{{ route('super.material-requests.create') }}"><i data-feather="plus" class="icon-sm me-1"></i>
                        <span class="mobile-list-action-label">{{ __('ui.workflow.create_request') }}</span></a>
                @endcan
            </div>
        </div>

        <div class="col-12">
            @include('super.purchasing.date-filter', ['compactActions' => true])
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="material-requests-table" class="mobile-list-table table table-bordered table-hover align-middle w-100">
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
                                    <th>{{ __('ui.workflow.items_needed') }}</th>
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

    @endif

    @can('material-requests.create')
        @if($isCreatePage)
        <div id="createRequestPage" class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="mb-0">{{ __('ui.workflow.material_request_title') }}</h5>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('super.material-requests.index') }}">{{ __('ui.common.back') }}</a>
            </div>
            <form id="materialRequestForm" class="material-request-form">
                @csrf
                <input type="hidden" name="bill_of_material_id" id="request_bom_id">
                <input type="hidden" name="production_order_id" id="request_production_order_id">
                <input type="hidden" name="output_product_id" id="request_output_product_id">
                <input type="hidden" name="bom_version" id="request_bom_version">
                <input type="hidden" name="production_quantity" id="request_production_quantity">
                <div class="card-body">
                    <div class="row g-3 g-xl-4">
                        <div class="col-lg-6">
                            <div class="row g-2 align-items-center mb-2">
                                <label for="request_department" class="col-md-4 col-form-label col-form-label-sm">{{ __('ui.common.department') }}</label>
                                <div class="col-md-8">
                                    <input type="text" class="form-control form-control-sm" id="request_department" name="department" maxlength="120" placeholder="{{ __('ui.workflow.department_example') }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-2 align-items-center mb-2">
                                <label for="request_needed_at" class="col-md-4 col-form-label col-form-label-sm">{{ __('ui.common.needed_date') }}</label>
                                <div class="col-md-8">
                                    <input type="date" class="form-control form-control-sm" id="request_needed_at" name="needed_at">
                                </div>
                            </div>
                        </div>
                    </div>

                    <section class="border-top mt-3 pt-3" aria-labelledby="mr-source-title">
                        <h6 id="mr-source-title" class="text-primary mb-3">{{ __('ui.workflow.source_production_order') }}</h6>
                        <div class="row g-2 align-items-center">
                            <div class="col-lg-6">
                                <div class="row g-2 align-items-center">
                                    <label for="source_production_order_label" class="col-md-4 col-form-label col-form-label-sm">{{ __('bc_reports.mo_no') }}</label>
                                    <div class="col-md-8">
                                        <input type="hidden" id="source_production_order">
                                        <div class="input-group input-group-sm">
                                            <input id="source_production_order_label" class="form-control" readonly placeholder="{{ __('ui.workflow.choose_production_order') }}">
                                            <button type="button" class="btn btn-outline-primary mr-source-search" data-kind="mo">{{ __('ui.common.search') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-outline-success btn-sm" id="load-production-order-materials">{{ __('variants.take_bom') }}</button>
                            </div>
                        </div>
                        <div id="mr-independent-bom" class="mt-3">
                            <h6 class="text-primary mb-2">{{ __('ui.workflow.generate_from_bom') }}</h6>
                            <div class="row g-3 g-xl-4">
                                <div class="col-lg-6">
                                    <div class="row g-2 align-items-center mb-2">
                                        <label for="bom_output_product_label" class="col-md-4 col-form-label col-form-label-sm">{{ __('ui.workflow.bom_product') }}</label>
                                        <div class="col-md-8">
                                            <input type="hidden" id="bom_output_product">
                                            <div class="input-group input-group-sm">
                                                <input id="bom_output_product_label" class="form-control" readonly placeholder="{{ __('ui.workflow.bom_choose_product') }}">
                                                <button type="button" class="btn btn-outline-primary mr-source-search" data-kind="product">{{ __('ui.common.search') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-2 align-items-center">
                                        <label for="request_bom_choice" class="col-md-4 col-form-label col-form-label-sm">{{ __('variants.choose_bom') }}</label>
                                        <div class="col-md-8">
                                            <select class="form-select form-select-sm" id="request_bom_choice">
                                                <option value="">{{ __('ui.common.select') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="row g-2 align-items-center mb-2">
                                        <label for="production_quantity_input" class="col-md-4 col-form-label col-form-label-sm">{{ __('ui.workflow.production_quantity') }}</label>
                                        <div class="col-md-8">
                                            <input type="number" class="form-control form-control-sm" id="production_quantity_input" min="0.0001" step="0.0001">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="generate-bom-requirements">{{ __('ui.workflow.calculate_bom_requirements') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small id="bom-generation-summary" class="text-muted"></small>
                        <div id="mr-live-calculation" class="mt-2"></div>
                        <div id="mr-order-source-details" class="table-responsive mt-2 d-none">
                            <table class="table table-sm"><thead><tr><th>{{ __('ui.nav.styles') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.common.quantity') }}</th></tr></thead><tbody></tbody></table>
                        </div>
                    </section>

                    <section class="border-top mt-3 pt-3" aria-labelledby="mr-items-title">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <h6 id="mr-items-title" class="text-primary mb-0">{{ __('ui.workflow.items_needed') }} <span class="text-danger">*</span></h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-request-item">
                                <i data-feather="plus" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.add_item') }}
                            </button>
                        </div>
                        <div class="table-responsive mb-2">
                            <table class="table table-sm table-bordered align-middle">
                                <thead><tr><th style="min-width:320px">{{ __('ui.common.item') }}</th><th style="min-width:100px">{{ __('ui.workflow.size') }}</th><th style="min-width:120px">{{ __('ui.workflow.color') }}</th><th style="width:150px">{{ __('ui.common.quantity') }}</th><th style="min-width:80px">{{ __('ui.common.unit') }}</th><th style="min-width:170px">{{ __('ui.common.notes') }}</th><th style="width:45px"></th></tr></thead>
                                <tbody id="request-items"></tbody>
                            </table>
                        </div>
                        <div id="mr-material-totals" class="mb-2"></div>
                        <div class="row g-2 align-items-start">
                            <label for="request_reason" class="col-md-2 col-form-label col-form-label-sm">{{ __('ui.common.request_reason') }} <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <textarea class="form-control form-control-sm" id="request_reason" name="reason" rows="2" maxlength="5000" required placeholder="{{ __('ui.common.explain_need') }}"></textarea>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('super.material-requests.index') }}">{{ __('ui.common.cancel') }}</a>
                    <button type="submit" class="btn btn-primary btn-sm" id="submitMaterialRequest">
                        <i data-feather="send" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.workflow.submit_request') }}
                    </button>
                </div>
            </form>
        </div>
    @endif
    @endcan

    @can('material-requests.create')
        <div class="modal fade" id="materialRequestProductPicker" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">{{ __('ui.workflow.choose_item_option') }}</h5>
                            <small class="text-muted">{{ __('ui.workflow.items_needed') }}</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table id="materialRequestProductPickerTable" class="table table-bordered table-hover align-middle w-100">
                                <thead><tr><th>{{ __('ui.workflow.item_code') }}</th><th>{{ __('ui.workflow.item_name') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="mrBomPicker" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5>{{ __('variants.choose_bom') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body"><p class="text-muted">{{ __('variants.mo_bom_picker_hint') }}</p><div class="table-responsive"><table id="mrBomPickerTable" class="table table-bordered w-100"><thead><tr><th>{{ __('variants.bom_number') }}</th><th>Style</th><th>{{ __('variants.bom_type') }}</th><th>{{ __('ui.workflow.bom_version') }}</th><th>{{ __('ui.common.actions') }}</th></tr></thead></table></div></div>
        </div></div></div>
        <div class="modal fade" id="mrSourcePicker" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="mrSourcePickerTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
                <div class="modal-body"><div class="table-responsive">
                    <table id="mrSourcePickerTable" class="table table-bordered table-hover align-middle w-100">
                        <thead><tr><th>{{ __('ui.workflow.material_order_no') }}</th><th>{{ __('ui.workflow.item_code') }}</th><th>{{ __('ui.workflow.item_name') }}</th><th>{{ __('ui.nav.styles') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.common.quantity') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead>
                    </table>
                </div></div>
            </div></div>
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
            const availableBoms = @json($availableBoms);
            const productionOrders = @json($productionOrders);
            const createPage = document.getElementById('createRequestPage');
            const viewModal = new bootstrap.Modal(document.getElementById('viewRequestModal'));
            let currentIssueUrl = null;
            let currentRequest = null;
            let bomGenerated = false;
            let itemIndex = 0;

            function orderSummary(details) {
                const section = $('<div class="mt-3">');
                if (!details?.length) return section;
                section.append($('<h6>').text(@json(__('variants.request_summary'))));
                const number = value => Number(value || 0).toLocaleString(undefined, {maximumFractionDigits: 4});
                const makeTable = (headers, rows) => {
                    const table = $('<table class="table table-sm table-bordered align-middle">');
                    const head = $('<tr>'); headers.forEach(h => head.append($('<th class="text-nowrap">').text(h)));
                    table.append($('<thead>').append(head)); const body = $('<tbody>');
                    rows.forEach(values => { const tr = $('<tr>'); values.forEach(v => tr.append($('<td class="text-nowrap">').text(v ?? '—'))); body.append(tr); });
                    table.append(body); return $('<div class="table-responsive mb-3">').append(table);
                };
                details.forEach(detail => {
                    const line = $('<div class="text-nowrap mb-1">').text(
                        (detail.style || '—') + ' / ' +
                        (detail.color || '—') + ' / ' +
                        (detail.size || '—') + ' / ' +
                        number(detail.quantity)
                    );
                    section.append(line);
                });
                const materials = details.flatMap(d => d.materials || []);
                if (materials.length && !createPage) {
                    section.append($('<h6>').text(@json(__('variants.material_calculation'))));
                    section.append($('<p class="small text-muted">').text(@json(__('variants.calculation_hint'))));
                    section.append(makeTable([@json(__('ui.nav.styles')), @json(__('ui.workflow.color')), @json(__('ui.workflow.size')), @json(__('ui.common.item')), @json(__('ui.common.unit')), @json(__('variants.order_qty')), 'Consumption', @json(__('variants.bom_output')), @json(__('variants.allowance'))+' %', @json(__('variants.required')), @json(__('ui.workflow.issued_quantity')), @json(__('variants.request_qty'))], materials.map(m => [m.style,m.color || '—',m.size || '—',m.code+' — '+m.name,m.unit,number(m.order_quantity),number(m.consumption),number(m.bom_output_quantity),number(m.allowance_percent),number(m.required_quantity),number(m.issued_quantity),number(m.request_quantity)])));
                }
                return section;
            }
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

            let activeProductRow = null;
            let productPicker = null;
            let productPickerTable = null;

            function addItemRow(item = {}) {
                const index = itemIndex++;
                const row = $('<tr class="request-material-row">').data('calculation', item.calculation || null);
                const selectedProduct = products.find(product => String(product.id) === String(item.product_id)) || null;
                const itemCell = $('<div class="input-group input-group-sm">')
                    .append($('<input>', { type: 'hidden', class: 'request-item-product', name: `items[${index}][product_id]`, required: true, value: item.product_id ?? '' }))
                    .append($('<input>', { type: 'text', class: 'form-control request-item-label bg-light', readonly: true, required: true, placeholder: '{{ __('ui.workflow.choose_item_option') }}', value: item.parent_label || selectedProduct?.parent_label || item.label || selectedProduct?.label || '' }))
                    .append($('<button>', { type: 'button', class: 'btn btn-outline-primary open-request-product-picker', html: '<i data-feather="search" class="icon-sm"></i>', title: '{{ __('ui.common.search') }}', 'aria-label': '{{ __('ui.common.search') }}' }));
                row.append($('<td>').append(itemCell));
                row.append($('<td>').append($('<input>', {type:'text', class:'form-control form-control-sm request-item-size', name:`items[${index}][size]`, maxlength:40, value:item.size ?? selectedProduct?.size ?? ''})));
                row.append($('<td>').append($('<input>', {type:'text', class:'form-control form-control-sm request-item-color', name:`items[${index}][color]`, maxlength:80, value:item.color ?? selectedProduct?.color ?? ''})));

                row.append($('<td>').append($('<input>', { type: 'text', inputmode: 'decimal', autocomplete: 'off', class: 'form-control form-control-sm', name: `items[${index}][quantity]`, required: true, placeholder: '{{ __('ui.common.quantity') }}', value: item.quantity ?? '' })));
                row.append($('<td>').append($('<span>', {class: 'request-item-unit', text: item.unit || selectedProduct?.unit || '—'})));
                row.append($('<td>').append($('<input>', { type: 'text', class: 'form-control form-control-sm', name: `items[${index}][note]`, maxlength: 255, placeholder: '{{ __('ui.common.optional') }}', value: item.note ?? '' })));
                row.append($('<td class="text-center text-nowrap">').append($('<button>', {type:'button', class:'btn btn-sm btn-outline-primary me-1', text:'+ '+@json(__('variants.variant'))}).on('click', () => addItemRow({product_id: row.find('.request-item-product').val(), parent_label:row.find('.request-item-label').val(), unit:row.find('.request-item-unit').text(), color:row.find('.request-item-color').val()}))).append($('<button>', { type: 'button', class: 'btn btn-sm btn-outline-danger remove-request-item', html: '<i data-feather="x"></i>' })));
                $('#request-items .mr-no-remaining-items').remove();
                $('#request-items').append(row);
                regroupRequestItems();
                refreshMaterialTotals();
                if (window.feather) feather.replace();
            }

            function requestRowData(row) {
                const product = products.find(p => String(p.id) === String(row.find('.request-item-product').val()));
                return {
                    key: String(product?.parent_product_id || product?.id || ''),
                    label: product?.parent_label || row.find('.request-item-label').val() || '—',
                    size: row.find('.request-item-size').val() || '', color: row.find('.request-item-color').val() || '',
                    quantity: parseQuantity(row.find('input[name$="[quantity]"]').val()),
                    unit: row.find('.request-item-unit').text(), calculation: row.data('calculation')
                };
            }
            function regroupRequestItems() {
                const body = $('#request-items');
                body.children('.mr-item-group').remove();
                const rows = body.children('.request-material-row').get().sort((a,b) => {
                    const x = requestRowData($(a)), y = requestRowData($(b));
                    const sizes = ['XS','S','M','L','XL','XXL','2XL','3XL','4XL','5XL'];
                    const rank = size => {const i = sizes.indexOf(size.toUpperCase()); return i < 0 ? 100 : i;};
                    return x.label.localeCompare(y.label,undefined,{numeric:true}) || rank(x.size)-rank(y.size) || x.size.localeCompare(y.size,undefined,{numeric:true}) || x.color.localeCompare(y.color,undefined,{numeric:true});
                });
                let previous = null;
                rows.forEach(row => {
                    const line = requestRowData($(row));
                    if (line.key !== previous) {
                        body.append($('<tr class="mr-item-group table-light">').append($('<th colspan="7">').text(line.label)));
                        previous = line.key;
                    }
                    body.append(row);
                });
            }
            function renderLiveCalculation() {
                const target = $('#mr-live-calculation').empty();
                const rows = $('#request-items tr.request-material-row').get().map(row=>requestRowData($(row))).filter(row=>row.key);
                if (!rows.length) return;
                target.append($('<h6>').text(@json(__('variants.material_calculation'))));
                target.append($('<p class="small text-muted">').text(@json(__('variants.same_request_rows'))));
                const table = $('<table class="table table-sm table-bordered align-middle">');
                const header = $('<tr>');
                [@json(__('ui.common.item')), @json(__('ui.workflow.size')), @json(__('ui.workflow.color')), @json(__('variants.order_qty')), 'Consumption', @json(__('variants.allowance'))+' %', @json(__('variants.request_qty')), @json(__('ui.common.unit'))].forEach(label=>header.append($('<th class="text-nowrap">').text(label)));
                table.append($('<thead>').append(header));
                const body = $('<tbody>'); let previous = null;
                const number = value => value === undefined || value === null ? '—' : Number(value).toLocaleString(undefined,{maximumFractionDigits:4});
                rows.forEach(line=>{
                    if (line.key !== previous) body.append($('<tr class="table-light">').append($('<th colspan="8">').text(line.label)));
                    previous = line.key;
                    const tr = $('<tr>');
                    [line.label,line.size || '—',line.color || '—',number(line.calculation?.order_quantity),number(line.calculation?.consumption),number(line.calculation?.allowance_percent),number(Number.isFinite(line.quantity)?line.quantity:0),line.unit].forEach(value=>tr.append($('<td class="text-nowrap">').text(value)));
                    body.append(tr);
                });
                target.append($('<div class="table-responsive">').append(table.append(body)));
            }
            function loadCalculatedItems(result) {
                const body = $('#request-items').empty();
                itemIndex = 0;
                const breakdown = result.breakdown ?? result.source_details?.flatMap(detail => detail.materials || []) ?? [];
                const items = breakdown.length
                    ? breakdown.filter(line => Number(line.request_quantity) > 0).map(line => ({
                        product_id: line.product_id,
                        color: line.color || '',
                        size: line.size || '',
                        quantity: line.request_quantity,
                        unit: line.unit,
                        label: line.code + ' — ' + line.name,
                        calculation: line,
                        note: [line.style, line.operation].filter(Boolean).join(' / '),
                    }))
                    : (result.items ?? []).filter(item => Number(item.quantity) > 0);

                items.forEach(addItemRow);
                if (!items.length) {
                    const message = breakdown.length
                        ? @json(__('variants.mr_bom_no_remaining_items'))
                        : @json(__('variants.raw_mo_no_active_bom_materials'));
                    body.append($('<tr class="mr-no-remaining-items">')
                        .append($('<td colspan="7" class="text-center text-danger fw-semibold py-2">').text(message)));
                }
                refreshMaterialTotals();
            }
            function refreshMaterialTotals() {
                const groups = new Map();
                $('#request-items tr.request-material-row').each(function () {
                    const id = $(this).find('.request-item-product').val();
                    if (!id) return;
                    const product = products.find(p => String(p.id) === String(id));
                    const key = product?.parent_product_id || id;
                    const group = groups.get(String(key)) || {label: product?.parent_label || $(this).find('.request-item-label').val(), unit: $(this).find('.request-item-unit').text(), quantity:0, lines:new Map()};
                    const quantity = parseQuantity($(this).find('input[name$="[quantity]"]').val());
                    const amount = Number.isFinite(quantity) ? quantity : 0;
                    group.quantity += amount;
                    const size = $(this).find('.request-item-size').val() || '—';
                    const color = $(this).find('.request-item-color').val() || '—';
                    const lineKey = JSON.stringify([size,color,group.unit]);
                    const line = group.lines.get(lineKey) || {size,color,unit:group.unit,quantity:0};
                    line.quantity += amount;
                    group.lines.set(lineKey,line);
                    groups.set(String(key),group);
                });
                const target = $('#mr-material-totals').empty();
                renderLiveCalculation();
                if (!groups.size) return;
                target.append($('<h6>').text(@json(__('variants.request_item_totals'))));
                const table = $('<table class="table table-sm table-bordered">');
                const body = $('<tbody>');
                const heading = $('<tr>');
                [@json(__('ui.common.item')), @json(__('ui.workflow.size')), @json(__('ui.workflow.color')), @json(__('ui.common.quantity')), @json(__('ui.common.unit'))].forEach(label=>heading.append($('<th>').text(label)));
                table.append($('<thead>').append(heading));
                groups.forEach(group => {
                    [...group.lines.values()].sort((a,b)=>a.size.localeCompare(b.size,undefined,{numeric:true}) || a.color.localeCompare(b.color)).forEach(line => {
                        body.append($('<tr>').append($('<td>').text(group.label), $('<td>').text(line.size), $('<td>').text(line.color), $('<td class="text-end">').text(line.quantity.toLocaleString(undefined,{maximumFractionDigits:4})), $('<td>').text(line.unit)));
                    });
                    body.append($('<tr class="table-light fw-bold">').append($('<td colspan="3">').text(@json(__('variants.total'))+' — '+group.label), $('<td class="text-end">').text(group.quantity.toLocaleString(undefined,{maximumFractionDigits:4})), $('<td>').text(group.unit)));
                });
                target.append($('<div class="table-responsive">').append(table.append(body)));
            }
            $('#request-items').on('input', 'input', refreshMaterialTotals);
            $('#request-items').on('change', 'input', () => {regroupRequestItems(); refreshMaterialTotals();});
            $('#request-items').on('click', 'button', () => setTimeout(()=>{regroupRequestItems();refreshMaterialTotals();},0));
            function openProductPicker(button) {
                activeProductRow = button.closest('tr');
                if (!productPickerTable) {
                    productPickerTable = $('#materialRequestProductPickerTable').DataTable({
                        data: products.filter(p => !p.parent_product_id),
                        responsive: true,
                        pageLength: 10,
                        lengthMenu: [5, 10, 50, 100],
                        order: [[0, 'asc']],
                        columns: [
                            { data: 'code', render: $.fn.dataTable.render.text() },
                            { data: 'name', render: $.fn.dataTable.render.text() },
                            { data: 'unit', defaultContent: '—', render: $.fn.dataTable.render.text() },
                            { data: null, orderable: false, searchable: false, className: 'text-end', render: (data, type, product) => `<button type="button" class="btn btn-primary btn-sm choose-request-product" data-id="${Number(product.id)}">{{ __('ui.common.select') }}</button>` }
                        ],
                        drawCallback: function() { if (window.feather) feather.replace(); }
                    });
                }
                productPicker.show();
            }

            @can('material-requests.create')
                productPicker = bootstrap.Modal.getOrCreateInstance(document.getElementById('materialRequestProductPicker'));
                $('#materialRequestProductPicker').on('shown.bs.modal', function() {
                    const element = this;
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    element.style.zIndex = '1065';
                    if (backdrops.length > 1) backdrops[backdrops.length - 1].style.zIndex = '1060';
                    if (productPickerTable) productPickerTable.columns.adjust().responsive?.recalc();
                });
                $(document).on('click', '.open-request-product-picker, .request-item-label', function() {
                    openProductPicker($(this));
                });
                $(document).on('click', '.choose-request-product', function() {
                    const product = products.find(item => String(item.id) === String($(this).data('id')));
                    if (product && activeProductRow?.length) {
                        activeProductRow.find('.request-item-product').val(product.id);
                        activeProductRow.find('.request-item-label').val(product.label);
                        activeProductRow.find('.request-item-color').val(product.color || '');
                        activeProductRow.find('.request-item-size').val(product.size || '');
                        activeProductRow.find('.request-item-unit').text(product.unit || '—');
                        activeProductRow.data('calculation', null);
                        regroupRequestItems(); refreshMaterialTotals();
                        productPicker.hide();
                    }
                });
                let sourceKind = 'mo', sourceTable = null;
                const sourcePicker = bootstrap.Modal.getOrCreateInstance(document.getElementById('mrSourcePicker'));
                $('.mr-source-search').on('click', function () {
                    sourceKind = $(this).data('kind');
                    $('#mrSourcePickerTitle').text(sourceKind === 'mo' ? @json(__('ui.workflow.choose_production_order')) : @json(__('ui.workflow.bom_product')));
                    const rows = sourceKind === 'mo' ? productionOrders : products.filter(p => p.finished_good).map(p => ({...p, number: '—', quantity: null}));
                    if (!sourceTable) {
                        sourceTable = $('#mrSourcePickerTable').DataTable({
                            data: rows, pageLength: 10, lengthMenu: [10, 25, 50, 100], scrollX: true,
                            columns: ['number', 'code', 'name', 'style', 'color', 'size', 'quantity', 'unit'].map(key => ({
                                data: key, defaultContent: '—',
                                render: function (value, type, row) {
                                    const lines = key !== 'number' && row.details ? row.details.map(detail => detail[key]) : [value];
                                    if (type !== 'display') return lines.map(v => v ?? '').join(' ');
                                    return lines.map(v => {
                                        const text = key === 'quantity' && v !== null && v !== undefined
                                            ? Number(v).toLocaleString(undefined, {maximumFractionDigits: 4}) : (v || '—');
                                        return $('<div>').addClass('mr-source-detail-line').text(text)[0].outerHTML;
                                    }).join('');
                                }
                            })).concat([
                                {data: 'id', orderable: false, searchable: false, render: id => `<button type="button" class="btn btn-primary btn-sm mr-select-source" data-id="${Number(id)}">{{ __('ui.common.select') }}</button>`}
                            ])
                        });
                    } else sourceTable.clear().rows.add(rows).draw();
                    sourceTable.search('').draw(); sourcePicker.show();
                });
                $('#mrSourcePicker').on('shown.bs.modal', function () {
                    this.style.zIndex = '1065';
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    if (backdrops.length > 1) backdrops[backdrops.length - 1].style.zIndex = '1060';
                    sourceTable?.columns.adjust();
                }).on('hidden.bs.modal', function () {

                });
                $(document).on('click', '.mr-select-source', function () {
                    const source = (sourceKind === 'mo' ? productionOrders : products).find(row => String(row.id) === String($(this).data('id')));
                    if (!source) return;
                    const target = sourceKind === 'mo' ? '#source_production_order' : '#bom_output_product';
                    if (sourceKind === 'product') {
                        $('#source_production_order,#source_production_order_label').val('');
                    } else {
                        $('#bom_output_product,#bom_output_product_label').val('');
                    }
                    $(target).val(source.id).trigger('change');
                    $(target + '_label').val(source.label);
                    if (sourceKind === 'product') updateMrBomOptions(source.style);
                    sourcePicker.hide();
                });
                addItemRow();
                function updateMrBomOptions(style) {
                    const select = $('#request_bom_choice').empty().append($('<option>', {value:'', text:@json(__('ui.common.select'))}));
                    availableBoms.filter(bom => String(bom.style || '') === String(style || '')).forEach(bom => {
                        select.append($('<option>', {value:bom.id, text:`${bom.number} — ${bom.type}`}));
                    });
                    if (select.find('option').length === 2) select.val(select.find('option').last().val());
                }
                function clearBomSnapshot() {
                    $('#request_bom_id, #request_production_order_id, #request_output_product_id, #request_bom_version, #request_production_quantity').val('');
                    $('#bom-generation-summary').text('');
                    $('#mr-order-source-details').addClass('d-none').empty();
                    $('#mr-independent-bom').toggle(!$('#source_production_order').val());
                    if (bomGenerated) { $('#request-items').empty(); itemIndex = 0; addItemRow(); bomGenerated = false; }
                    showSelectedOrder();
                }
                $('#bom_output_product, #production_quantity_input, #source_production_order, #request_bom_choice').on('change input', clearBomSnapshot);
                $('#request_needed_at').on('change', function () {
                    if (!$('#source_production_order').val() && bomGenerated) clearBomSnapshot();
                });
                $('#generate-bom-requirements').on('click', function() {
                    // A selected MO always calculates its complete order, even with only one detail.
                    if ($('#source_production_order').val()) {
                        $('#load-production-order-materials').trigger('click');
                        return;
                    }
                    if (!$('#bom_output_product').val()) {
                        Swal.fire({icon: 'warning', title: @json(__('ui.workflow.failure')), text: @json(__('ui.workflow.bom_choose_product'))});
                        return;
                    }
                    if (!$('#request_bom_choice').val()) {
                        Swal.fire({icon: 'warning', title: @json(__('ui.workflow.failure')), text: @json(__('variants.bom_selection_required'))});
                        return;
                    }
                    clearBomSnapshot();
                    const button = $(this).prop('disabled', true);
                    $.get(@json(route('super.material-requests.bom-requirements')), {
                        product_id: $('#bom_output_product').val(),
                        bill_of_material_id: $('#request_bom_choice').val(),
                        production_quantity: $('#production_quantity_input').val(),
                        needed_at: $('#request_needed_at').val()
                    }).done(function(result) {
                        loadCalculatedItems(result);
                        $('#mr-order-source-details').empty().append(orderSummary(result.source_details)).removeClass('d-none');
                        $('#request_bom_id').val(result.bill_of_material_id);
                        $('#request_production_order_id').val('');
                        $('#request_output_product_id').val(result.output_product_id);
                        $('#request_bom_version').val(result.bom_version);
                        $('#request_production_quantity').val(result.production_quantity);
                        $('#bom-generation-summary').text(`${@json(__('ui.workflow.bom_snapshot_summary'))}: ${result.output_product} × ${Number(result.production_quantity).toLocaleString()} — ${result.bom_number} (${result.bom_type}), v${result.bom_version}`);
                        bomGenerated = true;
                    }).fail(xhr => Swal.fire({icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {}).flat().join('\n') || '{{ __('ui.workflow.active_bom_not_found') }}'}))
                    .always(() => button.prop('disabled', false));
                });
                function showSelectedOrder() {
                    const order = productionOrders.find(row => String(row.id) === String($('#source_production_order').val()));
                    $('#mr-independent-bom').toggle(!order);
                    if (!order) return;
                    $('#request_production_order_id').val(order.id);
                    $('#source_production_order_label').val(order.number);
                    $('#bom-generation-summary').text(@json(__('ui.workflow.material_order_no')) + ': ' + order.number);
                    $('#mr-order-source-details').empty().append(orderSummary(order.details)).removeClass('d-none');
                }
                $('#source_production_order').on('change', showSelectedOrder);
                let bomPickerTable = null;
                $('#load-production-order-materials').on('click', function() {
                    const order = productionOrders.find(row => String(row.id) === String($('#source_production_order').val()));
                    if (!order) { Swal.fire({icon:'warning',text:@json(__('ui.workflow.choose_production_order'))}); return; }
                    if (!order.boms.length) {
                        Swal.fire({icon: 'info', text: @json(__('variants.mo_add_bom_before_request'))});
                        return;
                    }
                    if (order.boms.length === 1) {
                        loadOrderBom(order, order.boms[0].id);
                        return;
                    }
                    if (!bomPickerTable) bomPickerTable = $('#mrBomPickerTable').DataTable({
                        data:[], pageLength:10, columns:[
                            {data:'number',render:$.fn.dataTable.render.text()},
                            {data:'style',defaultContent:'—',render:$.fn.dataTable.render.text()},
                            {data:'type',defaultContent:'—',render:$.fn.dataTable.render.text()},
                            {data:'version',render:$.fn.dataTable.render.text()},
                            {data:'id',orderable:false,searchable:false,render:id=>'<button type="button" class="btn btn-sm btn-primary mr-select-bom" data-id="'+Number(id)+'">'+@json(__('variants.select_bom'))+'</button>'}
                        ]
                    });
                    bomPickerTable.clear().rows.add(order.boms || []).draw();
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('mrBomPicker')).show();
                });
                $('#mrBomPicker').on('shown.bs.modal',()=>bomPickerTable?.columns.adjust());
                $(document).on('click', '.mr-select-bom', function () {
                    const order = productionOrders.find(row => String(row.id) === String($('#source_production_order').val()));
                    if (order) loadOrderBom(order, $(this).data('id'), $(this));
                });
                function loadOrderBom(order, bomId, button = $('#load-production-order-materials')) {
                    button.prop('disabled', true);
                    const orderId = String(order.id);
                    $.get(@json(route('super.material-requests.index')) + `/material-orders/${orderId}/requirements`, {bill_of_material_id: bomId})
                        .done(result => {
                            if (String(orderId) !== String($('#source_production_order').val())) return;
                            loadCalculatedItems(result);
                            $('#request_bom_id').val(result.bill_of_material_id);
                            $('#request_output_product_id').val(result.output_product_id);
                            $('#request_bom_version').val(result.bom_version);
                            $('#request_production_quantity').val(result.production_quantity);
                            $('#bom_output_product,#bom_output_product_label').val('');
                            $('#production_quantity_input').val(result.production_quantity);
                            showSelectedOrder();
                            $('#request_production_order_id').val(result.production_order_id);
                            $('#bom-generation-summary').text(`${order.number} — ${result.bom_number} (${result.bom_type})`);
                            bomGenerated = true;
                            bootstrap.Modal.getInstance(document.getElementById('mrBomPicker'))?.hide();
                        })
                        .fail(xhr=>Swal.fire({icon:'error',text:xhr.responseJSON?.message || @json(__('ui.workflow.production_order_materials_failed'))}))
                        .always(()=>button.prop('disabled',false));
                }
                $('#add-request-item').on('click', addItemRow);
                $(document).on('click', '.remove-request-item', function() {
                    if ($('#request-items tr.request-material-row').length > 1) $(this).closest('tr').remove();
                });
                $('#materialRequestForm').on('submit', function(e) {
                    e.preventDefault();
                    const quantities = $('#request-items input[name$="[quantity]"]');
                    let quantitiesValid = quantities.length > 0;
                    quantities.each(function() {
                        const quantity = parseQuantity(this.value);
                        if (!Number.isFinite(quantity) || quantity <= 0) {
                            quantitiesValid = false;
                            return false;
                        }
                        this.value = Number(quantity.toFixed(4));
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
                            window.location.assign(@json(route('super.material-requests.index')));
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

            @can('material-requests.create')
                $('#materialRequestProductPicker').on('hidden.bs.modal', function() {
                    this.style.removeProperty('z-index');
                    activeProductRow = null;
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
                    { data: 'required_items', orderable: false, searchable: false, defaultContent: '—' },
                    { data: 'items_count', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'status_label', name: 'status', searchable: false },
                    { data: 'purchase_status_label', name: 'purchase_status', searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-end mobile-list-row-actions' }
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

            $('#exportMaterialRequests').on('click', function() {
                const url = new URL(this.href, window.location.origin);
                const fromDate = $('#from_date').val(), toDate = $('#to_date').val();
                if (fromDate) url.searchParams.set('from_date', fromDate); else url.searchParams.delete('from_date');
                if (toDate) url.searchParams.set('to_date', toDate); else url.searchParams.delete('to_date');
                this.href = url.toString();
            });

            $(document).on('click', '.btn-view-request', function() {
                $.get($(this).data('url')).done(function(request) {
                    currentRequest = request;
                    currentIssueUrl = request.issue_url;
                    const status = { pending: '{{ __('ui.workflow.approval_pending') }}', approved: '{{ __('ui.workflow.approval_approved') }}', rejected: '{{ __('ui.workflow.approval_rejected') }}' }[request.status];
                    const purchaseStatus = { not_processed: '{{ __('ui.workflow.purchasing_not_processed') }}', partially_processed: '{{ __('ui.workflow.purchasing_partially_processed') }}', processed: '{{ __('ui.workflow.purchasing_fully_processed') }}' }[request.purchase_status] || '—';
                    const bomSummary = request.bom_version ? `<dt class="col-sm-4">{{ __('ui.workflow.bom_used') }}</dt><dd class="col-sm-8">${$('<div>').text(request.bom_number || 'BOM').html()}${request.bom_type ? ` — ${$('<div>').text(request.bom_type).html()}` : ''} — ${$('<div>').text(request.output_product).html()} × ${Number(request.production_quantity).toLocaleString()}${request.production_order_number ? ` — ${$('<div>').text(request.production_order_number).html()}` : ''}</dd>` : '';
                    const rows = request.items.map(item => `<tr><td>${$('<div>').text(item.code).html()} — ${$('<div>').text(item.product).html()}<small class="d-block text-muted">${$('<div>').text([item.color,item.size].filter(Boolean).join(' / ')).html()}</small></td><td class="text-end">${item.quantity} ${$('<div>').text(item.unit).html()}</td><td class="text-end">${Number(item.issued_quantity).toLocaleString()}</td><td>${$('<div>').text(item.note).html()}</td></tr>`).join('');
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
                    $('#viewRequestBody').append(orderSummary(request.source_details));
                    viewModal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.request_detail_load_failed') }}' }));
            });

            @can('material-requests.issue')
                $('#issueMaterialsButton').on('click', function() {
                    const remaining = currentRequest.items.filter(item => item.remaining_quantity > 0);
                    const inputs = remaining.map(item => `
                        <div class="row align-items-center mb-2">
                            <div class="col-7">
                                ${$('<div>').text(item.code + ' — ' + item.product).html()}
                                <small class="d-block text-muted">${$('<div>').text([item.color, item.size].filter(Boolean).join(' / ')).html()}</small>
                                <small class="d-block text-muted">{{ __('ui.workflow.remaining_quantity') }}: ${Number(item.remaining_quantity).toLocaleString()}</small>
                            </div>
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

            @can('material-requests.delete')
                $('#material-requests-table').on('click', '.btn-delete-request', async function () {
                    const button = $(this);
                    const result = await Swal.fire({
                        icon: 'warning',
                        title: @json(__('variants.mr_delete_confirm')),
                        showCancelButton: true,
                        confirmButtonText: @json(__('ui.common.delete')),
                        cancelButtonText: @json(__('ui.common.cancel')),
                    });
                    if (!result.isConfirmed) return;

                    button.prop('disabled', true);
                    $.ajax({
                        url: button.data('url'),
                        method: 'DELETE',
                        headers: {'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json'},
                    }).done(response => {
                        Swal.fire({icon: 'success', text: response.message});
                        table.ajax.reload(null, false);
                    }).fail(xhr => {
                        Swal.fire({icon: 'error', text: xhr.responseJSON?.errors?.material_request?.[0]
                            || xhr.responseJSON?.message || @json(__('variants.mr_delete_failed'))});
                        if (xhr.status === 422 || xhr.status === 404) table.ajax.reload(null, false);
                    }).always(() => button.prop('disabled', false));
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
            @can('material-requests.create')
                const initialMo = new URLSearchParams(window.location.search).get('mo_id');
                if (createPage && initialMo && /^\d+$/.test(initialMo)) {
                    const order = productionOrders.find(row => row.line_ids.some(id => String(id) === initialMo));
                    if (order) {
                        $('#source_production_order').val(order.id);
                        showSelectedOrder();
                    }
                }
            @endcan
        });
    </script>
@endpush
