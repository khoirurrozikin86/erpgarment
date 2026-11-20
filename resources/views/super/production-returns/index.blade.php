@extends('layouts.admin')
@section('title', __('ui.production_return.return_title'))
@php($creating = request()->routeIs('super.return-to-warehouse.create'))
@section('content')
@if (!$creating)
<div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">{{ __('ui.production_return.return_title') }}</h4><span class="text-muted">{{ __('ui.production_return.subtitle') }}</span></div>@can('return-to-warehouse.create')<a class="btn btn-primary btn-sm" href="{{ route('super.return-to-warehouse.create') }}"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.production_return.add') }}</a>@endcan</div>
<div class="card"><div class="card-body"><div class="row g-2 align-items-end mb-3"><div class="col-sm-3"><label class="form-label">{{ __('ui.common.from_date') }}</label><input type="date" class="form-control form-control-sm" id="from_date" value="{{ now()->startOfMonth()->toDateString() }}"></div><div class="col-sm-3"><label class="form-label">{{ __('ui.common.to_date') }}</label><input type="date" class="form-control form-control-sm" id="to_date" value="{{ now()->toDateString() }}"></div><div class="col-auto"><button class="btn btn-primary btn-sm" id="filterReturn"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div><div class="col-auto"><a href="{{ route('super.return-to-warehouse.export') }}" class="btn btn-outline-success btn-sm" id="exportReturn"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a></div></div><div class="table-responsive"><table id="returnTable" class="table table-sm table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.production_return.number') }}</th><th>{{ __('ui.production_return.date') }}</th><th>{{ __('ui.production_return.mo') }}</th><th>{{ __('ui.production_return.style') }}</th><th>{{ __('ui.production_return.po_supplier') }}</th><th>{{ __('parent_code') }}</th><th>{{ __('sku_code') }}</th><th>{{ __('color') }}</th><th>{{ __('size') }}</th><th>{{ __('ui.production_return.item_name') }}</th><th>{{ __('ui.production_return.unit') }}</th><th>{{ __('ui.production_return.quantity') }}</th><th>{{ __('ui.production_return.description') }}</th></tr></thead></table></div></div></div>
@endif

@if ($creating)
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center gap-2">
    <h5 class="mb-0">{{ __('ui.production_return.add') }}</h5>
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('super.return-to-warehouse.index') }}">{{ __('ui.common.back') }}</a>
</div>
@else
    <div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
@endif
    <form id="returnForm">@csrf
        @unless ($creating)
            <div class="modal-header"><h5 class="modal-title">{{ __('ui.production_return.add') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        @endunless
        <div class="{{ $creating ? 'card-body' : 'modal-body' }}">
            <div class="row gx-lg-5 gy-2"><div class="col-lg-6">
<h6 class="text-primary mb-3">{{ __('ui.common.document_details') }}</h6>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="returnNumber">{{ __('ui.production_return.number') }}</label><div class="col-sm-8"><input id="returnNumber" class="form-control form-control-sm" readonly></div></div>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="returnDate">{{ __('ui.production_return.date') }} *</label><div class="col-sm-8"><input type="date" name="return_date" id="returnDate" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required></div></div>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="returnOrder">{{ __('ui.production_return.mo') }}</label><div class="col-sm-8">@if ($creating)
    <input type="hidden" name="raw_material_order_document_id" id="returnOrderId">
    <div class="input-group input-group-sm">
        <input id="returnOrder" class="form-control" readonly placeholder="—">
        <button type="button" id="chooseReturnOrder" class="btn btn-outline-primary" aria-haspopup="dialog" aria-controls="returnOrdersModal">{{ __('ui.common.search') }}</button>
        <button type="button" id="clearReturnOrder" class="btn btn-outline-secondary" aria-label="{{ __('ui.common.clear') }}" disabled>×</button>
    </div>
@else
    <select name="raw_material_order_document_id" id="returnOrder" class="form-select form-select-sm"><option value="">—</option>@foreach($orders as $order)<option value="{{ $order->id }}">{{ $order->number }}</option>@endforeach</select>
@endif</div></div>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="returnSupplierPo">{{ __('ui.production_return.po_supplier') }}</label><div class="col-sm-8"><input name="po_supplier_number" id="returnSupplierPo" class="form-control form-control-sm" maxlength="100"></div></div>
</div><div class="col-lg-6">
<h6 class="text-primary mb-3">{{ __('ui.common.item_details') }}</h6>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="returnProductLabel">{{ __('ui.production_return.item_code') }} *</label><div class="col-sm-8"><div class="input-group input-group-sm"><input id="returnProductLabel" class="form-control form-control-sm" readonly required><button type="button" class="btn btn-outline-primary" id="chooseReturnProduct">{{ __('ui.common.select') }}</button></div><input type="hidden" name="product_id" id="returnProductId"></div></div>
@foreach ([
    'ParentCode' => 'ui.gsn.parent_code',
    'SkuCode' => 'ui.gsn.sku_code',
    'Color' => 'ui.workflow.color',
    'Size' => 'ui.workflow.size',
] as $field => $label)
    <div class="row gx-3 gy-1 align-items-center mb-2">
        <label class="col-sm-4 col-form-label col-form-label-sm" for="return{{ $field }}">{{ __($label) }}</label>
        <div class="col-sm-8">
            <input id="return{{ $field }}" class="form-control form-control-sm" value="—" readonly>
        </div>
    </div>
@endforeach
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="returnQuantity">{{ __('ui.production_return.quantity') }} *</label><div class="col-sm-8"><input type="number" name="quantity" id="returnQuantity" min="0.0001" step="0.0001" class="form-control form-control-sm text-end" required></div></div>
<div class="row gx-3 gy-1 align-items-center mb-2"><label class="col-sm-4 col-form-label col-form-label-sm" for="returnDescription">{{ __('ui.production_return.description') }}</label><div class="col-sm-8"><textarea name="description" id="returnDescription" class="form-control form-control-sm" rows="2"></textarea></div></div>
</div></div>
            <p class="small text-muted mb-0 mt-2">{{ __('ui.production_return.approve_help') }}</p>
        </div>
        <div class="{{ $creating ? 'card-footer d-flex justify-content-end gap-2' : 'modal-footer' }}">
            @if ($creating)
                <a class="btn btn-light btn-sm" href="{{ route('super.return-to-warehouse.index') }}">{{ __('ui.common.cancel') }}</a>
            @else
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
            @endif
            <button type="submit" class="btn btn-primary btn-sm" id="saveReturn">{{ __('ui.common.save') }}</button>
        </div>
    </form>
    </div>
@unless ($creating)
    </div></div>
@endunless

<div class="modal fade" id="returnProductsModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.production_return.select_item') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="table-responsive"><table id="returnProductsTable" class="table table-sm table-bordered w-100"><thead><tr><th>{{ __('ui.gsn.parent_code') }}</th><th>{{ __('ui.gsn.sku_code') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.wip_issue.category') }}</th><th>{{ __('ui.production_return.item_name') }}</th><th>{{ __('ui.production_return.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@if ($creating)
    <div class="modal fade" id="returnOrdersModal" tabindex="-1" aria-labelledby="returnOrdersTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnOrdersTitle">{{ __('ui.production_return.mo') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table id="returnOrdersTable" class="table table-sm table-bordered align-middle w-100">
                            <thead><tr>
                                <th>{{ __('ui.production_return.mo') }}</th>
                                <th>{{ __('ui.raw_material_order.number') }}</th>
                                <th>{{ __('ui.raw_material_order.date') }}</th>
                                <th>{{ __('ui.common.action') }}</th>
                            </tr></thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <template id="selectReturnOrderAction">
        <button type="button" class="btn btn-sm btn-primary select-return-order">{{ __('ui.common.select') }}</button>
    </template>
@endif
@endsection
@push('scripts')<script>
$(function(){
 const creating = @json($creating);

 @if ($creating)
    const orderPicker = $('#returnOrdersModal');
    const orderModal = bootstrap.Modal.getOrCreateInstance(orderPicker[0]);
    let orders;

    $('#chooseReturnOrder').on('click', () => orderModal.show());
    orderPicker.on('shown.bs.modal', () => {
        orders ??= $('#returnOrdersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: @json(route('super.return-to-warehouse.orders.dt')),
            columns: [
                {data: 'production_order_number', defaultContent: '—'},
                {data: 'number'},
                {data: 'order_date'},
                {data: null, orderable: false, searchable: false, defaultContent: $('#selectReturnOrderAction').html()}
            ],
            order: [[2, 'desc']]
        });
        orders.columns.adjust();
    });
    orderPicker.on('click', '.select-return-order', function () {
        const order = orders.row($(this).closest('tr')).data();
        if ($('#returnOrderId').val() !== String(order.id)) {
            clearReturnProduct();
        }
        $('#returnOrderId').val(order.id);
        $('#returnOrder').val(order.production_order_number ? `${order.production_order_number} — ${order.number}` : order.number);
        $('#clearReturnOrder').prop('disabled', false);
        orderModal.hide();
    });
    orderPicker.on('hidden.bs.modal', () => $('#chooseReturnOrder').trigger('focus'));
    $('#clearReturnOrder').on('click', function () {
        $('#returnOrderId, #returnOrder').val('');
        clearReturnProduct();
        $('#chooseReturnOrder').trigger('focus');
        $(this).prop('disabled', true);
    });
 @endif

 function clearReturnProduct() {
    $('#returnProductId, #returnProductLabel').val('');
    showProductDetails();
 }
 $('#returnOrder').filter('select').on('change', clearReturnProduct);

 function showProductDetails(parent, sku, color, size) {
    $('#returnParentCode').val(parent || '—');
    $('#returnSkuCode').val(sku || '—');
    $('#returnColor').val(color || '—');
    $('#returnSize').val(size || '—');
 }

function dateDisplay(v){if(!v)return '—';const p=String(v).slice(0,10).split('-');return p.length===3?`${p[2]}-${p[1]}-${p[0]}`:v;}let editingId=null,updateUrl=null;const formModal=creating ? null : new bootstrap.Modal('#returnModal'),productsModal=new bootstrap.Modal('#returnProductsModal');const table=creating ? null : $('#returnTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.return-to-warehouse.dt')),data:d=>{d.from_date=$('#from_date').val();d.to_date=$('#to_date').val()}},columns:[{data:'id',orderable:false,searchable:false,render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},{data:'actions',orderable:false,searchable:false,className:'text-center'},{data:'number',name:'document.number'},{data:'return_date',name:'document.return_date',render:dateDisplay},{data:'mo',orderable:false},{data:'style',orderable:false},{data:'po_supplier',orderable:false},{data:'parent_code',orderable:false,searchable:false},{data:'sku_code',orderable:false,searchable:false},{data:'color',orderable:false,searchable:false},{data:'size',orderable:false,searchable:false},{data:'name',name:'product.name'},{data:'unit',orderable:false},{data:'quantity',name:'quantity',className:'text-end'},{data:'description',name:'description'}],order:[[3,'desc']],drawCallback:()=>window.feather&&feather.replace()});let productTable;
    const loadProducts = () => productTable ??= $('#returnProductsTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{
        url:@json(route('super.return-to-warehouse.products.dt')),
        data: data => {
            data.raw_material_order_document_id = $('#returnForm [name="raw_material_order_document_id"]').val();
        }
    },columns:[{data: 'parent_code', orderable: false, searchable: false}, {data: 'sku_code', name: 'code', orderable: false}, {data: 'color', orderable: false, searchable: true}, {data: 'size', orderable: false, searchable: true}, {data: 'category_label', orderable: false, searchable: false},{data:'name',name:'name'},{data:'unit_label',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false,className:'text-center'}],drawCallback:()=>window.feather&&feather.replace()});if (creating) { $.get(@json(route('super.return-to-warehouse.number-preview'))).done(r=>$('#returnNumber').val(r.number)); }
$('#chooseReturnProduct').on('click',()=>{if (productTable) { productTable.ajax.reload(); } else { loadProducts(); } productsModal.show()});$(document).on('click','.btn-select-return-product',function(){const b=$(this);$('#returnProductId').val(b.data('id'));showProductDetails(b.attr('data-parent-code'), b.attr('data-sku-code'), b.attr('data-color'), b.attr('data-size'));$('#returnProductLabel').val(`${b.data('code')} — ${b.data('name')} (${b.data('unit')})`);productsModal.hide()});$('#filterReturn').on('click',()=>table.ajax.reload());$('#exportReturn').on('click',function(e){e.preventDefault();const u=new URL(this.href);u.searchParams.set('from_date',$('#from_date').val());u.searchParams.set('to_date',$('#to_date').val());location.href=u});$(document).on('click','.btn-edit-production-return',function(){const b=$(this);updateUrl=b.data('update-url');$.get(b.data('url')).done(d=>{const i=d.items[0];editingId=i.id;$('#returnForm [name=return_date]').val(d.date);$('#returnNumber').val(d.number);$('#returnProductId').val(i.product_id);showProductDetails(i.parent_code, i.sku_code, i.color, i.size);$('#returnProductLabel').val(`${i.code} — ${i.name} (${i.unit||''})`);$('#returnForm [name=quantity]').val(i.quantity);$('#returnForm [name=raw_material_order_document_id]').val(d.mo_id||'');$('#returnForm [name=po_supplier_number]').val(d.po_supplier||'');$('#returnForm [name=description]').val(i.description||'');$('#returnModal .modal-title').text(@json(__('ui.common.edit'))+' '+@json(__('ui.production_return.return_title')));formModal.show()})});$('#returnModal').on('hidden.bs.modal',()=>$('#returnModal .modal-title').text(@json(__('ui.production_return.add'))));$('#returnForm').on('submit',function(e){e.preventDefault();const b=$('#saveReturn').prop('disabled',true);$.ajax({url:editingId?updateUrl:@json(route('super.return-to-warehouse.store')),method:editingId?'PUT':'POST',data:$(this).serialize()}).done(r=>{if (!creating) { formModal.hide();table.ajax.reload(null,false); }const feedback = Swal.fire({icon:'success',text:r.number?`${r.message} (${r.number})`:r.message}); if (creating) feedback.then(() => window.location.assign(@json(route('super.return-to-warehouse.index'))));}).fail(x=>Swal.fire({icon:'error',text:Object.values(x.responseJSON?.errors||{}).flat().join('\n')||x.responseJSON?.message||'Error'})).always(()=>b.prop('disabled',false))});$(document).on('click','.btn-delete-production-return',function(){const b=$(this);Swal.fire({icon:'warning',title:@json(__('ui.common.confirm_delete')),showCancelButton:true,confirmButtonText:@json(__('ui.common.delete')),cancelButtonText:@json(__('ui.common.cancel'))}).then(r=>{if(r.isConfirmed)$.ajax({url:b.data('url'),method:'DELETE',data:{_token:@json(csrf_token())}}).done(()=>table.ajax.reload(null,false)).fail(x=>Swal.fire({icon:'error',text:x.responseJSON?.message||'Error'}))})})});
</script>@endpush
