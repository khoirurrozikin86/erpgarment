@extends('layouts.admin')

@section('title', __('ui.master.category_title'))

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">

            <li class="breadcrumb-item">
                <a href="#">{{ __('ui.common.master') }}</a>
            </li>

            <li class="breadcrumb-item active" aria-current="page">
                {{ __('ui.master.category_title') }}
            </li>

        </ol>
    </nav>
@endsection

@section('content')

    {{-- =========================
        HEADER
    ========================== --}}

    <div class="row">


        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="page-title mb-1">
                    {{ __('ui.master.category_title') }}
                </h4>

                <span class="text-muted">
                    {{ __('ui.master.category_subtitle') }}
                </span>
            </div>

            <div class="d-flex gap-2">
                @can('categories.view')
                    <a href="{{ route('super.categories.template') }}" class="btn btn-outline-secondary">
                        <i data-feather="file-text" class="icon-sm me-1"></i> {{ __('ui.master.template') }}
                    </a>
                    <a href="{{ route('super.categories.export') }}" class="btn btn-success">
                        <i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>
                @endcan
                @can('categories.create')
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#categoryImportModal">
                        <i data-feather="upload" class="icon-sm me-1"></i> {{ __('ui.master.import_excel') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-add-category">
                        <i data-feather="plus" class="icon-sm me-1"></i> {{ __('ui.master.add_category') }}
                    </button>
                @endcan
            </div>

        </div>




        {{-- =========================
        TABLE
    ========================== --}}

        <div class="card">

            <div class="card-body">

                <div class="table-responsive">

                    <table id="categories-table" class="table table-bordered table-hover align-middle w-100">

                        <thead>

                            <tr>

                                <th width="5%">{{ __('ui.common.no') }}</th>

                                <th width="15%">
                                    {{ __('ui.master.code') }}
                                </th>

                                <th>{{ __('ui.common.name') }}</th>

                                <th>
                                    {{ __('ui.master.description') }}
                                </th>

                                <th width="10%">{{ __('ui.common.status') }}</th>

                                <th width="12%">{{ __('ui.common.action') }}</th>

                            </tr>

                        </thead>

                        <tbody></tbody>

                    </table>

                </div>

            </div>

        </div>


        @if (session('category_import_success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __('ui.master.success') }}',
                        text: @json(session('category_import_success')),
                        confirmButtonText: '{{ __('ui.master.ok') }}'
                    });
                });
            </script>
        @endif

        {{-- MODAL IMPORT KATEGORI --}}
        <div class="modal fade" id="categoryImportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content">
                <form action="{{ route('super.categories.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header"><div>
                        <h5 class="modal-title">{{ __('ui.master.import_category') }}</h5>
                        <small class="text-muted">{{ __('ui.master.import_template_help', ['name' => __('ui.master.category')]) }}</small>
                    </div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
                    <div class="modal-body">
                        @error('file')<div class="alert alert-danger">{{ $message }}</div>@enderror
                        <label for="category-import-file" class="form-label">{{ __('ui.common.file_excel_csv') }}</label>
                        <input type="file" id="category-import-file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">{{ __('ui.master.import_file_help', ['name' => __('ui.master.category')]) }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary"><i data-feather="upload" class="icon-sm me-1"></i> {{ __('ui.master.import_category') }}</button>
                    </div>
                </form>
            </div></div>
        </div>

        {{-- MODAL CREATE / EDIT --}}

        <div class="modal fade" id="category-modal" tabindex="-1" aria-hidden="true">

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    <form id="category-form">

                        {{-- HEADER --}}

                        <div class="modal-header">

                            <h5 class="modal-title" id="category-modal-title">

                                {{ __('ui.master.add_category') }}

                            </h5>

                            <button type="button" class="btn-close" data-bs-dismiss="modal">
                            </button>

                        </div>


                        {{-- BODY --}}

                        <div class="modal-body">

                            {{-- ID --}}

                            <input type="hidden" id="category-id">


                            {{-- CODE --}}

                            <div class="mb-3">

                                <label class="form-label" for="category-code">

                                    {{ __('ui.master.code') }}

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input type="text" class="form-control" id="category-code" maxlength="50"
                                    autocomplete="off" required>

                                <div class="invalid-feedback" id="category-code-error">
                                </div>

                            </div>


                            {{-- NAME --}}

                            <div class="mb-3">

                                <label class="form-label" for="category-name">{{ __('ui.common.name') }}<span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input type="text" class="form-control" id="category-name" maxlength="100"
                                    autocomplete="off" required>

                                <div class="invalid-feedback" id="category-name-error">
                                </div>

                            </div>


                            {{-- DESCRIPTION --}}

                            <div class="mb-3">

                                <label class="form-label" for="category-description">

                                    {{ __('ui.master.description') }}

                                </label>

                                <textarea class="form-control" id="category-description" rows="3">
                            </textarea>

                                <div class="invalid-feedback" id="category-description-error">
                                </div>

                            </div>


                            {{-- ACTIVE --}}

                            <div class="form-check form-switch">

                                <input type="checkbox" class="form-check-input" id="category-active" checked>

                                <label class="form-check-label" for="category-active">

                                    {{ __('ui.master.status_active') }}

                                </label>

                            </div>

                        </div>


                        {{-- FOOTER --}}

                        <div class="modal-footer">

                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>

                            <button type="submit" class="btn btn-primary" id="btn-save-category">

                                <i data-feather="save" class="me-1">
                                </i>{{ __('ui.common.save') }}</button>

                        </div>

                    </form>

                </div>

            </div>
        </div>

    </div>

@endsection


@push('scripts')
    <script>
        $(document).ready(function() {

            @if ($errors->has('file'))
                new bootstrap.Modal(document.getElementById('categoryImportModal')).show();
            @endif

            'use strict';


            // =====================================================
            // ELEMENT
            // =====================================================

            const DT_SEL = '#categories-table';

            const modalElement =
                document.getElementById('category-modal');

            const categoryModal =
                new bootstrap.Modal(modalElement);

            const $form =
                $('#category-form');

            const $btnSave =
                $('#btn-save-category');


            // =====================================================
            // AJAX SETUP
            // =====================================================

            $.ajaxSetup({

                headers: {

                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    'X-Requested-With': 'XMLHttpRequest',

                    'Accept': 'application/json'
                }

            });


            // =====================================================
            // FEATHER
            // =====================================================

            function safeFeatherReplace() {

                if (
                    !window.feather ||
                    !feather.icons
                ) {
                    return;
                }

                document
                    .querySelectorAll('[data-feather]')
                    .forEach(function(el) {

                        const name =
                            el.getAttribute('data-feather') ||
                            'settings';

                        if (!feather.icons[name]) {

                            el.setAttribute(
                                'data-feather',
                                'settings'
                            );
                        }

                    });

                try {

                    feather.replace();

                } catch (e) {

                    console.error(
                        'Feather error:',
                        e
                    );

                }

            }


            // =====================================================
            // TOAST SUCCESS
            // =====================================================

            function toastOk(message) {

                if (!window.Swal) {
                    return;
                }

                Swal.fire({

                    toast: true,

                    icon: 'success',

                    position: 'top-end',

                    timer: 1800,

                    showConfirmButton: false,

                    title: message || 'Success'

                });

            }


            // =====================================================
            // ERROR
            // =====================================================

            function toastError(message) {

                if (!window.Swal) {
                    return;
                }

                Swal.fire({

                    icon: 'error',

                    title: '{{ __('ui.master.failure') }}',

                    text: message ||
                        'Something went wrong'

                });

            }


            // =====================================================
            // CLEAR ERRORS
            // =====================================================

            function clearErrors() {

                $('#category-code')
                    .removeClass('is-invalid');

                $('#category-name')
                    .removeClass('is-invalid');

                $('#category-description')
                    .removeClass('is-invalid');

                $('#category-code-error')
                    .text('');

                $('#category-name-error')
                    .text('');

                $('#category-description-error')
                    .text('');

            }


            // =====================================================
            // RESET FORM
            // =====================================================

            function resetForm() {

                $form[0].reset();

                $('#category-id')
                    .val('');

                $form
                    .removeData('mode')
                    .removeData('action');

                $('#category-active')
                    .prop('checked', true);

                $('#category-modal-title')
                    .text('{{ __('ui.master.add_category') }}');

                clearErrors();

            }


            // =====================================================
            // DATATABLE
            // =====================================================

            const table =
                $(DT_SEL).DataTable({

                    processing: true,

                    serverSide: true,

                    ajax: {

                        url: '{{ route('super.categories.dt') }}',

                        type: 'GET'

                    },

                    columns: [

                        // NO
                        {

                            data: null,

                            searchable: false,

                            orderable: false,

                            className: 'text-center',

                            render: function(
                                data,
                                type,
                                row,
                                meta
                            ) {

                                return (
                                    meta.row +
                                    meta.settings
                                    ._iDisplayStart +
                                    1
                                );

                            }

                        },


                        // CODE
                        {

                            data: 'code',

                            name: 'code'

                        },


                        // NAME
                        {

                            data: 'name',

                            name: 'name'

                        },


                        // DESCRIPTION
                        {

                            data: 'description',

                            name: 'description',

                            render: function(data) {

                                return data ?
                                    data :
                                    '-';

                            }

                        },


                        // STATUS
                        {

                            data: 'status',

                            name: 'is_active',

                            orderable: false,

                            searchable: false,

                            className: 'text-center',

                            render: function(data) {

                                if (
                                    data === @json(__('ui.master.status_active'))
                                ) {

                                    return `
                                    <span class="badge bg-success">
                                        {{ __('ui.master.status_active') }}
                                    </span>
                                `;

                                }

                                return `
                                <span class="badge bg-secondary">
                                    {{ __('ui.master.status_inactive') }}
                                </span>
                            `;

                            }

                        },


                        // ACTIONS
                        {

                            data: 'actions',

                            name: 'actions',

                            orderable: false,

                            searchable: false,

                            className: 'text-center'

                        }

                    ],

                    order: [
                        [1, 'asc']
                    ],

                    language: {

                        processing: '{{ __('ui.common.data_processing') }}',

                        search: '{{ __('ui.common.search') }}:',

                        lengthMenu: '{{ __('ui.common.show_entries') }}',

                        info: '{{ __('ui.common.showing_entries') }}',

                        infoEmpty: '{{ __('ui.common.no_data') }}',

                        zeroRecords: '{{ __('ui.common.no_matching_data') }}',

                        emptyTable: '{{ __('ui.common.empty_table') }}',

                        paginate: {

                            first: '{{ __('ui.common.first') }}',

                            last: '{{ __('ui.common.last') }}',

                            next: '›',

                            previous: '‹'

                        }

                    }

                });


            // Render Feather setiap DataTables draw

            $(DT_SEL).on(
                'draw.dt',
                safeFeatherReplace
            );


            // =====================================================
            // NEW CATEGORY
            // =====================================================

            $('#btn-add-category')
                .on('click', function() {

                    resetForm();

                    $form
                        .data(
                            'mode',
                            'create'
                        )
                        .data(
                            'action',
                            '{{ route('super.categories.store') }}'
                        );

                    $('#category-modal-title')
                        .text('{{ __('ui.master.add_category') }}');

                    categoryModal.show();

                });


            // =====================================================
            // EDIT CATEGORY
            // =====================================================

            $(document).on(
                'click',
                '.btn-edit-role',
                function() {

                    clearErrors();

                    $form[0].reset();

                    const button =
                        $(this);

                    let payload = {};

                    try {

                        payload =
                            JSON.parse(
                                button.attr(
                                    'data-payload'
                                ) || '{}'
                            );

                    } catch (e) {

                        console.error(
                            'Invalid action payload:',
                            e
                        );

                        return;

                    }


                    // ID

                    $('#category-id')
                        .val(
                            payload.id || ''
                        );


                    // FORM DATA

                    $('#category-code')
                        .val(
                            payload.code || ''
                        );

                    $('#category-name')
                        .val(
                            payload.name || ''
                        );

                    $('#category-description')
                        .val(
                            payload.description || ''
                        );

                    $('#category-active')
                        .prop(
                            'checked',
                            payload.is_active == 1 ||
                            payload.is_active === true
                        );


                    // UPDATE URL

                    const updateUrl =
                        button.data(
                            'update-url'
                        );

                    if (!updateUrl) {

                        toastError(
                            'Update URL tidak ditemukan.'
                        );

                        return;

                    }


                    $form
                        .data(
                            'mode',
                            'edit'
                        )
                        .data(
                            'action',
                            updateUrl
                        );


                    $('#category-modal-title')
                        .text('{{ __('ui.common.edit') }} {{ __('ui.common.category') }}');


                    categoryModal.show();

                }
            );


            // =====================================================
            // SAVE CATEGORY
            // =====================================================

            $form.on(
                'submit',
                function(e) {

                    e.preventDefault();

                    clearErrors();


                    const mode =
                        $form.data('mode');

                    const action =
                        $form.data('action');


                    if (!action) {

                        toastError(
                            'URL proses tidak ditemukan.'
                        );

                        return;

                    }


                    const data = {

                        _token: '{{ csrf_token() }}',

                        code: $('#category-code').val(),

                        name: $('#category-name').val(),

                        description: $('#category-description').val(),

                        is_active: $('#category-active')
                            .is(':checked') ?
                            1 : 0

                    };


                    // PUT ketika EDIT

                    if (
                        mode === 'edit'
                    ) {

                        data._method = 'PUT';

                    }


                    $btnSave
                        .prop(
                            'disabled',
                            true
                        )
                        .html(
                            '<span class="spinner-border spinner-border-sm me-1"></span> {{ __('ui.workflow.saving') }}'
                        );


                    $.ajax({

                        url: action,

                        type: 'POST',

                        data: data,

                        dataType: 'json',


                        success: function(response) {

                            categoryModal.hide();

                            table.ajax.reload(
                                null,
                                false
                            );

                            toastOk(
                                response.message ||
                                (
                                    mode === 'edit' ?
                                    '{{ __('ui.master.updated', ['type' => __('ui.common.category')]) }}' :
                                    '{{ __('ui.master.created', ['type' => __('ui.common.category')]) }}'
                                )
                            );

                        },


                        error: function(xhr) {

                            if (
                                xhr.status === 422 &&
                                xhr.responseJSON?.errors
                            ) {

                                const errors =
                                    xhr.responseJSON.errors;


                                if (
                                    errors.code
                                ) {

                                    $('#category-code')
                                        .addClass(
                                            'is-invalid'
                                        );

                                    $('#category-code-error')
                                        .text(
                                            errors.code[0]
                                        );

                                }


                                if (
                                    errors.name
                                ) {

                                    $('#category-name')
                                        .addClass(
                                            'is-invalid'
                                        );

                                    $('#category-name-error')
                                        .text(
                                            errors.name[0]
                                        );

                                }


                                if (
                                    errors.description
                                ) {

                                    $('#category-description')
                                        .addClass(
                                            'is-invalid'
                                        );

                                    $('#category-description-error')
                                        .text(
                                            errors.description[0]
                                        );

                                }


                                return;

                            }


                            toastError(
                                xhr.responseJSON?.message ||
                                '{{ __('ui.master.category_save_failed') }}'
                            );

                        },


                        complete: function() {

                            $btnSave
                                .prop(
                                    'disabled',
                                    false
                                )
                                .html(
                                    '<i data-feather="save" class="me-1"></i> Simpan'
                                );

                            safeFeatherReplace();

                        }

                    });

                }
            );


            // =====================================================
            // DELETE CATEGORY
            // =====================================================

            $(document).on(
                'click',
                '.btn-delete-role',
                function() {

                    const button =
                        $(this);

                    const url =
                        button.data('url');

                    const confirmMessage =
                        button.data('confirm') ||
                        '{{ __('ui.master.category_delete_fallback') }}';


                    if (!url) {

                        toastError(
                            '{{ __('ui.master.category_delete_url_missing') }}'
                        );

                        return;

                    }


                    Swal.fire({

                        title: '{{ __('ui.master.delete_category_title') }}',

                        text: confirmMessage,

                        icon: 'warning',

                        showCancelButton: true,

                        confirmButtonText: '{{ __('ui.master.confirm_delete') }}',

                        cancelButtonText: '{{ __('ui.common.cancel') }}',

                        reverseButtons: true

                    }).then(
                        function(result) {

                            if (
                                !result.isConfirmed
                            ) {

                                return;

                            }


                            $.ajax({

                                url: url,

                                type: 'POST',

                                data: {

                                    _token: '{{ csrf_token() }}',

                                    _method: 'DELETE'

                                },

                                dataType: 'json',


                                success: function(response) {

                                    table.ajax.reload(
                                        null,
                                        false
                                    );

                                    toastOk(
                                        response.message ||
                                        '{{ __('ui.master.deleted', ['type' => __('ui.common.category')]) }}'
                                    );

                                },


                                error: function(xhr) {

                                    toastError(
                                        xhr.responseJSON?.message ||
                                        '{{ __('ui.master.record_delete_failed') }}'
                                    );

                                }

                            });

                        }
                    );

                }
            );


            // =====================================================
            // MODAL HIDDEN
            // =====================================================

            modalElement.addEventListener(
                'hidden.bs.modal',
                function() {

                    resetForm();

                }
            );


            // Initial feather

            safeFeatherReplace();

        });
    </script>
@endpush
