@extends('layouts.admin')

@section('title', __('ui.master.importers'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ __('ui.master.importers') }}</h4>
            <p class="text-muted mb-0">{{ __('ui.master.importer_subtitle') }}</p>
        </div>
        @can('importers.create')
            <button type="button" class="btn btn-primary" id="btn-add-importer">
                <i data-feather="plus" class="icon-sm me-1"></i> {{ __('ui.master.add_importer') }}
            </button>
        @endcan
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="importers-table" class="table table-bordered table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th width="50">{{ __('ui.common.no') }}</th>
                            <th>{{ __('ui.master.tax_number') }}</th>
                            <th>{{ __('ui.common.name') }}</th>
                            <th>{{ __('ui.common.address') }}</th>
                            <th>APIP</th>
                            <th>{{ __('ui.common.status') }}</th>
                            <th width="80">{{ __('ui.common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @canany(['importers.create', 'importers.update'])
        <div class="modal fade" id="importer-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form id="importer-form">
                        <div class="modal-header">
                            <h5 class="modal-title" id="importer-modal-title">{{ __('ui.master.add_importer') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="importer-id">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="importer-tax-number">{{ __('ui.master.tax_number') }}</label>
                                    <input type="text" class="form-control" id="importer-tax-number" maxlength="100" autocomplete="off">
                                    <div class="invalid-feedback" id="importer-tax-number-error"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="importer-name">{{ __('ui.common.name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="importer-name" maxlength="150" autocomplete="off" required>
                                    <div class="invalid-feedback" id="importer-name-error"></div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label" for="importer-address">{{ __('ui.common.address') }}</label>
                                    <textarea class="form-control" id="importer-address" rows="3"></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="importer-apip">APIP</label>
                                    <input type="text" class="form-control" id="importer-apip" maxlength="100" autocomplete="off">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="importer-status">{{ __('ui.common.status') }}</label>
                                    <select class="form-select" id="importer-status">
                                        <option value="1">{{ __('ui.master.status_active') }}</option>
                                        <option value="0">{{ __('ui.master.status_inactive') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
                            <button type="submit" class="btn btn-primary" id="btn-save-importer">{{ __('ui.common.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcanany
@endsection

@canany(['importers.view', 'importers.create', 'importers.update', 'importers.delete'])
    @push('scripts')
        <script>
            $(document).ready(function () {
                const tableSelector = '#importers-table';
                const form = $('#importer-form');
                const modalElement = document.getElementById('importer-modal');
                const importerModal = modalElement ? new bootstrap.Modal(modalElement) : null;

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
                    ajax: @json(route('super.importers.dt')),
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
                        { data: 'apip', name: 'apip', defaultContent: '-' },
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
                    $('#importer-tax-number, #importer-name').removeClass('is-invalid');
                    $('#importer-tax-number-error, #importer-name-error').text('');
                }

                function resetForm() {
                    if (!form.length) return;
                    form[0].reset();
                    $('#importer-id').val('');
                    $('#importer-status').val('1');
                    $('#importer-modal-title').text(@json(__('ui.master.add_importer')));
                    form.removeData('mode').removeData('action');
                    clearErrors();
                }

                function showError(xhr) {
                    const errors = xhr.responseJSON?.errors || {};
                    Object.entries(errors).forEach(([field, messages]) => {
                        const input = field === 'tax_number' ? '#importer-tax-number' : `#importer-${field}`;
                        $(`${input}`).addClass('is-invalid');
                        $(`${input}-error`).text(messages[0]);
                    });
                    if (!Object.keys(errors).length && window.Swal) {
                        Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || @json(__('ui.master.failure')) });
                    }
                }

                $('#btn-add-importer').on('click', function () {
                    resetForm();
                    form.data('mode', 'create').data('action', @json(route('super.importers.store')));
                    importerModal?.show();
                });

                $(document).on('click', '#importers-table .btn-edit-role', function () {
                    resetForm();
                    const button = $(this);
                    const payload = button.data('payload') || {};
                    const updateUrl = button.data('update-url');
                    if (!updateUrl) return;

                    $('#importer-id').val(payload.id || '');
                    $('#importer-tax-number').val(payload.tax_number || '');
                    $('#importer-name').val(payload.name || '');
                    $('#importer-address').val(payload.address || '');
                    $('#importer-apip').val(payload.apip || '');
                    $('#importer-status').val(payload.is_active ? '1' : '0');
                    $('#importer-modal-title').text(@json(__('ui.master.edit_importer')));
                    form.data('mode', 'edit').data('action', updateUrl);
                    importerModal?.show();
                });

                form.on('submit', function (event) {
                    event.preventDefault();
                    clearErrors();
                    const mode = form.data('mode');
                    const action = form.data('action');
                    if (!action) return;

                    const data = {
                        _token: @json(csrf_token()),
                        tax_number: $('#importer-tax-number').val(),
                        name: $('#importer-name').val(),
                        address: $('#importer-address').val(),
                        apip: $('#importer-apip').val(),
                        is_active: $('#importer-status').val()
                    };
                    if (mode === 'edit') data._method = 'PUT';

                    const saveButton = $('#btn-save-importer').prop('disabled', true);
                    $.ajax({ url: action, type: 'POST', data: data, dataType: 'json' })
                        .done(function (response) {
                            importerModal?.hide();
                            table.ajax.reload(null, false);
                            if (window.Swal) Swal.fire({ toast: true, icon: 'success', position: 'top-end', timer: 1800, showConfirmButton: false, title: response.message });
                        })
                        .fail(showError)
                        .always(() => saveButton.prop('disabled', false));
                });

                $(document).on('click', '#importers-table .btn-delete-role', function () {
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
                            text: button.data('confirm') || @json(__('ui.master.delete_importer_confirmation')),
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
