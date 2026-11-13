@extends('layouts.admin')

@section('title', 'Pengaturan Style')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">{{ __('ui.common.master') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pengaturan Style</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Pengaturan Style</h4>
        </div>
        @can('styles.create')
            <button type="button" class="btn btn-primary" id="btn-add-style"><i data-feather="plus"
                    class="icon-sm me-1"></i>Tambah Style</button>
        @endcan
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="styles-table" class="table table-bordered table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>{{ __('ui.common.no') }}</th>

                            <th>Kode Style</th>
                            <th>Nama Style</th>
                            <th>{{ __('ui.common.action') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @canany(['styles.create', 'styles.update'])
        <div class="modal fade" id="style-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="style-form">
                        <div class="modal-header">
                            <h5 class="modal-title" id="style-modal-title">Tambah Style</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="{{ __('ui.common.close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="style-id">
                            <div class="mb-3">
                                <label for="style-code" class="form-label">Kode Style <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="style-code" maxlength="80" autocomplete="off"
                                    required>
                                <div class="invalid-feedback" id="style-code-error"></div>
                            </div>
                            <div class="mb-3">
                                <label for="style-name" class="form-label">Nama Style <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="style-name" maxlength="150" autocomplete="off"
                                    required>
                                <div class="invalid-feedback" id="style-name-error"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
                            <button type="submit" class="btn btn-primary"
                                id="btn-save-style">{{ __('ui.common.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcanany
@endsection

@push('scripts')
    <script>
        $(function() {
            const table = $('#styles-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 5,
                ajax: @json(route('super.styles.dt')),
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },

                    {
                        data: 'code',
                        name: 'code'
                    }, {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                drawCallback: function() {
                    if (typeof feather !== 'undefined') feather.replace();
                }
            });

            const form = $('#style-form');
            const modalElement = document.getElementById('style-modal');
            const styleModal = modalElement ? new bootstrap.Modal(modalElement) : null;
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            function clearErrors() {
                $('#style-code, #style-name').removeClass('is-invalid');
                $('#style-code-error, #style-name-error').text('');
            }

            function resetForm() {
                if (!form.length) return;
                form[0].reset();
                $('#style-id').val('');
                clearErrors();
                $('#style-modal-title').text('Tambah Style');
                form.removeData('mode').removeData('action');
            }

            function showErrors(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                Object.entries(errors).forEach(([field, messages]) => {
                    const input = $(`#style-${field}`);
                    input.addClass('is-invalid');
                    $(`#style-${field}-error`).text(messages[0]);
                });
                if (!Object.keys(errors).length) Swal.fire({
                    icon: 'error',
                    title: @json(__('ui.master.failure')),
                    text: xhr.responseJSON?.message || @json(__('ui.master.failure'))
                });
            }

            $('#btn-add-style').on('click', function() {
                resetForm();
                form.data('mode', 'create').data('action', @json(route('super.styles.store')));
                styleModal?.show();
            });
            $(document).on('click', '#styles-table .btn-edit-role', function() {
                resetForm();
                const button = $(this),
                    payload = button.data('payload') || {},
                    updateUrl = button.data('update-url');
                if (!updateUrl) return;
                $('#style-id').val(payload.id || '');
                $('#style-code').val(payload.code || '');
                $('#style-name').val(payload.name || '');
                $('#style-modal-title').text('Edit Style');
                form.data('mode', 'edit').data('action', updateUrl);
                styleModal?.show();
            });
            modalElement?.addEventListener('hidden.bs.modal', resetForm);

            form.on('submit', function(event) {
                event.preventDefault();
                clearErrors();
                const mode = form.data('mode'),
                    data = {
                        code: $('#style-code').val(),
                        name: $('#style-name').val()
                    };
                if (mode === 'edit') data._method = 'PUT';
                const button = $('#btn-save-style').prop('disabled', true);
                $.ajax({
                    url: form.data('action'),
                    type: 'POST',
                    data,
                    success: response => {
                        styleModal?.hide();
                        table.ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: @json(__('ui.master.success')),
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: showErrors,
                    complete: () => button.prop('disabled', false)
                });
            });

            $(document).on('click', '#styles-table .btn-delete-role', function() {
                const button = $(this);
                Swal.fire({
                        icon: 'warning',
                        title: @json(__('ui.master.confirmation')),
                        text: button.data('confirm') || @json(__('ui.master.record_delete_confirmation')),
                        showCancelButton: true,
                        confirmButtonText: @json(__('ui.master.confirm_delete')),
                        cancelButtonText: @json(__('ui.common.cancel')),
                        reverseButtons: true
                    })
                    .then(result => {
                        if (!result.isConfirmed) return;
                        $.ajax({
                            url: button.data('url'),
                            type: 'POST',
                            data: {
                                _method: 'DELETE'
                            },
                            success: response => {
                                table.ajax.reload(null, false);
                                Swal.fire({
                                    icon: 'success',
                                    title: @json(__('ui.master.success')),
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            },
                            error: xhr => Swal.fire({
                                icon: 'error',
                                title: @json(__('ui.master.cannot_delete')),
                                text: xhr.responseJSON?.message ||
                                    @json(__('ui.master.failure'))
                            })
                        });
                    });
            });
            if (typeof feather !== 'undefined') feather.replace();
        });
    </script>
@endpush
