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

        #material-orders-table td { vertical-align: top; }
        #material-orders-table .mo-merged-detail { height: 64px; display: flex; align-items: center; gap: .35rem; border-bottom: 1px solid #eef2f6; }
        #material-orders-table .mo-merged-detail:last-child { border-bottom: 0; }
        #material-orders-table .mo-action-column { position: sticky; right: 0; background: var(--bs-body-bg, #fff); z-index: 2; min-width: 55px; }
        #material-orders-table th.mo-action-column { z-index: 3; }
        #material-orders-table .mo-action-column .mo-merged-detail { justify-content: flex-end; }
    </style>
    <div class="mobile-list-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="page-title mb-1">{{ __('ui.workflow.material_order') }}</h4>
            <p class="text-muted mb-0">{{ __('ui.workflow.material_order_subtitle') }}</p>
        </div>
        <div class="mobile-list-actions d-flex flex-wrap gap-2">
            @can('production-orders.create')
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('super.material-orders.full-import-template') }}"><i data-feather="file-text" class="icon-sm me-1"></i>
                        <span class="mobile-list-action-label">{{ __('variants.full_import_template') }}</span></a>
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#moFullImportModal"><i data-feather="upload" class="icon-sm me-1"></i>
                        <span class="mobile-list-action-label">{{ __('variants.full_import') }}</span></button>
            @endcan

            @can('production-orders.view')
                <a href="{{ route('super.material-orders.export') }}" id="exportMaterialOrders" class="btn btn-outline-success btn-sm"><i data-feather="download" class="icon-sm me-1"></i>
                        <span class="mobile-list-action-label">{{ __('ui.common.export_excel') }}</span></a>
            @endcan
            @can('production-orders.create')
                <a class="btn btn-primary btn-sm" href="{{ route('super.material-orders.create') }}"><i data-feather="plus" class="icon-sm me-1"></i>
                        <span class="mobile-list-action-label">{{ __('ui.workflow.material_order_admin') }}</span></a>
            @endcan
        </div>
    </div>
    @include('super.purchasing.date-filter', ['compactActions' => true])
    <div class="card border-0 shadow-sm material-orders-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="material-orders-table" class="mobile-list-table table table-hover align-middle text-nowrap w-100 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('ui.common.no') }}</th>
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
                            <th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th>
                            <th>{{ __('ui.workflow.order_quantity') }}</th>
                            <th>{{ __('ui.workflow.price_per_unit') }}</th>
                            <th>{{ __('ui.workflow.total_price') }}</th>
                            <th>{{ __('ui.workflow.description') }}</th>
                            <th class="text-end mo-action-column">{{ __('ui.common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders->groupBy(fn ($line) => $line->production_order_header_id ?: 'legacy-'.$line->number) as $details)
                            @php $order = $details->first(); @endphp
                            <tr data-po-date="{{ $order->po_date?->format('Y-m-d') }}">
                                <td></td>

                                <td>{{ $order->number }}</td>
                                <td>{{ $order->po_date?->format('Y-m-d') ?: '—' }}</td>
                                <td>{{ $order->due_date?->format('Y-m-d') ?: '—' }}</td>
                                <td>{{ $order->buyer_po_number ?: '—' }}</td>
                                <td>{{ $order->buyer?->name ?: '—' }}</td>
                                <td>{{ $order->no_cbd ?: '—' }}</td>
                                <td>{{ $order->currency }}</td>
                                <td>{{ $order->destination ?: '—' }}</td>
                                <td>@foreach($details as $detail)
                                    @php $order = $detail; @endphp
                                    <div class="mo-merged-detail">{{ $order->outputProduct?->parentProduct?->code ?: $order->outputProduct?->code ?: '—' }}</div>
                                    @endforeach
                                    @php $order = $details->first(); @endphp
                                </td>
                                <td>@foreach($details as $detail)
                                    @php $order = $detail; @endphp
                                    <div class="mo-merged-detail">@if($order->image_url)<img src="{{ $order->image_url }}" alt="{{ $order->outputProduct?->code ?? $order->model_style }}" loading="lazy" style="width:48px;height:48px;object-fit:contain" class="me-2 rounded">@endif{{ $order->outputProduct?->name ?: ($order->status === 'draft' ? __('variants.finished_good_pending') : '—') }}</div>
                                    @endforeach
                                    @php $order = $details->first(); @endphp
                                </td>
                                <td>@foreach($details as $detail)
                                    @php $order = $detail; @endphp
                                    <div class="mo-merged-detail">{{ $order->outputProduct?->unit?->symbol ?: $order->outputProduct?->unit?->name ?: '—' }}
                                </div>
                                    @endforeach
                                    @php $order = $details->first(); @endphp
                                </td>
                                <td>@foreach($details as $detail)
                                    @php $order = $detail; @endphp
                                    <div class="mo-merged-detail">{{ $order->model_style ?: '—' }}</div>
                                    @endforeach
                                    @php $order = $details->first(); @endphp
                                </td>
                                <td>@foreach($details as $detail)
                                    @php $order = $detail; @endphp
                                    <div class="mo-merged-detail">{{ $order->line_color ?: '—' }}</div>
                                    @endforeach
                                    @php $order = $details->first(); @endphp
                                </td><td>@foreach($details as $detail)
                                    @php $order = $detail; @endphp
                                    <div class="mo-merged-detail">{{ $order->line_size ?: '—' }}</div>
                                    @endforeach
                                    @php $order = $details->first(); @endphp
                                </td>
                                <td class="text-end">@foreach($details as $detail)
                                    @php $order = $detail; @endphp
                                    <div class="mo-merged-detail">{{ \App\Support\DisplayNumber::format((float) $order->planned_quantity, 4) }}
                                </div>
                                    @endforeach
                                    @php $order = $details->first(); @endphp
                                </td>
                                <td class="text-end">@foreach($details as $detail)
                                    @php $order = $detail; @endphp
                                    <div class="mo-merged-detail">{{ \App\Support\DisplayNumber::format((float) $order->price_per_unit, 4) }}</div>
                                    @endforeach
                                    @php $order = $details->first(); @endphp
                                </td>
                                <td class="text-end">@foreach($details as $detail)
                                    @php $order = $detail; @endphp
                                    <div class="mo-merged-detail">{{ \App\Support\DisplayNumber::format((float) $order->total_price, 4) }}</div>
                                    @endforeach
                                    @php $order = $details->first(); @endphp
                                </td>
                                <td>@foreach($details as $detail)
                                    @php $order = $detail; @endphp
                                    <div class="mo-merged-detail">{{ $order->description ?: '—' }}</div>
                                    @endforeach
                                    @php $order = $details->first(); @endphp
                                </td>
                                @php
                                    $needsFinishedGood = $details->contains(fn ($line) => !$line->output_product_id);
                                    $requestOrder = $needsFinishedGood ? null : $details->first(fn ($line) => in_array($line->status, ['released', 'in_progress'], true));
                                @endphp
                                <td class="text-end mo-action-column">@foreach($details as $detail)
                                    @php
                                        $order = $detail;
                                        $showRequestAction = $loop->first && ($requestOrder || $needsFinishedGood) && auth()->user()->can('material-requests.create');
                                    @endphp
                                    <div class="mo-merged-detail">
                                    @if($loop->first || $showRequestAction || auth()->user()->canAny(['production-orders.update', 'production-orders.delete']))
                                        <div class="dropdown">
                                            <button class="btn btn-sm bg-transparent border-0 p-0" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                title="{{ __('ui.common.actions') }}"><i data-feather="more-vertical"
                                                    class="icon-sm"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @if($loop->first)
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('super.material-orders.progress', $order) }}">
                                                            <i data-feather="activity" class="icon-sm" aria-hidden="true"></i>{{ __('production_progress.title') }}
                                                        </a>
                                                    </li>
                                                @endif
                                                @if($showRequestAction)
                                                    <li>
                                                        @if($requestOrder)
                                                            <a class="dropdown-item d-flex align-items-center gap-2"
                                                                href="{{ route('super.material-requests.create', ['mo_id' => $requestOrder->id]) }}"><i
                                                                data-feather="file-plus" class="icon-sm"></i>{{ __('variants.create_mr') }}</a>
                                                        @else
                                                            <span class="dropdown-item disabled" aria-disabled="true">{{ __('variants.create_mr') }}</span>
                                                            <small class="dropdown-item-text text-muted text-wrap">{{ __('variants.link_finished_good') }}</small>
                                                        @endif
                                                    </li>
                                                @endif
                                                @can('production-orders.update')
                                                    <li><a class="dropdown-item d-flex align-items-center gap-2"
                                                            href="{{ route('super.material-orders.edit', $order) }}"><i
                                                                data-feather="edit-2"
                                                                class="icon-sm"></i>{{ __('ui.common.edit') }}</a></li>
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
                                    @endif
                                </div>
                                    @endforeach
                                    @php $order = $details->first(); @endphp
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

        @can('production-orders.create')
        <div class="modal fade" id="moFullImportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable"><form id="moFullImportForm" class="modal-content">
                @csrf
                <div class="modal-header"><h5 class="modal-title">{{ __('variants.full_import') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
                <div class="modal-body">
                    <p class="small text-muted">{{ __('variants.full_import_hint') }}</p>
                    <a class="btn btn-outline-secondary btn-sm mb-2" href="{{ route('super.material-orders.full-import-template') }}">{{ __('variants.full_import_template') }}</a>
                    <p class="small text-muted">{{ __('variants.mo_template_variant_hint') }}</p>
                    <input type="file" name="file" class="form-control form-control-sm mb-3" accept=".xlsx" required>
                    <label class="form-label small" for="mo_full_image">{{ __('variants.image') }}</label>
                    <input id="mo_full_image" type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                    <p class="small text-muted mt-1">{{ __('variants.import_image_hint') }}</p>
                    <div class="table-responsive" style="max-height:320px"><table class="table table-sm"><thead id="mo_full_preview_head"></thead><tbody id="mo_full_preview_body"></tbody></table></div>
                    <div id="mo_full_preview_count" class="small text-muted"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-outline-primary btn-sm" id="mo_full_preview">{{ __('variants.preview_import') }}</button><button type="button" class="btn btn-primary btn-sm" id="mo_full_confirm" disabled>{{ __('variants.confirm_import') }}</button></div>
            </form></div>
        </div>
        @endcan


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
                    [2, 'desc']
                ],
                columnDefs: [{
                    targets: [0, -1],
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
            function clearFullPreview() {
                $('#mo_full_confirm').prop('disabled', true);
                $('#mo_full_preview_head,#mo_full_preview_body,#mo_full_preview_count').empty();
            }
            $('#moFullImportForm input[type=file]').on('change', clearFullPreview);
            let fullImportBusy = false;
            function uploadFullImport(confirm) {
                const form = document.getElementById('moFullImportForm');
                if (!form || fullImportBusy || !form.reportValidity()) return;
                fullImportBusy = true;
                const payload = new FormData(form); payload.append('confirm', confirm ? '1' : '0');
                $('#mo_full_preview,#mo_full_confirm').prop('disabled', true);
                $('#moFullImportForm input[type=file]').prop('disabled', true);
                let previewReady = false;
                $.ajax({url: @json(route('super.material-orders.full-import')), method: 'POST',
                    data: payload, processData: false, contentType: false
                }).done(response => {
                    if (confirm) {
                        Swal.fire({icon: 'success', text: response.message}).then(() => window.location.reload());
                        return;
                    }
                    clearFullPreview();
                    const columns = ['number','buyer_po_number','no_cbd','currency','destination','po_date','due_date','customer_code','style_code','color','size','qty','price_per_unit','description'];
                    const head = $('<tr>'); columns.forEach(key => head.append($('<th>').text(key)));
                    $('#mo_full_preview_head').append(head);
                    response.rows.forEach(row => {
                        const tr = $('<tr>'); columns.forEach(key => tr.append($('<td>').text(row[key] ?? '')));
                        $('#mo_full_preview_body').append(tr);
                    });
                    $('#mo_full_preview_count').text(response.count);
                    previewReady = true;
                }).fail(xhr => { clearFullPreview(); showError(xhr); }).always(() => {
                    fullImportBusy = false;
                    $('#mo_full_preview').prop('disabled', false);
                    $('#moFullImportForm input[type=file]').prop('disabled', false);
                    $('#mo_full_confirm').prop('disabled', !previewReady);
                });
            }
            $('#moFullImportForm').on('submit', function (e) { e.preventDefault(); uploadFullImport(false); });
            $('#mo_full_confirm').on('click', () => uploadFullImport(true));
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
