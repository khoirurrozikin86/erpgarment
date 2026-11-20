@extends('layouts.admin')

@php($title = $document ? __('ui.subcontract_semi_finished.edit') : __('ui.subcontract_semi_finished.add'))
@section('title', $title)
@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">{{ $title }}</h5>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('super.subcontract.semi-finished.index') }}">{{ __('ui.common.back') }}</a>
    </div>
    <form id="semiFinishedForm" method="POST" action="{{ $document ? route('super.subcontract.semi-finished.update', $document) : route('super.subcontract.semi-finished.store') }}">
        @csrf
        @if($document)
            @method('PUT')
        @endif
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif
            <div class="row g-3 g-xl-4">
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="semiFinishedNumber">{{ __('ui.subcontract_semi_finished.number') }}</label>
                        <div class="col-md-8">
                            <input id="semiFinishedNumber" value="{{ $number }}" class="form-control form-control-sm" readonly><div class="form-text">{{ __('ui.subcontract_semi_finished.number_help') }}</div>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="semiFinishedDate">{{ __('ui.subcontract_semi_finished.date') }} *</label>
                        <div class="col-md-8">
                            <input type="date" name="production_date" id="semiFinishedDate" value="{{ old('production_date', $entry['production_date'] ?? now()->toDateString()) }}" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="semiFinishedMoNumber">{{ __('ui.subcontract_semi_finished.mo_number') }}</label>
                        <div class="col-md-8">
                            <div class="input-group input-group-sm"><input id="semiFinishedMoNumber" class="form-control" readonly><input type="hidden" name="production_order_id" id="semiFinishedMoId"><button type="button" id="chooseSemiFinishedMo" class="btn btn-outline-primary">{{ __('ui.subcontract_semi_finished.choose_mo') }}</button><button type="button" id="clearSemiFinishedMo" class="btn btn-outline-secondary" title="{{ __('ui.common.clear') }}"><i data-feather="x" class="icon-sm"></i></button></div>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="semiFinishedBuyerPo">{{ __('ui.subcontract_semi_finished.buyer_po') }}</label>
                        <div class="col-md-8">
                            <input type="text" name="buyer_po_number" id="semiFinishedBuyerPo" value="{{ old('buyer_po_number', $entry['buyer_po_number'] ?? null) }}" maxlength="100" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="semiFinishedDescription">{{ __('ui.subcontract_semi_finished.description') }}</label>
                        <div class="col-md-8">
                            <textarea name="description" id="semiFinishedDescription" rows="2" maxlength="5000" class="form-control form-control-sm">{{ old('description', $entry['description'] ?? null) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="semiFinishedCode">{{ __('variants.stock_parent_code') }}</label>
                        <div class="col-md-8">
                            <div class="input-group input-group-sm"><input id="semiFinishedCode" class="form-control" readonly required><input type="hidden" name="product_id" id="semiFinishedProductId"><button type="button" id="chooseSemiFinishedProduct" class="btn btn-outline-primary"><i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.search') }}</button></div>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="semiFinishedName">{{ __('ui.subcontract_semi_finished.product_name') }}</label>
                        <div class="col-md-8">
                            <input id="semiFinishedName" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                @foreach(\Illuminate\Support\Arr::only(\App\Domain\Subcontract\Queries\SubcontractProductColumns::HEADINGS, ['sku_code', 'category_name', 'color', 'size']) as $field => $label)
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="semi-finished-{{ $field }}">{{ __($label) }}</label>
                        <div class="col-md-8"><input id="semi-finished-{{ $field }}" class="form-control form-control-sm" readonly></div>
                    </div>
                @endforeach
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="semiFinishedUnit">{{ __('ui.subcontract_semi_finished.unit') }}</label>
                        <div class="col-md-8">
                            <input id="semiFinishedUnit" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="semiFinishedQuantity">{{ __('ui.subcontract_semi_finished.quantity') }} *</label>
                        <div class="col-md-8">
                            <input type="number" min="0.0001" step="0.0001" name="quantity" id="semiFinishedQuantity" value="{{ old('quantity', $entry['quantity'] ?? null) }}" class="form-control form-control-sm" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm"><i data-feather="save" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.save') }}</button>
        </div>
    </form>
</div>

<div class="modal fade" id="semiFinishedProductModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.subcontract_semi_finished.search_products') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="semiFinishedProductsTable" class="table table-sm table-bordered" style="width: 100%"><thead><tr>@foreach(\App\Domain\Subcontract\Queries\SubcontractProductColumns::HEADINGS as $label)<th>{{ __($label) }}</th>@endforeach<th>{{ __('ui.subcontract_semi_finished.style') }}</th><th>{{ __('ui.subcontract_semi_finished.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>

<div class="modal fade" id="semiFinishedMoModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.subcontract_semi_finished.search_mo') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="semiFinishedMoTable" class="table table-sm table-bordered" style="width: 100%"><thead><tr><th>{{ __('ui.subcontract_semi_finished.mo_number') }}</th>@foreach(\App\Domain\Subcontract\Queries\SubcontractProductColumns::HEADINGS as $label)<th>{{ __($label) }}</th>@endforeach<th>{{ __('ui.subcontract_semi_finished.buyer_po') }}</th><th>{{ __('ui.common.quantity') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection

@push('scripts')
<script>
$(function () {
    const productModal = new bootstrap.Modal(document.getElementById('semiFinishedProductModal'));
    const moModal = new bootstrap.Modal(document.getElementById('semiFinishedMoModal'));
    const itemFields = @json(array_keys(\App\Domain\Subcontract\Queries\SubcontractProductColumns::HEADINGS));
    function itemColumns(picker = false) {
        return itemFields.map(field => ({
            data: 'item_' + field,
            name: field === 'sku_code' ? 'code' : field,
            defaultContent: '—',
            orderable: false,
            searchable: picker,
            ...(picker ? {render: $.fn.dataTable.render.text()} : {}),
        }));
    }
    function fillProduct(product = {}) {
        $('#semiFinishedProductId').val(product.id ?? '');
        $('#semiFinishedCode').val(product.parent_code ?? '');
        $('#semiFinishedName').val(product.name ?? '');
        $('#semiFinishedUnit').val(product.unit ?? '');
        for (const field of ['sku_code', 'category_name', 'color', 'size']) {
            document.getElementById('semi-finished-' + field).value = product[field] ?? '—';
        }
    }
    const products = $('#semiFinishedProductsTable').DataTable({processing:true,serverSide:true,ajax:@json(route('super.subcontract.semi-finished.products.dt')),columns:[
        ...itemColumns(true),{data:'style_label',orderable:false,searchable:false,render:$.fn.dataTable.render.text()},{data:'unit_label',orderable:false,searchable:false,render:$.fn.dataTable.render.text()},{data:'actions',orderable:false,searchable:false}
    ], drawCallback:()=>window.feather&&feather.replace()});
    const mos = $('#semiFinishedMoTable').DataTable({processing:true,serverSide:true,ajax:@json(route('super.subcontract.semi-finished.production-orders.dt')),columns:[
        {data:'number',name:'number'},...itemColumns(),{data:'buyer_po_number',name:'buyer_po_number',defaultContent:'—'},{data:'planned_quantity',name:'planned_quantity'},{data:'actions',orderable:false,searchable:false}
    ], drawCallback:()=>window.feather&&feather.replace()});

    $('#semiFinishedProductModal').on('shown.bs.modal', () => products.columns.adjust());
    $('#semiFinishedMoModal').on('shown.bs.modal', () => mos.columns.adjust());
    $('#chooseSemiFinishedProduct').on('click',()=>{productModal.show();products.ajax.reload()});
    $('#chooseSemiFinishedMo').on('click',()=>{moModal.show();mos.ajax.reload()});
    $('#clearSemiFinishedMo').on('click',()=>{$('#semiFinishedMoId,#semiFinishedMoNumber').val('')});
    $('#semiFinishedProductsTable').on('click','.js-select-semi-finished-product', function () {
        const row = products.row(this.closest('tr')).data();
        const identity = Object.fromEntries(itemFields.map(field => [field, row['item_' + field]]));
        fillProduct({...identity, id: row.id, unit: row.unit_label});
        productModal.hide();
    });
    $(document).on('click','.js-select-semi-finished-mo',function(){const b=$(this);$('#semiFinishedMoId').val(b.data('id'));$('#semiFinishedMoNumber').val(b.data('number'));if(b.data('buyer-po'))$('#semiFinishedBuyerPo').val(b.data('buyer-po'));moModal.hide()});
    fillProduct(@json($entry['product'] ?? []));
    $('#semiFinishedMoId').val(@json($entry['production_order_id'] ?? null));
    $('#semiFinishedMoNumber').val(@json($entry['mo_number'] ?? null));
});
</script>
@endpush
