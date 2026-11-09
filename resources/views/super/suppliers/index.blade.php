@extends('layouts.admin')

@section('title', 'suppliers')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">

            <li class="breadcrumb-item">
                <a href="#">Master</a>
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
                <h4 class="mb-1">Supplier</h4>
                <p class="text-muted mb-0">
                    Kelola data supplier / pemasok.
                </p>
            </div>

            <div class="d-flex gap-2">

                @can('suppliers.view')
                    <a href="{{ route('super.suppliers.template') }}" class="btn btn-outline-secondary">
                        <i data-feather="file-text" class="icon-sm me-1"></i>
                        Template
                    </a>
                    <a href="{{ route('super.suppliers.export') }}" class="btn btn-success">

                        <i data-feather="download" class="icon-sm me-1">
                        </i>

                        Export Excel

                    </a>
                @endcan


                @can('suppliers.create')
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#supplierImportModal">
                        <i data-feather="upload" class="icon-sm me-1"></i>
                        Import Excel
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-add-supplier">

                        <i data-feather="plus" class="icon-sm me-1">
                        </i>

                        Tambah Supplier

                    </button>
                @endcan

            </div>

        </div>

        @if (session('supplier_import_success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: @json(session('supplier_import_success')),
                        confirmButtonText: 'OK'
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
                                <th width="50">No</th>
                                <th>Kode</th>
                                <th>Nama Supplier</th>
                                <th>Jenis</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Payment Term</th>
                                <th>Status</th>
                                <th width="80">Action</th>
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
                            <h5 class="modal-title">Import Supplier</h5>
                            <small class="text-muted">Pilih file yang sudah diisi menggunakan template supplier.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        @error('file')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <label for="supplier-import-file" class="form-label">File Excel atau CSV</label>
                        <input type="file" id="supplier-import-file" name="file" class="form-control"
                            accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Format XLSX, XLS, atau CSV. Maksimal 10 MB. Kode supplier harus unik.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="upload" class="icon-sm me-1"></i> Import Supplier
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

                        Tambah Supplier

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
                            Informasi Supplier
                        </h6>

                        <div class="row">

                            {{-- CODE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Kode Supplier
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="supplier-code" name="code"
                                    placeholder="SUP-001" required>

                            </div>


                            {{-- NAME --}}
                            <div class="col-md-5 mb-3">

                                <label class="form-label">
                                    Nama Supplier
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="supplier-name" name="name"
                                    placeholder="Nama perusahaan / supplier" required>

                            </div>


                            {{-- TYPE --}}
                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Jenis Supplier
                                    <span class="text-danger">*</span>
                                </label>

                                <select class="form-select" id="supplier-type" name="supplier_type" required>

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

                            {{-- CONTACT PERSON --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Contact Person
                                </label>

                                <input type="text" class="form-control" id="supplier-contact-person"
                                    name="contact_person" placeholder="Nama PIC">

                            </div>


                            {{-- PHONE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Phone
                                </label>

                                <input type="text" class="form-control" id="supplier-phone" name="phone"
                                    placeholder="08xxxxxxxxxx">

                            </div>


                            {{-- EMAIL --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Email
                                </label>

                                <input type="email" class="form-control" id="supplier-email" name="email"
                                    placeholder="supplier@example.com">

                            </div>

                        </div>


                        <div class="row">

                            {{-- WEBSITE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Website
                                </label>

                                <input type="text" class="form-control" id="supplier-website" name="website"
                                    placeholder="https://example.com">

                            </div>


                            {{-- TAX --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    NPWP / Tax Number
                                </label>

                                <input type="text" class="form-control" id="supplier-tax-number" name="tax_number"
                                    placeholder="Nomor NPWP">

                            </div>


                            {{-- PAYMENT TERM --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Payment Term
                                    <span class="text-danger">*</span>
                                </label>

                                <select class="form-select" id="supplier-payment-term" name="payment_term" required>

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

                            {{-- ADDRESS --}}
                            <div class="col-md-12 mb-3">

                                <label class="form-label">
                                    Alamat
                                </label>

                                <textarea class="form-control" id="supplier-address" name="address" rows="3"
                                    placeholder="Alamat lengkap supplier"></textarea>

                            </div>

                        </div>


                        <div class="row">

                            {{-- CITY --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Kota
                                </label>

                                <input type="text" class="form-control" id="supplier-city" name="city">

                            </div>


                            {{-- PROVINCE --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Provinsi
                                </label>

                                <input type="text" class="form-control" id="supplier-province" name="province">

                            </div>


                            {{-- POSTAL --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Kode Pos
                                </label>

                                <input type="text" class="form-control" id="supplier-postal-code" name="postal_code">

                            </div>

                        </div>


                        <hr class="my-4">


                        {{-- ================================================= --}}
                        {{-- BANK --}}
                        {{-- ================================================= --}}

                        <h6 class="mb-3">
                            Informasi Bank
                        </h6>

                        <div class="row">

                            {{-- BANK NAME --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Nama Bank
                                </label>

                                <input type="text" class="form-control" id="supplier-bank-name" name="bank_name"
                                    placeholder="BCA / BRI / Mandiri">

                            </div>


                            {{-- ACCOUNT NUMBER --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Nomor Rekening
                                </label>

                                <input type="text" class="form-control" id="supplier-bank-account-number"
                                    name="bank_account_number">

                            </div>


                            {{-- ACCOUNT NAME --}}
                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Nama Rekening
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

                                <label class="form-label">
                                    Catatan
                                </label>

                                <textarea class="form-control" id="supplier-notes" name="notes" rows="3" placeholder="Catatan supplier"></textarea>

                            </div>


                            {{-- STATUS --}}
                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Status
                                </label>

                                <div class="form-check form-switch mt-2">

                                    <input class="form-check-input" type="checkbox" id="supplier-active"
                                        name="is_active" value="1" checked>

                                    <label class="form-check-label" for="supplier-active">

                                        Active

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- FOOTER --}}
                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                            Batal

                        </button>

                        <button type="submit" class="btn btn-primary" id="btn-save-supplier">

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
                        .text('Tambah Supplier');


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
                        .text('Edit Supplier');


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
                        .text('Menyimpan...');


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

                                title: 'Berhasil',

                                text: response.message ||
                                    'Supplier berhasil disimpan.',

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
                                        'Supplier berhasil dihapus.',

                                    timer: 1500,

                                    showConfirmButton: false

                                });

                            },


                            error: function(xhr) {

                                let message =
                                    'Supplier gagal dihapus.';


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
