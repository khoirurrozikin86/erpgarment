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
                <div class="col-lg-8"><label class="form-label">{{ __('ui.raw_material_order.mo_number') }} <span class="text-danger">*</span></label><div class="input-group"><input id="rawMoLabel" class="form-control" value="{{ $document ? $document->production_order_number : '' }}" readonly placeholder="{{ __('ui.raw_material_order.select_mo') }}"><input type="hidden" id="rawMoNumber" value="{{ $document?->production_order_number }}"><button class="btn btn-outline-primary" type="button" id="openRawMoPicker" @disabled($isEdit)><i data-feather="search" class="icon-sm"></i></button>@unless($isEdit)<button class="btn btn-primary" type="button" id="loadRawMo">{{ __('ui.raw_material_order.load_mo') }}</button>@endunless</div></div>
            </div>
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3"><h6 class="mb-0 fw-semibold">{{ __('ui.raw_material_order.materials') }}</h6><span class="small text-muted" id="rawMoSummary"></span></div>
            <div class="form-text mb-2">{{ __('ui.raw_material_order.item_number_note') }}</div>
            <div class="table-responsive"><table id="rawMoMaterialsTable" class="table table-sm table-bordered table-hover align-middle w-100"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.raw_material_order.number') }}</th><th>{{ __('ui.raw_material_order.item_code') }}</th><th>{{ __('ui.raw_material_order.item_name') }}</th><th>{{ __('ui.raw_material_order.unit') }}</th><th>{{ __('ui.raw_material_order.quantity') }}</th><th>{{ __('ui.raw_material_order.style') }}</th></tr></thead><tbody></tbody></table></div>
            <div class="text-muted small" id="rawMoEmpty">{{ __('ui.raw_material_order.empty_materials') }}</div>
        </div>
        <div class="card-footer bg-light d-flex justify-content-end gap-2"><a href="{{ route('super.raw-material-orders.index') }}" class="btn btn-light btn-sm">{{ __('ui.common.cancel') }}</a><button class="btn btn-primary btn-sm" id="saveRawMaterialOrder" type="submit"><i data-feather="save" class="icon-sm me-1"></i>{{ __('ui.common.save') }}</button></div>
    </form>
</div>

<div class="modal fade" id="rawMoPickerModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.raw_material_order.select_mo') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="rawMoPickerTable" class="table table-sm table-bordered align-middle w-100"><thead><tr><th>{{ __('ui.raw_material_order.mo_number') }}</th><th>{{ __('ui.raw_material_order.mo_date') }}</th><th>{{ __('ui.raw_material_order.style') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection

@push('scripts')
<script>
$(function(){
    const editMode=@json($isEdit);
    const initialItems=@json($items);
    const moModal=new bootstrap.Modal(document.getElementById('rawMoPickerModal'));
    const materials=$('#rawMoMaterialsTable').DataTable({data:initialItems,processing:false,serverSide:false,responsive:true,pageLength:10,columns:[
        {data:null,orderable:false,searchable:false,render:(d,t,r,m)=>m.row+1},{data:'number',defaultContent:'{{ __('ui.raw_material_order.assigned_after_save') }}'},
        {data:'code',defaultContent:'—'},{data:'name',defaultContent:'—'},{data:'unit',defaultContent:'—'},
        {data:'quantity',className:'text-end',render:d=>`<input type="number" class="form-control form-control-sm raw-order-quantity text-end" min="0.0001" step="0.0001" value="${Number(d||0)}">`},
        {data:'style_code',defaultContent:'—'}
    ],drawCallback:function(){$('#rawMoEmpty').toggle(this.api().rows().count()===0);if(window.feather)feather.replace()}});
    const picker=$('#rawMoPickerTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:@json(route('super.raw-material-orders.mos.dt')),columns:[{data:'number',name:'number'},{data:'mo_date',name:'po_date',defaultContent:'—'},{data:'style_code',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false,className:'text-center'}],order:[[1,'desc']],drawCallback:()=>window.feather&&feather.replace()});
    if(initialItems.length){$('#rawMoSummary').text(`${initialItems.length} {{ __('ui.raw_material_order.item_name') }}`);$('#rawMoEmpty').hide()}
    $('#openRawMoPicker').on('click',()=>{picker.ajax.reload();moModal.show()});
    $(document).on('click','.btn-select-raw-mo',function(){const b=$(this);$('#rawMoNumber').val(b.data('number'));$('#rawMoLabel').val(`${b.data('number')} · ${b.data('style')} · ${b.data('date')}`);materials.clear().draw();$('#rawMoSummary').text('');$('#rawMoEmpty').show();moModal.hide()});
    $('#loadRawMo').on('click',function(){const number=$('#rawMoNumber').val();if(!number){Swal.fire({icon:'info',title:@json(__('ui.raw_material_order.select_mo_first'))});return}const button=$(this).prop('disabled',true);$.get(@json(route('super.raw-material-orders.materials')),{'production_order_number':number}).done(r=>{materials.clear().rows.add(r.items).draw();$('#rawMoSummary').text(`${r.items.length} {{ __('ui.raw_material_order.item_name') }}`);}).fail(x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))})).always(()=>button.prop('disabled',false))});
    $('#rawMoMaterialsTable').on('input','.raw-order-quantity',function(){const row=materials.row($(this).closest('tr'));const item=row.data();if(item)item.quantity=$(this).val()});
    $('#rawMaterialOrderForm').on('submit',function(e){e.preventDefault();const items=materials.rows().data().toArray().map(i=>({production_order_material_id:i.source_id,quantity:i.quantity}));if(!items.length){Swal.fire({icon:'warning',title:@json(__('ui.raw_material_order.empty_materials'))});return}if(items.some(i=>!Number(i.quantity)||Number(i.quantity)<=0)){Swal.fire({icon:'warning',title:@json(__('ui.raw_material_order.quantity'))});return}const button=$('#saveRawMaterialOrder').prop('disabled',true);const form=$(this);$.ajax({url:form.data('url'),method:form.data('method'),data:{_token:form.find('[name="_token"]').val(),order_date:$('#rawOrderDate').val(),production_order_number:$('#rawMoNumber').val(),items},success:r=>{Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:`${r.message} (${r.number})`}).then(()=>window.location.href=r.url)},error:x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:x.responseJSON?.message||@json(__('ui.workflow.save_failed'))}),complete:()=>button.prop('disabled',false)});});
});
</script>
@endpush
