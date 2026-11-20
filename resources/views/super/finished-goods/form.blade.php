@extends('layouts.admin')

@section('title', __('ui.finished_goods.title'))
@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('ui.nav.production') }}</li>
            <li class="breadcrumb-item"><a href="{{ route('super.finished-goods.index') }}">{{ __('ui.finished_goods.title') }}</a></li>
            <li class="breadcrumb-item active">{{ $entry ? __('ui.common.edit') : __('ui.finished_goods.new') }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    @php
        $order = $entry?->productionOrder;
        $product = $order?->outputProduct;
    @endphp
    <form id="finishedForm" class="card" method="post"
        action="{{ $entry ? route('super.finished-goods.update', $entry) : route('super.finished-goods.store') }}">
        @csrf
        @if ($entry)
            @method('PUT')
        @endif
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ $entry ? __('ui.common.edit').' – '.__('ui.finished_goods.title') : __('ui.finished_goods.new') }}</h5>
        </div>
        <div class="card-body">
            <div class="row gx-lg-5 gy-3">
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label for="finishedNumber" class="col-5 col-form-label col-form-label-sm">{{ __('ui.finished_goods.number') }}</label>
                        <div class="col-7">
                            <input id="finishedNumber" class="form-control form-control-sm" value="{{ $number }}" readonly>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label for="finishedDate" class="col-5 col-form-label col-form-label-sm">{{ __('ui.finished_goods.date') }}</label>
                        <div class="col-7">
                            <input id="finishedDate" type="date" name="finished_goods_date" class="form-control form-control-sm"
                                value="{{ $entry?->processed_at?->toDateString() ?? now()->toDateString() }}" required>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label for="finishedMoLabel" class="col-5 col-form-label col-form-label-sm">{{ __('ui.finished_goods.mo') }}</label>
                        <div class="col-7">
                            <div class="input-group input-group-sm">
                                <input id="finishedMoLabel" class="form-control" value="{{ $order?->number }}" readonly>
                                @if (!$entry)
                                    <button type="button" id="chooseFinishedMo" class="btn btn-outline-primary">{{ __('ui.common.search') }}</button>
                                @endif
                            </div>
                            <input type="hidden" id="finishedMoId" name="production_order_id" value="{{ $order?->id }}">
                        </div>
                    </div>
                    @foreach ([
                        'finishedPo' => [__('ui.finished_goods.po_buyer'), $order?->buyer_po_number],
                        'finishedStyle' => [__('ui.finished_goods.style'), $order?->model_style],
                    ] as $id => [$label, $value])
                        <div class="row g-2 align-items-center mb-2">
                            <label for="{{ $id }}" class="col-5 col-form-label col-form-label-sm">{{ $label }}</label>
                            <div class="col-7">
                                <input id="{{ $id }}" class="form-control form-control-sm" value="{{ $value }}" readonly>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="col-lg-6">
                    @foreach ([
                        'finishedCode' => [__('ui.finished_goods.item_code'), $product?->code],
                        'finishedName' => [__('ui.finished_goods.item_name'), $product?->variant_display_name],
                        'finishedUnit' => [__('ui.finished_goods.unit'), $product?->unit?->symbol ?: $product?->unit?->name],
                        'finishedAvailable' => [__('ui.finished_goods.available_from_packing'), $available],
                    ] as $id => [$label, $value])
                        <div class="row g-2 align-items-center mb-2">
                            <label for="{{ $id }}" class="col-5 col-form-label col-form-label-sm">{{ $label }}</label>
                            <div class="col-7">
                                <input id="{{ $id }}" class="form-control form-control-sm" value="{{ $value }}" readonly>
                            </div>
                        </div>
                    @endforeach
                    <div class="row g-2 align-items-center mb-2">
                        <label for="receivePacking" class="col-5 col-form-label col-form-label-sm">{{ __('ui.finished_goods.receive_from_packing') }}</label>
                        <div class="col-7">
                            <input id="receivePacking" type="number" name="receive_from_packing" min="0.0001" step="0.0001"
                                @if ($available !== null) max="{{ $available }}" @endif
                                value="{{ $entry?->input_quantity }}" class="form-control form-control-sm text-end" required>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center">
                        <label for="finishedQty" class="col-5 col-form-label col-form-label-sm">{{ __('ui.finished_goods.finished_good_quantity') }}</label>
                        <div class="col-7">
                            <input id="finishedQty" type="number" name="finished_good_quantity" min="0" step="0.0001"
                                value="{{ $entry?->good_quantity }}" class="form-control form-control-sm text-end" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2 py-2">
            <a class="btn btn-light btn-sm" href="{{ route('super.finished-goods.index') }}">{{ __('ui.common.cancel') }}</a>
            <button id="saveFinished" type="submit" class="btn btn-primary btn-sm">{{ $entry ? __('ui.common.save_changes') : __('ui.common.save') }}</button>
        </div>
    </form>

    @if (!$entry)
        <div class="modal fade" id="finishedMoModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('ui.finished_goods.select_mo') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table id="finishedMoTable" class="table table-sm table-bordered w-100">
                                <thead><tr>
                                    <th>{{ __('ui.finished_goods.mo') }}</th>
                                    <th>{{ __('ui.finished_goods.po_buyer') }}</th>
                                    <th>{{ __('ui.finished_goods.style') }}</th>
                                    <th>{{ __('ui.finished_goods.item_code') }}</th>
                                    <th>{{ __('ui.finished_goods.item_name') }}</th>
                                    <th>{{ __('ui.finished_goods.available_from_packing') }}</th>
                                    <th>{{ __('ui.workflow.color') }}</th>
                                    <th>{{ __('ui.workflow.size') }}</th>
                                    <th>{{ __('ui.workflow.order_quantity') }}</th>
                                    <th>{{ __('ui.common.action') }}</th>
                                </tr></thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        $(function () {
            @if (!$entry)
                const modalElement = document.getElementById('finishedMoModal');
                const modal = new bootstrap.Modal(modalElement);
                const orders = $('#finishedMoTable').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: @json(route('super.finished-goods.orders.dt')),
                    columns: [
                        {data: 'number'},
                        {data: 'buyer_po_number'},
                        {data: 'style', orderable: false, searchable: false},
                        {data: 'item_code', orderable: false, searchable: false},
                        {data: 'item_name', orderable: false, searchable: false},
                        {data: 'available_packing_quantity', className: 'text-end'},
                        {data: 'color', name: 'outputProduct.color'},
                        {data: 'size', name: 'outputProduct.size'},
                        {data: 'order_quantity', name: 'planned_quantity', className: 'text-end'},
                        {data: 'actions', orderable: false, searchable: false}
                    ]
                });

                $('#chooseFinishedMo').on('click', () => {
                    orders.ajax.reload();
                    modal.show();
                });
                modalElement.addEventListener('shown.bs.modal', () => orders.columns.adjust().responsive.recalc());
                $('#finishedMoTable').on('click', '.js-select-finished-mo', function () {
                    const selected = $(this);
                    $('#finishedMoId').val(selected.attr('data-id'));
                    $('#finishedMoLabel').val(selected.attr('data-no'));
                    $('#finishedStyle').val(selected.attr('data-style'));
                    $('#finishedCode').val(selected.attr('data-code'));
                    $('#finishedName').val(selected.attr('data-name'));
                    $('#finishedUnit').val(selected.attr('data-unit'));
                    $('#finishedPo').val(selected.attr('data-po'));
                    $('#finishedAvailable').val(selected.attr('data-available'));
                    $('#receivePacking').attr('max', selected.attr('data-available'));
                    modal.hide();
                });
            @endif

            $('#finishedForm').on('submit', function (event) {
                event.preventDefault();
                if (!$('#finishedMoId').val()) {
                    Swal.fire({icon: 'warning', text: @json(__('ui.finished_goods.select_mo'))});
                    return;
                }

                const save = $('#saveFinished').prop('disabled', true);
                $.post(this.action, $(this).serialize())
                    .done(result => Swal.fire({icon: 'success', text: result.message})
                        .then(() => { window.location.href = @json(route('super.finished-goods.index')); }))
                    .fail(error => {
                        save.prop('disabled', false);
                        Swal.fire({
                            icon: 'error',
                            text: Object.values(error.responseJSON?.errors || {}).flat().join('\n')
                                || error.responseJSON?.message || @json(__('ui.workflow.save_failed'))
                        });
                    });
            });
        });
    </script>
@endpush
