@extends('layouts.admin')

@php($title = $document ? __('ui.common.edit').' '.__('ui.machine_receipt.title') : __('ui.machine_receipt.add'))
@section('title', $title)
@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">{{ $title }}</h5>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('super.equipment-receipts.index') }}">{{ __('ui.common.back') }}</a>
    </div>
    <form id="equipmentForm" method="POST" action="{{ $document ? route('super.equipment-receipts.update', $document) : route('super.equipment-receipts.store') }}">
        @csrf
        @if($document)
            @method('PUT')
        @endif
        <div class="card-body">
            <div class="row g-3 g-xl-4">
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentNumber">{{ __('ui.machine_receipt.number') }}</label>
                        <div class="col-md-8">
                            <input id="equipmentNumber" value="{{ $number }}" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentDate">{{ __('ui.machine_receipt.date') }} *</label>
                        <div class="col-md-8">
                            <input name="receipt_date" id="equipmentDate" value="{{ $document?->receipt_date?->toDateString() ?? now()->toDateString() }}" type="date" required class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentOwnership">{{ __('ui.machine_receipt.ownership') }} *</label>
                        <div class="col-md-8">
                            <select name="ownership_type" id="equipmentOwnership" required class="form-select form-select-sm">
                                @foreach(\App\Models\EquipmentReceiptDocument::OWNERSHIP_TYPES as $type)
                                    <option value="{{ $type }}" @selected(($document?->ownership_type ?? 'own') === $type)>
                                        {{ __('ui.machine_receipt.ownership_'.$type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentSupplierLabel">{{ __('ui.machine_receipt.supplier') }}</label>
                        <div class="col-md-8">
                            <div class="input-group input-group-sm"><input id="equipmentSupplierLabel" value="{{ $document?->supplier?->name }}" class="form-control" readonly><input type="hidden" name="supplier_id" id="equipmentSupplierId" value="{{ $document?->supplier_id }}"><button type="button" id="chooseEquipmentSupplier" class="btn btn-outline-primary"><i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.search') }}</button></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentInvoice">{{ __('ui.machine_receipt.invoice_number') }}</label>
                        <div class="col-md-8">
                            <input name="invoice_number" id="equipmentInvoice" value="{{ $document?->invoice_number }}" class="form-control form-control-sm" maxlength="150">
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentInvoiceDate">{{ __('ui.machine_receipt.invoice_date') }}</label>
                        <div class="col-md-8">
                            <input name="invoice_date" id="equipmentInvoiceDate" value="{{ $document?->invoice_date?->toDateString() }}" type="date" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentCurrency">{{ __('ui.machine_receipt.currency') }}</label>
                        <div class="col-md-8">
                            <input name="currency_code" id="equipmentCurrency" value="{{ $document?->currency_code }}" class="form-control form-control-sm" maxlength="10" placeholder="USD">
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentDescription">{{ __('ui.common.description') }}</label>
                        <div class="col-md-8">
                            <input name="description" id="equipmentDescription" value="{{ $document?->description }}" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
            </div>
            <section class="border-top mt-3 pt-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2"><h6 class="text-primary mb-0">{{ __('ui.machine_receipt.items') }}</h6><button type="button" id="addEquipmentLine" class="btn btn-outline-primary btn-sm"><i data-feather="plus" class="icon-sm"></i> {{ __('ui.machine_receipt.add_item') }}</button></div><div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.category') }}</th><th>{{ __('ui.common.unit') }}</th><th style="width:110px">{{ __('ui.common.quantity') }}</th><th style="width:140px">{{ __('ui.machine_receipt.unit_price') }}</th><th style="width:140px">{{ __('ui.machine_receipt.total_price') }}</th><th>{{ __('ui.machine_receipt.supplier_po') }}</th><th>{{ __('ui.common.description') }}</th><th></th></tr></thead><tbody id="equipmentLines"></tbody></table></div>
            </section>
            <p class="small text-muted mb-0 mt-2">{{ __('ui.machine_receipt.stock_notice') }}</p>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-feather="save" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.save') }}
            </button>
        </div>
    </form>
</div>

<div class="modal fade" id="equipmentProductModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.machine_receipt.select_item') }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="table-responsive"><table id="equipmentProducts" class="table table-sm table-bordered w-100"><thead><tr><th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.category') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
<div class="modal fade" id="equipmentSupplierModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.machine_receipt.select_supplier') }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="table-responsive"><table id="equipmentSuppliers" class="table table-sm table-bordered w-100"><thead><tr><th>{{ __('ui.common.code') }}</th><th>{{ __('ui.common.name') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection

@push('scripts')
<script>
$(function () {
 const productModal = new bootstrap.Modal('#equipmentProductModal');
 const supplierModal = new bootstrap.Modal('#equipmentSupplierModal');
 let activeLine = null;
 let nextLine = 0;
 const products=$('#equipmentProducts').DataTable({processing:true,serverSide:true,scrollX:true,ajax:@json(route('super.equipment-receipts.products.dt')),columns:[{data:'code',name:'code'},{data:'name',name:'name'},{data:'category_label',orderable:false,searchable:false},{data:'unit_label',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false}]});
 const suppliers=$('#equipmentSuppliers').DataTable({processing:true,serverSide:true,scrollX:true,ajax:@json(route('super.equipment-receipts.suppliers.dt')),columns:[{data:'code',name:'code'},{data:'name',name:'name'},{data:'actions',orderable:false,searchable:false}]});
 function addLine(v={}){const ix=nextLine++, row=$(`<tr><td><div class="input-group input-group-sm"><input class="form-control line-code" readonly><button class="btn btn-outline-primary choose-product" type="button">...</button><input type="hidden" class="line-product" name="items[${ix}][product_id]"></div></td><td><input class="form-control form-control-sm line-name" readonly></td><td><input class="form-control form-control-sm line-category" readonly></td><td><input class="form-control form-control-sm line-unit" readonly></td><td><input type="number" min="0.0001" step="0.0001" required name="items[${ix}][quantity]" class="form-control form-control-sm line-qty"></td><td><input type="number" min="0" step="0.0001" name="items[${ix}][unit_price]" class="form-control form-control-sm line-price"></td><td><input class="form-control form-control-sm line-total" readonly></td><td><input name="items[${ix}][supplier_po_number]" class="form-control form-control-sm line-po"></td><td><input name="items[${ix}][description]" class="form-control form-control-sm line-description"></td><td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i data-feather="trash-2" class="icon-sm"></i></button></td></tr>`); row.find('.line-code').val(v.item_code||'');row.find('.line-name').val(v.item_name||'');row.find('.line-category').val(v.product?.category?.name ?? '—');row.find('.line-unit').val(v.unit||'');row.find('.line-product').val(v.product_id||'');row.find('.line-qty').val(v.quantity||'');row.find('.line-price').val(v.unit_price||''); row.find('.line-total').val(v.total_price ?? ''); row.find('.line-po').val(v.supplier_po_number ?? ''); row.find('.line-description').val(v.description ?? ''); row.find('.line-qty,.line-price').on('input',()=>row.find('.line-total').val(Number((Number(row.find('.line-qty').val())*Number(row.find('.line-price').val())).toFixed(4))));$('#equipmentLines').append(row); if (window.feather) feather.replace(); }
 $('#addEquipmentLine').on('click',()=>addLine());$('#equipmentLines').on('click','.remove-line',function(){if($('#equipmentLines tr').length>1)$(this).closest('tr').remove()});$('#equipmentLines').on('click','.choose-product',function(){activeLine=$(this).closest('tr');productModal.show()});
 $('#chooseEquipmentSupplier').on('click',()=>supplierModal.show());$(document).on('click','.btn-select-equipment-product',function(){const b=$(this);activeLine.find('.line-code').val(b.data('code'));activeLine.find('.line-name').val(b.data('name'));activeLine.find('.line-category').val(b.attr('data-category'));activeLine.find('.line-unit').val(b.data('unit'));activeLine.find('.line-product').val(b.data('id'));activeLine.find('.line-price').val(b.data('price')||0).trigger('input');productModal.hide()});$(document).on('click','.btn-select-equipment-supplier',function(){const b=$(this);$('#equipmentSupplierId').val(b.data('id'));$('#equipmentSupplierLabel').val(b.data('name'));supplierModal.hide()});
 const savedLines = @json($document?->items ?? []);
 if (savedLines.length) savedLines.forEach(line => addLine(line)); else addLine();
 $('#equipmentProductModal').on('shown.bs.modal', () => products.columns.adjust());
 $('#equipmentSupplierModal').on('shown.bs.modal', () => suppliers.columns.adjust());
 $('#equipmentForm').on('submit', function (event) {
    event.preventDefault();
    const save = $(this).find('[type=submit]').prop('disabled', true);
    $.ajax({url: this.action, method: 'POST', data: $(this).serialize(), dataType: 'json'})
        .done(result => {
            const date = this.elements['receipt_date'].value;
            const from = @json(now()->startOfMonth()->toDateString());
            const to = @json(now()->toDateString());
            const params = new URLSearchParams({from_date: date < from ? date : from, to_date: date > to ? date : to});
            Swal.fire({icon: 'success', text: result.message + ' (' + result.number + ')'}).then(() => {
                window.location.href = @json(route('super.equipment-receipts.index')) + '?' + params;
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
