@extends('layouts.admin')

@php
    $title = $document ? __('ui.common.edit').' '.__('ui.scrap_receipt.title') : __('ui.scrap_receipt.add');
    $productLabel = fn ($product) => $product
        ? $product->code.' — '.$product->name.($product->unit ? ' ('.($product->unit->symbol ?: $product->unit->name).')' : '')
        : '';
@endphp

@section('title', $title)
@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">{{ $title }}</h5>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('super.scrap-receipts.index') }}">{{ __('ui.common.back') }}</a>
    </div>
    <form method="POST" action="{{ $document ? route('super.scrap-receipts.update', $document) : route('super.scrap-receipts.store') }}">
        @csrf
        @if($document)
            @method('PUT')
        @endif
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
            <div class="row g-3 g-xl-4">
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="scrapReceiptNumber">{{ __('ui.scrap_receipt.number') }}</label>
                        <div class="col-md-8">
                            <input class="form-control form-control-sm" id="scrapReceiptNumber" value="{{ $number }}" readonly>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="scrapReceiptDate">{{ __('ui.scrap_receipt.date') }} *</label>
                        <div class="col-md-8">
                            <input type="date" class="form-control form-control-sm" id="scrapReceiptDate" name="receipt_date" value="{{ old('receipt_date', $document?->receipt_date?->toDateString() ?? now()->toDateString()) }}" required>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="scrapProductLabel">{{ __('ui.scrap_receipt.scrap_code') }} *</label>
                        <div class="col-md-8">
                            <div class="input-group input-group-sm"><input class="form-control" id="scrapProductLabel" value="{{ $productLabel($scrapProduct) }}" readonly placeholder="{{ __('ui.scrap_receipt.select_scrap') }}"><button class="btn btn-outline-primary" type="button" id="chooseScrapProduct"><i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.search') }}</button></div><input type="hidden" name="scrap_product_id" id="scrapProductId" value="{{ $scrapProduct?->id }}">
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="sourceProductLabel">{{ __('ui.scrap_receipt.source_code') }}</label>
                        <div class="col-md-8">
                            <div class="input-group input-group-sm"><input class="form-control" id="sourceProductLabel" value="{{ $productLabel($sourceProduct) }}" readonly placeholder="{{ __('ui.scrap_receipt.select_source') }}"><button class="btn btn-outline-primary" type="button" id="chooseSourceProduct"><i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.search') }}</button><button class="btn btn-outline-secondary" type="button" id="clearSourceProduct" aria-label="{{ __('ui.common.clear') }}" title="{{ __('ui.common.clear') }}"><i data-feather="x" class="icon-sm" aria-hidden="true"></i></button></div><input type="hidden" name="source_product_id" id="sourceProductId" value="{{ $sourceProduct?->id }}">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="scrapReceiptQuantity">{{ __('ui.scrap_receipt.quantity') }} *</label>
                        <div class="col-md-8">
                            <input type="number" class="form-control form-control-sm text-end" name="quantity" id="scrapReceiptQuantity" value="{{ old('quantity', $document?->quantity) }}" min="0.0001" step="0.0001" required>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="scrapProductionNumber">{{ __('ui.scrap_receipt.production_number') }}</label>
                        <div class="col-md-8">
                            <div class="input-group input-group-sm">
                                <input class="form-control" name="production_number" id="scrapProductionNumber" value="{{ old('production_number', $document?->production_number) }}" maxlength="100">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#scrap-production-modal">
                                    <i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.search') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="scrapDescription">{{ __('ui.common.description') }}</label>
                        <div class="col-md-8">
                            <textarea class="form-control form-control-sm" name="description" id="scrapDescription" rows="2" maxlength="5000">{{ old('description', $document?->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <p class="small text-muted mb-0 mt-2">{{ __('ui.scrap_receipt.stock_notice') }} {{ __('ui.scrap_receipt.confirmation_note') }}</p>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-feather="save" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.save') }}
            </button>
        </div>
    </form>
</div>

<div class="modal fade" id="scrapProductModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="scrapProductModalTitle"></h5><button class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="scrapProductsTable" class="table table-sm table-bordered align-middle w-100"><thead><tr><th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
<div class="modal fade" id="scrap-production-modal" tabindex="-1" aria-labelledby="scrap-production-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scrap-production-title">{{ __('bc_reports.select_mo') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered align-middle w-100" id="scrap-production-table">
                    <thead><tr><th>{{ __('bc_reports.mo_no') }}</th><th>{{ __('ui.common.actions') }}</th></tr></thead>
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
    const productionModal = document.getElementById('scrap-production-modal');
    const productionTable = $('#scrap-production-table');
    let orders;

    productionModal.addEventListener('shown.bs.modal', () => {
        if (orders) {
            orders.columns.adjust();
            return;
        }

        orders = productionTable.DataTable({
            processing: true,
            serverSide: true,
            ajax: @json(route('super.scrap-receipts.production-orders.dt')),
            columns: [
                {data: 'number', name: 'number', render: $.fn.dataTable.render.text()},
                {data: null, orderable: false, searchable: false, defaultContent: '',
                    createdCell(cell) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'btn btn-sm btn-primary js-select-scrap-mo';
                        button.textContent = @json(__('ui.common.select'));
                        cell.replaceChildren(button);
                    }
                },
            ],
            order: [[0, 'asc']],
        });
    });

    productionTable.on('click', '.js-select-scrap-mo', function () {
        document.getElementById('scrapProductionNumber').value = orders.row(this.closest('tr')).data().number;
        bootstrap.Modal.getInstance(productionModal).hide();
    });

    const productModal=new bootstrap.Modal(document.getElementById('scrapProductModal'));
    let pickerTarget='scrap';
    const products=$('#scrapProductsTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.scrap-receipts.products.dt')),data:d=>{d.type=pickerTarget;}},columns:[{data:'code',name:'code'},{data:'name',name:'name'},{data:'unit_label',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false,className:'text-center'}],drawCallback:()=>window.feather&&feather.replace()});
    function setPicker(type){pickerTarget=type;$('#scrapProductModalTitle').text(type==='scrap'?@json(__('ui.scrap_receipt.select_scrap')):@json(__('ui.scrap_receipt.select_source')));products.ajax.reload();productModal.show();}
    $('#chooseScrapProduct').on('click',()=>setPicker('scrap'));$('#chooseSourceProduct').on('click',()=>setPicker('source'));
    $('#clearSourceProduct').on('click', () => $('#sourceProductId,#sourceProductLabel').val(''));
    $(document).on('click','.btn-select-scrap-product',function(){const b=$(this),scrap=pickerTarget==='scrap';$(scrap?'#scrapProductId':'#sourceProductId').val(b.data('id'));$(scrap?'#scrapProductLabel':'#sourceProductLabel').val(`${b.data('code')} — ${b.data('name')} (${b.data('unit')})`);productModal.hide();});
    $('#scrapProductModal').on('shown.bs.modal', () => products.columns.adjust());
});
</script>
@endpush
