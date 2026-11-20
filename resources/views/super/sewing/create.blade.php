@extends('layouts.admin')
@section('title', __('ui.sewing.new'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('super.sewing.index') }}">{{ __('ui.sewing.title') }}</a></li><li class="breadcrumb-item active">{{ __('ui.sewing.new') }}</li></ol></nav>@endsection
@section('content')
<form id="sewingCreateForm" class="card sewing-create">
@csrf
<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2"><h6 class="mb-0">{{ __('ui.sewing.form_title') }}</h6><a href="{{ route('super.sewing.index') }}" class="text-white small text-decoration-none">{{ __('ui.common.back') }}</a></div>
<div class="card-body pb-2">
<div class="row g-2 align-items-end mb-3">
<div class="col-sm-6 col-lg-3"><label for="sewingNumber" class="form-label mb-1">{{ __('ui.sewing.number') }}</label><input id="sewingNumber" class="form-control form-control-sm" value="{{ $sewingNumber }}" readonly></div>
<div class="col-sm-6 col-lg-3"><label for="sewingDate" class="form-label mb-1">{{ __('ui.sewing.date') }} *</label><input type="date" name="sewing_date" id="sewingDate" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required></div>
<div class="col-lg-6"><label for="sewingMoLabel" class="form-label mb-1">{{ __('ui.sewing.mo') }} *</label><div class="input-group input-group-sm"><input id="sewingMoLabel" class="form-control" readonly required><button type="button" id="chooseSewingMo" class="btn btn-primary">{{ __('ui.common.search') }}</button></div><input type="hidden" name="production_order_id" id="sewingMoId"></div>
</div>
<div class="row g-3"><div class="col-lg-6">
<div class="row g-2 align-items-center mb-2"><label class="col-4 col-form-label col-form-label-sm" for="sewingProduct">{{ __('ui.sewing.product_code') }}</label><div class="col-8"><input type="text" id="sewingProduct" class="form-control form-control-sm " readonly></div></div>
<div class="row g-2 align-items-center mb-2"><label class="col-4 col-form-label col-form-label-sm" for="sewingProductName">{{ __('ui.sewing.product_name') }}</label><div class="col-8"><input type="text" id="sewingProductName" class="form-control form-control-sm " readonly></div></div>
<div class="row g-2 align-items-center mb-2"><label class="col-4 col-form-label col-form-label-sm" for="sewingStyle">{{ __('ui.sewing.style') }}</label><div class="col-8"><input type="text" id="sewingStyle" class="form-control form-control-sm " readonly></div></div>
<div class="row g-2 align-items-center mb-2"><label class="col-4 col-form-label col-form-label-sm" for="sewingPoBuyer">{{ __('ui.sewing.po_buyer') }}</label><div class="col-8"><input type="text" id="sewingPoBuyer" class="form-control form-control-sm " readonly></div></div>
<div class="row g-2 align-items-center mb-2"><label class="col-4 col-form-label col-form-label-sm" for="sewingColor">{{ __('ui.workflow.color') }}</label><div class="col-8"><input type="text" id="sewingColor" class="form-control form-control-sm " readonly></div></div>
<div class="row g-2 align-items-center mb-2"><label class="col-4 col-form-label col-form-label-sm" for="sewingSize">{{ __('ui.workflow.size') }}</label><div class="col-8"><input type="text" id="sewingSize" class="form-control form-control-sm " readonly></div></div>
</div><div class="col-lg-6">
<div class="row g-2 align-items-center mb-2"><label class="col-4 col-form-label col-form-label-sm" for="sewingUnit">{{ __('ui.sewing.unit') }}</label><div class="col-8"><input type="text" id="sewingUnit" class="form-control form-control-sm " readonly></div></div>
<div class="row g-2 align-items-center mb-2"><label class="col-4 col-form-label col-form-label-sm" for="sewingAvailable">{{ __('ui.sewing.cutting_reception') }}</label><div class="col-8"><input type="number" id="sewingAvailable" class="form-control form-control-sm text-end" readonly></div></div>
<div class="row g-2 align-items-center mb-2"><label class="col-4 col-form-label col-form-label-sm" for="sewingQty">{{ __('ui.sewing.quantity') }}</label><div class="col-8"><input type="number" id="sewingQty" class="form-control form-control-sm text-end" name="input_quantity" min="0.0001" step="0.0001"  required></div></div>
<div class="row g-2 align-items-center mb-2"><label class="col-4 col-form-label col-form-label-sm" for="sewingReject">{{ __('ui.sewing.rejected') }}</label><div class="col-8"><input type="number" id="sewingReject" class="form-control form-control-sm text-end" name="rejected_quantity" min="0" step="0.0001" value="0" required></div></div>
<div class="row g-2 align-items-center mb-2"><label class="col-4 col-form-label col-form-label-sm" for="sewingPacking">{{ __('ui.sewing.send_to_packing') }}</label><div class="col-8"><input type="number" id="sewingPacking" class="form-control form-control-sm text-end" name="send_to_packing" min="0" step="0.0001"  required></div></div>
</div></div>
<div class="table-responsive mt-3 mb-2"><table class="table table-sm table-bordered align-middle"><thead><tr>
<th>{{ __('ui.sewing.product_code') }} /SEW</th><th>{{ __('ui.sewing.product_name') }}</th><th>{{ __('ui.sewing.unit') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.sewing.product_code') }} /CUT</th><th>{{ __('ui.sewing.cutting_reception') }} (PCS)</th><th>{{ __('ui.sewing.quantity') }} (PCS)</th><th>{{ __('ui.sewing.rejected') }} (PCS)</th><th>{{ __('ui.sewing.send_to_packing') }} (PCS)</th>
</tr></thead><tbody id="sewingOrderLines"></tbody></table></div>
</div><div class="card-footer d-flex justify-content-end gap-2 py-2"><a href="{{ route('super.sewing.index') }}" class="btn btn-light btn-sm">{{ __('ui.common.cancel') }}</a><button type="submit" id="saveSewing" class="btn btn-primary btn-sm">{{ __('ui.common.save') }}</button></div></form>
<div class="modal fade" id="sewingMoModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.sewing.select_mo') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><table id="sewingMoTable" class="table table-sm table-bordered w-100"><thead><tr><th>{{ __('ui.sewing.mo') }}</th><th>{{ __('ui.sewing.po_buyer') }}</th><th>{{ __('ui.sewing.style') }}</th><th>{{ __('ui.sewing.product_code') }}</th><th>{{ __('ui.sewing.product_name') }}</th><th>{{ __('ui.sewing.cutting_reception') }}</th><th>{{ __('ui.common.action') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.workflow.order_quantity') }}</th></tr></thead></table></div></div></div></div>
<style>.sewing-create .form-control,.sewing-create .btn{font-size:.875rem}.sewing-create .form-label{font-size:.875rem;margin-bottom:.3rem}.sewing-create .row{--bs-gutter-y:1rem}</style>
@endsection
@push('scripts')<script>
$(function(){
 const canCreate=true;
 const moModal=new bootstrap.Modal(document.getElementById('sewingMoModal'));

 $('#sewingNumber').val(@json($sewingNumber));
 $('#sewingMoModal').on('shown.bs.modal',function(){$(this).find('table').DataTable().columns.adjust().responsive.recalc();});
  const moTable=canCreate?$('#sewingMoTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:@json(route('super.sewing.production-orders.dt')),columns:[{data:'number',name:'number'},{data:'buyer_po_number',name:'buyer_po_number'},{data:'style',orderable:false,searchable:false},{data:'product_code',orderable:false,searchable:false},{data:'product_name',orderable:false,searchable:false},{data:'available_cutting_quantity',className:'text-end'},{data:'actions',orderable:false,searchable:false},{data:'color',name:'outputProduct.color'},{data:'size',name:'outputProduct.size'},{data:'order_quantity',name:'planned_quantity',className:'text-end'}]}):null;
  let detailRequest=null;
  function summary(){
    ['input_quantity','rejected_quantity','send_to_packing'].forEach((field,index)=>{
      let total=0;$('#sewingOrderLines [data-field="'+field+'"]').each(function(){total+=Number(this.value||0);});
      $(['#sewingQty','#sewingReject','#sewingPacking'][index]).val(total);
    });
  }
  function renderLines(lines){
    const body=$('#sewingOrderLines').empty();
    lines.forEach((line,index)=>{
      const tr=$('<tr>');
      [line.code,line.name,line.unit,line.color,line.size,line.cutting_code,line.available].forEach(value=>tr.append($('<td>').text(value)));
      tr.find('td:first').append($('<input>',{type:'hidden',name:`lines[${index}][production_order_id]`,value:line.production_order_id}));
      ['input_quantity','rejected_quantity','send_to_packing'].forEach(field=>tr.append($('<td>').append($('<input>',{type:'number',class:'form-control form-control-sm text-end',name:`lines[${index}][${field}]`,'data-field':field,min:0,step:'0.0001',value:0,required:true,max:line.available}))));
      body.append(tr);
    });
    $('#sewingAvailable').val(lines.reduce((sum,line)=>sum+line.available,0));summary();
  }
  $('#sewingQty,#sewingReject,#sewingPacking').prop('readonly',true);
  $('#sewingOrderLines').on('input','input[type="number"]',summary);
  $('#chooseSewingMo').on('click',()=>{moTable.ajax.reload();moModal.show();});
  $(document).on('click','.btn-select-sewing-mo',function(){
    const b=$(this);if(detailRequest)detailRequest.abort();
    $('#sewingMoId').val(b.data('id'));$('#sewingMoLabel').val(b.data('number'));
    $('#sewingProduct').val(b.data('product').split(' — ')[0]+'/SEW');$('#sewingProductName').val(b.data('product').split(' — ').slice(1).join(' — '));
    $('#sewingStyle').val(b.data('style'));$('#sewingPoBuyer').val(b.data('po'));$('#sewingUnit').val(b.data('unit'));
    $('#sewingColor,#sewingSize').val('');renderLines([]);moModal.hide();$('#saveSewing').prop('disabled',true);
    detailRequest=$.get(@json(route('super.sewing.production-orders.details',['order'=>'__ID__'])).replace('__ID__',b.data('id')))
      .done(r=>{renderLines(r.lines);$('#sewingColor').val([...new Set(r.lines.map(line=>line.color))].join(', '));$('#sewingSize').val([...new Set(r.lines.map(line=>line.size))].join(', '));$('#saveSewing').prop('disabled',!r.lines.length);})
      .fail(x=>{if(x.statusText!=='abort')Swal.fire({icon:'error',text:x.responseJSON?.message||@json(__('ui.workflow.failure'))});});
  });
  $('#sewingCreateForm').on('submit',function(e){
    e.preventDefault();
    const button=$('#saveSewing').prop('disabled',true);
    $.ajax({url:@json(route('super.sewing.store')),method:'POST',data:$(this).serialize()})
      .done(r=>Swal.fire({icon:'success',text:r.message}).then(()=>location.href=@json(route('super.sewing.index'))))
      .fail(x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:Object.values(x.responseJSON?.errors||{}).flat().join('\n')||x.responseJSON?.message||'Error'}))
      .always(()=>button.prop('disabled',false));
  });

});
</script>@endpush
