@extends('layouts.admin')

@section('title', __('ui.master.exporters'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ __('ui.master.exporters') }}</h4>
            <p class="text-muted mb-0">{{ __('ui.master.exporter_subtitle') }}</p>
        </div>
        @can('exporters.create')
            <button type="button" class="btn btn-primary" id="btn-add-exporter">
                <i data-feather="plus" class="icon-sm me-1"></i> {{ __('ui.master.add_exporter') }}
            </button>
        @endcan
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="exporters-table" class="table table-bordered table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th width="50">{{ __('ui.common.no') }}</th>
                            <th>{{ __('ui.master.tax_number') }}</th>
                            <th>{{ __('ui.common.name') }}</th>
                            <th>{{ __('ui.common.address') }}</th>
                            <th>{{ __('ui.common.status') }}</th>
                            <th width="80">{{ __('ui.common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @canany(['exporters.create', 'exporters.update'])
        <div class="modal fade" id="exporter-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form id="exporter-form">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exporter-modal-title">{{ __('ui.master.add_exporter') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="exporter-id">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="exporter-tax-number">{{ __('ui.master.tax_number') }}</label>
                                    <input type="text" class="form-control" id="exporter-tax-number" maxlength="100" autocomplete="off">
                                    <div class="invalid-feedback" id="exporter-tax-number-error"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="exporter-name">{{ __('ui.common.name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="exporter-name" maxlength="150" autocomplete="off" required>
                                    <div class="invalid-feedback" id="exporter-name-error"></div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label" for="exporter-address">{{ __('ui.common.address') }}</label>
                                    <textarea class="form-control" id="exporter-address" rows="3"></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="exporter-status">{{ __('ui.common.status') }}</label>
                                    <select class="form-select" id="exporter-status">
                                        <option value="1">{{ __('ui.master.status_active') }}</option>
                                        <option value="0">{{ __('ui.master.status_inactive') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
                            <button type="submit" class="btn btn-primary" id="btn-save-exporter">{{ __('ui.common.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcanany
@endsection

@canany(['exporters.view', 'exporters.create', 'exporters.update', 'exporters.delete'])
    @push('scripts')
        <script>
            $(document).ready(function () {
                const tableSelector = '#exporters-table';
                const form = $('#exporter-form');
                const modalElement = document.getElementById('exporter-modal');
                const exporterModal = modalElement ? new bootstrap.Modal(modalElement) : null;

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const table = $(tableSelector).DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: @json(route('super.exporters.dt')),
                    columns: [
                        {
                            data: null,
                            name: 'id',
                            searchable: false,
                            orderable: false,
                            render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                        },
                        { data: 'tax_number', name: 'tax_number', defaultContent: '-' },
                        { data: 'name', name: 'name' },
                        { data: 'address', name: 'address', defaultContent: '-' },
                        {
                            data: 'status',
                            name: 'is_active',
                            searchable: false,
                            render: function (status) {
                                const active = status === @json(__('ui.master.status_active'));
                                return `<span class="badge ${active ? 'bg-success' : 'bg-secondary'}">${status}</span>`;
                            }
                        },
                        { data: 'actions', name: 'actions', searchable: false, orderable: false }
                    ],
                    drawCallback: function () {
                        if (window.feather) feather.replace();
                    }
                });

                function clearErrors() {
                    $('#exporter-tax-number, #exporter-name').removeClass('is-invalid');
                    $('#exporter-tax-number-error, #exporter-name-error').text('');
                }

                function resetForm() {
                    if (!form.length) return;
                    form[0].reset();
                    $('#exporter-id').val('');
                    $('#exporter-status').val('1');
                    $('#exporter-modal-title').text(@json(__('ui.master.add_exporter')));
                    form.removeData('mode').removeData('action');
                    clearErrors();
                }

                function showError(xhr) {
                    const errors = xhr.responseJSON?.errors || {};
                    Object.entries(errors).forEach(([field, messages]) => {
                        const input = field === 'tax_number' ? '#exporter-tax-number' : `#exporter-${field}`;
                        $(`${input}`).addClass('is-invalid');
                        $(`${input}-error`).text(messages[0]);
                    });
                    if (!Object.keys(errors).length && window.Swal) {
                        Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || @json(__('ui.master.failure')) });
                    }
                }

                $('#btn-add-exporter').on('click', function () {
                    resetForm();
                    form.data('mode', 'create').data('action', @json(route('super.exporters.store')));
                    exporterModal?.show();
                });

                $(document).on('click', '#exporters-table .btn-edit-role', function () {
                    resetForm();
                    const button = $(this);
                    const payload = button.data('payload') || {};
                    const updateUrl = button.data('update-url');
                    if (!updateUrl) return;

                    $('#exporter-id').val(payload.id || '');
                    $('#exporter-tax-number').val(payload.tax_number || '');
                    $('#exporter-name').val(payload.name || '');
                    $('#exporter-address').val(payload.address || '');
                    $('#exporter-status').val(payload.is_active ? '1' : '0');
                    $('#exporter-modal-title').text(@json(__('ui.master.edit_exporter')));
                    form.data('mode', 'edit').data('action', updateUrl);
                    exporterModal?.show();
                });

                form.on('submit', function (event) {
                    event.preventDefault();
                    clearErrors();
                    const mode = form.data('mode');
                    const action = form.data('action');
                    if (!action) return;

                    const data = {
                        _token: @json(csrf_token()),
                        tax_number: $('#exporter-tax-number').val(),
                        name: $('#exporter-name').val(),
                        address: $('#exporter-address').val(),
                        is_active: $('#exporter-status').val()
                    };
                    if (mode === 'edit') data._method = 'PUT';

                    const saveButton = $('#btn-save-exporter').prop('disabled', true);
                    $.ajax({ url: action, type: 'POST', data: data, dataType: 'json' })
                        .done(function (response) {
                            exporterModal?.hide();
                            table.ajax.reload(null, false);
                            if (window.Swal) Swal.fire({ toast: true, icon: 'success', position: 'top-end', timer: 1800, showConfirmButton: false, title: response.message });
                        })
                        .fail(showError)
                        .always(() => saveButton.prop('disabled', false));
                });

                $(document).on('click', '#exporters-table .btn-delete-role', function () {
                    const button = $(this);
                    const remove = function () {
                        $.ajax({
                            url: button.data('url'),
                            type: 'POST',
                            data: { _token: @json(csrf_token()), _method: 'DELETE' },
                            dataType: 'json'
                        }).done(function (response) {
                            table.ajax.reload(null, false);
                            if (window.Swal) Swal.fire({ toast: true, icon: 'success', position: 'top-end', timer: 1800, showConfirmButton: false, title: response.message });
                        }).fail(showError);
                    };

                    if (window.Swal) {
                        Swal.fire({
                            title: @json(__('ui.master.confirmation')),
                            text: button.data('confirm') || @json(__('ui.master.delete_exporter_confirmation')),
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: @json(__('ui.master.confirm_delete')),
                            cancelButtonText: @json(__('ui.common.cancel')),
                            reverseButtons: true
                        }).then(result => { if (result.isConfirmed) remove(); });
                    } else if (confirm(button.data('confirm'))) {
                        remove();
                    }
                });
            });
        </script>
    @endpush
@endcanany
