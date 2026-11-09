@extends('layouts.admin')

@section('title', __('ui.common.customer'))

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">

            <li class="breadcrumb-item">
                <a href="#">{{ __('ui.common.master') }}</a>
            </li>

            <li class="breadcrumb-item active" aria-current="page">
                Customers
            </li>

        </ol>
    </nav>
@endsection


@section('content')
    <div class="row">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h4 class="mb-1">{{ __('ui.common.customer') }}</h4>
                <p class="text-muted mb-0">
                    {{ __('ui.master.customer_subtitle') }}
                </p>
            </div>

            <div class="d-flex gap-2">

                @can('customers.view')
                    <a href="{{ route('super.customers.template') }}" class="btn btn-outline-secondary">
                        <i data-feather="file-text" class="icon-sm me-1"></i>
                        {{ __('ui.master.template') }}
                    </a>
                    <a href="{{ route('super.customers.export') }}" class="btn btn-success">

                        <i data-feather="download" class="icon-sm me-1">
                        </i>{{ __('ui.common.export_excel') }}</a>
                @endcan


                @can('customers.create')
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#customerImportModal">
                        <i data-feather="upload" class="icon-sm me-1"></i>
                        {{ __('ui.master.import_excel') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-add-customer">

                        <i data-feather="plus" class="icon-sm me-1">
                        </i>

                        {{ __('ui.master.add_customer') }}

                    </button>
                @endcan

            </div>

        </div>

        @if (session('customer_import_success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __('ui.master.success') }}',
                        text: @json(session('customer_import_success')),
                        confirmButtonText: '{{ __('ui.master.ok') }}'
                    });
                });
            </script>
        @endif


        {{-- TABLE --}}
        <div class="card">

            <div class="card-body">

                <div class="table-responsive">

                    <table id="customers-table" class="table table-bordered table-hover align-middle w-100">

                        <thead>
                            <tr>
                                <th width="50">{{ __('ui.common.no') }}</th>
                                <th>{{ __('ui.common.code') }}</th>
                                <th>{{ __('ui.common.customer') }}</th>
                                <th>{{ __('ui.common.type') }}</th>
                                <th>{{ __('ui.common.contact_person') }}</th>
                                <th>{{ __('ui.common.phone') }}</th>
                                <th>{{ __('ui.common.payment_term') }}</th>
                                <th>{{ __('ui.common.credit_limit') }}</th>
                                <th>{{ __('ui.common.status') }}</th>
                                <th width="80">{{ __('ui.common.action') }}</th>
                            </tr>
                        </thead>

                    </table>

                </div>

            </div>

        </div>

    </div>

    {{-- MODAL IMPORT CUSTOMER --}}
    <div class="modal fade" id="customerImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('super.customers.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">{{ __('ui.master.import_customer') }}</h5>
                            <small class="text-muted">{{ __('ui.master.import_pick_help', ['name' => __('ui.master.customer')]) }}</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        @error('file')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <label for="customer-import-file" class="form-label">{{ __('ui.common.file_excel_csv') }}</label>
                        <input type="file" id="customer-import-file" name="file" class="form-control"
                            accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">{{ __('ui.master.import_file_help', ['name' => __('ui.master.customer')]) }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="upload" class="icon-sm me-1"></i> {{ __('ui.master.import_customer') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- MODAL CUSTOMER --}}
    {{-- ========================================================= --}}

    <div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">

        <div class="modal-dialog modal-xl">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title" id="customerModalTitle">

                        {{ __('ui.master.add_customer') }}

                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>

                </div>


                <form id="customer-form">

                    <div class="modal-body">

                        <input type="hidden" id="customer-id">


                        {{-- ================================================= --}}
                        {{-- INFORMASI CUSTOMER --}}
                        {{-- ================================================= --}}

                        <h6 class="mb-3">
                            {{ __('ui.master.customer_info') }}
                        </h6>

                        <div class="row">

                            {{-- CODE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.customer_code') }}
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="customer-code" name="code"
                                    placeholder="CUS-001" required>

                            </div>


                            {{-- NAME --}}
                            <div class="col-md-5 mb-3">

                                <label class="form-label">{{ __('ui.common.customer') }}<span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="customer-name" name="name"
                                    placeholder="{{ __('ui.master.customer_company_placeholder') }}" required>

                            </div>


                            {{-- TYPE --}}
                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.customer_type') }}
                                    <span class="text-danger">*</span>
                                </label>

                                <select class="form-select" id="customer-type" name="customer_type" required>

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

                            {{-- CONTACT --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">{{ __('ui.common.contact_person') }}</label>

                                <input type="text" class="form-control" id="customer-contact-person"
                                    name="contact_person" placeholder="{{ __('ui.master.contact_name_placeholder') }}">

                            </div>


                            {{-- PHONE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">{{ __('ui.common.phone') }}</label>

                                <input type="text" class="form-control" id="customer-phone" name="phone"
                                    placeholder="08xxxxxxxxxx">

                            </div>


                            {{-- EMAIL --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">{{ __('ui.common.email') }}</label>

                                <input type="email" class="form-control" id="customer-email" name="email"
                                    placeholder="customer@example.com">

                            </div>

                        </div>


                        <div class="row">

                            {{-- WEBSITE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.website') }}
                                </label>

                                <input type="text" class="form-control" id="customer-website" name="website"
                                    placeholder="https://example.com">

                            </div>


                            {{-- TAX --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.tax_number') }}
                                </label>

                                <input type="text" class="form-control" id="customer-tax-number" name="tax_number"
                                    placeholder="{{ __('ui.master.tax_number_placeholder') }}">

                            </div>


                            {{-- PAYMENT TERM --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">{{ __('ui.common.payment_term') }}<span class="text-danger">*</span>
                                </label>

                                <select class="form-select" id="customer-payment-term" name="payment_term" required>

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

                            <div class="col-md-12 mb-3">

                                <label class="form-label">{{ __('ui.common.address') }}</label>

                                <textarea class="form-control" id="customer-address" name="address" rows="3"
                                    placeholder="{{ __('ui.master.address_customer') }}"></textarea>

                            </div>

                        </div>


                        <div class="row">

                            {{-- CITY --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">{{ __('ui.common.city') }}</label>

                                <input type="text" class="form-control" id="customer-city" name="city">

                            </div>


                            {{-- PROVINCE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.province') }}
                                </label>

                                <input type="text" class="form-control" id="customer-province" name="province">

                            </div>


                            {{-- POSTAL --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    {{ __('ui.master.postal_code') }}
                                </label>

                                <input type="text" class="form-control" id="customer-postal-code" name="postal_code">

                            </div>

                        </div>


                        <hr class="my-4">


                        {{-- ================================================= --}}
                        {{-- CREDIT --}}
                        {{-- ================================================= --}}

                        <h6 class="mb-3">
                            {{ __('ui.master.customer_credit') }}
                        </h6>

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">{{ __('ui.common.credit_limit') }}</label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        Rp
                                    </span>

                                    <input type="number" class="form-control" id="customer-credit-limit"
                                        name="credit_limit" min="0" step="0.01" value="0">

                                </div>

                                <small class="text-muted">
                                    {{ __('ui.master.credit_limit_help') }}
                                </small>

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">{{ __('ui.common.status') }}</label>

                                <div class="form-check form-switch mt-2">

                                    <input class="form-check-input" type="checkbox" id="customer-active"
                                        name="is_active" value="1" checked>

                                    <label class="form-check-label" for="customer-active">

                                        {{ __('ui.master.status_active') }}

                                    </label>

                                </div>

                            </div>

                        </div>


                        <hr class="my-4">


                        {{-- ================================================= --}}
                        {{-- NOTES --}}
                        {{-- ================================================= --}}

                        <h6 class="mb-3">{{ __('ui.common.notes') }}</h6>

                        <div class="row">

                            <div class="col-md-12 mb-3">

                                <textarea class="form-control" id="customer-notes" name="notes" rows="3" placeholder="{{ __('ui.master.notes_customer') }}"></textarea>

                            </div>

                        </div>

                    </div>


                    {{-- FOOTER --}}
                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>

                        <button type="submit" class="btn btn-primary" id="btn-save-customer">{{ __('ui.common.save') }}</button>

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
                new bootstrap.Modal(document.getElementById('customerImportModal')).show();
            @endif

            /*
            |--------------------------------------------------------------------------
            | CSRF
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
                @json(route('super.customers.store'));

            const dtUrl =
                @json(route('super.customers.dt'));


            /*
            |--------------------------------------------------------------------------
            | DATATABLE
            |--------------------------------------------------------------------------
            */

            const table = $('#customers-table').DataTable({

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
                        data: 'customer_type',
                        name: 'customer_type',

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
                        data: 'credit_limit',
                        name: 'credit_limit',

                        render: function(data) {

                            if (
                                data === null ||
                                data === undefined ||
                                data === ''
                            ) {
                                return 'Rp 0';
                            }

                            return new Intl.NumberFormat(
                                'id-ID', {
                                    style: 'currency',
                                    currency: 'IDR',
                                    maximumFractionDigits: 0
                                }
                            ).format(data);

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
            | ADD CUSTOMER
            |--------------------------------------------------------------------------
            */

            $('#btn-add-customer').on(
                'click',
                function() {

                    const $form =
                        $('#customer-form');


                    $form[0].reset();


                    $('#customer-id')
                        .val('');


                    $('#customer-type')
                        .val('company');


                    $('#customer-payment-term')
                        .val('COD');


                    $('#customer-credit-limit')
                        .val('0');


                    $('#customer-active')
                        .prop('checked', true);


                    $('#customerModalTitle')
                        .text('{{ __('ui.master.add_customer') }}');


                    $form
                        .data('mode', 'create')
                        .data('action', createUrl);


                    $('#customerModal')
                        .modal('show');

                }
            );


            /*
            |--------------------------------------------------------------------------
            | EDIT CUSTOMER
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


                    $('#customer-id')
                        .val(payload.id || '');


                    $('#customer-code')
                        .val(payload.code || '');


                    $('#customer-name')
                        .val(payload.name || '');


                    $('#customer-type')
                        .val(
                            payload.customer_type ||
                            'company'
                        );


                    $('#customer-contact-person')
                        .val(
                            payload.contact_person ||
                            ''
                        );


                    $('#customer-phone')
                        .val(
                            payload.phone ||
                            ''
                        );


                    $('#customer-email')
                        .val(
                            payload.email ||
                            ''
                        );


                    $('#customer-website')
                        .val(
                            payload.website ||
                            ''
                        );


                    $('#customer-tax-number')
                        .val(
                            payload.tax_number ||
                            ''
                        );


                    $('#customer-address')
                        .val(
                            payload.address ||
                            ''
                        );


                    $('#customer-city')
                        .val(
                            payload.city ||
                            ''
                        );


                    $('#customer-province')
                        .val(
                            payload.province ||
                            ''
                        );


                    $('#customer-postal-code')
                        .val(
                            payload.postal_code ||
                            ''
                        );


                    $('#customer-payment-term')
                        .val(
                            payload.payment_term ||
                            'COD'
                        );


                    $('#customer-credit-limit')
                        .val(
                            payload.credit_limit ||
                            0
                        );


                    $('#customer-notes')
                        .val(
                            payload.notes ||
                            ''
                        );


                    $('#customer-active')
                        .prop(
                            'checked',
                            payload.is_active == true ||
                            payload.is_active == 1
                        );


                    $('#customerModalTitle')
                        .text('{{ __('ui.common.edit') }} {{ __('ui.common.customer') }}');


                    $('#customer-form')
                        .data('mode', 'edit')
                        .data('action', updateUrl);


                    $('#customerModal')
                        .modal('show');

                }
            );


            /*
            |--------------------------------------------------------------------------
            | SAVE CUSTOMER
            |--------------------------------------------------------------------------
            */

            $('#customer-form').on(
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

                        code: $('#customer-code').val(),

                        name: $('#customer-name').val(),

                        customer_type: $('#customer-type').val(),

                        contact_person: $('#customer-contact-person').val(),

                        phone: $('#customer-phone').val(),

                        email: $('#customer-email').val(),

                        website: $('#customer-website').val(),

                        tax_number: $('#customer-tax-number').val(),

                        address: $('#customer-address').val(),

                        city: $('#customer-city').val(),

                        province: $('#customer-province').val(),

                        postal_code: $('#customer-postal-code').val(),

                        payment_term: $('#customer-payment-term').val(),

                        credit_limit: $('#customer-credit-limit').val(),

                        notes: $('#customer-notes').val(),

                        is_active: $('#customer-active').is(':checked') ?
                            1 : 0

                    };


                    if (mode === 'edit') {

                        data._method = 'PUT';

                    }


                    const $button =
                        $('#btn-save-customer');


                    $button
                        .prop('disabled', true)
                        .text('{{ __('ui.workflow.saving') }}');


                    $.ajax({

                        url: action,

                        type: 'POST',

                        data: data,


                        success: function(response) {

                            $('#customerModal')
                                .modal('hide');


                            table.ajax.reload(
                                null,
                                false
                            );


                            Swal.fire({

                                icon: 'success',

                                title: '{{ __('ui.master.success') }}',

                                text: response.message ||
                                    '{{ __('ui.master.created', ['type' => __('ui.common.customer')]) }}',

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
            | DELETE CUSTOMER
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
                                        '{{ __('ui.master.deleted', ['type' => __('ui.common.customer')]) }}',

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
            | FEATHER
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
