@extends('layouts.admin')

@section('title', __( 'ui.common.create_stock_count' ))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('super.stock-opnames.index') }}">{{ __('ui.common.stock_opname') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.create_opname') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="page-title mb-1">{{ __('ui.common.create_stock_count') }}</h4><span class="text-muted">{{ __('ui.workflow.select_item_prompt') }}</span></div>
        <a href="{{ route('super.stock-opnames.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i>{{ __('ui.common.back') }}</a>
    </div>

    <form id="createStockOpnameForm">
        @csrf
        <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">{{ __('ui.workflow.items_counted') }}</h6>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#selectProductsModal"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.choose_item') }}</button>
        </div><div class="card-body">
            <div class="table-responsive"><table class="table table-sm table-bordered align-middle">
                <thead><tr><th>{{ __('ui.common.code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.unit') }}</th><th class="text-center" style="width:80px">{{ __('ui.common.action') }}</th></tr></thead>
                <tbody id="selectedProducts"><tr><td colspan="4" class="text-center text-muted">{{ __('ui.common.no_selected_items') }}</td></tr></tbody>
            </table></div>
            <div class="mb-3"><label class="form-label" for="notes">{{ __('ui.common.notes') }}</label><textarea id="notes" class="form-control" rows="2" maxlength="5000" placeholder="{{ __('ui.workflow.stocktake_notes_example') }}"></textarea></div>
        </div></div>
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('super.stock-opnames.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.cancel') }}</a><button type="submit" id="submitStockOpname" class="btn btn-primary btn-sm" disabled><i data-feather="check" class="icon-sm me-1"></i>{{ __('ui.workflow.stocktake_document') }}</button></div>
    </form>

    <div class="modal fade" id="selectProductsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ __('ui.workflow.choose_stock_item') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body"><div class="table-responsive"><table id="stockOpnameProductsTable" class="table table-bordered table-hover align-middle w-100">
                <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead>
            </table></div></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const selected = new Map();
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('selectProductsModal'));
            const safe = value => $('<div>').text(value ?? '').html();

            function renderSelected() {
                if (!selected.size) {
                    $('#selectedProducts').html('<tr><td colspan="4" class="text-center text-muted">{{ __('ui.common.no_selected_items') }}</td></tr>');
                    $('#submitStockOpname').prop('disabled', true);
                    return;
                }
                const rows = [...selected.values()].map(product => `<tr><td>${safe(product.code)}</td><td>${safe(product.name)}</td><td>${safe(product.unit || '—')}</td><td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-opname-product" data-id="${product.id}" title="{{ __('ui.workflow.remove') }}"><i data-feather="x" class="icon-sm"></i></button></td></tr>`).join('');
                $('#selectedProducts').html(rows);
                $('#submitStockOpname').prop('disabled', false);
                if (window.feather) feather.replace();
            }

            $('#stockOpnameProductsTable').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: @json(route('super.stock-opnames.products.dt')),
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'code', name: 'code' },
                    { data: 'name', name: 'name' },
                    { data: 'unit_label', orderable: false, searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[2, 'asc']],
                drawCallback: function() {
                    $('#stockOpnameProductsTable .btn-toggle-opname-product').each(function() {
                        const button = $(this);
                        const isSelected = selected.has(Number(button.data('id')));
                        button.toggleClass('btn-primary', isSelected)
                            .toggleClass('btn-outline-primary', !isSelected)
                            .html(isSelected
                                ? '<i data-feather="check" class="icon-sm me-1"></i>{{ __('ui.workflow.selected') }}'
                                : '<i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.workflow.select') }}');
                    });
                    if (window.feather) feather.replace();
                }
            });

            $(document).on('click', '.btn-toggle-opname-product', function() {
                const button = $(this);
                const id = Number(button.data('id'));
                if (selected.has(id)) {
                    selected.delete(id);
                    button.removeClass('btn-primary').addClass('btn-outline-primary').html('<i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.workflow.select') }}');
                } else {
                    selected.set(id, { id, code: button.data('code'), name: button.data('name'), unit: button.data('unit') });
                    button.removeClass('btn-outline-primary').addClass('btn-primary').html('<i data-feather="check" class="icon-sm me-1"></i>{{ __('ui.workflow.selected') }}');
                }
                renderSelected();
                if (window.feather) feather.replace();
            });

            $(document).on('click', '.btn-remove-opname-product', function() {
                selected.delete(Number($(this).data('id')));
                renderSelected();
            });

            $('#createStockOpnameForm').on('submit', function(event) {
                event.preventDefault();
                if (!selected.size) return;
                $('#submitStockOpname').prop('disabled', true);
                $.ajax({
                    url: @json(route('super.stock-opnames.store')),
                    method: 'POST',
                    data: { _token: @json(csrf_token()), product_ids: [...selected.keys()], notes: $('#notes').val() },
                    success: response => Swal.fire({ icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message }).then(() => window.location.href = response.url),
                    error: xhr => {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || '{{ __('ui.workflow.stocktake_create_failed') }}');
                        Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: message });
                        $('#submitStockOpname').prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
