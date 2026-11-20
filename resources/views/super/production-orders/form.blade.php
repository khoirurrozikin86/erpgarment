@extends('layouts.admin')
@section('title', __('ui.workflow.material_order_form_title'))
@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('ui.nav.production') }}</li>
            <li class="breadcrumb-item"><a href="{{ route('super.material-orders.index') }}">{{ __('ui.workflow.material_order') }}</a></li>
            <li class="breadcrumb-item active">{{ $order ? __('ui.common.edit') : __('ui.workflow.material_order_admin') }}</li>
        </ol>
    </nav>
@endsection
@push('styles')
    <style>
        .material-order-section {
            border: 1px solid #e8edf3;
            border-radius: .65rem;
            padding: 1rem;
            background: #fff
        }

        .material-order-section-title {
            font-size: .9rem;
            font-weight: 600;
            color: #344054;
            margin-bottom: .9rem
        }
        #finishedGoodsPickerTable tbody tr:has(.choose-finished-good) { cursor: pointer; }
        #materialOrderForm .material-order-section-title { margin-bottom: .75rem; }
        #materialOrderForm .row { --bs-gutter-x: 1rem; --bs-gutter-y: .75rem; }
        #materialOrderForm .mo-field { display: grid; grid-template-columns: 38% minmax(0, 1fr); align-items: center; column-gap: .5rem; }
        #materialOrderForm .mo-field > .form-label { grid-column: 1; grid-row: 1; margin: 0; font-size: .78rem; }
        #materialOrderForm .mo-field > .form-control,
        #materialOrderForm .mo-field > .form-select,
        #materialOrderForm .mo-field > .input-group { grid-column: 2; grid-row: 1; min-width: 0; }
        #materialOrderForm .form-control, #materialOrderForm .form-select { font-size: .8rem; min-height: 31px; padding: .25rem .5rem; }
        #materialOrderForm .form-select { padding-right: 1.7rem; }
        #materialOrderForm .btn { font-size: .78rem; padding: .25rem .5rem; }
        #materialOrderForm .mo-image-field > .form-text,
        #materialOrderForm .mo-image-field > .img-thumbnail,
        #materialOrderForm #mo_item_image_empty { grid-column: 2; }
        #materialOrderForm .mo-image-field > .form-text { font-size: .75rem; }
        #materialOrderForm #mo_size_matrix .table-responsive { max-height: 200px; overflow: auto; }
        #materialOrderForm #mo_size_matrix th { position: sticky; top: 0; background: #fff; z-index: 1; }
        #materialOrderForm #mo_size_matrix td { padding: .2rem; }
        @media (max-width: 575.98px) {
            #materialOrderForm .mo-field { grid-template-columns: 34% minmax(0, 1fr); }
            #materialOrderForm #mo_size_matrix table { min-width: 450px; }
        }
    </style>
@endpush
@section('content')
    <form id="materialOrderForm" class="card mb-3" method="post" enctype="multipart/form-data"
        action="{{ $order ? route('super.material-orders.update', $order) : route('super.material-orders.store') }}">
        @csrf
        @if ($order)
            @method('PUT')
        @endif
        <input type="hidden" id="mo_image_only" name="image_only" value="0">
        <input type="hidden" name="output_product_id" id="output_product_id">
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ $order ? __('ui.common.edit').' · '.$order->number : __('ui.workflow.material_order_form_title') }}</h5>
        </div>
        <div class="card-body">
            <div id="mo_image_only_notice" class="alert alert-info d-none">{{ __('variants.image_only_notice') }}</div>
            <section class="material-order-section mb-3">
                <h6 class="material-order-section-title">{{ __('ui.workflow.order_information') }}</h6>
                <div class="row g-3">
                    <div class="col-lg-6 mo-field"><label for="number"
                            class="form-label">{{ __('ui.workflow.material_order_no') }} <span
                                class="text-danger">*</span></label><input class="form-control" id="number"
                            name="number" maxlength="100" required></div>
                    <div class="col-lg-6 mo-field"><label for="po_date"
                            class="form-label">{{ __('ui.workflow.po_date') }} <span
                                class="text-danger">*</span></label><input class="form-control" type="date"
                            id="po_date" name="po_date" required></div>
                    <div class="col-lg-6 mo-field"><label for="due_date"
                            class="form-label">{{ __('ui.workflow.material_order_due_date') }} <span
                                class="text-danger">*</span></label><input class="form-control" type="date"
                            id="due_date" name="due_date" required></div>
                    <div class="col-lg-6 mo-field"><label for="buyer_po_number"
                            class="form-label">{{ __('ui.workflow.buyer_po_no') }}</label><input
                            class="form-control" id="buyer_po_number" name="buyer_po_number" maxlength="100">
                    </div>
                    <div class="col-lg-6 mo-field"><label for="currency"
                            class="form-label">{{ __('ui.workflow.currency') }} <span
                                class="text-danger">*</span></label><select class="form-select" id="currency"
                            name="currency" required>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency }}">{{ $currency }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6 mo-field"><label for="no_cbd"
                            class="form-label">{{ __('ui.workflow.no_cbd') }}</label><input
                            class="form-control" id="no_cbd" name="no_cbd" maxlength="100"></div>
                    <div class="col-lg-6 mo-field"><label for="production-order-buyer"
                            class="form-label">{{ __('ui.workflow.buyer') }} <span
                                class="text-danger">*</span></label>
                        @include('super.production-orders.partials.buyer-field', ['selectedBuyer' => $order?->buyer])
                    </div>
                    <div class="col-lg-6 mo-field"><label for="destination"
                            class="form-label">{{ __('ui.workflow.destination') }}</label><input
                            class="form-control" id="destination" name="destination" maxlength="150"></div>
                </div>
            </section>
            <section class="material-order-section">
                <h6 class="material-order-section-title">{{ __('ui.workflow.detail_finished_good') }}</h6>
                <div class="row g-3">
                    <div class="col-lg-6 mo-field"><label class="form-label"
                            for="item_code_display">{{ __('ui.workflow.item_code') }} <span
                                class="text-danger">*</span></label>
                        <div class="input-group"><input class="form-control bg-light" id="item_code_display"
                                readonly data-bs-toggle="modal" data-bs-target="#finishedGoodPicker"
                                aria-haspopup="dialog" aria-controls="finishedGoodPicker" style="cursor:pointer"><button class="btn btn-primary" type="button"
                                id="searchFinishedGood" data-bs-toggle="modal" data-bs-target="#finishedGoodPicker"
                                aria-haspopup="dialog" aria-controls="finishedGoodPicker">{{ __('ui.common.search') }}</button></div>
                    </div>
                    <div class="col-lg-6 mo-field"><label class="form-label"
                            for="item_name_display">{{ __('ui.workflow.item_name') }}</label><input
                            class="form-control bg-light" id="item_name_display" readonly
                            data-bs-toggle="modal" data-bs-target="#finishedGoodPicker" aria-haspopup="dialog"
                            aria-controls="finishedGoodPicker" style="cursor:pointer"></div>
                    <div class="col-lg-6 mo-field mo-image-field">
                        <label class="form-label" for="mo_image">{{ __('variants.image') }}</label>
                        <input type="file" class="form-control mb-2" id="mo_image" name="image" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text mb-2">{{ __('variants.upload_hint') }}</div>
                        <img id="mo_item_image" class="img-thumbnail d-none" alt="{{ __('variants.image') }}" style="width:72px;height:72px;max-width:100%;object-fit:contain">
                        <div id="mo_item_image_empty" class="text-muted small">{{ __('variants.image_hint') }}</div>
                    </div>
                    <div class="col-lg-6 mo-field"><label class="form-label"
                            for="item_unit_display">{{ __('ui.common.unit') }}</label><input
                            class="form-control bg-light" id="item_unit_display" readonly></div>
                    <div class="col-lg-6 mo-field"><label class="form-label"
                            for="item_style_display">{{ __('ui.nav.styles') }}</label><input
                            class="form-control" id="item_style_display" name="style_code" maxlength="80"></div>
                    <div class="col-lg-6 mo-field"><label class="form-label" for="item_color_display">{{ __('ui.workflow.color') }}</label><input class="form-control" id="item_color_display" list="mo-master-colors" name="order_color" maxlength="80"></div>
                    <div class="col-lg-6 mo-field"><label class="form-label" for="item_size_display">{{ __('ui.workflow.size') }}</label><input class="form-control" id="item_size_display" list="mo-master-sizes" name="order_size" maxlength="40"></div>
                    <div class="col-12" id="mo_matrix_toggle">
                        <label class="form-check-label"><input class="form-check-input me-2" type="checkbox" id="mo_use_matrix" checked> {{ __('variants.matrix') }}</label>
                    </div>
                    <datalist id="mo-master-colors">
                        @foreach ($colors as $code)
                            <option value="{{ $code }}"></option>
                        @endforeach
                    </datalist>
                    <datalist id="mo-master-sizes">
                        @foreach ($sizes as $code)
                            <option value="{{ $code }}"></option>
                        @endforeach
                    </datalist>
                    <div class="col-12" id="mo_size_matrix">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <a href="{{ route('super.material-orders.import-template') }}" class="btn btn-outline-secondary btn-sm">{{ __('variants.template_lines') }}</a>
                            <label class="btn btn-outline-primary btn-sm mb-0" id="mo_import_label" for="mo_excel">{{ __('variants.import_lines') }}</label>
                            <input type="file" id="mo_excel" class="d-none" accept=".xlsx">
                        </div>
                        <p class="text-muted small mb-2">{{ __('variants.import_hint') }}</p>
                        <div class="table-responsive"><table class="table table-sm align-middle">
                            <thead><tr><th>{{ __('ui.workflow.bom_product') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.workflow.order_quantity') }}</th><th></th></tr></thead>
                            <tbody id="mo_size_rows"></tbody>
                        </table></div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="mo_add_size">{{ __('variants.add_size') }}</button>
                    </div>
                    <div class="col-lg-6 mo-field"><label class="form-label"
                            for="planned_quantity">{{ __('ui.workflow.order_quantity') }} <span
                                class="text-danger">*</span></label><input class="form-control text-end"
                            type="number" min="0.0001" step="0.0001" id="planned_quantity"
                            name="planned_quantity" value="0" required></div>
                    <div class="col-lg-6 mo-field"><label class="form-label"
                            for="price_per_unit">{{ __('ui.workflow.price_per_unit') }}</label><input
                            class="form-control text-end" type="number" min="0" step="0.0001"
                            id="price_per_unit" name="price_per_unit" value="0"></div>
                    <div class="col-lg-6 mo-field"><label class="form-label"
                            for="total_price_display">{{ __('ui.workflow.total_price') }}</label><input
                            class="form-control text-end bg-light" id="total_price_display" value="0"
                            readonly></div>
                    <div class="col-lg-6 mo-field"><label class="form-label"
                            for="description">{{ __('ui.workflow.description') }}</label><input
                            class="form-control" id="description" name="description" maxlength="5000"></div>
                </div>
            </section>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('super.material-orders.index') }}" class="btn btn-light btn-sm">{{ __('ui.common.cancel') }}</a>
            <button type="submit" id="saveMaterialOrder" class="btn btn-primary btn-sm">{{ __('ui.common.save') }}</button>
        </div>
    </form>

    <div class="modal fade" id="finishedGoodPicker" tabindex="-1" aria-labelledby="finishedGoodPickerTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="finishedGoodPickerTitle">{{ __('ui.workflow.choose_finished_good') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table id="finishedGoodsPickerTable"
                            class="table table-sm table-bordered table-hover align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('ui.workflow.item_code') }}</th>
                                    <th>{{ __('ui.workflow.item_name') }}</th>
                                    <th>{{ __('ui.common.category') }}</th>
                                    <th>{{ __('ui.common.unit') }}</th>
                                    <th>{{ __('ui.common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $product)
                                    <tr>
                                        <td>{{ $product->code }}</td>
                                        <td>@if($product->mo_image_url)<img src="{{ $product->mo_image_url }}" alt="{{ $product->code }}" loading="lazy" style="width:48px;height:48px;object-fit:contain" class="me-2 rounded">@endif{{ $product->name }}</td>
                                        <td>{{ $product->category?->name ?? '—' }}</td>
                                        <td>{{ $product->unit?->symbol ?: $product->unit?->name }}</td>
                                        <td><button type="button" class="btn btn-primary btn-sm choose-finished-good"
                                                data-id="{{ $product->id }}" data-code="{{ $product->code }}"
                                                data-name="{{ $product->name }}"
                                                data-unit="{{ $product->unit?->symbol ?: $product->unit?->name }}"
                                                data-image="{{ $product->mo_image_url ?? '' }}" data-style="{{ $product->style_code }}">{{ __('ui.common.select') }}</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('super.production-orders.partials.buyer-picker')
@endsection

@push('scripts')
    <script>
        $(function () {
            const initialOrder = @json($formValues);
            const dtLanguage = {
                emptyTable: @json(__('ui.workflow.empty_table')),
                zeroRecords: @json(__('ui.workflow.no_matching_data')),
                processing: @json(__('ui.workflow.data_processing')),
                search: @json(__('ui.common.search') . ':'),
                lengthMenu: @json(__('ui.workflow.show_entries')),
                info: @json(__('ui.workflow.showing_entries')),
                infoEmpty: @json(__('ui.workflow.no_data')),
                paginate: {
                    first: @json(__('ui.workflow.first')),
                    last: @json(__('ui.workflow.last')),
                    next: @json(__('ui.workflow.next')),
                    previous: @json(__('ui.workflow.previous'))
                }
            };
            const setItemImage = (url, code = '') => {
                const image = $('#mo_item_image');
                image.off('error').on('error', function () {
                    $(this).addClass('d-none').removeAttr('src');
                    $('#mo_item_image_empty').removeClass('d-none');
                });
                if (url) image.attr({src: url, alt: code}).removeClass('d-none');
                else image.addClass('d-none').removeAttr('src');
                $('#mo_item_image_empty').toggleClass('d-none', Boolean(url));
            };
            let uploadedImagePreview = null;
            let savedImageUrl = null;
            $('#mo_image').on('change', function () {
                if (uploadedImagePreview) URL.revokeObjectURL(uploadedImagePreview);
                uploadedImagePreview = this.files[0] ? URL.createObjectURL(this.files[0]) : null;
                setItemImage(uploadedImagePreview || savedImageUrl, $('#item_code_display').val());
            });
            let sizeRowIndex = 0;
            function addSizeRow(row = {}) {
                const index = sizeRowIndex++;
                $('#mo_size_rows').append(`<tr><td><select class="form-select mo-detail-product" name="variant_rows[${index}][output_product_id]"></select></td><td><input class="form-control" name="variant_rows[${index}][color]" maxlength="80" placeholder="{{ __('ui.workflow.color') }}"></td><td><input class="form-control" name="variant_rows[${index}][size]" maxlength="40" required></td><td><input class="form-control mo-size-quantity" type="number" name="variant_rows[${index}][quantity]" min="0.0001" step="0.0001" required></td><td><button type="button" class="btn btn-outline-danger btn-sm mo-remove-size" aria-label="{{ __('ui.common.delete') }}">×</button></td></tr>`);
                const productSelect = $('#mo_size_rows tr:last .mo-detail-product');
                productSelect.append($('<option>').val('').text(@json(__('variants.use_selected_product'))));
                @foreach($products as $product)
                    productSelect.append($('<option>').val(@json($product->id)).text(@json($product->code . ' — ' . $product->name)));
                @endforeach
                productSelect.val(row.output_product_id || '');
                const inputs = $('#mo_size_rows tr:last input');
                inputs.eq(0).val(row.color || '');
                inputs.eq(1).val(row.size || '');
                inputs.eq(2).val(row.quantity || '');
            }
            $('#mo_excel').on('change', function () {
                const file = this.files[0];
                if (!file) return;
                const payload = new FormData();
                payload.append('file', file);
                payload.append('_token', $('#materialOrderForm input[name="_token"]').val());
                $('#mo_excel').prop('disabled', true);
                $.ajax({url: @json(route('super.material-orders.import-lines')), method: 'POST',
                    data: payload, processData: false, contentType: false
                }).done(response => {
                    $('#mo_size_rows').empty(); sizeRowIndex = 0;
                    response.rows.forEach(row => addSizeRow(row));
                    $('#mo_use_matrix').prop('checked', true);
                    syncMatrix();
                }).fail(xhr => showError(xhr)).always(() => {
                    $('#mo_excel').prop('disabled', false).val('');
                });
            });
            function syncMatrix() {
                const active = $('#mo_use_matrix').prop('checked');
                $('#mo_size_matrix').toggleClass('d-none', !active).find('input,select').prop('disabled', !active);
                $('#item_size_display').prop('disabled', active).closest('.mo-field').toggleClass('d-none', active);
                $('#item_color_display').prop('required', false);
                $('#planned_quantity').prop('readonly', active).toggleClass('bg-light', active);
                recalculate();
            }
            $('#mo_add_size').on('click', () => addSizeRow());
            $('#mo_use_matrix').on('change', syncMatrix);
            $('#mo_size_rows').on('input', 'input', () => recalculate()).on('click', '.mo-remove-size', function () {
                $(this).closest('tr').remove();
                if (!$('#mo_size_rows tr').length) addSizeRow();
                recalculate();
            });
            const recalculate = () => {
                if ($('#mo_use_matrix').prop('checked')) {
                    const total = [...document.querySelectorAll('.mo-size-quantity')].reduce((sum, input) => sum + Math.max(0, Number(input.value) || 0), 0);
                    $('#planned_quantity').val(Number(total.toFixed(4)));
                }
                $('#total_price_display').val(Number((Number($('#planned_quantity').val() || 0) * Number($('#price_per_unit').val() || 0)).toFixed(4)));
            };
            $('#planned_quantity,#price_per_unit').on('input', recalculate);
            const picker = document.getElementById('finishedGoodPicker');
            $('#item_code_display, #item_name_display').on('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    this.click();
                }
            });
            picker.addEventListener('shown.bs.modal', () => {
                const table = $('#finishedGoodsPickerTable').DataTable({
                    retrieve: true,
                    pageLength: 10,
                    lengthMenu: [5, 10, 50, 100],
                    order: [[0, 'asc']],
                    language: dtLanguage
                });
                table.columns.adjust();
                $(picker).find('.dataTables_filter input, .dt-search input').first().focus();
            });
            $(picker).on('click', '#finishedGoodsPickerTable tbody tr', function (event) {
                if (!$(event.target).closest('button').length) {
                    $(this).find('.choose-finished-good').trigger('click');
                }
            });
            $(picker).on('click', '.choose-finished-good', function() {
                const b = $(this);
                $('#output_product_id').val(b.data('id'));
                $('#item_code_display').val(b.attr('data-code'));
                $('#item_name_display').val(b.data('name'));
                $('#item_unit_display').val(b.attr('data-unit'));
                if (!initialOrder && !$('#item_style_display').val()) {
                    $('#item_style_display').val(b.attr('data-style'));
                }
                if (!initialOrder || !savedImageUrl) savedImageUrl = b.attr('data-image');
                setItemImage(uploadedImagePreview || savedImageUrl, b.attr('data-code'));
                bootstrap.Modal.getInstance(picker).hide();
            });
            if (initialOrder) {
                $('#mo_use_matrix').prop('checked', false);
                $('#mo_matrix_toggle').addClass('d-none');
                const values = {
                    number: initialOrder.number,
                    buyer_po_number: initialOrder.buyer_po_number,
                    no_cbd: initialOrder.no_cbd,
                    currency: initialOrder.currency || 'USD',
                    destination: initialOrder.destination,
                    po_date: initialOrder.po_date,
                    due_date: initialOrder.due_date,
                    customer_id: initialOrder.customer_id,
                    output_product_id: initialOrder.output_product_id,
                    item_code_display: initialOrder.product_code,
                    item_name_display: initialOrder.product,
                    item_unit_display: initialOrder.unit,
                    item_style_display: initialOrder.style,
                    item_color_display: initialOrder.color,
                    item_size_display: initialOrder.size,
                    planned_quantity: initialOrder.planned_quantity,
                    price_per_unit: initialOrder.price_per_unit,
                    description: initialOrder.description
                };
                Object.entries(values).forEach(([id, value]) => $('#' + id).val(value ?? ''));
                savedImageUrl = initialOrder.image_url;
                setItemImage(savedImageUrl, initialOrder.product_code);
            } else {
                $('#po_date').val(@json(now()->toDateString()));
                addSizeRow();
            }
            syncMatrix();
            if (initialOrder?.is_locked) {
                $('#mo_image_only').val('1');
                $('#mo_image_only_notice').removeClass('d-none');
                $('#materialOrderForm .card-body :input').not('#mo_image').prop('disabled', true);
            }
            $('#materialOrderForm').on('submit', function(event) {
                event.preventDefault();
                if ($('#mo_image_only').val() !== '1' && !$('#customer_id').val()) {
                    $('#production-order-buyer').trigger('click');
                    return;
                }
                const form = $(this),
                    button = $('#saveMaterialOrder').prop('disabled', true);
                const payload = new FormData(form[0]);
                $.ajax({
                    url: this.action,
                    method: 'POST',
                    data: payload,
                    processData: false,
                    contentType: false
                }).done(response => {
                    Swal.fire({
                        icon: 'success',
                        title: @json(__('ui.workflow.success')),
                        text: response.message
                    }).then(() => window.location.href = @json(route('super.material-orders.index')));
                }).fail(xhr => {
                    button.prop('disabled', false);
                    showError(xhr);
                });
            });
            function showError(xhr) {
                const messages = Object.values(xhr.responseJSON?.errors || {}).flat().join('\n');
                Swal.fire({
                    icon: 'error',
                    title: @json(__('ui.workflow.failure')),
                    text: messages || xhr.responseJSON?.message || @json(__('ui.workflow.material_order_save_failed'))
                });
            }
        });
    </script>
@endpush
