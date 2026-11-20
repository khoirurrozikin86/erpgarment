@extends('layouts.admin')

@php($title = $document ? __('ui.common.edit').' '.__('ui.machine_issue.title') : __('ui.machine_issue.add'))
@section('title', $title)
@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">{{ $title }}</h5>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('super.equipment-issues.index') }}">{{ __('ui.common.back') }}</a>
    </div>
    <form id="equipmentIssueForm" method="POST" action="{{ $document ? route('super.equipment-issues.update', $document) : route('super.equipment-issues.store') }}">
        @csrf
        @if($document)
            @method('PUT')
        @endif
        <div class="card-body">
            <div class="row g-3 g-xl-4">
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentIssueNumber">{{ __('ui.machine_issue.number') }}</label>
                        <div class="col-md-8">
                            <input id="equipmentIssueNumber" value="{{ $number }}" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentIssueDate">{{ __('ui.machine_issue.date') }} *</label>
                        <div class="col-md-8">
                            <input name="issue_date" id="equipmentIssueDate" value="{{ $document?->issue_date?->toDateString() ?? now()->toDateString() }}" type="date" required class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentIssueCode">{{ __('ui.common.item_code') }} *</label>
                        <div class="col-md-8">
                            <div class="input-group input-group-sm"><input id="equipmentIssueCode" value="{{ $document?->item?->item_code }}" class="form-control" readonly required><input type="hidden" name="product_id" id="equipmentIssueProductId" value="{{ $document?->item?->product_id }}"><button type="button" id="chooseEquipmentIssueProduct" class="btn btn-outline-primary"><i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.search') }}</button></div>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentIssueName">{{ __('ui.common.item_name') }}</label>
                        <div class="col-md-8">
                            <input id="equipmentIssueName" value="{{ $document?->item?->item_name }}" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentIssueCategory">{{ __('ui.common.category') }}</label>
                        <div class="col-md-8">
                            <input id="equipmentIssueCategory" value="{{ $document?->item?->product?->category?->name ?? '—' }}" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentIssueUnit">{{ __('ui.common.unit') }}</label>
                        <div class="col-md-8">
                            <input id="equipmentIssueUnit" value="{{ $document?->item?->unit }}" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentIssueQuantity">{{ __('ui.common.quantity') }} *</label>
                        <div class="col-md-8">
                            <input type="number" min="0.0001" step="0.0001" name="quantity" id="equipmentIssueQuantity" value="{{ $document?->item?->quantity }}" required class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="equipmentIssueDescription">{{ __('ui.common.description') }}</label>
                        <div class="col-md-8">
                            <textarea name="description" id="equipmentIssueDescription" rows="2" class="form-control form-control-sm">{{ $document?->item?->description }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <p class="small text-muted mb-0 mt-2">{{ __('ui.machine_issue.stock_notice') }}</p>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-feather="save" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.save') }}
            </button>
        </div>
    </form>
</div>

<div class="modal fade" id="equipmentIssueProductModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ __('ui.machine_issue.select_item') }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="table-responsive"><table id="equipmentIssueProducts" class="table table-sm table-bordered w-100"><thead><tr><th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.category') }}</th><th>{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead></table></div></div></div></div></div>
@endsection

@push('scripts')
<script>
$(function () {
 const productModal = new bootstrap.Modal('#equipmentIssueProductModal');
 const products=$('#equipmentIssueProducts').DataTable({processing:true,serverSide:true,scrollX:true,ajax:@json(route('super.equipment-issues.products.dt')),columns:[{data:'code',name:'code'},{data:'name',name:'name'},{data:'category_label',orderable:false,searchable:false},{data:'unit_label',orderable:false,searchable:false},{data:'actions',orderable:false,searchable:false}]});
 $(document).on('click','.btn-select-equipment-issue-product',function(){const b=$(this);$('#equipmentIssueProductId').val(b.data('id'));$('#equipmentIssueCode').val(b.data('code'));$('#equipmentIssueName').val(b.data('name'));$('#equipmentIssueCategory').val(b.attr('data-category'));$('#equipmentIssueUnit').val(b.data('unit'));productModal.hide()});
 $('#chooseEquipmentIssueProduct').on('click', () => productModal.show());
 $('#equipmentIssueProductModal').on('shown.bs.modal', () => products.columns.adjust());
 $('#equipmentIssueForm').on('submit', function (event) {
    event.preventDefault();
    const save = $(this).find('[type=submit]').prop('disabled', true);
    $.ajax({url: this.action, method: 'POST', data: $(this).serialize(), dataType: 'json'})
        .done(result => {
            const date = this.elements['issue_date'].value;
            const from = @json(now()->startOfMonth()->toDateString());
            const to = @json(now()->toDateString());
            const params = new URLSearchParams({from_date: date < from ? date : from, to_date: date > to ? date : to});
            Swal.fire({icon: 'success', text: result.message + ' (' + result.number + ')'}).then(() => {
                window.location.href = @json(route('super.equipment-issues.index')) + '?' + params;
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
