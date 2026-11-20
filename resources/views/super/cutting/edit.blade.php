@extends('layouts.admin')

@section('title', __('ui.common.edit').' — '.(__('ui.cutting.form_title')))

@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('super.cutting.index') }}">{{ __('ui.cutting.title') }}</a></li>
    <li class="breadcrumb-item active">{{ __('ui.common.edit') }}</li>
</ol></nav>
@endsection

@section('content')
<form id="cuttingEditForm" class="card" data-entry-id="{{ $entry->id }}">
    @csrf
    @method('PUT')
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0">{{ __('ui.cutting.form_title') }}</h6>
        <a href="{{ route('super.cutting.index') }}" class="d-flex align-items-center gap-2 mb-0 small text-white text-decoration-none">
            <input type="checkbox" class="form-check-input m-0" checked onclick="return false">
            <span>{{ __('ui.common.back') }}</span>
        </a>
    </div>
    <div class="card-body pb-2">
        <div class="alert alert-light border py-2">{{ __('ui.cutting.mo') }}: <strong>{{ $entry->productionOrder?->number }}</strong> — {{ __('ui.workflow.color') }}: <strong>{{ $entry->productionOrder?->outputProduct?->color ?: '—' }}</strong> — {{ __('ui.workflow.size') }}: <strong>{{ $entry->productionOrder?->outputProduct?->size ?: '—' }}</strong> — {{ __('variants.order_qty') }}: {{ $entry->productionOrder?->planned_quantity }}</div>
        <div class="row g-2 align-items-end mb-3">
            <div class="col-lg-3">
                <label class="form-label mb-1">{{ __('ui.cutting.number') }}</label>
                <input class="form-control form-control-sm" value="{{ $cuttingNumber }}" readonly>
            </div>
            <div class="col-lg-2">
                <label class="form-label mb-1" for="cuttingDate">{{ __('ui.cutting.date') }} *</label>
                <input type="date" name="cutting_date" id="cuttingDate" class="form-control form-control-sm" value="{{ $entry->processed_at?->toDateString() }}" required>
            </div>
        </div>

        <div class="border-bottom mb-2 pb-1 fw-semibold small">Detail Barang:</div>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-4 col-form-label col-form-label-sm">{{ __('ui.cutting.product_code') }}</label>
                    <div class="col-8"><input id="cuttingProduct" class="form-control form-control-sm" value="{{ $entry->productionOrder?->stageProductCode('cutting') }}" readonly></div>
                </div>
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-4 col-form-label col-form-label-sm">{{ __('ui.cutting.product_name') }}</label>
                    <div class="col-8"><input id="cuttingProductName" class="form-control form-control-sm" value="{{ $entry->productionOrder?->outputProduct?->name }}" readonly></div>
                </div>
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-4 col-form-label col-form-label-sm">{{ __('ui.cutting.unit') }}</label>
                    <div class="col-8"><input id="cuttingUnit" class="form-control form-control-sm" value="PCS" readonly></div>
                </div>
                <div class="row g-2 align-items-center">
                    <label class="col-4 col-form-label col-form-label-sm">{{ __('ui.cutting.style') }}</label>
                    <div class="col-8"><input id="cuttingStyle" class="form-control form-control-sm" value="{{ $entry->productionOrder?->model_style }}" readonly></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-5 col-form-label col-form-label-sm">{{ __('ui.cutting.mo') }}</label>
                    <div class="col-7"><input id="cuttingMoLabel" class="form-control form-control-sm" value="{{ $entry->productionOrder?->number }}" readonly required></div>
                </div>
                <input type="hidden" name="production_order_id" id="cuttingMoId" value="{{ $entry->production_order_id }}">
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-5 col-form-label col-form-label-sm">{{ __('ui.cutting.po_buyer') }}</label>
                    <div class="col-7"><input id="cuttingPoBuyer" class="form-control form-control-sm" value="{{ $entry->productionOrder?->buyer_po_number }}" readonly></div>
                </div>
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-5 col-form-label col-form-label-sm">{{ __('ui.cutting.send_to_sewing') }} (PCS)</label>
                    <div class="col-7"><input type="number" name="send_to_sewing" id="cuttingSewing" step="0.0001" min="0" value="{{ $entry->good_quantity }}" class="form-control form-control-sm text-end" required></div>
                </div>
                <div class="row g-2 align-items-center">
                    <label class="col-5 col-form-label col-form-label-sm">{{ __('ui.cutting.rejected') }} (PCS)</label>
                    <div class="col-7"><input type="number" name="rejected_quantity" id="cuttingReject" step="0.0001" min="0" value="{{ $entry->rejected_quantity }}" class="form-control form-control-sm text-end" required></div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2 py-2">
        <a href="{{ route('super.cutting.index') }}" class="btn btn-light btn-sm">{{ __('ui.common.cancel') }}</a>
        <button type="submit" id="saveCutting" class="btn btn-primary btn-sm"><i data-feather="save" class="icon-sm me-1"></i>{{ __('ui.common.save_changes') }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function(){
    $('#cuttingEditForm').on('submit',function(e){
        e.preventDefault();
        const button=$('#saveCutting').prop('disabled',true);
        $.ajax({
            url:@json(route('super.cutting.update',['entry'=>$entry->id])),
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
