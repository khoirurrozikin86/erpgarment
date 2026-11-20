@extends('layouts.admin')

@section('title', __('ui.support_material_order.form_title'))
@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('ui.nav.production') }}</li>
            <li class="breadcrumb-item"><a href="{{ route('super.support-material-orders.index') }}">{{ __('ui.support_material_order.title') }}</a></li>
            <li class="breadcrumb-item active">{{ $item ? __('ui.common.edit') : __('ui.support_material_order.new_order') }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    @php
        $product = $item?->product;
    @endphp
    <form id="supportOrderForm" class="card mb-3" method="post"
        action="{{ $item ? route('super.support-material-orders.update', $item) : route('super.support-material-orders.store') }}">
        @csrf
        @if ($item)
            @method('PUT')
        @elseif ($document)
            <input type="hidden" name="document_id" value="{{ $document->id }}">
        @endif
        <input type="hidden" id="supportProductId" name="product_id" value="{{ $item?->product_id }}">

        <div class="card-header bg-white">
            <h5 class="mb-0">{{ __('ui.support_material_order.form_title') }}</h5>
        </div>
        <div class="card-body">
            <div class="row gx-lg-5 gy-3">
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-5 col-form-label col-form-label-sm" for="supportNumber">{{ __('ui.support_material_order.number') }}</label>
                        <div class="col-7">
                            <input id="supportNumber" class="form-control form-control-sm" value="{{ $number }}" readonly>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-5 col-form-label col-form-label-sm" for="supportDate">{{ __('ui.support_material_order.date') }}</label>
                        <div class="col-7">
                            <input type="date" id="supportDate" name="order_date" class="form-control form-control-sm"
                                value="{{ $document?->order_date?->toDateString() ?? now()->toDateString() }}" required>
                        </div>
                    </div>
                    @foreach ([
                        'po_other' => [__('ui.support_material_order.po_other'), $item?->po_other],
                        'style' => [__('ui.support_material_order.style'), $item?->style],
                    ] as $field => [$label, $value])
                        <div class="row g-2 align-items-center mb-2">
                            <label class="col-5 col-form-label col-form-label-sm" for="support-{{ $field }}">{{ $label }}</label>
                            <div class="col-7">
                                <input id="support-{{ $field }}" name="{{ $field }}" maxlength="100"
                                    class="form-control form-control-sm" value="{{ $value }}">
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-5 col-form-label col-form-label-sm" for="supportProductLabel">{{ __('ui.support_material_order.item_code') }}</label>
                        <div class="col-7">
                            <div class="input-group input-group-sm">
                                <input id="supportProductLabel" class="form-control" value="{{ $product?->code }}" readonly>
                                <button type="button" id="chooseSupportProduct" class="btn btn-outline-primary" aria-controls="supportProducts"
                                    aria-expanded="false">{{ __('ui.common.search') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-5 col-form-label col-form-label-sm" for="supportProductName">{{ __('ui.support_material_order.item_name') }}</label>
                        <div class="col-7">
                            <input id="supportProductName" class="form-control form-control-sm" value="{{ $product?->name }}" readonly>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-5 col-form-label col-form-label-sm" for="supportUnit">{{ __('ui.support_material_order.unit') }}</label>
                        <div class="col-7">
                            <input id="supportUnit" class="form-control form-control-sm" value="{{ $product?->unit?->symbol ?: $product?->unit?->name }}" readonly>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center">
                        <label class="col-5 col-form-label col-form-label-sm" for="supportQuantity">{{ __('ui.support_material_order.quantity') }}</label>
                        <div class="col-7">
                            <input id="supportQuantity" name="quantity" type="number" min="0.0001" step="0.0001"
                                class="form-control form-control-sm text-end" value="{{ $item?->quantity }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <section id="supportProducts" class="border-top mt-3 pt-3 d-none" aria-labelledby="supportProductsTitle">
                <h6 id="supportProductsTitle">{{ __('ui.support_material_order.select_item') }}</h6>
                <div class="table-responsive">
                    <table id="supportProductsTable" class="table table-sm table-bordered align-middle w-100">
                        <thead><tr>
                            <th>{{ __('ui.support_material_order.item_code') }}</th>
                            <th>{{ __('ui.support_material_order.item_name') }}</th>
                            <th>{{ __('ui.support_material_order.unit') }}</th>
                            <th>{{ __('ui.common.action') }}</th>
                        </tr></thead>
                    </table>
                </div>
            </section>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2 py-2">
            <a href="{{ route('super.support-material-orders.index') }}" class="btn btn-light btn-sm">{{ $document && !$item ? __('ui.common.back') : __('ui.common.cancel') }}</a>
            <button type="submit" id="saveSupportOrder" class="btn btn-primary btn-sm">{{ $item ? __('ui.common.save_changes') : __('ui.common.save') }}</button>
        </div>
    </form>

    @if ($document && !$item)
        <div class="card">
            <div class="card-header bg-white">{{ __('ui.support_material_order.detail') }}</div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead><tr>
                        <th>{{ __('ui.support_material_order.item_code') }}</th>
                        <th>{{ __('ui.support_material_order.item_name') }}</th>
                        <th>{{ __('ui.support_material_order.unit') }}</th>
                        <th class="text-end">{{ __('ui.support_material_order.quantity') }}</th>
                        <th>{{ __('ui.support_material_order.po_other') }}</th>
                        <th>{{ __('ui.support_material_order.style') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($document->items as $line)
                            <tr>
                                <td>{{ $line->product?->code ?? '—' }}</td>
                                <td>{{ $line->product?->name ?? '—' }}</td>
                                <td>{{ $line->product?->unit?->symbol ?: $line->product?->unit?->name ?: '—' }}</td>
                                <td class="text-end">{{ $line->quantity }}</td>
                                <td>{{ $line->po_other ?: '—' }}</td>
                                <td>{{ $line->style ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        $(function () {
            let products;
            const picker = $('#supportProducts');
            const choose = $('#chooseSupportProduct');

            choose.on('click', () => {
                const opening = picker.hasClass('d-none');
                picker.toggleClass('d-none', !opening);
                choose.attr('aria-expanded', opening);
                if (!opening) return;

                if (!products) {
                    products = $('#supportProductsTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: @json(route('super.support-material-orders.products.dt')),
                        columns: [
                            {data: 'code', name: 'code'},
                            {data: 'name', name: 'name'},
                            {data: 'unit_label', orderable: false, searchable: false},
                            {data: 'actions', orderable: false, searchable: false}
                        ]
                    });
                }
                products.columns.adjust();
            });

            picker.on('click', '.btn-select-support-product', function () {
                const selected = $(this);
                $('#supportProductId').val(selected.attr('data-id'));
                $('#supportProductLabel').val(selected.attr('data-code'));
                $('#supportProductName').val(selected.attr('data-name'));
                $('#supportUnit').val(selected.attr('data-unit'));
                picker.addClass('d-none');
                choose.attr('aria-expanded', false);
                $('#supportQuantity').focus();
            });

            $('#supportOrderForm').on('submit', function (event) {
                event.preventDefault();
                if (!$('#supportProductId').val()) {
                    Swal.fire({icon: 'warning', text: @json(__('ui.support_material_order.select_item'))});
                    return;
                }

                const save = $('#saveSupportOrder').prop('disabled', true);
                $.post(this.action, $(this).serialize()).done(result => {
                    const destination = new URL(@json(route('super.support-material-orders.index')), window.location.href);
                    if (result.document_id) {
                        destination.href = @json(route('super.support-material-orders.create'));
                        destination.searchParams.set('document_id', result.document_id);
                    }
                    Swal.fire({icon: 'success', text: result.message})
                        .then(() => { window.location.href = destination.href; });
                }).fail(error => {
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
