<div class="input-group input-group-sm">
    <input id="production-order-buyer" class="form-control bg-light" readonly
        value="{{ $selectedBuyer ? $selectedBuyer->code.' — '.$selectedBuyer->name : '' }}"
        data-bs-toggle="modal" data-bs-target="#productionOrderBuyerPicker"
        aria-haspopup="dialog" aria-controls="productionOrderBuyerPicker" style="cursor:pointer">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productionOrderBuyerPicker"
        aria-haspopup="dialog" aria-controls="productionOrderBuyerPicker">{{ __('ui.common.search') }}</button>
    <input type="hidden" id="customer_id" name="customer_id" value="{{ $selectedBuyer?->id }}">
</div>
