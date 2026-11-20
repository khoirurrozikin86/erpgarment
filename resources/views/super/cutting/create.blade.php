@extends('layouts.admin')

@section('title', __('ui.cutting.form_title'))

@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('super.cutting.index') }}">{{ __('ui.cutting.title') }}</a></li>
    <li class="breadcrumb-item active">{{ __('ui.cutting.new') }}</li>
</ol></nav>
@endsection

@section('content')
<form id="cuttingCreateForm" class="card">
    @csrf
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0">{{ __('ui.cutting.form_title') }}</h6>
        <a href="{{ route('super.cutting.index') }}" class="d-flex align-items-center gap-2 mb-0 small text-white text-decoration-none">
            <input type="checkbox" class="form-check-input m-0" checked onclick="return false">
            <span>{{ __('ui.common.back') }}</span>
        </a>
    </div>
    <div class="card-body pb-2">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-lg-3">
                <label class="form-label mb-1">{{ __('ui.cutting.number') }}</label>
                <input class="form-control form-control-sm" value="{{ $cuttingNumber }}" readonly>
            </div>
            <div class="col-lg-2">
                <label class="form-label mb-1" for="cuttingDate">{{ __('ui.cutting.date') }} *</label>
                <input type="date" name="cutting_date" id="cuttingDate" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="col-lg-1">
                <button type="button" id="chooseCuttingMo" class="btn btn-primary btn-sm w-100" title="{{ __('ui.common.search') }}">
                    <i data-feather="search" class="icon-sm"></i>
                </button>
            </div>
            <div class="col-lg-6"></div>
            <input type="hidden" name="production_order_id" id="cuttingMoId">
        </div>

        <div class="border-bottom mb-2 pb-1 fw-semibold small">Detail Barang:</div>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-4 col-form-label col-form-label-sm">{{ __('ui.cutting.product_code') }}</label>
                    <div class="col-8"><input id="cuttingProduct" class="form-control form-control-sm" readonly></div>
                </div>
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-4 col-form-label col-form-label-sm">{{ __('ui.cutting.product_name') }}</label>
                    <div class="col-8"><input id="cuttingProductName" class="form-control form-control-sm" readonly></div>
                </div>
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-4 col-form-label col-form-label-sm">{{ __('ui.cutting.unit') }}</label>
                    <div class="col-8"><input id="cuttingUnit" class="form-control form-control-sm" readonly></div>
                </div>
                <div class="row g-2 align-items-center">
                    <label class="col-4 col-form-label col-form-label-sm">{{ __('ui.cutting.style') }}</label>
                    <div class="col-8"><input id="cuttingStyle" class="form-control form-control-sm" readonly></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-5 col-form-label col-form-label-sm">{{ __('ui.cutting.mo') }}</label>
                    <div class="col-7"><input id="cuttingMoLabel" class="form-control form-control-sm" readonly required></div>
                </div>
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-5 col-form-label col-form-label-sm">{{ __('ui.cutting.po_buyer') }}</label>
                    <div class="col-7"><input id="cuttingPoBuyer" class="form-control form-control-sm" readonly></div>
                </div>
                <div class="border-top pt-2 mt-2">
                    <div class="small fw-semibold mb-1">Qty Cutting per Color / Size</div>
                    <div id="cuttingVariantDetails" class="small text-muted">Pilih MO untuk memuat detail.</div>
                </div>
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-5 col-form-label col-form-label-sm">{{ __('ui.cutting.send_to_sewing') }} (PCS)</label>
                    <div class="col-7"><input type="number" name="send_to_sewing" id="cuttingSewing" step="0.0001" min="0" value="0" class="form-control form-control-sm text-end" required></div>
                </div>
                <div class="row g-2 align-items-center">
                    <label class="col-5 col-form-label col-form-label-sm">{{ __('ui.cutting.rejected') }} (PCS)</label>
                    <div class="col-7"><input type="number" name="rejected_quantity" id="cuttingReject" step="0.0001" min="0" value="0" class="form-control form-control-sm text-end" required></div>
                </div>
            </div>
        </div>
    </div>
    <div class="px-3 pb-3 table-responsive"><h6>{{ __('variants.cutting_details') }}</h6>
    <table class="table table-sm table-bordered align-middle"><thead><tr>
    <th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('variants.order_qty') }}</th><th>{{ __('ui.workflow.remaining_quantity') }} (PCS)</th>
    <th>{{ __('ui.cutting.send_to_sewing') }} (PCS)</th><th>{{ __('ui.cutting.rejected') }} (PCS)</th>
    </tr></thead><tbody id="cuttingOrderLines"></tbody></table><small class="text-muted">{{ __('variants.cutting_detail_hint') }}</small></div>
    <div class="card-footer d-flex justify-content-end gap-2 py-2">
        <button type="submit" id="saveCutting" class="btn btn-primary btn-sm"><i data-feather="save" class="icon-sm me-1"></i>{{ __('ui.common.save') }}</button>
        <button type="button" id="footerChooseCuttingMo" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>Ambil No MO</button>
    </div>
</form>

<div class="mt-3 mb-2 bg-primary text-white px-3 py-2 rounded-top small">
    Daftar Barang dari No Cutting: {{ $cuttingNumber }}
</div>
<div class="table-responsive">
    <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>{{ __('ui.common.no') }}</th>
                <th>{{ __('ui.cutting.style') }}</th>
                <th>{{ __('ui.cutting.product_code') }}</th>
                <th>{{ __('ui.cutting.product_name') }}</th>
                <th>{{ __('ui.common.unit') }}</th>
                <th class="text-end">{{ __('ui.cutting.quantity') }} (PCS)</th>
                <th class="text-end">{{ __('ui.cutting.rejected') }}</th>
                <th class="text-end">{{ __('ui.cutting.send_to_sewing') }}</th>
                <th>{{ __('ui.cutting.po_buyer') }}</th>
                <th>{{ __('ui.cutting.mo') }}</th>
            </tr>
        </thead>
        <tbody id="cuttingSelectedItems">
            <tr><td colspan="10" class="text-center text-muted py-3">{{ __('ui.common.no_matching_records') }}</td></tr>
        </tbody>
    </table>
</div>

<div class="modal fade" id="cuttingMoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ __('ui.cutting.select_mo') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <table id="cuttingMoTable" class="table table-sm table-bordered w-100">
                    <thead><tr><th>{{ __('ui.cutting.mo') }}</th><th>{{ __('ui.common.status') }}</th><th>{{ __('ui.cutting.po_buyer') }}</th><th>Detail Barang / Style / Warna / Size / Qty</th><th class="text-end">Total Qty</th><th>{{ __('ui.common.action') }}</th></tr></thead>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function(){
    function renderOrderLines(variants) {
        const body=$('#cuttingOrderLines').empty();
        variants.forEach((line,index)=> {
            const tr=$('<tr>').data('detail',line);
            tr.append($('<td>').text(line.color),$('<td>').text(line.size),$('<td>').text(line.quantity),$('<td>').text(line.remaining_quantity));
            tr.find('td:first').append($('<input>',{type:'hidden',name:`lines[${index}][production_order_id]`,value:line.production_order_id}));
            ['send_to_sewing','rejected_quantity'].forEach(field=>{
                tr.append($('<td>').append($('<input>',{type:'number',class:'form-control form-control-sm cutting-line-value',name:`lines[${index}][${field}]`,min:0,step:'0.0001',value:0,required:true,max:line.remaining_quantity,'data-field':field})));
            });
            body.append(tr);
        });
        refreshCuttingSummary();
    }
    function refreshCuttingSummary(){
        const totals={send_to_sewing:0,rejected_quantity:0};
        $('#cuttingOrderLines .cutting-line-value').each(function(){totals[this.dataset.field]+=Number(this.value||0);});
        $('#cuttingSewing').val(totals.send_to_sewing);
        $('#cuttingReject').val(totals.rejected_quantity);
        const body=$('#cuttingSelectedItems').empty();
        $('#cuttingOrderLines tr').each(function(index){
            const row=$('<tr>');
            const detail=$(this).data('detail');
            const values=[index+1,detail.style,detail.product_code,detail.product_name,'PCS',Number($(this).find('[data-field="send_to_sewing"]').val()||0)+Number($(this).find('[data-field="rejected_quantity"]').val()||0),$(this).find('[data-field="rejected_quantity"]').val(),$(this).find('[data-field="send_to_sewing"]').val(),$('#cuttingPoBuyer').val(),$('#cuttingMoLabel').val()];
            values.forEach(value=>row.append($('<td>').text(value||'—'))); body.append(row);
        });
    }
    $('#cuttingSewing,#cuttingReject').prop('readonly',true);
    $('#cuttingOrderLines').on('input','.cutting-line-value',refreshCuttingSummary);
    const moModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('cuttingMoModal'));
    const moTable = $('#cuttingMoTable').DataTable({
        processing:true, serverSide:true, responsive:true,
        ajax:@json(route('super.cutting.production-orders.dt')),
        columns:[
            {data:'number',name:'number'},
            {data:'mo_status',orderable:false,searchable:false},
            {data:'buyer_po_number',name:'buyer_po_number'},
            {data:'variant_details',orderable:false,searchable:false},
            {data:'total_quantity',orderable:false,searchable:false,className:'text-end'},
            {data:'actions',orderable:false,searchable:false,className:'text-center'}
        ],
        drawCallback:()=>window.feather&&feather.replace()
    });
    $('#chooseCuttingMo, #footerChooseCuttingMo').on('click',()=>{moTable.ajax.reload();moModal.show();});
    $(document).on('click','.btn-select-cutting-mo',function(){
        const b=$(this);
        $('#cuttingMoId').val(b.data('id'));
        $('#cuttingMoLabel').val(b.data('number'));
        $('#cuttingProduct').val(b.data('product'));
        $('#cuttingStyle').val(b.data('style'));
        $('#cuttingPoBuyer').val(b.data('po'));
        $('#cuttingUnit').val(b.data('unit'));
        $('#cuttingProductName').val(b.data('product').split(' — ').slice(1).join(' — '));
        const variants=Array.isArray(b.data('variants')) ? b.data('variants') : JSON.parse(b.attr('data-variants') || '[]');
        renderOrderLines(variants);
        $('#cuttingVariantDetails').text(@json(__('variants.cutting_detail_hint')));
        moModal.hide();
        refreshCuttingSummary();
    });
    $('#cuttingCreateForm').on('submit',function(e){
        e.preventDefault();
        const button=$('#saveCutting').prop('disabled',true);
        $.ajax({
            url:@json(route('super.cutting.store')),
            method:'POST',
            data:$(this).serialize(),
            success:r=>Swal.fire({icon:'success',title:@json(__('ui.workflow.success')),text:r.message}).then(()=>window.location.href=@json(route('super.cutting.index'))),
            error:x=>Swal.fire({icon:'error',title:@json(__('ui.workflow.failure')),text:Object.values(x.responseJSON?.errors||{}).flat().join('\n')||x.responseJSON?.message||'Error'}),
            complete:()=>button.prop('disabled',false)
        });
    });
});
</script>
@endpush
