@extends('layouts.admin')

@section('title', __('ui.common.price_list'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('ui.common.master') }}</li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.price_list') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="row">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ __('ui.common.price_list') }}</h4>
            <p class="text-muted mb-0">{{ __('ui.master.price_list_subtitle') }}</p>
        </div>
        @can('pricelists.create')
            <button type="button" class="btn btn-primary" id="btn-add-pricelist">
                <i data-feather="plus" class="icon-sm me-1"></i> {{ __('ui.master.add_price_list') }}
            </button>
        @endcan
    </div>

    <div class="card"><div class="card-body"><div class="table-responsive">
        <table id="pricelists-table" class="table table-bordered table-hover align-middle w-100">
            <thead><tr>
                <th width="50">{{ __('ui.common.no') }}</th><th>{{ __('ui.common.code') }}</th><th>{{ __('ui.master.price_list_name') }}</th><th>{{ __('ui.common.type') }}</th><th>{{ __('ui.master.customer_supplier') }}</th>
                <th>{{ __('ui.master.period') }}</th><th>{{ __('ui.common.item_count') }}</th><th>{{ __('ui.common.status') }}</th><th width="80">{{ __('ui.common.action') }}</th>
            </tr></thead><tbody></tbody>
        </table>
    </div></div></div>
    </div>

    <div class="modal fade" id="priceListModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <form id="priceListForm">
                @csrf
                <div class="modal-header">
                    <div><h5 class="modal-title" id="priceListModalTitle">{{ __('ui.master.add_price_list') }}</h5>
                        <small class="text-muted">{{ __('ui.master.price_list_help') }}</small></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div id="price-list-alert"></div>
                    <input type="hidden" id="price_list_id">
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">{{ __('ui.common.code') }}<span class="text-danger">*</span></label><input class="form-control" name="code" id="price_list_code" maxlength="50" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label">{{ __('ui.common.name') }}<span class="text-danger">*</span></label><input class="form-control" name="name" id="price_list_name" maxlength="150" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label">{{ __('ui.master.price_type') }} <span class="text-danger">*</span></label>
                            <select class="form-select" name="type" id="price_list_type" required><option value="sales">{{ __('ui.common.selling_price') }}</option><option value="purchase">{{ __('ui.common.purchase_price') }}</option></select>
                        </div>
                        <div class="col-md-6 mb-3" id="customer-field"><label class="form-label">{{ __('ui.master.optional_customer') }}</label>
                            <select class="form-select" name="customer_id" id="price_list_customer"><option value="">{{ __('ui.common.general') }}</option>
                                @foreach ($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 d-none" id="supplier-field"><label class="form-label">{{ __('ui.master.optional_supplier') }}</label>
                            <select class="form-select" name="supplier_id" id="price_list_supplier"><option value="">{{ __('ui.common.general') }}</option>
                                @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3"><label class="form-label">{{ __('ui.master.valid_from') }}</label><input type="date" class="form-control" name="valid_from" id="price_list_valid_from"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">{{ __('ui.common.valid_until') }}</label><input type="date" class="form-control" name="valid_until" id="price_list_valid_until"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                        <h6 class="fw-bold mb-0">{{ __('ui.master.item_prices') }}</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-price-item"><i data-feather="plus" class="me-1"></i>{{ __('ui.common.add_item') }}</button>
                    </div>
                    <div class="table-responsive"><table class="table table-sm table-bordered align-middle">
                        <thead><tr><th style="min-width:260px">{{ __('ui.common.item') }}</th><th style="width:220px">{{ __('ui.common.price') }}</th><th style="width:70px">{{ __('ui.common.actions') }}</th></tr></thead>
                        <tbody id="price-list-items"></tbody>
                    </table></div>
                    <div class="row align-items-center">
                        <div class="col-md-3 mb-3"><input type="hidden" name="is_active" value="0"><div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="price_list_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="price_list_active">{{ __('ui.common.active') }}</label>
                        </div></div>
                        <div class="col-md-9 mb-3"><label class="form-label">{{ __('ui.common.notes') }}</label><input class="form-control" name="notes" id="price_list_notes" maxlength="2000"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-pricelist"><i data-feather="save" class="icon-sm me-1"></i>{{ __('ui.common.save') }}</button>
                </div>
            </form>
        </div></div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const modal = new bootstrap.Modal(document.getElementById('priceListModal'));
    let nextItemIndex = 0;
    const products = @json($products->map(fn ($product) => ['id' => $product->id, 'label' => $product->code . ' — ' . $product->name . ($product->is_active ? '' : ' (Nonaktif)')])->values());
    const table = $('#pricelists-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: @json(route('super.pricelists.dt')),
        columns: [
            { data: 'id', name: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { data: 'type_label', name: 'type' },
            { data: 'party_name', name: 'party_name', orderable: false, searchable: false, defaultContent: '{{ __('ui.master.general') }}' },
            { data: 'period', name: 'valid_from', orderable: false },
            { data: 'items_count', name: 'items_count', searchable: false },
            { data: 'status', name: 'is_active', searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'asc']],
        drawCallback: function () {
            if (window.feather) feather.replace();
        }
    });

    function addItem(item = {}) {
        const itemIndex = nextItemIndex++;
        const row = $('<tr>');
        const select = $('<select>', { class: 'form-select product-select', name: `items[${itemIndex}][product_id]`, required: true })
            .append($('<option>', { value: '', text: '{{ __('ui.master.select_item') }}' }));
        products.forEach(product => select.append($('<option>', { value: product.id, text: product.label })));
        if (item.product_id) select.val(item.product_id);
        row.append($('<td>').append(select));
        row.append($('<td>').append($('<input>', {
            type: 'number', class: 'form-control', name: `items[${itemIndex}][price]`, min: 0, step: '0.01', required: true,
            value: item.price ?? 0
        })));
        const removeButton = $('<button>', { type: 'button', class: 'btn btn-sm btn-outline-danger btn-remove-price-item', title: '{{ __('ui.master.remove_item') }}' });
        removeButton.append($('<i>', { 'data-feather': 'x' }));
        row.append($('<td class="text-center">').append(removeButton));
        $('#price-list-items').append(row);
        if (window.feather) feather.replace();
    }

    function resetForm() {
        $('#priceListForm')[0].reset();
        $('#priceListForm').removeData('update-url');
        nextItemIndex = 0;
        $('#price_list_id').val('');
        $('#price_list_type').val('sales');
        $('#price_list_customer').val('');
        $('#price_list_supplier').val('');
        $('#customer-field').removeClass('d-none');
        $('#supplier-field').addClass('d-none');
        $('#price_list_active').prop('checked', true);
        $('#price-list-items').empty();
        $('#price-list-alert').empty();
        addItem();
    }

    $('#btn-add-pricelist').on('click', function () {
        resetForm();
        $('#priceListModalTitle').text('{{ __('ui.master.add_price_list') }}');
        modal.show();
    });

    $('#btn-add-price-item').on('click', () => addItem());
    $(document).on('click', '.btn-remove-price-item', function () {
        $(this).closest('tr').remove();
    });

    $(document).on('click', '.btn-edit-pricelist', function () {
        let data;
        try { data = JSON.parse($(this).attr('data-payload')); } catch (error) { return; }
        resetForm();
        $('#priceListModalTitle').text('{{ __('ui.common.edit') }} {{ __('ui.common.price_list') }}');
        $('#priceListForm').data('update-url', $(this).data('update-url'));
        $('#price_list_id').val(data.id);
        $('#price_list_code').val(data.code);
        $('#price_list_name').val(data.name);
        $('#price_list_type').val(data.type);
        $('#price_list_type').trigger('change');
        $('#price_list_customer').val(data.customer_id || '');
        $('#price_list_supplier').val(data.supplier_id || '');
        $('#price_list_valid_from').val(data.valid_from || '');
        $('#price_list_valid_until').val(data.valid_until || '');
        $('#price_list_active').prop('checked', Boolean(data.is_active));
        $('#price_list_notes').val(data.notes || '');
        $('#price-list-items').empty();
        nextItemIndex = 0;
        (data.items || []).forEach(item => addItem(item));
        modal.show();
    });

    $('#price_list_type').on('change', function () {
        const purchase = $(this).val() === 'purchase';
        $('#customer-field').toggleClass('d-none', purchase);
        $('#supplier-field').toggleClass('d-none', !purchase);
        if (purchase) $('#price_list_customer').val('');
        else $('#price_list_supplier').val('');
    });

    $('#priceListForm').on('submit', function (event) {
        event.preventDefault();
        const id = $('#price_list_id').val();
        const url = id ? $('#priceListForm').data('update-url') : @json(route('super.pricelists.store'));
        const data = $(this).serializeArray();
        data.push({ name: 'is_active', value: $('#price_list_active').is(':checked') ? 1 : 0 });
        $('#btn-save-pricelist').prop('disabled', true);
        $.ajax({ url, method: id ? 'PUT' : 'POST', data })
            .done(function (response) {
                modal.hide();
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: '{{ __('ui.master.success') }}', text: response.message, confirmButtonText: '{{ __('ui.master.ok') }}' });
            })
            .fail(function (xhr) {
                const errors = xhr.responseJSON?.errors || {};
                const message = Object.values(errors).flat().join('<br>') || xhr.responseJSON?.message || '{{ __('ui.master.failure') }}';
                $('#price-list-alert').html($('<div class="alert alert-danger">').html(message));
            })
            .always(() => $('#btn-save-pricelist').prop('disabled', false));
    });

    $(document).on('click', '.btn-delete-pricelist', function () {
        const button = $(this);
        Swal.fire({ icon: 'warning', title: '{{ __('ui.master.confirmation') }}', text: button.data('confirm'), showCancelButton: true,
            confirmButtonText: '{{ __('ui.master.confirm_delete') }}', cancelButtonText: '{{ __('ui.common.cancel') }}' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'DELETE', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
                .done(response => {
                    table.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: '{{ __('ui.master.success') }}', text: response.message });
                })
                .fail(xhr => Swal.fire({ icon: 'error', title: '{{ __('ui.master.failure') }}', text: xhr.responseJSON?.message || '{{ __('ui.master.cannot_delete') }}' }));
        });
    });
});
</script>
@endpush
