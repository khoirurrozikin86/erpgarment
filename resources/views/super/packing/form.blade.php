@extends('layouts.admin')

@section('title', __('ui.packing.form_title'))
@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('ui.nav.production') }}</li>
            <li class="breadcrumb-item"><a href="{{ route('super.packing.index') }}">{{ __('ui.packing.title') }}</a></li>
            <li class="breadcrumb-item active">{{ $entry ? __('ui.common.edit') : __('ui.packing.new') }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    @php
        $order = $entry?->productionOrder;
        $product = $order?->outputProduct;
    @endphp
    <form id="packingForm" class="card" method="post"
        action="{{ $entry ? route('super.packing.update', $entry) : route('super.packing.store') }}">
        @csrf
        @if ($entry)
            @method('PUT')
        @endif
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ __('ui.packing.form_title') }}</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="packingNumber">{{ __('ui.packing.number') }}</label>
                    <input id="packingNumber" class="form-control form-control-sm" value="{{ $packingNumber }}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="packingDate">{{ __('ui.packing.date') }}</label>
                    <input type="date" name="packing_date" id="packingDate" class="form-control form-control-sm"
                        value="{{ $entry?->processed_at?->toDateString() ?? now()->toDateString() }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="packingMoLabel">{{ __('ui.packing.mo') }}</label>
                    <div class="input-group input-group-sm">
                        <input id="packingMoLabel" class="form-control" value="{{ $order?->number }}" readonly>
                        @if (!$entry)
                            <button type="button" id="choosePackingMo" class="btn btn-outline-primary">{{ __('ui.common.search') }}</button>
                        @endif
                    </div>
                    <input type="hidden" name="production_order_id" id="packingMoId" value="{{ $order?->id }}">
                </div>
                @foreach ([
                    'packingProduct' => [__('ui.packing.product_code'), $product ? $order->stageProductCode('packing').' — '.$product->variant_display_name : null],
                    'packingStyle' => [__('ui.packing.style'), $order?->model_style],
                    'packingUnit' => [__('ui.packing.unit'), $product?->unit?->symbol ?: $product?->unit?->name],
                    'packingPoBuyer' => [__('ui.packing.po_buyer'), $order?->buyer_po_number],
                    'packingAvailable' => [__('ui.packing.sewing_reception'), $available],
                ] as $id => [$label, $value])
                    <div class="{{ $id === 'packingProduct' ? 'col-md-4' : 'col-md-2' }}">
                        <label class="form-label" for="{{ $id }}">{{ $label }}</label>
                        <input id="{{ $id }}" class="form-control form-control-sm" value="{{ $value }}" readonly>
                    </div>
                @endforeach
                @foreach ([
                    'input_quantity' => ['packingQty', __('ui.packing.quantity'), $entry?->input_quantity, '0.0001'],
                    'rejected_quantity' => ['packingReject', __('ui.packing.rejected'), $entry?->rejected_quantity ?? 0, '0'],
                    'send_to_finished_goods' => ['packingFinished', __('ui.packing.send_to_finished_goods'), $entry?->good_quantity ?? 0, '0'],
                ] as $name => [$id, $label, $value, $min])
                    <div class="col-md-4">
                        <label class="form-label" for="{{ $id }}">{{ $label }}</label>
                        <input type="number" name="{{ $name }}" id="{{ $id }}" step="0.0001" min="{{ $min }}"
                            @if ($name === 'input_quantity' && $available !== null) max="{{ $available }}" @endif
                            value="{{ $value }}" class="form-control form-control-sm text-end" required>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2 py-2">
            <a class="btn btn-light btn-sm" href="{{ route('super.packing.index') }}">{{ __('ui.common.cancel') }}</a>
            <button id="savePacking" type="submit" class="btn btn-primary btn-sm">{{ $entry ? __('ui.common.save_changes') : __('ui.common.save') }}</button>
        </div>
    </form>

    @foreach ([
        ['packingMoModal', 'packingMoTable', __('ui.packing.select_mo'), [
            __('ui.packing.mo'), __('ui.packing.po_buyer'), __('ui.packing.style'), __('ui.packing.product_code'),
            __('ui.packing.product_name'), __('ui.packing.sewing_reception'), __('ui.workflow.color'),
            __('ui.workflow.size'), __('ui.workflow.order_quantity'), __('ui.common.action'),
        ]],
    ] as [$modalId, $tableId, $title, $headings])
        @continue($entry)
        <div class="modal fade" id="{{ $modalId }}" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $title }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <table id="{{ $tableId }}" class="table table-sm table-bordered w-100">
                            <thead><tr>
                                @foreach ($headings as $heading)
                                    <th>{{ $heading }}</th>
                                @endforeach
                            </tr></thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
    <script>
        $(function () {
            @if (!$entry)
                const orderModal = new bootstrap.Modal(document.getElementById('packingMoModal'));
                const orderTable = $('#packingMoTable').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: @json(route('super.packing.production-orders.dt')),
                    columns: [
                        {data: 'number', name: 'number'},
                        {data: 'buyer_po_number', name: 'buyer_po_number'},
                        {data: 'style', orderable: false, searchable: false},
                        {data: 'product_code', orderable: false, searchable: false},
                        {data: 'product_name', orderable: false, searchable: false},
                        {data: 'available_sewing_quantity', className: 'text-end'},
                        {data: 'color', name: 'outputProduct.color'},
                        {data: 'size', name: 'outputProduct.size'},
                        {data: 'order_quantity', name: 'planned_quantity', className: 'text-end'},
                        {data: 'actions', orderable: false, searchable: false}
                    ]
                });

                $('#choosePackingMo').on('click', () => {
                    orderTable.ajax.reload();
                    orderModal.show();
                });
                $('#packingMoTable').on('click', '.btn-select-packing-mo', function () {
                    const selected = $(this);
                    $('#packingMoId').val(selected.attr('data-id'));
                    $('#packingMoLabel').val(selected.attr('data-number'));
                    $('#packingProduct').val(selected.attr('data-product'));
                    $('#packingStyle').val(selected.attr('data-style'));
                    $('#packingPoBuyer').val(selected.attr('data-po'));
                    $('#packingUnit').val(selected.attr('data-unit'));
                    $('#packingAvailable').val(selected.attr('data-available'));
                    $('#packingQty').attr('max', selected.attr('data-available'));
                    orderModal.hide();
                });
            @endif

            $('#packingForm').on('submit', function (event) {
                event.preventDefault();
                if (!$('#packingMoId').val()) {
                    Swal.fire({icon: 'warning', text: @json(__('ui.packing.select_mo'))});
                    return;
                }

                const save = $('#savePacking').prop('disabled', true);
                $.post(this.action, $(this).serialize())
                    .done(result => Swal.fire({icon: 'success', text: result.message})
                        .then(() => { window.location.href = @json(route('super.packing.index')); }))
                    .fail(error => Swal.fire({
                        icon: 'error',
                        title: @json(__('ui.workflow.failure')),
                        text: Object.values(error.responseJSON?.errors || {}).flat().join('\n')
                            || error.responseJSON?.message || @json(__('ui.workflow.save_failed'))
                    }))
                    .always(() => save.prop('disabled', false));
            });
        });
    </script>
@endpush
