@extends('layouts.admin')

@section('title', __('ui.common.supplier'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">{{ __('ui.common.master') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.supplier') }}</li>
    </ol></nav>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="mb-1">{{ __('ui.common.supplier') }}</h4><p class="text-muted mb-0">{{ __('ui.master.supplier_subtitle') }}</p></div>
    <div class="d-flex gap-2">
        @can('suppliers.view')
            <a href="{{ route('super.suppliers.template') }}" class="btn btn-outline-secondary"><i data-feather="file-text" class="icon-sm me-1"></i>{{ __('ui.master.template') }}</a>
            <a href="{{ route('super.suppliers.export') }}" class="btn btn-success"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>
        @endcan
        @can('suppliers.create')
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#supplierImportModal"><i data-feather="upload" class="icon-sm me-1"></i>{{ __('ui.master.import_excel') }}</button>
            <button type="button" class="btn btn-primary" id="btn-add-supplier"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.master.add_supplier') }}</button>
        @endcan
    </div>
</div>
@if (session('supplier_import_success'))
    <div class="alert alert-success">{{ session('supplier_import_success') }}</div>
@endif
<div class="card"><div class="card-body"><div class="table-responsive">
    <table id="suppliers-table" class="table table-bordered table-hover align-middle w-100">
        <thead><tr>
            <th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.code') }}</th><th>{{ __('ui.master.supplier_name') }}</th>
            <th>{{ __('ui.common.address') }}</th><th>{{ __('ui.common.contact_person') }}</th><th>{{ __('ui.common.phone') }}</th>
            <th>{{ __('ui.common.email') }}</th><th>Fax</th><th>Negara</th><th>{{ __('ui.common.action') }}</th>
        </tr></thead>
    </table>
</div></div></div>

<div class="modal fade" id="supplierImportModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <form action="{{ route('super.suppliers.import') }}" method="POST" enctype="multipart/form-data">@csrf
        <div class="modal-header"><div><h5 class="modal-title">{{ __('ui.master.import_supplier') }}</h5><small class="text-muted">{{ __('ui.master.import_pick_help', ['name' => __('ui.master.supplier')]) }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">@error('file')<div class="alert alert-danger">{{ $message }}</div>@enderror<label for="supplier-import-file" class="form-label">{{ __('ui.common.file_excel_csv') }}</label><input type="file" id="supplier-import-file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required><div class="form-text">{{ __('ui.master.import_file_help', ['name' => __('ui.common.supplier')]) }}</div></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('ui.master.import_supplier') }}</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="supplierModalTitle">{{ __('ui.master.add_supplier') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form id="supplier-form"><div class="modal-body">
        <input type="hidden" id="supplier-id"><input type="hidden" id="supplier-active" value="1">
        <div class="row">
            <div class="col-md-4 mb-3"><label for="supplier-code" class="form-label">{{ __('ui.master.supplier_code') }} <span class="text-danger">*</span></label><input type="text" class="form-control" id="supplier-code" required></div>
            <div class="col-md-8 mb-3"><label for="supplier-name" class="form-label">{{ __('ui.master.supplier_name') }} <span class="text-danger">*</span></label><input type="text" class="form-control" id="supplier-name" required></div>
            <div class="col-12 mb-3"><label for="supplier-address" class="form-label">{{ __('ui.common.address') }}</label><textarea class="form-control" id="supplier-address" rows="2"></textarea></div>
            <div class="col-md-6 mb-3"><label for="supplier-contact-person" class="form-label">{{ __('ui.common.contact_person') }}</label><input type="text" class="form-control" id="supplier-contact-person"></div>
            <div class="col-md-6 mb-3"><label for="supplier-phone" class="form-label">{{ __('ui.common.phone') }}</label><input type="text" class="form-control" id="supplier-phone"></div>
            <div class="col-md-6 mb-3"><label for="supplier-fax" class="form-label">Fax</label><input type="text" class="form-control" id="supplier-fax"></div>
            <div class="col-md-6 mb-3"><label for="supplier-email" class="form-label">{{ __('ui.common.email') }}</label><input type="email" class="form-control" id="supplier-email"></div>
            <div class="col-md-6 mb-3"><label for="supplier-country" class="form-label">Negara</label><select class="form-select" id="supplier-country"><option value="">{{ __('ui.common.select') }}</option>@foreach ($countries as $country)<option value="{{ $country }}">{{ $country }}</option>@endforeach</select></div>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button><button type="submit" class="btn btn-primary" id="btn-save-supplier">{{ __('ui.common.save') }}</button></div></form>
</div></div></div>
@endsection

@push('scripts')
<script>
$(function () {
    $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}});
    const createUrl = @json(route('super.suppliers.store'));
    const table = $('#suppliers-table').DataTable({
        processing: true, serverSide: true, ajax: @json(route('super.suppliers.dt')),
        columns: [
            {data: null, orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1},
            {data: 'code', name: 'code'}, {data: 'name', name: 'name'},
            {data: 'address', name: 'address', render: data => data || '-'},
            {data: 'contact_person', name: 'contact_person', render: data => data || '-'},
            {data: 'phone', name: 'phone', render: data => data || '-'},
            {data: 'email', name: 'email', render: data => data || '-'},
            {data: 'fax', name: 'fax', render: data => data || '-'},
            {data: 'country', name: 'country', render: data => data || '-'},
            {data: 'actions', orderable: false, searchable: false, className: 'text-center'}
        ], drawCallback: function () { if (typeof feather !== 'undefined') feather.replace(); }
    });

    $('#btn-add-supplier').on('click', function () {
        $('#supplier-form')[0].reset(); $('#supplier-id').val(''); $('#supplier-active').val('1');
        $('#supplierModalTitle').text(@json(__('ui.master.add_supplier')));
        $('#supplier-form').data('mode', 'create').data('action', createUrl);
        $('#supplierModal').modal('show');
    });

    $(document).on('click', '.btn-edit-role', function () {
        const payload = $(this).data('payload') || {};
        $('#supplier-id').val(payload.id || '');
        $('#supplier-code').val(payload.code || ''); $('#supplier-name').val(payload.name || '');
        $('#supplier-address').val(payload.address || ''); $('#supplier-contact-person').val(payload.contact_person || '');
        $('#supplier-phone').val(payload.phone || ''); $('#supplier-fax').val(payload.fax || '');
        $('#supplier-email').val(payload.email || '');
        if (payload.country && !$('#supplier-country option').filter(function () { return this.value === payload.country; }).length) {
            $('#supplier-country').append(new Option(payload.country, payload.country));
        }
        $('#supplier-country').val(payload.country || ''); $('#supplier-active').val(payload.is_active ? '1' : '0');
        $('#supplierModalTitle').text(@json(__('ui.common.edit').' '.__('ui.common.supplier')));
        $('#supplier-form').data('mode', 'edit').data('action', $(this).data('update-url'));
        $('#supplierModal').modal('show');
    });

    $('#supplier-form').on('submit', function (event) {
        event.preventDefault();
        const $form = $(this), mode = $form.data('mode'), $button = $('#btn-save-supplier');
        const data = {
            code: $('#supplier-code').val(), name: $('#supplier-name').val(), address: $('#supplier-address').val(),
            contact_person: $('#supplier-contact-person').val(), phone: $('#supplier-phone').val(), fax: $('#supplier-fax').val(),
            email: $('#supplier-email').val(), country: $('#supplier-country').val(), is_active: $('#supplier-active').val()
        };
        if (mode === 'edit') data._method = 'PUT';
        $button.prop('disabled', true);
        $.ajax({url: $form.data('action'), type: 'POST', data,
            success: response => { $('#supplierModal').modal('hide'); table.ajax.reload(null, false); Swal.fire({icon: 'success', title: @json(__('ui.master.success')), text: response.message, timer: 1500, showConfirmButton: false}); },
            error: xhr => { const errors = xhr.responseJSON?.errors; Swal.fire({icon: 'error', title: @json(__('ui.master.failure')), html: errors ? Object.values(errors).flat().join('<br>') : (xhr.responseJSON?.message || @json(__('ui.master.failure')))}); },
            complete: () => $button.prop('disabled', false)
        });
    });

    $(document).on('click', '.btn-delete-role', function () {
        const $button = $(this);
        Swal.fire({icon: 'warning', title: @json(__('ui.master.confirmation')), text: $button.data('confirm') || @json(__('ui.master.record_delete_confirmation')), showCancelButton: true, confirmButtonText: @json(__('ui.master.confirm_delete')), cancelButtonText: @json(__('ui.common.cancel')), reverseButtons: true})
            .then(result => { if (!result.isConfirmed) return; $.ajax({url: $button.data('url'), type: 'POST', data: {_method: 'DELETE'}, success: response => { table.ajax.reload(null, false); Swal.fire({icon: 'success', title: @json(__('ui.master.success')), text: response.message, timer: 1500, showConfirmButton: false}); }, error: xhr => Swal.fire({icon: 'error', title: @json(__('ui.master.cannot_delete')), text: xhr.responseJSON?.message || @json(__('ui.master.failure'))})}); });
    });

    @if ($errors->has('file')) new bootstrap.Modal(document.getElementById('supplierImportModal')).show(); @endif
    if (typeof feather !== 'undefined') feather.replace();
});
</script>
@endpush
