@extends('layouts.admin')

@section('title', __('ui.workflow.material_order'))

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('ui.nav.ppic') }}</li>
            <li class="breadcrumb-item active">{{ __('ui.workflow.material_order') }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    <style>
        #material-orders-table_wrapper .dataTables_length label,
        #material-orders-table_wrapper .dataTables_filter label {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: 0;
            white-space: nowrap
        }

        #material-orders-table_wrapper .dataTables_length select,
        #material-orders-table_wrapper .dataTables_filter input {
            height: 2rem;
            margin: 0;
            padding: .2rem .55rem;
            border: 1px solid #dfe5ec;
            border-radius: .45rem;
            font-size: .8125rem
        }

        #material-orders-table_wrapper .dataTables_length select {
            width: auto !important;
            min-width: 4.25rem;
            padding-right: 1.75rem
        }

        #material-orders-table_wrapper .dataTables_info,
        #material-orders-table_wrapper .dataTables_paginate {
            color: #748094;
            font-size: .82rem
        }

        .material-orders-card {
            border-radius: .75rem
        }

        .material-orders-card .table thead th {
            color: #64748b;
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .025em;
            text-transform: uppercase;
            border-bottom-color: #e9eef5;
            white-space: nowrap
        }

        .material-orders-card .table tbody td {
            border-color: #eef2f6
        }

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
    </style>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="page-title mb-1">{{ __('ui.workflow.material_order') }}</h4>
            <p class="text-muted mb-0">{{ __('ui.workflow.material_order_subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
            @can('production-orders.view')
                <a href="{{ route('super.material-orders.export') }}" id="exportMaterialOrders" class="btn btn-outline-success btn-sm"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>
            @endcan
            @can('production-orders.create')
                <button type="button" class="btn btn-primary btn-sm" id="addMaterialOrder"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.workflow.material_order_admin') }}</button>
            @endcan
        </div>
    </div>
    @include('super.purchasing.date-filter')
    <div class="card border-0 shadow-sm material-orders-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="material-orders-table" class="table table-hover align-middle text-nowrap w-100 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('ui.common.no') }}</th>
                            <th>{{ __('ui.common.action') }}</th>
                            <th>{{ __('ui.workflow.material_order_no') }}</th>
                            <th>{{ __('ui.workflow.po_date') }}</th>
                            <th>{{ __('ui.workflow.material_order_due_date') }}</th>
                            <th>{{ __('ui.workflow.buyer_po') }}</th>
                            <th>{{ __('ui.workflow.buyer') }}</th>
                            <th>{{ __('ui.workflow.no_cbd') }}</th>
                            <th>{{ __('ui.workflow.currency') }}</th>
                            <th>{{ __('ui.workflow.destination') }}</th>
                            <th>{{ __('ui.workflow.item_code') }}</th>
                            <th>{{ __('ui.workflow.item_name') }}</th>
                            <th>{{ __('ui.common.unit') }}</th>
                            <th>{{ __('ui.nav.styles') }}</th>
                            <th>{{ __('ui.workflow.order_quantity') }}</th>
                            <th>{{ __('ui.workflow.price_per_unit') }}</th>
                            <th>{{ __('ui.workflow.total_price') }}</th>
                            <th>{{ __('ui.workflow.description') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr data-po-date="{{ $order->po_date?->format('Y-m-d') }}">
                                <td></td>
                                <td class="text-center">
                                    @canany(['production-orders.update', 'production-orders.delete'])
                                        <div class="dropdown">
                                            <button class="btn btn-sm bg-transparent border-0 p-0" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                title="{{ __('ui.common.actions') }}"><i data-feather="more-vertical"
                                                    class="icon-sm"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('production-orders.update')
                                                    <li><button type="button"
                                                            class="dropdown-item d-flex align-items-center gap-2 edit-material-order"
                                                            data-url="{{ route('super.material-orders.show', $order) }}"><i
                                                                data-feather="edit-2"
                                                                class="icon-sm"></i>{{ __('ui.common.edit') }}</button></li>
                                                @endcan
                                                @can('production-orders.delete')
                                                    <li><button type="button"
                                                            class="dropdown-item d-flex align-items-center gap-2 text-danger delete-material-order"
                                                            data-url="{{ route('super.material-orders.destroy', $order) }}"><i
                                                                data-feather="trash-2"
                                                                class="icon-sm"></i>{{ __('ui.common.delete') }}</button></li>
                                                @endcan
                                            </ul>
                                        </div>
                                    @endcanany
                                </td>
                                <td>{{ $order->number }}</td>
                                <td>{{ $order->po_date?->format('Y-m-d') ?: '—' }}</td>
                                <td>{{ $order->due_date?->format('Y-m-d') ?: '—' }}</td>
                                <td>{{ $order->buyer_po_number ?: '—' }}</td>
                                <td>{{ $order->buyer?->name ?: '—' }}</td>
                                <td>{{ $order->no_cbd ?: '—' }}</td>
                                <td>{{ $order->currency }}</td>
                                <td>{{ $order->destination ?: '—' }}</td>
                                <td>{{ $order->outputProduct?->code ?: '—' }}</td>
                                <td>{{ $order->outputProduct?->name ?: '—' }}</td>
                                <td>{{ $order->outputProduct?->unit?->symbol ?: $order->outputProduct?->unit?->name ?: '—' }}
                                </td>
                                <td>{{ $order->outputProduct?->style_code ?: '—' }}</td>
                                <td class="text-end">{{ number_format((float) $order->planned_quantity, 4, ',', '.') }}
                                </td>
                                <td class="text-end">{{ number_format((float) $order->price_per_unit, 4, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $order->total_price, 4, ',', '.') }}</td>
                                <td>{{ $order->description ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @canany(['production-orders.create', 'production-orders.update'])
        <div class="modal fade" id="materialOrderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <form id="materialOrderForm">
                        @csrf <input type="hidden" id="materialOrderId"><input type="hidden" name="output_product_id"
                            id="output_product_id">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="materialOrderModalTitle">
                                {{ __('ui.workflow.material_order_form_title') }}</h5><button type="button"
                                class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="{{ __('ui.common.close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <section class="material-order-section mb-3">
                                <h6 class="material-order-section-title">{{ __('ui.workflow.material_order') }} —
                                    {{ __('ui.workflow.order_information') }}</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label for="number"
                                            class="form-label">{{ __('ui.workflow.material_order_no') }} <span
                                                class="text-danger">*</span></label><input class="form-control" id="number"
                                            name="number" maxlength="100" required></div>
                                    <div class="col-md-4"><label for="po_date"
                                            class="form-label">{{ __('ui.workflow.po_date') }} <span
                                                class="text-danger">*</span></label><input class="form-control" type="date"
                                            id="po_date" name="po_date" required></div>
                                    <div class="col-md-4"><label for="due_date"
                                            class="form-label">{{ __('ui.workflow.material_order_due_date') }} <span
                                                class="text-danger">*</span></label><input class="form-control" type="date"
                                            id="due_date" name="due_date" required></div>
                                    <div class="col-md-4"><label for="buyer_po_number"
                                            class="form-label">{{ __('ui.workflow.buyer_po_no') }}</label><input
                                            class="form-control" id="buyer_po_number" name="buyer_po_number" maxlength="100">
                                    </div>
                                    <div class="col-md-4"><label for="currency"
                                            class="form-label">{{ __('ui.workflow.currency') }} <span
                                                class="text-danger">*</span></label><select class="form-select" id="currency"
                                            name="currency" required>
                                            <option value="USD">USD</option>
                                            @if ($defaultCurrency !== 'USD')
                                                <option value="{{ $defaultCurrency }}">{{ $defaultCurrency }}</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-4"><label for="no_cbd"
                                            class="form-label">{{ __('ui.workflow.no_cbd') }}</label><input
                                            class="form-control" id="no_cbd" name="no_cbd" maxlength="100"></div>
                                    <div class="col-md-4"><label for="customer_id"
                                            class="form-label">{{ __('ui.workflow.buyer') }} <span
                                                class="text-danger">*</span></label><select class="form-select"
                                            id="customer_id" name="customer_id" required>
                                            <option value="">{{ __('ui.common.select') }}</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                            @endforeach
                                        </select></div>
                                    <div class="col-md-8"><label for="destination"
                                            class="form-label">{{ __('ui.workflow.destination') }}</label><input
                                            class="form-control" id="destination" name="destination" maxlength="150"></div>
                                </div>
                            </section>
                            <section class="material-order-section">
                                <h6 class="material-order-section-title">{{ __('ui.workflow.detail_finished_good') }}</h6>
                                <div class="row g-3">
                                    <div class="col-lg-6"><label class="form-label"
                                            for="item_code_display">{{ __('ui.workflow.item_code') }} <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group"><input class="form-control bg-light" id="item_code_display"
                                                readonly required><button class="btn btn-primary" type="button"
                                                id="searchFinishedGood">{{ __('ui.common.search') }}</button></div>
                                    </div>
                                    <div class="col-lg-6"><label class="form-label"
                                            for="item_name_display">{{ __('ui.workflow.item_name') }}</label><input
                                            class="form-control bg-light" id="item_name_display" readonly></div>
                                    <div class="col-md-3"><label class="form-label"
                                            for="item_unit_display">{{ __('ui.common.unit') }}</label><input
                                            class="form-control bg-light" id="item_unit_display" readonly></div>
                                    <div class="col-md-3"><label class="form-label"
                                            for="item_style_display">{{ __('ui.nav.styles') }}</label><input
                                            class="form-control bg-light" id="item_style_display" readonly></div>
                                    <div class="col-md-3"><label class="form-label"
                                            for="planned_quantity">{{ __('ui.workflow.order_quantity') }} <span
                                                class="text-danger">*</span></label><input class="form-control text-end"
                                            type="number" min="0.0001" step="0.0001" id="planned_quantity"
                                            name="planned_quantity" value="0" required></div>
                                    <div class="col-md-3"><label class="form-label"
                                            for="price_per_unit">{{ __('ui.workflow.price_per_unit') }}</label><input
                                            class="form-control text-end" type="number" min="0" step="0.0001"
                                            id="price_per_unit" name="price_per_unit" value="0"></div>
                                    <div class="col-md-4"><label class="form-label"
                                            for="total_price_display">{{ __('ui.workflow.total_price') }}</label><input
                                            class="form-control text-end bg-light" id="total_price_display" value="0"
                                            readonly></div>
                                    <div class="col-md-8"><label class="form-label"
                                            for="description">{{ __('ui.workflow.description') }}</label><input
                                            class="form-control" id="description" name="description" maxlength="5000"></div>
                                </div>
                            </section>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-light btn-sm"
                                data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button><button type="submit"
                                class="btn btn-primary btn-sm" id="saveMaterialOrder"><i data-feather="save"
                                    class="icon-sm me-1"></i>{{ __('ui.common.save') }}</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="finishedGoodPickerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('ui.workflow.choose_finished_good') }}</h5><button type="button"
                            class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table id="finishedGoodsPickerTable"
                                class="table table-sm table-bordered table-hover align-middle w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('ui.workflow.item_code') }}</th>
                                        <th>{{ __('ui.workflow.item_name') }}</th>
                                        <th>{{ __('ui.common.unit') }}</th>
                                        <th>{{ __('ui.nav.styles') }}</th>
                                        <th>{{ __('ui.common.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($products as $product)
                                        <tr>
                                            <td>{{ $product->code }}</td>
                                            <td>{{ $product->name }}</td>
                                            <td>{{ $product->unit?->symbol ?: $product->unit?->name }}</td>
                                            <td>{{ $product->style_code }}</td>
                                            <td><button type="button" class="btn btn-primary btn-sm choose-finished-good"
                                                    data-id="{{ $product->id }}" data-code="{{ $product->code }}"
                                                    data-name="{{ $product->name }}"
                                                    data-unit="{{ $product->unit?->symbol ?: $product->unit?->name }}"
                                                    data-style="{{ $product->style_code }}">{{ __('ui.common.select') }}</button>
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
    @endcanany
@endsection

@push('scripts')
    <script>
        $(function() {
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
            const table = $('#material-orders-table').DataTable({
                pageLength: 10,
                lengthMenu: [5, 10, 50, 100],
                order: [
                    [3, 'desc']
                ],
                columnDefs: [{
                    targets: 0,
                    orderable: false,
                    searchable: false
                }],
                language: dtLanguage,
                drawCallback: function() {
                    this.api().column(0, {
                        search: 'applied',
                        order: 'applied'
                    }).nodes().each((cell, i) => cell.innerHTML = i + 1);
                    if (window.feather) feather.replace();
                }
            });
            $.fn.dataTable.ext.search.push(function(settings, data, index) {
                if (settings.nTable.id !== 'material-orders-table') return true;
                const row = table.row(index).node(),
                    date = row?.dataset.poDate || '';
                const from = $('#from_date').val(),
                    until = $('#to_date').val();
                return (!from || date >= from) && (!until || date <= until);
            });
            table.draw();
            $('#applyDateFilter').on('click', () => table.draw());
            $('#todayDateFilter').on('click', () => {
                const today = @json(now()->toDateString());
                $('#from_date,#to_date').val(today);
                table.draw();
            });
            $('#exportMaterialOrders').on('click', function() {
                const url = new URL(this.href, window.location.origin);
                const fromDate = $('#from_date').val(), toDate = $('#to_date').val();
                if (fromDate) url.searchParams.set('from_date', fromDate); else url.searchParams.delete('from_date');
                if (toDate) url.searchParams.set('to_date', toDate); else url.searchParams.delete('to_date');
                this.href = url.toString();
            });
            const modalElement = document.getElementById('materialOrderModal'),
                pickerElement = document.getElementById('finishedGoodPickerModal');
            const modal = modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
            const picker = pickerElement ? bootstrap.Modal.getOrCreateInstance(pickerElement) : null;
            const resetLine = () => {
                $('#output_product_id,#item_code_display,#item_name_display,#item_unit_display,#item_style_display')
                    .val('');
                $('#planned_quantity').val('0');
                $('#price_per_unit').val('0');
                $('#total_price_display').val('0');
                $('#description').val('');
            };
            const recalculate = () => $('#total_price_display').val((Number($('#planned_quantity').val() || 0) *
                Number($('#price_per_unit').val() || 0)).toFixed(4));
            $('#planned_quantity,#price_per_unit').on('input', recalculate);
            $('#searchFinishedGood').on('click', function() {
                if (!$.fn.DataTable.isDataTable('#finishedGoodsPickerTable')) $('#finishedGoodsPickerTable')
                    .DataTable({
                        pageLength: 10,
                        lengthMenu: [5, 10, 50, 100],
                        order: [
                            [0, 'asc']
                        ],
                        language: dtLanguage
                    });
                picker.show();
            });
            $(document).on('click', '.choose-finished-good', function() {
                const b = $(this);
                $('#output_product_id').val(b.data('id'));
                $('#item_code_display').val(b.data('code'));
                $('#item_name_display').val(b.data('name'));
                $('#item_unit_display').val(b.data('unit'));
                $('#item_style_display').val(b.data('style'));
                picker.hide();
            });
            @can('production-orders.create')
                $('#addMaterialOrder').on('click', function() {
                    $('#materialOrderForm')[0].reset();
                    $('#materialOrderId').val('');
                    resetLine();
                    $('#number').prop('readonly', false);
                    $('#po_date').val(@json(now()->format('Y-m-d')));
                    $('#currency').val('USD');
                    $('#materialOrderForm').attr('data-method', 'POST').attr('data-url',
                        @json(route('super.material-orders.store')));
                    $('#materialOrderModalTitle').text(@json(__('ui.workflow.material_order_form_title')));
                    modal.show();
                });
            @endcan
            @can('production-orders.update')
                $(document).on('click', '.edit-material-order', function() {
                    const b = $(this);
                    $.get(b.data('url')).done(data => {
                        $('#materialOrderForm')[0].reset();
                        $('#materialOrderId').val(data.id);
                        $('#number').val(data.number).prop('readonly', false);
                        $('#buyer_po_number').val(data.buyer_po_number || '');
                        $('#no_cbd').val(data.no_cbd || '');
                        $('#currency').val(data.currency || 'USD');
                        $('#destination').val(data.destination || '');
                        $('#po_date').val(data.po_date);
                        $('#due_date').val(data.due_date);
                        $('#customer_id').val(data.customer_id);
                        $('#output_product_id').val(data.output_product_id);
                        $('#item_code_display').val(data.product_code);
                        $('#item_name_display').val(data.product);
                        $('#item_unit_display').val(data.unit);
                        $('#item_style_display').val(data.style);
                        $('#planned_quantity').val(data.planned_quantity);
                        $('#price_per_unit').val(data.price_per_unit);
                        $('#description').val(data.description || '');
                        recalculate();
                        $('#materialOrderForm').attr('data-method', 'PUT').attr('data-url', b.data(
                            'url'));
                        $('#materialOrderModalTitle').text(@json(__('ui.common.edit')) + ' MO ' +
                            data.number);
                        modal.show();
                    }).fail(xhr => showError(xhr));
                });
            @endcan
            $('#materialOrderForm').on('submit', function(event) {
                event.preventDefault();
                const form = $(this),
                    button = $('#saveMaterialOrder').prop('disabled', true);
                $.ajax({
                    url: form.attr('data-url'),
                    method: form.attr('data-method'),
                    data: form.serialize()
                }).done(response => {
                    modal.hide();
                    Swal.fire({
                        icon: 'success',
                        title: @json(__('ui.workflow.success')),
                        text: response.message
                    }).then(() => window.location.reload());
                }).fail(xhr => showError(xhr)).always(() => button.prop('disabled', false));
            });
            @can('production-orders.delete')
                $(document).on('click', '.delete-material-order', function() {
                    const b = $(this);
                    Swal.fire({
                        icon: 'warning',
                        title: @json(__('ui.workflow.confirm_material_order_delete')),
                        showCancelButton: true,
                        confirmButtonText: @json(__('ui.common.delete')),
                        cancelButtonText: @json(__('ui.common.cancel'))
                    }).then(result => {
                        if (!result.isConfirmed) return;
                        $.ajax({
                            url: b.data('url'),
                            method: 'DELETE',
                            data: {
                                _token: @json(csrf_token())
                            }
                        }).done(response => Swal.fire({
                            icon: 'success',
                            title: @json(__('ui.workflow.success')),
                            text: response.message
                        }).then(() => window.location.reload())).fail(xhr => showError(xhr));
                    });
                });
            @endcan
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
