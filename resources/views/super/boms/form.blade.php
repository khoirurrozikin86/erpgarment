@extends('layouts.admin')
@section('title', $bom->exists ? __('ui.workflow.bom_edit') : __('ui.workflow.bom_create'))
@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('super.boms.index') }}">{{ __('ui.workflow.bom_title') }}</a></li><li class="breadcrumb-item active">{{ $bom->exists ? __('ui.workflow.bom_edit') : __('ui.workflow.bom_create') }}</li></ol></nav>
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
    ])->all());
    $itemOptions = $products->reject(fn ($item) => $item->id === $finishedGood?->id)->map(fn ($item) => [
        'id' => $item->id,
        'code' => $item->code,
        'name' => $item->name,
        'unit' => $item->unit?->symbol ?: $item->unit?->name,
    ])->values();
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="page-title mb-1">Input BOM Material</h4><p class="text-muted mb-0">{{ $finishedGood?->style_code }} · {{ $finishedGood?->code }} — {{ $finishedGood?->name }}</p></div>
    <a href="{{ route('super.boms.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.workflow.bom_cancel') }}</a>
</div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $bom->exists ? route('super.boms.update', $bom) : route('super.boms.store') }}">
    @csrf @if($bom->exists)@method('PUT')@endif
    <input type="hidden" name="product_id" value="{{ old('product_id', $bom->product_id) }}">
    <div class="card border-0 shadow-sm bom-card">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3"><div><h6 class="mb-0">Material / Support</h6><small class="text-muted">Pilih item, lalu isi konsumsi per produk</small></div><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#bomMaterialModal">Pilih Material</button></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle"><thead><tr><th>Item Code</th><th>Name Material/Support</th><th>Unit</th><th>Consumption per PCS</th><th>Span</th><th>Scrap (%)</th><th></th></tr></thead>
            <tbody id="bomMaterialRows">
            @foreach($oldComponents as $i => $row)
                @php($material = $products->firstWhere('id', $row['product_id'] ?? null))
                @if($material)
                <tr data-product-id="{{ $material->id }}">
                    <td>{{ $material->code }}<input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $material->id }}"></td><td>{{ $material->name }}<input type="hidden" name="items[{{ $i }}][operation]" value="{{ $row['operation'] ?? '' }}"><input type="hidden" name="items[{{ $i }}][notes]" value="{{ $row['notes'] ?? '' }}"></td><td>{{ $material->unit?->symbol ?: $material->unit?->name ?: '—' }}</td>
                    <td><input class="form-control" type="number" min="0.0001" step="0.0001" name="items[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? '' }}" required></td><td><input class="form-control" type="number" min="0" step="0.0001" name="items[{{ $i }}][span]" value="{{ $row['span'] ?? 0 }}"></td><td><input class="form-control" type="number" min="0" max="100" step="0.001" name="items[{{ $i }}][wastage_percent]" value="{{ $row['wastage_percent'] ?? 0 }}"></td><td><button class="btn btn-outline-danger remove-bom-material" type="button">×</button></td>
                </tr>
                @endif
            @endforeach
            </tbody></table>
            <div id="bom-empty-state" class="text-muted text-center py-3 {{ count($oldComponents) ? 'd-none' : '' }}">Pilih material untuk mulai mengisi BOM.</div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3"><a href="{{ route('super.boms.index') }}" class="btn btn-outline-secondary">{{ __('ui.workflow.bom_cancel') }}</a><button class="btn btn-primary" type="submit">{{ __('ui.workflow.bom_save') }}</button></div>
</form>

<div class="modal fade" id="bomMaterialModal" tabindex="-1" aria-labelledby="bomMaterialModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><div><h5 class="modal-title mb-1" id="bomMaterialModalTitle">Pilih Item Material / Support</h5><small class="text-muted">Cari dan pilih item dari daftar</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <div class="table-responsive"><table class="table table-hover align-middle w-100" id="bomMaterialOptionsTable"><thead><tr><th>Item Code</th><th>Nama Item</th><th>Unit</th><th>Aksi</th></tr></thead><tbody></tbody></table></div>
        </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div></div>
</div>
@endsection
@push('styles')
<style>
    .bom-card { border-radius: .75rem; }
    .bom-card .table thead th { color: #64748b; font-size: .75rem; font-weight: 600; letter-spacing: .025em; text-transform: uppercase; border-bottom-color: #e9eef5; white-space: nowrap; }
    .bom-card .table tbody td { border-color: #eef2f6; }
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
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const selectedIds = () => new Set([...rows.querySelectorAll('tr[data-product-id]')].map(row => String(row.dataset.productId)));

    function initializeMaterialTable() {
        if (materialTable) return;
        materialTable = $('#bomMaterialOptionsTable').DataTable({
            data: items,
            pageLength: 10,
            lengthMenu: [5, 10, 50, 100],
            columns: [
                { data: 'code' },
                { data: 'name' },
                { data: 'unit', defaultContent: '—' },
                { data: null, orderable: false, searchable: false, render: (data, type, item) => `<button type="button" class="btn btn-sm btn-primary choose-bom-material" data-id="${item.id}">Pilih</button>` },
            ],
            rowCallback: (row, item) => {
                const button = row.querySelector('.choose-bom-material');
                if (button && selectedIds().has(String(item.id))) {
                    button.disabled = true;
                    button.textContent = 'Terpilih';
                }
            },
        });
    }

    document.getElementById('bomMaterialModal').addEventListener('shown.bs.modal', initializeMaterialTable);
    document.getElementById('bomMaterialOptionsTable').addEventListener('click', event => {
        const button = event.target.closest('.choose-bom-material');
        if (!button) return;
        const item = items.find(candidate => String(candidate.id) === button.dataset.id);
        if (!item || selectedIds().has(String(item.id))) return;
        const index = nextIndex++;
        rows.insertAdjacentHTML('beforeend', `<tr data-product-id="${item.id}">
            <td>${escapeHtml(item.code)}<input type="hidden" name="items[${index}][product_id]" value="${item.id}"></td>
            <td>${escapeHtml(item.name)}<input type="hidden" name="items[${index}][operation]" value=""><input type="hidden" name="items[${index}][notes]" value=""></td>
            <td>${escapeHtml(item.unit || '—')}</td>
            <td><input class="form-control" type="number" min="0.0001" step="0.0001" name="items[${index}][quantity]" required></td>
            <td><input class="form-control" type="number" min="0" step="0.0001" name="items[${index}][span]" value="0"></td>
            <td><input class="form-control" type="number" min="0" max="100" step="0.001" name="items[${index}][wastage_percent]" value="0"></td>
            <td><button class="btn btn-outline-danger remove-bom-material" type="button">×</button></td></tr>`);
        empty.classList.add('d-none');
        materialTable?.draw(false);
    });
    rows.addEventListener('click', event => {
        const button = event.target.closest('.remove-bom-material');
        if (!button) return;
        button.closest('tr').remove();
        empty.classList.toggle('d-none', rows.querySelectorAll('tr').length > 0);
        materialTable?.draw(false);
    });
})();
</script>
@endpush
