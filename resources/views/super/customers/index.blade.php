@extends('layouts.admin')

@section('title', 'customers')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">

            <li class="breadcrumb-item">
                <a href="#">Master</a>
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
                <h4 class="mb-1">Customer</h4>
                <p class="text-muted mb-0">
                    Kelola data customer / pelanggan.
                </p>
            </div>

            <div class="d-flex gap-2">

                @can('customers.view')
                    <a href="{{ route('super.customers.template') }}" class="btn btn-outline-secondary">
                        <i data-feather="file-text" class="icon-sm me-1"></i>
                        Template
                    </a>
                    <a href="{{ route('super.customers.export') }}" class="btn btn-success">

                        <i data-feather="download" class="icon-sm me-1">
                        </i>

                        Export Excel

                    </a>
                @endcan


                @can('customers.create')
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#customerImportModal">
                        <i data-feather="upload" class="icon-sm me-1"></i>
                        Import Excel
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-add-customer">

                        <i data-feather="plus" class="icon-sm me-1">
                        </i>

                        Tambah Customer

                    </button>
                @endcan

            </div>

        </div>

        @if (session('customer_import_success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: @json(session('customer_import_success')),
                        confirmButtonText: 'OK'
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
                                <th width="50">No</th>
                                <th>Kode</th>
                                <th>Nama Customer</th>
                                <th>Jenis</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Payment Term</th>
                                <th>Credit Limit</th>
                                <th>Status</th>
                                <th width="80">Action</th>
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
                            <h5 class="modal-title">Import Customer</h5>
                            <small class="text-muted">Pilih file yang sudah diisi menggunakan template customer.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        @error('file')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <label for="customer-import-file" class="form-label">File Excel atau CSV</label>
                        <input type="file" id="customer-import-file" name="file" class="form-control"
                            accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Format XLSX, XLS, atau CSV. Maksimal 10 MB. Kode customer harus unik.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="upload" class="icon-sm me-1"></i> Import Customer
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

                        Tambah Customer

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
                            Informasi Customer
                        </h6>

                        <div class="row">

                            {{-- CODE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Kode Customer
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="customer-code" name="code"
                                    placeholder="CUS-001" required>

                            </div>


                            {{-- NAME --}}
                            <div class="col-md-5 mb-3">

                                <label class="form-label">
                                    Nama Customer
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="customer-name" name="name"
                                    placeholder="Nama perusahaan / customer" required>

                            </div>


                            {{-- TYPE --}}
                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Jenis Customer
                                    <span class="text-danger">*</span>
                                </label>

                                <select class="form-select" id="customer-type" name="customer_type" required>

                                    <option value="company">
                                        Company
                                    </option>

                                    <option value="individual">
                                        Individual
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="row">

                            {{-- CONTACT --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Contact Person
                                </label>

                                <input type="text" class="form-control" id="customer-contact-person"
                                    name="contact_person" placeholder="Nama PIC">

                            </div>


                            {{-- PHONE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Phone
                                </label>

                                <input type="text" class="form-control" id="customer-phone" name="phone"
                                    placeholder="08xxxxxxxxxx">

                            </div>


                            {{-- EMAIL --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Email
                                </label>

                                <input type="email" class="form-control" id="customer-email" name="email"
                                    placeholder="customer@example.com">

                            </div>

                        </div>


                        <div class="row">

                            {{-- WEBSITE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Website
                                </label>

                                <input type="text" class="form-control" id="customer-website" name="website"
                                    placeholder="https://example.com">

                            </div>


                            {{-- TAX --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    NPWP / Tax Number
                                </label>

                                <input type="text" class="form-control" id="customer-tax-number" name="tax_number"
                                    placeholder="Nomor NPWP">

                            </div>


                            {{-- PAYMENT TERM --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Payment Term
                                    <span class="text-danger">*</span>
                                </label>

                                <select class="form-select" id="customer-payment-term" name="payment_term" required>

                                    <option value="COD">
                                        COD
                                    </option>

                                    <option value="7 Days">
                                        7 Days
                                    </option>

                                    <option value="14 Days">
                                        14 Days
                                    </option>

                                    <option value="30 Days">
                                        30 Days
                                    </option>

                                    <option value="45 Days">
                                        45 Days
                                    </option>

                                    <option value="60 Days">
                                        60 Days
                                    </option>

                                </select>

                            </div>

                        </div>


                        <hr class="my-4">


                        {{-- ================================================= --}}
                        {{-- ALAMAT --}}
                        {{-- ================================================= --}}

                        <h6 class="mb-3">
                            Alamat
                        </h6>

                        <div class="row">

                            <div class="col-md-12 mb-3">

                                <label class="form-label">
                                    Alamat
                                </label>

                                <textarea class="form-control" id="customer-address" name="address" rows="3"
                                    placeholder="Alamat lengkap customer"></textarea>

                            </div>

                        </div>


                        <div class="row">

                            {{-- CITY --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Kota
                                </label>

                                <input type="text" class="form-control" id="customer-city" name="city">

                            </div>


                            {{-- PROVINCE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Provinsi
                                </label>

                                <input type="text" class="form-control" id="customer-province" name="province">

                            </div>


                            {{-- POSTAL --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Kode Pos
                                </label>

                                <input type="text" class="form-control" id="customer-postal-code" name="postal_code">

                            </div>

                        </div>


                        <hr class="my-4">


                        {{-- ================================================= --}}
                        {{-- CREDIT --}}
                        {{-- ================================================= --}}

                        <h6 class="mb-3">
                            Kredit Customer
                        </h6>

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Credit Limit
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        Rp
                                    </span>

                                    <input type="number" class="form-control" id="customer-credit-limit"
                                        name="credit_limit" min="0" step="0.01" value="0">

                                </div>

                                <small class="text-muted">
                                    Batas maksimal piutang customer.
                                </small>

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Status
                                </label>

                                <div class="form-check form-switch mt-2">

                                    <input class="form-check-input" type="checkbox" id="customer-active"
                                        name="is_active" value="1" checked>

                                    <label class="form-check-label" for="customer-active">

                                        Active

                                    </label>

                                </div>

                            </div>

                        </div>


                        <hr class="my-4">


                        {{-- ================================================= --}}
                        {{-- NOTES --}}
                        {{-- ================================================= --}}

                        <h6 class="mb-3">
                            Catatan
                        </h6>

                        <div class="row">

                            <div class="col-md-12 mb-3">

                                <textarea class="form-control" id="customer-notes" name="notes" rows="3" placeholder="Catatan customer"></textarea>

                            </div>

                        </div>

                    </div>


                    {{-- FOOTER --}}
                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                            Batal

                        </button>

                        <button type="submit" class="btn btn-primary" id="btn-save-customer">

                            Simpan

                        </button>

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
                                Individual
                            </span>
                        `;

                            }

                            return `
                        <span class="badge bg-primary">
                            Company
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

                            if (data === 'Active') {

                                return `
                            <span class="badge bg-success">
                                Active
                            </span>
                        `;

                            }

                            return `
                        <span class="badge bg-secondary">
                            Not Active
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
                        .text('Tambah Customer');


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
                        .text('Edit Customer');


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
                        .text('Menyimpan...');


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

                                title: 'Berhasil',

                                text: response.message ||
                                    'Customer berhasil disimpan.',

                                timer: 1500,

                                showConfirmButton: false

                            });

                        },


                        error: function(xhr) {

                            let message =
                                'Terjadi kesalahan.';


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

                                title: 'Gagal',

                                html: message

                            });

                        },


                        complete: function() {

                            $button
                                .prop('disabled', false)
                                .text('Simpan');

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
                        'Apakah Anda yakin ingin menghapus data ini?';


                    Swal.fire({

                        icon: 'warning',

                        title: 'Konfirmasi',

                        text: confirmText,

                        showCancelButton: true,

                        confirmButtonText: 'Ya, Hapus',

                        cancelButtonText: 'Batal',

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

                                    title: 'Berhasil',

                                    text: response.message ||
                                        'Customer berhasil dihapus.',

                                    timer: 1500,

                                    showConfirmButton: false

                                });

                            },


                            error: function(xhr) {

                                let message =
                                    'Customer gagal dihapus.';


                                if (
                                    xhr.responseJSON &&
                                    xhr.responseJSON.message
                                ) {

                                    message =
                                        xhr.responseJSON.message;

                                }


                                Swal.fire({

                                    icon: 'error',

                                    title: 'Tidak dapat menghapus',

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
