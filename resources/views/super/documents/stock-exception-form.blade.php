@extends('layouts.admin')

@php
    $title = $line ? __('ui.common.edit').' '.__($translation.'.title') : __($translation.'.add');
    $product = $line?->product;
    $unit = $product?->unit?->symbol ?: $product?->unit?->name;
    $productLabel = $product ? $product->code.' — '.$product->name.($unit ? ' ('.$unit.')' : '') : '';
@endphp

@section('title', $title)
@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">{{ $title }}</h5>
        <a href="{{ route($routePrefix.'.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('ui.common.back') }}</a>
    </div>
    <form id="stock-exception-form" method="POST" action="{{ $line ? route($routePrefix.'.update', $line) : route($routePrefix.'.store') }}">
        @csrf
        @if($line)
            @method('PUT')
        @endif
        <div class="card-body">
            <div class="row g-3 g-xl-4">
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="stock-exception-number">{{ __($translation.'.minutes_number') }}</label>
                        <div class="col-md-8">
                            <input class="form-control form-control-sm" id="stock-exception-number" value="{{ $number }}" readonly>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="stock-exception-date">{{ __($translation.'.date') }} *</label>
                        <div class="col-md-8">
                            <input type="date" class="form-control form-control-sm" name="document_date" id="stock-exception-date"
                                value="{{ $line?->document?->document_date?->toDateString() ?? now()->toDateString() }}" required>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="stock-exception-product">{{ __($translation.'.item_code') }} *</label>
                        <div class="col-md-8">
                            <input type="hidden" name="product_id" id="stock-exception-product-id" value="{{ $line?->product_id }}">
                            <div class="input-group input-group-sm">
                                <input class="form-control" id="stock-exception-product" value="{{ $productLabel }}" readonly placeholder="{{ __($translation.'.select_item') }}">
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#stock-exception-product-modal">
                                    <i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.search') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="stock-exception-quantity">{{ __($translation.'.quantity') }} *</label>
                        <div class="col-md-8">
                            <input type="number" class="form-control form-control-sm" name="quantity" id="stock-exception-quantity"
                                value="{{ $line?->quantity }}" min="0.0001" step="0.0001" required>
                        </div>
                    </div>
                    <div class="row g-2 align-items-start mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="stock-exception-description">{{ __($translation.'.description') }}</label>
                        <div class="col-md-8">
                            <textarea class="form-control form-control-sm" name="description" id="stock-exception-description" rows="2" maxlength="5000">{{ $line?->description }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <p class="small text-muted mb-0 mt-2">{{ __($translation.'.stock_notice') }}</p>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-feather="save" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.save') }}
            </button>
        </div>
    </form>
</div>

<div class="modal fade" id="stock-exception-product-modal" tabindex="-1" aria-labelledby="stock-exception-picker-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stock-exception-picker-title">{{ __($translation.'.select_item') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
            </div>
            <div class="modal-body">
                <table id="stock-exception-products" class="table table-sm table-bordered align-middle" style="width: 100%">
                    <thead><tr>
                        <th>{{ __($translation.'.item_code') }}</th>
                        <th>{{ __($translation.'.item_name') }}</th>
                        <th>{{ __($translation.'.unit') }}</th>
                        <th>{{ __('ui.common.actions') }}</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const modal = document.getElementById('stock-exception-product-modal');
    const productTable = $('#stock-exception-products');
    let products;

    modal.addEventListener('shown.bs.modal', () => {
        if (products) {
            products.columns.adjust();
            return;
        }
        products = productTable.DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            ajax: @json(route($routePrefix.'.products.dt')),
            columns: [
                {data: 'code', name: 'code'},
                {data: 'name', name: 'name'},
                {data: 'unit_label', orderable: false, searchable: false},
                {data: 'actions', orderable: false, searchable: false, className: 'text-center'},
            ],
            drawCallback: () => window.feather && feather.replace(),
        });
    });

    productTable.on('click', 'button[data-id]', function () {
        const {id, code, name, unit} = this.dataset;
        document.getElementById('stock-exception-product-id').value = id;
        document.getElementById('stock-exception-product').value = code + ' — ' + name + (unit ? ' (' + unit + ')' : '');
        bootstrap.Modal.getInstance(modal).hide();
    });

    $('#stock-exception-form').on('submit', function (event) {
        event.preventDefault();
        const save = $(this).find('[type=submit]').prop('disabled', true);

        $.ajax({url: this.action, method: 'POST', data: $(this).serialize(), dataType: 'json'})
            .done(result => {
                const message = result.number ? result.message + ' (' + result.number + ')' : result.message;
                Swal.fire({icon: 'success', text: message}).then(() => {
                    window.location.href = @json(route($routePrefix.'.index'));
                });
            })
            .fail(xhr => {
                Swal.fire({icon: 'error', title: @json(__('ui.workflow.failure')),
                    text: Object.values(xhr.responseJSON?.errors || {}).flat().join('\n')
                        || xhr.responseJSON?.message || @json(__('ui.workflow.save_failed'))});
                save.prop('disabled', false);
            });
    });
});
</script>
@endpush
