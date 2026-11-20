@extends('layouts.admin')
@include('super.boms.partials.feedback')
@section('title', $bom->exists ? __('ui.workflow.bom_edit') : __('ui.workflow.bom_create'))
@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('super.boms.index') }}">{{ __('ui.workflow.bom_title') }}</a></li><li class="breadcrumb-item active">{{ $bom->exists ? __('ui.workflow.bom_edit') : __('ui.workflow.bom_create') }}</li></ol></nav>
<div class="modal fade" id="bomStyleModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5>{{ __('variants.choose_style') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="table-responsive"><table class="table w-100" id="bomStyleTable"><thead><tr><th>Style</th><th>{{ __('ui.common.name') }}</th><th></th></tr></thead><tbody>@foreach($styles as $style)<tr><td>{{ $style->code }}</td><td>{{ $style->name }}</td><td><button type="button" class="btn btn-sm btn-primary choose-bom-style" data-id="{{ $style->code }}" data-label="{{ $style->code }} — {{ $style->name }}">{{ __('ui.common.select') }}</button></td></tr>@endforeach</tbody></table></div></div></div></div></div>
@endsection
@section('content')
@php
    $finishedGood = $finishedGoods->firstWhere('id', old('product_id', $bom->product_id));
    $oldComponents = old('items', $components->map(fn ($row) => [
        'product_id' => $row->product_id,
        'quantity' => $row->quantity,
        'span' => $row->span,
        'wastage_percent' => $row->wastage_percent,
        'operation' => $row->operation,
        'notes' => $row->notes,
        'applies_to_color' => $row->applies_to_color,
        'applies_to_size' => $row->applies_to_size,
    ])->all());
    $itemOptions = $products->reject(fn ($item) => $item->id === $finishedGood?->id)->map(fn ($item) => [
        'id' => $item->id,
        'parent_code' => $item->parentProduct?->code ?? $item->code,
        'sku_code' => $item->parent_product_id ? $item->code : null,
        'name' => $item->variant_display_name,
        'category' => $item->category?->name,
        'unit' => $item->unit?->symbol ?: $item->unit?->name,
    ])->values();
@endphp
<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="mb-0">{{ $bom->exists ? __('ui.workflow.bom_edit') : __('ui.workflow.bom_create') }}</h5>
        <a href="{{ route('super.boms.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.back') }}</a>
    </div>
    <form id="bomForm" method="POST" action="{{ $bom->exists ? route('super.boms.update', $bom) : route('super.boms.store') }}">
        @csrf
        @if($bom->exists)
            @method('PUT')
        @endif
        @php($selectedStyle = $styles->firstWhere('code', old('style_code', $bom->style_code)) ?? $styles->firstWhere('code', $bom->product?->style_code))
        <div class="card-body">
            <p class="small text-muted mb-3">{{ __('variants.bom_manual_help') }}</p>
            <div class="row g-3 g-xl-4">
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="bomNumber">{{ __('variants.bom_number') }}</label>
                        <div class="col-md-8">
                            <input id="bomNumber" class="form-control form-control-sm" readonly value="{{ $bom->number ?: __('variants.bom_auto_number') }}">
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="bomStyleLabel">{{ __('ui.workflow.style') }}</label>
                        <div class="col-md-8">
                            <div class="input-group input-group-sm">
                                <input type="hidden" name="style_code" id="bomStyleId" value="{{ $selectedStyle?->code }}">
                                <input id="bomStyleLabel" class="form-control" readonly value="{{ $selectedStyle ? $selectedStyle->code.' — '.$selectedStyle->name : '' }}" placeholder="{{ __('ui.common.select') }}">
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#bomStyleModal">
                                    <i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.search') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="bomType">{{ __('variants.bom_type') }}</label>
                        <div class="col-md-8">
                            <select class="form-select form-select-sm" id="bomType" name="bom_type" required>
                                <option value="">{{ __('ui.common.select') }}</option>
                                @foreach(['fabric', 'accessory', 'general'] as $type)
                                    <option value="{{ $type }}" @selected(old('bom_type', $bom->bom_type) === $type)>{{ __('variants.bom_type_'.$type) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <section class="border-top mt-3 pt-3" aria-labelledby="bom-matrix-title">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2"><h6 id="bom-matrix-title" class="text-primary mb-0">{{ __('variants.consumption_matrix') }}</h6><div class="d-flex gap-2"><button type="button" id="bomAddSize" class="btn btn-outline-secondary btn-sm">{{ __('variants.add_size') }}</button><button type="button" id="bomAddMatrix" class="btn btn-outline-primary btn-sm">{{ __('ui.common.add_item') }}</button></div></div>
                <p class="text-muted small mb-2">{{ __('variants.consumption_matrix_hint') }}</p>
                    <div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr>
                        <th style="min-width:250px">{{ __('ui.common.item') }}</th><th>{{ __('variants.stock_parent_code') }}</th><th>{{ __('variants.stock_sku') }}</th><th style="min-width:130px">{{ __('variants.process') }}</th><th style="min-width:140px">{{ __('ui.workflow.color') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('variants.allowance') }} %</th>
                        @foreach(['', 'XS','S','M','L','XL','XXL','3XL','4XL','5XL'] as $size)<th class="bom-size-header" style="min-width:85px">{{ $size ?: __('variants.all_sizes') }}</th>@endforeach
                        <th></th></tr></thead><tbody id="bomMatrixRows"></tbody></table></div>
            </section>
            <section class="border-top mt-3 pt-3" aria-labelledby="bom-material-title">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2"><div><h6 id="bom-material-title" class="text-primary mb-0">Material / Support</h6><small class="text-muted">Pilih item, lalu isi konsumsi per produk</small></div><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#bomMaterialModal">Pilih Material</button></div>
                <p class="text-muted small mb-2">{{ __('variants.selector_help') }}</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle"><thead><tr><th>{{ __('variants.stock_parent_code') }}</th><th>{{ __('variants.stock_sku') }}</th><th>Name Material/Support</th><th>Unit</th><th>{{ __('variants.applies_color') }}</th><th>{{ __('variants.applies_size') }}</th><th>{{ __('variants.process') }}</th><th>Consumption per PCS</th><th>Span</th><th>Scrap (%)</th><th></th></tr></thead>
                    <tbody id="bomMaterialRows">
                    @foreach($oldComponents as $i => $row)
                        @php($material = $products->firstWhere('id', $row['product_id'] ?? null) ?? $components->firstWhere('product_id', $row['product_id'] ?? null)?->product)
                        @if($material)
                        <tr data-product-id="{{ $material->id }}">
                            <td>{{ $material->parentProduct?->code ?? $material->code }}<input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $material->id }}"></td><td>{{ $material->parent_product_id ? $material->code : '—' }}</td><td>{{ $material->variant_display_name }}<input type="hidden" name="items[{{ $i }}][notes]" value="{{ $row['notes'] ?? '' }}"></td><td>{{ $material->unit?->symbol ?: $material->unit?->name ?: '—' }}</td>
                            <td><input class="form-control" name="items[{{ $i }}][applies_to_color]" maxlength="80" value="{{ $row['applies_to_color'] ?? '' }}" placeholder="{{ __('variants.all') }}"></td>
                            <td><input class="form-control" name="items[{{ $i }}][applies_to_size]" maxlength="40" value="{{ $row['applies_to_size'] ?? '' }}" placeholder="{{ __('variants.all') }}"></td>
                            <td><input class="form-control form-control-sm" name="items[{{ $i }}][operation]" maxlength="40" value="{{ $row['operation'] ?? '' }}"></td><td><input class="form-control" type="number" min="0.0001" step="0.0001" name="items[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? '' }}" required></td><td><input class="form-control" type="number" min="0" step="0.0001" name="items[{{ $i }}][span]" value="{{ $row['span'] ?? 0 }}"></td><td><input class="form-control" type="number" min="0" max="100" step="0.001" name="items[{{ $i }}][wastage_percent]" value="{{ $row['wastage_percent'] ?? 0 }}"></td><td><button class="btn btn-outline-danger remove-bom-material" type="button">×</button></td>
                        </tr>
                        @endif
                    @endforeach
                    </tbody></table>
                    <div id="bom-empty-state" class="text-muted text-center py-3 {{ count($oldComponents) ? 'd-none' : '' }}">Pilih material untuk mulai mengisi BOM.</div>
                </div>
            </section>
        </div>
        <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
            <a href="{{ route('super.boms.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.workflow.bom_cancel') }}</a>
            <button class="btn btn-primary btn-sm" type="submit">
                <i data-feather="save" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.workflow.bom_save') }}
            </button>
        </div>
    </form>
</div>

<div class="modal fade" id="bomMaterialModal" tabindex="-1" aria-labelledby="bomMaterialModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><div><h5 class="modal-title mb-1" id="bomMaterialModalTitle">Pilih Item Material / Support</h5><small class="text-muted">Cari dan pilih item dari daftar</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="bomMaterialOptionsTable">
                    <thead><tr>
                        <th>{{ __('variants.stock_parent_code') }}</th><th>{{ __('variants.stock_sku') }}</th>
                        <th>Nama Item</th>
                        <th>{{ __('ui.common.category') }}</th>
                        <th>{{ __('ui.common.unit') }}</th>
                        <th>{{ __('ui.common.action') }}</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div></div>
</div>
@endsection
@push('styles')
<style>
    #bomMaterialOptionsTable_wrapper .dataTables_length label, #bomMaterialOptionsTable_wrapper .dataTables_filter label { display: inline-flex; align-items: center; gap: .45rem; margin-bottom: 0; white-space: nowrap; }
    #bomMaterialOptionsTable_wrapper .dataTables_length select { width: auto !important; min-width: 4.25rem; height: 2rem; box-sizing: border-box; margin: 0 !important; padding: .2rem 1.75rem .2rem .5rem; border: 1px solid #dfe5ec; border-radius: .45rem; font-size: .8125rem; line-height: 1.25; }
    #bomMaterialOptionsTable_wrapper .dataTables_filter input { height: 2rem; margin-left: 0; padding: .25rem .55rem; border: 1px solid #dfe5ec; border-radius: .45rem; font-size: .8125rem; }
    #bomMaterialOptionsTable_wrapper .dataTables_info, #bomMaterialOptionsTable_wrapper .dataTables_paginate { color: #748094; font-size: .82rem; }
</style>
@endpush
@push('scripts')
<script>
(() => {
    const items = @json($itemOptions);
    const rows = document.getElementById('bomMaterialRows');
    const empty = document.getElementById('bom-empty-state');
    let nextIndex = {{ count($oldComponents) }};
    let materialTable = null;
    let matrixTarget = null;
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

    function confirmRemoveRow(remove) {
        Swal.fire({
            icon: 'warning',
            title: @json(__('variants.bom_item_delete_confirm')),
            showCancelButton: true,
            confirmButtonText: @json(__('ui.common.delete')),
            cancelButtonText: @json(__('ui.common.cancel')),
        }).then(result => {
            if (result.isConfirmed) remove();
        });
    }

    function initializeMaterialTable() {
        if (materialTable) return;
        materialTable = $('#bomMaterialOptionsTable').DataTable({
            data: items,
            pageLength: 10,
            lengthMenu: [5, 10, 50, 100],
            columns: [
                { data: 'parent_code', render: $.fn.dataTable.render.text() },
                { data: 'sku_code', defaultContent: '—', render: $.fn.dataTable.render.text() },
                { data: 'name', render: $.fn.dataTable.render.text() },
                { data: 'category', defaultContent: '—', render: $.fn.dataTable.render.text() },
                { data: 'unit', defaultContent: '—', render: $.fn.dataTable.render.text() },
                { data: null, orderable: false, searchable: false, render: (data, type, item) => `<button type="button" class="btn btn-sm btn-primary choose-bom-material" data-id="${item.id}">${escapeHtml(@json(__('ui.common.select')))}</button>` },
            ],

        });
    }

    const materialModal = document.getElementById('bomMaterialModal');
    materialModal.addEventListener('show.bs.modal', event => {
        if (event.relatedTarget) matrixTarget = null;
    });
    materialModal.addEventListener('shown.bs.modal', () => {
        initializeMaterialTable();
        materialTable.columns.adjust();
        $(materialModal).find('.dataTables_filter input').trigger('focus');
    });
    materialModal.addEventListener('hidden.bs.modal', () => { matrixTarget = null; });
    function setMatrixItem(row, item) {
        row.find('.bom-matrix-product').val(item?.id || '');
        row.find('.bom-matrix-item-label').text(item ? item.name : @json(__('ui.common.select')));
        row.find('.bom-matrix-parent-code').text(item?.parent_code || '—');
        row.find('.bom-matrix-sku-code').text(item?.sku_code || '—');
        row.find('.bom-matrix-unit').text(item?.unit || '—');
    }
    $('#bomMatrixRows').on('click', '.bom-matrix-item-picker', function () {
        matrixTarget = $(this).closest('tr');
        bootstrap.Modal.getOrCreateInstance(materialModal).show();
    });
    document.getElementById('bomMaterialOptionsTable').addEventListener('click', event => {
        const button = event.target.closest('.choose-bom-material');
        if (!button) return;
        const item = items.find(candidate => String(candidate.id) === button.dataset.id);
        if (!item) return;
        if (matrixTarget && document.body.contains(matrixTarget[0])) {
            setMatrixItem(matrixTarget, item);
            bootstrap.Modal.getOrCreateInstance(materialModal).hide();
            return;
        }
        const index = nextIndex++;
        rows.insertAdjacentHTML('beforeend', `<tr data-product-id="${item.id}">
            <td>${escapeHtml(item.parent_code)}<input type="hidden" name="items[${index}][product_id]" value="${item.id}"></td>
            <td>${escapeHtml(item.sku_code || '—')}</td>
            <td>${escapeHtml(item.name)}<input type="hidden" name="items[${index}][notes]" value=""></td>
            <td>${escapeHtml(item.unit || '—')}</td>
            <td><input class="form-control" name="items[${index}][applies_to_color]" maxlength="80" placeholder="{{ __('variants.all') }}"></td>
            <td><input class="form-control" name="items[${index}][applies_to_size]" maxlength="40" placeholder="{{ __('variants.all') }}"></td>
            <td><input class="form-control form-control-sm" name="items[${index}][operation]" maxlength="40"></td><td><input class="form-control" type="number" min="0.0001" step="0.0001" name="items[${index}][quantity]" required></td>
            <td><input class="form-control" type="number" min="0" step="0.0001" name="items[${index}][span]" value="0"></td>
            <td><input class="form-control" type="number" min="0" max="100" step="0.001" name="items[${index}][wastage_percent]" value="0"></td>
            <td><button class="btn btn-outline-danger remove-bom-material" type="button">×</button></td></tr>`);
        empty.classList.add('d-none');
        materialTable?.draw(false);
        bootstrap.Modal.getOrCreateInstance(materialModal).hide();
    });
    const matrixSizes = ['', 'XS','S','M','L','XL','XXL','3XL','4XL','5XL'];
    $('#bomAddMatrix').on('click', function () {
        const row = $('<tr>');
        const selectedId = $('<input type="hidden" class="bom-matrix-product">');
        const picker = $('<button type="button" class="form-control form-control-sm text-start bom-matrix-item-picker" style="min-height:32px;white-space:normal">');
        picker.append($('<span class="bom-matrix-item-label">').text(@json(__('ui.common.select'))));
        row.append($('<td>').append(selectedId, picker));
        row.append($('<td class="bom-matrix-parent-code text-nowrap">').text('—'));
        row.append($('<td class="bom-matrix-sku-code text-nowrap">').text('—'));
        row.append($('<td>').append($('<input class="form-control form-control-sm bom-matrix-operation" maxlength="40">')));
        row.append($('<td>').append($('<input class="form-control form-control-sm bom-matrix-color" maxlength="80">')));
        row.append($('<td class="bom-matrix-unit">').text('—'));
        row.append($('<td>').append($('<input class="form-control form-control-sm bom-matrix-waste" type="number" min="0" max="100" step="0.001" value="0">')));
        matrixSizes.forEach(size => row.append($('<td>').append($('<input class="form-control form-control-sm bom-matrix-cons" type="number" min="0" step="0.0001">').attr('data-size', size))));
        row.append($('<td class="text-nowrap">').append($('<button type="button" class="btn btn-sm btn-outline-primary me-1">').text(@json(__('variants.add_color'))).on('click', () => {
            $('#bomAddMatrix').trigger('click');
            const added = $('#bomMatrixRows tr').last();
            setMatrixItem(added, items.find(item => String(item.id) === String(selectedId.val())));
            added.find('.bom-matrix-operation').val(row.find('.bom-matrix-operation').val());
        })).append($('<button type="button" class="btn btn-sm btn-outline-danger">×</button>').on('click', () => confirmRemoveRow(() => row.remove()))));
        $('#bomMatrixRows').append(row);
    });
    $('#bomAddSize').on('click', function () {
        const size = prompt(@json(__('variants.size_code_prompt')))?.trim();
        if (!size || matrixSizes.some(s => s.toLowerCase() === size.toLowerCase()) || size.length > 40) return;
        matrixSizes.push(size);
        const table = $('#bomMatrixRows').closest('table');
        table.find('thead tr th').last().before($('<th style="min-width:85px">').text(size));
        $('#bomMatrixRows tr').each(function () {
            $(this).children('td').last().before($('<td>').append($('<input class="form-control form-control-sm bom-matrix-cons" type="number" min="0" step="0.0001">').attr('data-size', size)));
        });
    });
    $('#bomForm').on('submit', function(event) {
        if (!$('#bomStyleId').val()) {
            event.preventDefault();
            Swal.fire({icon: 'warning', text: @json(__('variants.choose_style'))});
        }
    });
    let styleTable;
    $('#bomStyleModal').on('shown.bs.modal', function () {
        if (!styleTable) styleTable = $('#bomStyleTable').DataTable({pageLength: 10});
        styleTable.columns.adjust();
    });
    $(document).on('click', '.choose-bom-style', function () {
        $('#bomStyleId').val(this.dataset.id); $('#bomStyleLabel').val(this.dataset.label);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('bomStyleModal')).hide();
    });
    $('#bomMatrixRows').on('change', '.bom-matrix-product', function () {
        $(this).closest('tr').find('.bom-matrix-unit').text(items.find(i => String(i.id) === String(this.value))?.unit || '—');
    });
    $('#bomForm').on('submit', function (event) {
        $(this).find('.bom-matrix-generated').remove();
        let invalid = false;
        $('#bomMatrixRows tr').each(function () {
            const row = $(this), product = row.find('.bom-matrix-product').val();
            if (!product) { invalid = true; row.find('.bom-matrix-item-picker').addClass('is-invalid'); }
            else row.find('.bom-matrix-item-picker').removeClass('is-invalid');
            let count = 0;
            row.find('.bom-matrix-cons').each(function () {
                if (!this.value || Number(this.value) === 0) return;
                count++;
                const index = nextIndex++;
                const data = {product_id: product, quantity: this.value, operation: row.find('.bom-matrix-operation').val(), applies_to_color: row.find('.bom-matrix-color').val(),
                    applies_to_size: this.dataset.size, wastage_percent: row.find('.bom-matrix-waste').val() || 0, span: 0};
                Object.entries(data).forEach(([key,value]) => $('#bomForm').append($('<input type="hidden" class="bom-matrix-generated">').attr('name', `items[${index}][${key}]`).val(value)));
            });
            if (!count) invalid = true;
        });
        if (invalid && !event.isDefaultPrevented()) {
            event.preventDefault();
            Swal.fire({icon: 'warning', text: @json(__('variants.matrix_consumption_required'))});
        }
    });
    rows.addEventListener('click', event => {
        const button = event.target.closest('.remove-bom-material');
        if (!button) return;
        confirmRemoveRow(() => {
            button.closest('tr').remove();
            empty.classList.toggle('d-none', rows.querySelectorAll('tr').length > 0);
            materialTable?.draw(false);
        });
    });
})();
</script>
@endpush
