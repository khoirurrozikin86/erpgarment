@extends('layouts.admin')

@php
    $title = $document ? __('ui.common.edit').' '.__('ui.scrap_issue.title') : __('ui.scrap_issue.add');
    $productLabel = fn ($product) => $product
        ? $product->code.' — '.$product->name.($product->unit ? ' ('.($product->unit->symbol ?: $product->unit->name).')' : '')
        : '';
@endphp

@section('title', $title)
@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">{{ $title }}</h5>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('super.scrap-issues.index') }}">{{ __('ui.common.back') }}</a>
    </div>
    <form method="POST" action="{{ $document ? route('super.scrap-issues.update', $document) : route('super.scrap-issues.store') }}">
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
                        <label class="col-md-4 col-form-label col-form-label-sm" for="scrapIssueNumber">{{ __('ui.scrap_issue.number') }}</label>
                        <div class="col-md-8">
                            <input class="form-control form-control-sm" id="scrapIssueNumber" value="{{ $number }}" readonly>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="scrapIssueDate">{{ __('ui.scrap_issue.date') }} *</label>
                        <div class="col-md-8">
                            <input type="date" class="form-control form-control-sm" id="scrapIssueDate" name="issue_date" value="{{ old('issue_date', $document?->issue_date?->toDateString() ?? now()->toDateString()) }}" required>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="scrapIssueCustomerLabel">{{ __('ui.scrap_issue.customer') }} *</label>
                        <div class="col-md-8">
                            <div class="input-group input-group-sm"><input class="form-control" id="scrapIssueCustomerLabel" value="{{ $customer?->name }}" readonly placeholder="{{ __('ui.scrap_issue.select_customer') }}"><button class="btn btn-outline-primary" type="button" id="chooseScrapIssueCustomer"><i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.search') }}</button></div><input type="hidden" id="scrapIssueCustomerId" value="{{ $customer?->id }}" name="customer_id">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="scrapIssueProductLabel">{{ __('ui.common.item_code') }} *</label>
                        <div class="col-md-8">
                            <div class="input-group input-group-sm"><input class="form-control" id="scrapIssueProductLabel" value="{{ $productLabel($scrapProduct) }}" readonly placeholder="{{ __('ui.scrap_issue.select_scrap') }}"><button class="btn btn-outline-primary" type="button" id="chooseScrapIssueProduct"><i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.search') }}</button></div><input type="hidden" id="scrapIssueProductId" value="{{ $scrapProduct?->id }}" name="scrap_product_id">
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="scrapIssueQuantity">{{ __('ui.scrap_issue.quantity') }} *</label>
                        <div class="col-md-8">
                            <input type="number" class="form-control form-control-sm text-end" name="quantity" id="scrapIssueQuantity" value="{{ old('quantity', $document?->quantity) }}" min="0.0001" step="0.0001" required>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="scrapIssueDescription">{{ __('ui.common.description') }}</label>
                        <div class="col-md-8">
                            <textarea class="form-control form-control-sm" name="description" id="scrapIssueDescription" rows="2" maxlength="5000">{{ old('description', $document?->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <p class="small text-muted mb-0 mt-2">{{ __('ui.scrap_issue.stock_notice') }}</p>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-feather="save" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.save') }}
            </button>
        </div>
    </form>
</div>

<div class="modal fade" id="scrapIssueProductModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.scrap_issue.select_scrap') }}</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="scrapIssueProductsTable" class="table table-sm table-bordered align-middle w-100"><thead><tr><th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
<div class="modal fade" id="scrapIssueCustomerModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.scrap_issue.select_customer') }}</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><div class="table-responsive"><table id="scrapIssueCustomersTable" class="table table-sm table-bordered align-middle w-100"><thead><tr><th>{{ __('ui.common.code') }}</th><th>{{ __('ui.common.name') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection

@push('scripts')
<script>
$(function () {
    const productModal=new bootstrap.Modal(document.getElementById('scrapIssueProductModal'));
    const customerModal=new bootstrap.Modal(document.getElementById('scrapIssueCustomerModal'));
    const products=$('#scrapIssueProductsTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:@json(route('super.scrap-issues.products.dt')),columns:[{data:'code',name:'code'},{data:'name',name:'name'},{data:'unit_label',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false}],drawCallback:()=>window.feather&&feather.replace()});
    const customers=$('#scrapIssueCustomersTable').DataTable({processing:true,serverSide:true,responsive:true,ajax:@json(route('super.scrap-issues.customers.dt')),columns:[{data:'code',name:'code'},{data:'name',name:'name'},{data:'actions',orderable:false,searchable:false}],drawCallback:()=>window.feather&&feather.replace()});
    $('#chooseScrapIssueProduct').on('click',()=>productModal.show());$('#chooseScrapIssueCustomer').on('click',()=>customerModal.show());
    $(document).on('click','.btn-select-scrap-issue-product',function(){const b=$(this);$('#scrapIssueProductId').val(b.data('id'));$('#scrapIssueProductLabel').val(`${b.data('code')} — ${b.data('name')} (${b.data('unit')})`);productModal.hide();});
    $(document).on('click','.btn-select-scrap-issue-customer',function(){const b=$(this);$('#scrapIssueCustomerId').val(b.data('id'));$('#scrapIssueCustomerLabel').val(b.data('name'));customerModal.hide();});
    $('#scrapIssueProductModal').on('shown.bs.modal', () => products.columns.adjust());
    $('#scrapIssueCustomerModal').on('shown.bs.modal', () => customers.columns.adjust());
});
</script>
@endpush
