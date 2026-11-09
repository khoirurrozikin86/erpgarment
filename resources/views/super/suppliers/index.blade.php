@extends('layouts.admin')

@section('title', __('ui.common.supplier'))

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">

            <li class="breadcrumb-item">
                <a href="#">{{ __('ui.common.master') }}</a>
            </li>

            <li class="breadcrumb-item active" aria-current="page">
                Suppliers
            </li>

        </ol>
    </nav>
@endsection


@section('content')
    <div class="row">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h4 class="mb-1">{{ __('ui.common.supplier') }}</h4>
                <p class="text-muted mb-0">
                    {{ __('ui.master.supplier_subtitle') }}
                </p>
            </div>

            <div class="d-flex gap-2">

                @can('suppliers.view')
                    <a href="{{ route('super.suppliers.template') }}" class="btn btn-outline-secondary">
                        <i data-feather="file-text" class="icon-sm me-1"></i>
                        {{ __('ui.master.template') }}
                    </a>
                    <a href="{{ route('super.suppliers.export') }}" class="btn btn-success">

                        <i data-feather="download" class="icon-sm me-1">
                        </i>{{ __('ui.common.export_excel') }}</a>
                @endcan


                @can('suppliers.create')
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#supplierImportModal">
                        <i data-feather="upload" class="icon-sm me-1"></i>
                        {{ __('ui.master.import_excel') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-add-supplier">

                        <i data-feather="plus" class="icon-sm me-1">
                        </i>

                        {{ __('ui.master.add_supplier') }}

                    </button>
                @endcan

            </div>

        </div>

        @if (session('supplier_import_success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __('ui.master.success') }}',
                        text: @json(session('supplier_import_success')),
                        confirmButtonText: '{{ __('ui.master.ok') }}'
                    });
                });
            </script>
        @endif


        {{-- TABLE --}}
        <div class="card">

            <div class="card-body">

                <div class="table-responsive">

                    <table id="suppliers-table" class="table table-bordered table-hover align-middle w-100">

                        <thead>
                            <tr>
                                <th width="50">{{ __('ui.common.no') }}</th>
                                <th>{{ __('ui.common.code') }}</th>
                                <th>{{ __('ui.master.supplier_name') }}</th>
                                <th>{{ __('ui.common.type') }}</th>
                                <th>{{ __('ui.common.contact_person') }}</th>
                                <th>{{ __('ui.common.phone') }}</th>
                                <th>{{ __('ui.common.payment_term') }}</th>
                                <th>{{ __('ui.common.status') }}</th>
                                <th width="80">{{ __('ui.common.action') }}</th>
                            </tr>
                        </thead>

                    </table>

                </div>

            </div>

        </div>

    </div>

    {{-- MODAL IMPORT SUPPLIER --}}
    <div class="modal fade" id="supplierImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('super.suppliers.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">{{ __('ui.master.import_supplier') }}</h5>
                            <small class="text-muted">{{ __('ui.master.import_pick_help', ['name' => __('ui.master.supplier')]) }}</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        @error('file')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <label for="supplier-import-file" class="form-label">{{ __('ui.common.file_excel_csv') }}</label>
                        <input type="file" id="supplier-import-file" name="file" class="form-control"
                            accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">{{ __('ui.master.import_file_help', ['name' => __('ui.master.supplier')]) }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="upload" class="icon-sm me-1"></i> {{ __('ui.master.import_supplier') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- MODAL SUPPLIER --}}
    {{-- ========================================================= --}}

    <div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">

        <div class="modal-dialog modal-xl">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title" id="supplierModalTitle">

                        {{ __('ui.master.add_supplier') }}

                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>

                </div>


                <form id="supplier-form">

                    <div class="modal-body">

                        <input type="hidden" id="supplier-id">


                        {{-- ================================================= --}}
                        {{-- INFORMASI UTAMA --}}
                        {{-- ================================================= --}}

                        <h6 class="mb-3">
                            {{ __('ui.master.supplier_info') }}
                        </h6>

                        <div class="row">

                            {{-- CODE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.supplier_code') }}
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="supplier-code" name="code"
                                    placeholder="SUP-001" required>

                            </div>


                            {{-- NAME --}}
                            <div class="col-md-5 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.supplier_name') }}
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="supplier-name" name="name"
                                    placeholder="{{ __('ui.master.supplier_company_placeholder') }}" required>

                            </div>


                            {{-- TYPE --}}
                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.supplier_type') }}
                                    <span class="text-danger">*</span>
                                </label>

                                <select class="form-select" id="supplier-type" name="supplier_type" required>

                                    <option value="company">
                                        {{ __('ui.master.company') }}
                                    </option>

                                    <option value="individual">
                                        {{ __('ui.master.individual') }}
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="row">

                            {{-- CONTACT PERSON --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">{{ __('ui.common.contact_person') }}</label>

                                <input type="text" class="form-control" id="supplier-contact-person"
                                    name="contact_person" placeholder="{{ __('ui.master.contact_name_placeholder') }}">

                            </div>


                            {{-- PHONE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">{{ __('ui.common.phone') }}</label>

                                <input type="text" class="form-control" id="supplier-phone" name="phone"
                                    placeholder="08xxxxxxxxxx">

                            </div>


                            {{-- EMAIL --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">{{ __('ui.common.email') }}</label>

                                <input type="email" class="form-control" id="supplier-email" name="email"
                                    placeholder="supplier@example.com">

                            </div>

                        </div>


                        <div class="row">

                            {{-- WEBSITE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.website') }}
                                </label>

                                <input type="text" class="form-control" id="supplier-website" name="website"
                                    placeholder="https://example.com">

                            </div>


                            {{-- TAX --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.tax_number') }}
                                </label>

                                <input type="text" class="form-control" id="supplier-tax-number" name="tax_number"
                                    placeholder="{{ __('ui.master.tax_number_placeholder') }}">

                            </div>


                            {{-- PAYMENT TERM --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">{{ __('ui.common.payment_term') }}<span class="text-danger">*</span>
                                </label>

                                <select class="form-select" id="supplier-payment-term" name="payment_term" required>

                                    <option value="COD">
                                        {{ __('ui.master.payment_cod') }}
                                    </option>

                                    <option value="7 Days">
                                        {{ __('ui.master.payment_days', ['days' => 7]) }}
                                    </option>

                                    <option value="14 Days">
                                        {{ __('ui.master.payment_days', ['days' => 14]) }}
                                    </option>

                                    <option value="30 Days">
                                        {{ __('ui.master.payment_days', ['days' => 30]) }}
                                    </option>

                                    <option value="45 Days">
                                        {{ __('ui.master.payment_days', ['days' => 45]) }}
                                    </option>

                                    <option value="60 Days">
                                        {{ __('ui.master.payment_days', ['days' => 60]) }}
                                    </option>

                                </select>

                            </div>

                        </div>


                        <hr class="my-4">


                        {{-- ================================================= --}}
                        {{-- ALAMAT --}}
                        {{-- ================================================= --}}

                        <h6 class="mb-3">{{ __('ui.common.address') }}</h6>

                        <div class="row">

                            {{-- ADDRESS --}}
                            <div class="col-md-12 mb-3">

                                <label class="form-label">{{ __('ui.common.address') }}</label>

                                <textarea class="form-control" id="supplier-address" name="address" rows="3"
                                    placeholder="{{ __('ui.master.address_supplier') }}"></textarea>

                            </div>

                        </div>


                        <div class="row">

                            {{-- CITY --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">{{ __('ui.common.city') }}</label>

                                <input type="text" class="form-control" id="supplier-city" name="city">

                            </div>


                            {{-- PROVINCE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.province') }}
                                </label>

                                <input type="text" class="form-control" id="supplier-province" name="province">

                            </div>


                            {{-- POSTAL --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.postal_code') }}
                                </label>

                                <input type="text" class="form-control" id="supplier-postal-code" name="postal_code">

                            </div>

                        </div>


                        <hr class="my-4">


                        {{-- ================================================= --}}
                        {{-- BANK --}}
                        {{-- ================================================= --}}

                        <h6 class="mb-3">
                            {{ __('ui.master.bank_info') }}
                        </h6>

                        <div class="row">

                            {{-- BANK NAME --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.bank_name') }}
                                </label>

                                <input type="text" class="form-control" id="supplier-bank-name" name="bank_name"
                                    placeholder="BCA / BRI / Mandiri">

                            </div>


                            {{-- ACCOUNT NUMBER --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.account_number') }}
                                </label>

                                <input type="text" class="form-control" id="supplier-bank-account-number"
                                    name="bank_account_number">

                            </div>


                            {{-- ACCOUNT NAME --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.account_name') }}
                                </label>

                                <input type="text" class="form-control" id="supplier-bank-account-name"
                                    name="bank_account_name">

                            </div>

                        </div>


                        <hr class="my-4">


                        {{-- ================================================= --}}
                        {{-- NOTES & STATUS --}}
                        {{-- ================================================= --}}

                        <div class="row">

                            {{-- NOTES --}}
                            <div class="col-md-9 mb-3">

                                <label class="form-label">{{ __('ui.common.notes') }}</label>

                                <textarea class="form-control" id="supplier-notes" name="notes" rows="3" placeholder="{{ __('ui.master.notes_supplier') }}"></textarea>

                            </div>


                            {{-- STATUS --}}
                            <div class="col-md-3 mb-3">

                                <label class="form-label">{{ __('ui.common.status') }}</label>

                                <div class="form-check form-switch mt-2">

                                    <input class="form-check-input" type="checkbox" id="supplier-active"
                                        name="is_active" value="1" checked>

                                    <label class="form-check-label" for="supplier-active">

                                        {{ __('ui.master.status_active') }}

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- FOOTER --}}
                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>

                        <button type="submit" class="btn btn-primary" id="btn-save-supplier">{{ __('ui.common.save') }}</button>

                    </div>

                </form>

            </div>

        </div>

    </div>
@endsection


@push('scripts')
    <script>
        $(function() {

            @if ($errors->has('file'))
                new bootstrap.Modal(document.getElementById('supplierImportModal')).show();
            @endif

            /*
            |--------------------------------------------------------------------------
            | AJAX CSRF
            |--------------------------------------------------------------------------
            */

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });


            /*
            |--------------------------------------------------------------------------
            | URL
            |--------------------------------------------------------------------------
            */

            const createUrl =
                @json(route('super.suppliers.store'));

            const dtUrl =
                @json(route('super.suppliers.dt'));


            /*
            |--------------------------------------------------------------------------
            | DATATABLE
            |--------------------------------------------------------------------------
            */

            const table = $('#suppliers-table').DataTable({

                processing: true,

                serverSide: true,

                ajax: dtUrl,

                columns: [

                    {
                        data: null,

                        name: 'id',

                        orderable: false,

                        searchable: false,

                        render: function(
                            data,
                            type,
                            row,
                            meta
                        ) {

                            return meta.row +
                                meta.settings._iDisplayStart +
                                1;

                        }
                    },


                    {
                        data: 'code',
                        name: 'code'
                    },


                    {
                        data: 'name',
                        name: 'name'
                    },


                    {
                        data: 'supplier_type',
                        name: 'supplier_type',

                        render: function(data) {

                            if (data === 'individual') {

                                return `
                            <span class="badge bg-info">
                                {{ __('ui.master.individual') }}
                            </span>
                        `;

                            }

                            return `
                        <span class="badge bg-primary">
                            {{ __('ui.master.company') }}
                        </span>
                    `;

                        }
                    },


                    {
                        data: 'contact_person',
                        name: 'contact_person',

                        render: function(data) {

                            return data || '-';

                        }
                    },


                    {
                        data: 'phone',
                        name: 'phone',

                        render: function(data) {

                            return data || '-';

                        }
                    },


                    {
                        data: 'payment_term',
                        name: 'payment_term',

                        render: function(data) {

                            return `
                        <span class="badge bg-light text-dark">
                            ${data || '-'}
                        </span>
                    `;

                        }
                    },


                    {
                        data: 'status',
                        name: 'is_active',

                        orderable: false,

                        searchable: false,

                        render: function(data) {

                            if (data === @json(__('ui.master.status_active'))) {

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


                    {
                        data: 'actions',

                        name: 'actions',

                        orderable: false,

                        searchable: false,

                        className: 'text-center'
                    }

                ],


                drawCallback: function() {

                    if (
                        typeof feather !== 'undefined'
                    ) {

                        feather.replace();

                    }

                }

            });


            /*
            |--------------------------------------------------------------------------
            | ADD SUPPLIER
            |--------------------------------------------------------------------------
            */

            $('#btn-add-supplier').on(
                'click',
                function() {

                    const $form =
                        $('#supplier-form');


                    $form[0].reset();


                    $('#supplier-id')
                        .val('');


                    $('#supplier-type')
                        .val('company');


                    $('#supplier-payment-term')
                        .val('COD');


                    $('#supplier-active')
                        .prop('checked', true);


                    $('#supplierModalTitle')
                        .text('{{ __('ui.master.add_supplier') }}');


                    $form
                        .data('mode', 'create')
                        .data('action', createUrl);


                    $('#supplierModal')
                        .modal('show');

                }
            );


            /*
            |--------------------------------------------------------------------------
            | EDIT SUPPLIER
            |--------------------------------------------------------------------------
            */

            $(document).on(
                'click',
                '.btn-edit-role',
                function() {

                    const $button =
                        $(this);


                    const payload =
                        $button.data('payload') || {};


                    const updateUrl =
                        $button.data('update-url');


                    $('#supplier-id')
                        .val(payload.id || '');


                    $('#supplier-code')
                        .val(payload.code || '');


                    $('#supplier-name')
                        .val(payload.name || '');


                    $('#supplier-type')
                        .val(
                            payload.supplier_type ||
                            'company'
                        );


                    $('#supplier-contact-person')
                        .val(
                            payload.contact_person ||
                            ''
                        );


                    $('#supplier-phone')
                        .val(
                            payload.phone ||
                            ''
                        );


                    $('#supplier-email')
                        .val(
                            payload.email ||
                            ''
                        );


                    $('#supplier-website')
                        .val(
                            payload.website ||
                            ''
                        );


                    $('#supplier-tax-number')
                        .val(
                            payload.tax_number ||
                            ''
                        );


                    $('#supplier-address')
                        .val(
                            payload.address ||
                            ''
                        );


                    $('#supplier-city')
                        .val(
                            payload.city ||
                            ''
                        );


                    $('#supplier-province')
                        .val(
                            payload.province ||
                            ''
                        );


                    $('#supplier-postal-code')
                        .val(
                            payload.postal_code ||
                            ''
                        );


                    $('#supplier-payment-term')
                        .val(
                            payload.payment_term ||
                            'COD'
                        );


                    $('#supplier-bank-name')
                        .val(
                            payload.bank_name ||
                            ''
                        );


                    $('#supplier-bank-account-number')
                        .val(
                            payload.bank_account_number ||
                            ''
                        );


                    $('#supplier-bank-account-name')
                        .val(
                            payload.bank_account_name ||
                            ''
                        );


                    $('#supplier-notes')
                        .val(
                            payload.notes ||
                            ''
                        );


                    $('#supplier-active')
                        .prop(
                            'checked',
                            payload.is_active == true ||
                            payload.is_active == 1
                        );


                    $('#supplierModalTitle')
                        .text('{{ __('ui.common.edit') }} {{ __('ui.common.supplier') }}');


                    $('#supplier-form')
                        .data('mode', 'edit')
                        .data('action', updateUrl);


                    $('#supplierModal')
                        .modal('show');

                }
            );


            /*
            |--------------------------------------------------------------------------
            | SAVE SUPPLIER
            |--------------------------------------------------------------------------
            */

            $('#supplier-form').on(
                'submit',
                function(e) {

                    e.preventDefault();


                    const $form =
                        $(this);


                    const mode =
                        $form.data('mode');


                    const action =
                        $form.data('action');


                    const data = {

                        code: $('#supplier-code').val(),

                        name: $('#supplier-name').val(),

                        supplier_type: $('#supplier-type').val(),

                        contact_person: $('#supplier-contact-person').val(),

                        phone: $('#supplier-phone').val(),

                        email: $('#supplier-email').val(),

                        website: $('#supplier-website').val(),

                        tax_number: $('#supplier-tax-number').val(),

                        address: $('#supplier-address').val(),

                        city: $('#supplier-city').val(),

                        province: $('#supplier-province').val(),

                        postal_code: $('#supplier-postal-code').val(),

                        payment_term: $('#supplier-payment-term').val(),

                        bank_name: $('#supplier-bank-name').val(),

                        bank_account_number: $('#supplier-bank-account-number').val(),

                        bank_account_name: $('#supplier-bank-account-name').val(),

                        notes: $('#supplier-notes').val(),

                        is_active: $('#supplier-active').is(':checked') ?
                            1 : 0

                    };


                    if (mode === 'edit') {

                        data._method = 'PUT';

                    }


                    const $button =
                        $('#btn-save-supplier');


                    $button
                        .prop('disabled', true)
                        .text('{{ __('ui.workflow.saving') }}');


                    $.ajax({

                        url: action,

                        type: 'POST',

                        data: data,


                        success: function(response) {

                            $('#supplierModal')
                                .modal('hide');


                            table.ajax.reload(
                                null,
                                false
                            );


                            Swal.fire({

                                icon: 'success',

                                title: '{{ __('ui.master.success') }}',

                                text: response.message ||
                                    '{{ __('ui.master.created', ['type' => __('ui.common.supplier')]) }}',

                                timer: 1500,

                                showConfirmButton: false

                            });

                        },


                        error: function(xhr) {

                            let message =
                                '{{ __('ui.master.failure') }}';


                            if (
                                xhr.responseJSON &&
                                xhr.responseJSON.message
                            ) {

                                message =
                                    xhr.responseJSON.message;

                            }


                            if (
                                xhr.responseJSON &&
                                xhr.responseJSON.errors
                            ) {

                                const errors =
                                    xhr.responseJSON.errors;


                                message =
                                    Object.values(errors)
                                    .flat()
                                    .join('<br>');

                            }


                            Swal.fire({

                                icon: 'error',

                                title: '{{ __('ui.master.failure') }}',

                                html: message

                            });

                        },


                        complete: function() {

                            $button
                                .prop('disabled', false)
                                .text('{{ __('ui.common.save') }}');

                        }

                    });

                }
            );


            /*
            |--------------------------------------------------------------------------
            | DELETE SUPPLIER
            |--------------------------------------------------------------------------
            */

            $(document).on(
                'click',
                '.btn-delete-role',
                function() {

                    const $button =
                        $(this);


                    const url =
                        $button.data('url');


                    const confirmText =
                        $button.data('confirm') ||
                        '{{ __('ui.master.record_delete_confirmation') }}';


                    Swal.fire({

                        icon: 'warning',

                        title: '{{ __('ui.master.confirmation') }}',

                        text: confirmText,

                        showCancelButton: true,

                        confirmButtonText: '{{ __('ui.master.confirm_delete') }}',

                        cancelButtonText: '{{ __('ui.common.cancel') }}',

                        reverseButtons: true

                    }).then(function(result) {

                        if (
                            !result.isConfirmed
                        ) {

                            return;

                        }


                        $.ajax({

                            url: url,

                            type: 'POST',

                            data: {
                                _method: 'DELETE'
                            },


                            success: function(response) {

                                table.ajax.reload(
                                    null,
                                    false
                                );


                                Swal.fire({

                                    icon: 'success',

                                    title: '{{ __('ui.master.success') }}',

                                    text: response.message ||
                                        '{{ __('ui.master.deleted', ['type' => __('ui.common.supplier')]) }}',

                                    timer: 1500,

                                    showConfirmButton: false

                                });

                            },


                            error: function(xhr) {

                                let message =
                                    '{{ __('ui.master.failure') }}';


                                if (
                                    xhr.responseJSON &&
                                    xhr.responseJSON.message
                                ) {

                                    message =
                                        xhr.responseJSON.message;

                                }


                                Swal.fire({

                                    icon: 'error',

                                    title: '{{ __('ui.master.cannot_delete') }}',

                                    text: message

                                });

                            }

                        });

                    });

                }
            );


            /*
            |--------------------------------------------------------------------------
            | FEATHER ICON
            |--------------------------------------------------------------------------
            */

            if (
                typeof feather !== 'undefined'
            ) {

                feather.replace();

            }

        });
    </script>
@endpush
