@extends('layouts.admin')

@php($isEdit = (bool) $document)
@section('title', $isEdit ? __('ui.common.edit').' — '.__('ui.raw_material_order.title') : __('ui.raw_material_order.create'))

@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.production') }}</li><li class="breadcrumb-item"><a href="{{ route('super.raw-material-orders.index') }}">{{ __('ui.raw_material_order.title') }}</a></li><li class="breadcrumb-item active">{{ $isEdit ? __('ui.common.edit') : __('ui.raw_material_order.create') }}</li></ol></nav>
@endsection

@section('content')
<div class="card border-primary-subtle">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center"><span>{{ __('ui.raw_material_order.title') }}</span><a href="{{ route('super.raw-material-orders.index') }}" class="btn btn-sm btn-light"><i data-feather="arrow-left" class="icon-sm me-1"></i>{{ __('ui.common.back') }}</a></div>
    <form id="rawMaterialOrderForm" data-url="{{ $isEdit ? route('super.raw-material-orders.update', $document) : route('super.raw-material-orders.store') }}" data-method="{{ $isEdit ? 'PUT' : 'POST' }}">
        @csrf
        <div class="card-body">
            <div class="row g-3 align-items-end mb-4">
                <div class="col-lg-4"><label for="rawOrderDate" class="form-label">{{ __('ui.raw_material_order.date') }} <span class="text-danger">*</span></label><input type="date" id="rawOrderDate" name="order_date" class="form-control" value="{{ $document?->order_date?->toDateString() ?? now()->toDateString() }}" required></div>
                <div class="col-md-4"><label for="rawOrderMode" class="form-label">Sumber Order</label><select id="rawOrderMode" class="form-select"><option value="mo">Dari MO</option><option value="manual">Manual</option></select></div>
                <div class="col-md-4" id="rawCategoryField"><label for="rawOrderCategory" class="form-label">{{ __('variants.bom_type') }}</label><select id="rawOrderCategory" class="form-select"><option value="all">Semua</option><option value="fabric">{{ __('variants.bom_type_fabric') }}</option><option value="accessory">{{ __('variants.bom_type_accessory') }}</option><option value="general">{{ __('variants.bom_type_general') }}</option></select></div>
                <div class="col-md-4" id="rawMoSourceField"><label class="form-label">{{ __('ui.raw_material_order.mo_number') }} <span class="text-danger">*</span></label><div class="input-group"><input id="rawMoLabel" class="form-control" value="{{ $document ? $document->production_order_number : '' }}" readonly placeholder="{{ __('ui.raw_material_order.select_mo') }}"><input type="hidden" id="rawMoNumber" value="{{ $document?->production_order_number }}"><button class="btn btn-outline-primary" type="button" id="openRawMoPicker" @disabled($isEdit)><i data-feather="search" class="icon-sm"></i></button></div></div>
                <div class="col-12" id="rawLoadMoField"><div class="d-flex justify-content-end"><button class="btn btn-primary btn-sm" type="button" id="loadRawMo" @disabled($isEdit)>{{ __('ui.raw_material_order.load_mo') }}</button></div></div>
            </div>
            <section id="rawMoProductionSummary" class="mb-4 d-none" aria-labelledby="rawMoProductionTitle">
                <h6 id="rawMoProductionTitle" class="fw-semibold mb-2">{{ __('variants.raw_mo_production_summary') }}</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>{{ __('ui.raw_material_order.style') }}</th>
                            <th>{{ __('ui.workflow.color') }}</th>
                            <th>{{ __('ui.workflow.size') }}</th>
                            <th class="text-end">{{ __('variants.raw_mo_production_quantity') }}</th>
                        </tr></thead>
                        <tbody id="rawMoProductionRows"></tbody>
                    </table>
                </div>
            </section>
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3"><h6 class="mb-0 fw-semibold">{{ __('ui.raw_material_order.materials') }}</h6><span class="small text-muted" id="rawMoSummary"></span></div>
            <div class="form-text mb-2">Satu nomor Order Bahan Baku dapat memuat beberapa detail barang.</div>
            <div class="table-responsive"><table id="rawMoMaterialsTable" class="table table-sm table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('variants.stock_parent_code') }}</th><th>{{ __('variants.stock_sku') }}</th><th>{{ __('ui.raw_material_order.item_name') }}</th><th>{{ __('variants.bom_type') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.raw_material_order.unit') }}</th><th>{{ __('ui.raw_material_order.quantity') }}</th><th>{{ __('ui.raw_material_order.style') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead><tbody></tbody></table></div>
            <div class="text-muted small" id="rawMoEmpty">{{ __('ui.raw_material_order.empty_materials') }}</div>
            <div id="manualRawMaterials" class="d-none">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Barang Manual</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#manualRawProductModal">Tambah Barang</button>
                </div>
                <div class="table-responsive"><table id="manualRawMaterialsTable" class="table table-sm table-bordered align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.raw_material_order.item_code') }}</th><th>{{ __('ui.raw_material_order.item_name') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.raw_material_order.unit') }}</th><th>{{ __('ui.raw_material_order.quantity') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead><tbody></tbody></table></div>
            </div>
        </div>
        <div class="card-footer bg-light d-flex justify-content-end gap-2"><a href="{{ route('super.raw-material-orders.index') }}" class="btn btn-light btn-sm">{{ __('ui.common.cancel') }}</a><button class="btn btn-primary btn-sm" id="saveRawMaterialOrder" type="submit"><i data-feather="save" class="icon-sm me-1"></i>{{ __('ui.common.save') }}</button></div>
    </form>
</div>

<div class="modal fade" id="rawMoPickerModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.raw_material_order.select_mo') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="rawMoPickerTable" class="table table-sm table-bordered align-middle w-100"><thead><tr><th>{{ __('ui.raw_material_order.mo_number') }}</th><th>{{ __('ui.raw_material_order.mo_date') }}</th><th>{{ __('ui.nav.styles') }} / {{ __('ui.workflow.color') }} / {{ __('ui.workflow.size') }} / {{ __('ui.workflow.order_quantity') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>

<div class="modal fade" id="manualRawProductModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Pilih Barang Manual</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="table-responsive"><table id="manualRawProductTable" class="table table-sm table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.raw_material_order.item_code') }}</th><th>{{ __('ui.raw_material_order.item_name') }}</th><th>{{ __('ui.raw_material_order.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection

@push('scripts')
<script>
$(function(){
    const editMode=@json($isEdit);
    const initialItems=@json($items);
    let productionDetails = @json($productionDetails);
    const initialManualMode=@json($document && !$document->production_order_number ? 'manual' : 'mo');
    const moModal=new bootstrap.Modal(document.getElementById('rawMoPickerModal'));
    const manualModal=new bootstrap.Modal(document.getElementById('manualRawProductModal'));
    const manualRows=initialItems.filter(item=>!item.source_id);
    const moRows=initialItems.filter(item=>item.source_id);
    const materials=$('#rawMoMaterialsTable').DataTable({data:moRows,processing:false,serverSide:false,ordering:false,pageLength:10,columns:[
        {data:null,orderable:false,searchable:false,render:(d,t,r,m)=>m.row+1},
        {data:'parent_code',defaultContent:'—',render:$.fn.dataTable.render.text()},
        {data:'sku_code',defaultContent:'—',render:$.fn.dataTable.render.text()},
        {data:'name',defaultContent:'—',render:$.fn.dataTable.render.text()},
        {data:'category',render:(category,type,item)=>{
            const categories = item.categories ?? [category];
            const labels = {
                fabric:@json(__('variants.bom_type_fabric')),
                accessory:@json(__('variants.bom_type_accessory')),
                general:@json(__('variants.bom_type_general'))
            };
            return [...new Set(categories.map(value=>labels[value] || @json(__('ui.common.optional'))))].join(', ');
        }},
        {data:'color',defaultContent:'—'},{data:'size',defaultContent:'—'},
        {data:'unit',defaultContent:'—'},
        {data:'quantity',className:'text-end',render:(quantity,type)=>{
            if(type!=='display')return Number(quantity);
            return `<input type="number" class="form-control form-control-sm raw-order-quantity text-end" min="0.0001" step="0.0001" value="${Number(quantity||0)}">`;
        }},
        {data:'style_code',defaultContent:'—',render:(style,type,item)=>{
            if(item.sources?.length>1)style=[...new Set(item.sources.map(source=>source.style_code).filter(Boolean))].join(', ');
            return type==='display' ? $('<div>').text(style || '—').html() : style;
        }},
        {data:null,orderable:false,searchable:false,className:'text-center',render:()=>'<button type="button" class="btn btn-link btn-sm text-danger remove-mo-material">Hapus</button>'}
    ],drawCallback:function(){$('#rawMoEmpty').toggle(this.api().rows().count()===0);$('#rawMoSummary').text(`${this.api().rows().count()} {{ __('ui.raw_material_order.item_name') }}`);if(window.feather)feather.replace()}});
    const manualMaterials=$('#manualRawMaterialsTable').DataTable({data:manualRows,processing:false,serverSide:false,responsive:true,pageLength:10,columns:[
        {data:null,orderable:false,searchable:false,render:(d,t,r,m)=>m.row+1},
        {data:'code',defaultContent:'—'}, {data:'name',defaultContent:'—'},
        {data:'color',render:d=>`<input type="text" class="form-control form-control-sm manual-color" value="${$('<div>').text(d||'').html()}">`},
        {data:'size',render:d=>`<input type="text" class="form-control form-control-sm manual-size" value="${$('<div>').text(d||'').html()}">`},
        {data:'unit',defaultContent:'—'},
        {data:'quantity',className:'text-end',render:d=>`<input type="number" class="form-control form-control-sm manual-quantity text-end" min="0.0001" step="0.0001" value="${Number(d||0)}">`},
        {data:null,orderable:false,searchable:false,className:'text-center',render:()=>'<button type="button" class="btn btn-link btn-sm text-danger remove-manual-material">Hapus</button>'}
    ],drawCallback:()=>window.feather&&feather.replace()});
    const manualProducts=$('#manualRawProductTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:{url:@json(route('super.raw-material-orders.manual-products.dt')),data:d=>{}},columns:[
        {data:'code',name:'code'}, {data:'name',name:'name'}, {data:'unit',orderable:false,searchable:false}, {data:'actions',orderable:false,searchable:false,className:'text-center'}
    ],order:[[1,'asc']],drawCallback:()=>window.feather&&feather.replace()});
    const picker=$('#rawMoPickerTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:@json(route('super.raw-material-orders.mos.dt')),columns:[{data:'number',name:'number'},{data:'mo_date',name:'po_date',defaultContent:'—'},{data:'variant_details',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false,className:'text-center'}],order:[[1,'desc']],drawCallback:()=>window.feather&&feather.replace()});
    $('#rawOrderMode').val(initialManualMode);
    function renderProductionSummary() {
        const rows = $('#rawMoProductionRows').empty();
        const quantity = new Intl.NumberFormat(@json(app()->getLocale()), {maximumFractionDigits: 4});
        for (const detail of productionDetails) {
            rows.append($('<tr>').append(
                $('<td>').text(detail.style || '—'),
                $('<td>').text(detail.color || '—'),
                $('<td>').text(detail.size || '—'),
                $('<td class="text-end">').text(quantity.format(detail.quantity)),
            ));
        }
        $('#rawMoProductionSummary').toggleClass('d-none', $('#rawOrderMode').val() !== 'mo' || !productionDetails.length);
    }

    function applyRawMode(mode){
        const manual=mode==='manual';
        $('#rawMoSourceField').toggle(!manual);
        $('#rawCategoryField').toggle(!manual);
        $('#rawLoadMoField').toggle(!manual);
        $('#rawMoMaterialsTable').closest('.table-responsive').toggle(!manual);
        $('#rawMoEmpty').toggle(!manual && materials.rows().count()===0);
        $('#manualRawMaterials').toggleClass('d-none',!manual);
        renderProductionSummary();
    }
    applyRawMode(initialManualMode);
    $('#rawOrderMode').on('change',function(){applyRawMode($(this).val())});
    $(document).on('click','.btn-select-manual-material',function(){
        const b=$(this);
        manualMaterials.row.add({
            product_id:b.data('id'), code:b.data('code'), name:b.data('name'), unit:b.data('unit'),
            style_code:b.data('style'), color:b.data('color'), size:b.data('size'), quantity:0
        }).draw();
        manualModal.hide();
    });
    $(document).on('input','.manual-color,.manual-size,.manual-quantity',function(){
        const row=manualMaterials.row($(this).closest('tr')); const item=row.data(); if(!item)return;
        if($(this).hasClass('manual-color'))item.color=$(this).val();
        if($(this).hasClass('manual-size'))item.size=$(this).val();
        if($(this).hasClass('manual-quantity'))item.quantity=$(this).val();
    });
    $(document).on('click','.remove-manual-material',function(){manualMaterials.row($(this).closest('tr')).remove().draw();});
    $(document).on('click','.remove-mo-material',function(){
        Swal.fire({
            icon:'warning',
            title:'Hapus detail barang?',
            text:'Barang ini tidak akan dimasukkan ke Order Bahan Baku.',
            showCancelButton:true,
            confirmButtonText:'Hapus',
            cancelButtonText:'Batal'
        }).then(result=>{
            if(!result.isConfirmed)return;
            materials.row($(this).closest('tr')).remove().draw();
        });
    });
    if(moRows.length){$('#rawMoSummary').text(`${moRows.length} {{ __('ui.raw_material_order.item_name') }}`);$('#rawMoEmpty').hide()}
    $('#openRawMoPicker').on('click',()=>{picker.ajax.reload();moModal.show()});
    $(document).on('click', '.btn-select-raw-mo', function () {
        const button = $(this);
        $('#rawMoNumber').val(button.data('number'));
        $('#rawMoLabel').val(`${button.data('number')} · ${button.data('style')} · ${button.data('date')}`);
        productionDetails = [];
        renderProductionSummary();
        $('#rawMoEmpty').text(@json(__('ui.raw_material_order.empty_materials')));
        materials.clear().draw();
        moModal.hide();
    });
    $('#rawOrderCategory').on('change', function () {
        if (!editMode) {
            $('#rawMoEmpty').text(@json(__('ui.raw_material_order.empty_materials')));
            materials.clear().draw();
        }
    });
    $('#loadRawMo').on('click', function () {
        const number = $('#rawMoNumber').val();
        if (!number) {
            Swal.fire({icon:'info', title:@json(__('ui.raw_material_order.select_mo_first'))});
            return;
        }
        const category = $('#rawOrderCategory').val();
        const button = $(this).prop('disabled', true);
        $.get(@json(route('super.raw-material-orders.materials')), {production_order_number: number, category, grouped: 1})
            .done(response => {
                if ($('#rawMoNumber').val() !== number || $('#rawOrderCategory').val() !== category) return;
                productionDetails = response.production_details;
                renderProductionSummary();
                $('#rawMoEmpty').text(@json(__('variants.raw_mo_no_active_bom_materials')));
                materials.clear().rows.add(response.items).draw();
            }).fail(error => Swal.fire({
                icon:'error', title:@json(__('ui.workflow.failure')),
                text:error.responseJSON?.message || @json(__('ui.workflow.save_failed')),
            })).always(() => button.prop('disabled', false));
    });
    $('#rawMoMaterialsTable').on('input','.raw-order-quantity',function(){const row=materials.row($(this).closest('tr'));const item=row.data();if(item)item.quantity=$(this).val()});
    function distributeMoQuantity(item) {
        if (!item.sources || item.sources.length < 2) return [item];

        let remaining = Number(item.quantity);
        return item.sources.flatMap((source, index) => {
            const quantity = index === item.sources.length - 1
                ? remaining : Math.min(remaining, Number(source.quantity));
            remaining = Math.round((remaining - quantity) * 10000) / 10000;
            return quantity > 0 ? [{...source, quantity}] : [];
        });
    }

    $('#rawMaterialOrderForm').on('submit',function(e){
        e.preventDefault();
        const mode=$('#rawOrderMode').val();
        let items=[];
        if(mode==='manual'){
            items=manualMaterials.rows().data().toArray().map(i=>({
                product_id:i.product_id, quantity:i.quantity, style_code:i.style_code||'', color:i.color||'', size:i.size||'', category:i.category||'general'
            }));
        }else{
            const rows = materials.rows().data().toArray();
            if (rows.some(row => !Number.isFinite(Number(row.quantity)) || Number(row.quantity) <= 0)) {
                Swal.fire({icon:'warning',title:@json(__('ui.raw_material_order.quantity'))});
                return;
            }
            items=rows.flatMap(distributeMoQuantity).map(i=>({
                production_order_material_id:i.source_id, quantity:i.quantity, category:i.category||$('#rawOrderCategory').val()
            }));
        }
        if(!items.length){Swal.fire({icon:'warning',title:@json(__('ui.raw_material_order.empty_materials'))});return}
        if(items.some(i=>!Number(i.quantity)||Number(i.quantity)<=0)){Swal.fire({icon:'warning',title:@json(__('ui.raw_material_order.quantity'))});return}
        if(mode==='mo'&&!$('#rawMoNumber').val()){Swal.fire({icon:'info',title:@json(__('ui.raw_material_order.select_mo_first'))});return}
        const button=$('#saveRawMaterialOrder').prop('disabled',true);
        const form=$(this);
        $.ajax({
            url:form.data('url'),method:form.data('method'),
            data:{_token:form.find('[name="_token"]').val(),order_date:$('#rawOrderDate').val(),mode,category:mode==='mo'?$('#rawOrderCategory').val():null,production_order_number:mode==='mo'?$('#rawMoNumber').val():null,items},
            success:r=>{Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:`${r.message} (${r.number})`}).then(()=>window.location.href=r.url)},
            error:x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))}),
            complete:()=>button.prop('disabled',false)
        });
    });
});
</script>
@endpush
