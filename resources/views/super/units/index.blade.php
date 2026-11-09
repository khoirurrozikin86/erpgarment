@extends('layouts.admin')


@section('title', 'units')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">

            <li class="breadcrumb-item">
                <a href="#">Master</a>
            </li>

            <li class="breadcrumb-item active" aria-current="page">
                Units
            </li>

        </ol>
    </nav>
@endsection



@section('content')
    <div class="row">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Unit</h4>
                <p class="text-muted mb-0">
                    Kelola satuan barang.
                </p>
            </div>

            <div class="d-flex gap-2">
                @can('units.view')
                    <a href="{{ route('super.units.template') }}" class="btn btn-outline-secondary">
                        <i data-feather="file-text" class="icon-sm me-1"></i> Template
                    </a>
                    <a href="{{ route('super.units.export') }}" class="btn btn-success">
                        <i data-feather="download" class="icon-sm me-1"></i> Export Excel
                    </a>
                @endcan
                @can('units.create')
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#unitImportModal">
                        <i data-feather="upload" class="icon-sm me-1"></i> Import Excel
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-add-unit">
                        <i data-feather="plus" class="icon-sm me-1"></i> Tambah Satuan
                    </button>
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-body">

                <div class="table-responsive">

                    <table id="units-table" class="table table-bordered table-hover align-middle w-100">

                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Simbol</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                                <th width="80">Action</th>
                            </tr>
                        </thead>

                    </table>

                </div>

            </div>
        </div>

    </div>


    @if (session('unit_import_success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: @json(session('unit_import_success')),
                    confirmButtonText: 'OK'
                });
            });
        </script>
    @endif

    {{-- MODAL IMPORT SATUAN --}}
    <div class="modal fade" id="unitImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <form action="{{ route('super.units.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><div>
                    <h5 class="modal-title">Import Satuan</h5>
                    <small class="text-muted">Isi file menggunakan template satuan.</small>
                </div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <div class="modal-body">
                    @error('file')<div class="alert alert-danger">{{ $message }}</div>@enderror
                    <label for="unit-import-file" class="form-label">File Excel atau CSV</label>
                    <input type="file" id="unit-import-file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    <div class="form-text">Format XLSX, XLS, atau CSV. Maksimal 10 MB. Kode satuan harus unik.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i data-feather="upload" class="icon-sm me-1"></i> Import Satuan</button>
                </div>
            </form>
        </div></div>
    </div>

    {{-- MODAL --}}
    <div class="modal fade" id="unitModal" tabindex="-1" aria-hidden="true">

        <div class="modal-dialog modal-lg">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title" id="unitModalTitle">
                        Tambah Satuan
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>

                </div>

                <form id="unit-form">

                    <div class="modal-body">

                        <input type="hidden" id="unit-id">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Kode
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="unit-code" name="code" required>

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Nama
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="unit-name" name="name" required>

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Simbol
                                </label>

                                <input type="text" class="form-control" id="unit-symbol" name="symbol"
                                    placeholder="Contoh: pcs">

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Status
                                </label>

                                <div class="form-check form-switch mt-2">

                                    <input class="form-check-input" type="checkbox" id="unit-active" name="is_active"
                                        value="1" checked>

                                    <label class="form-check-label" for="unit-active">
                                        Active
                                    </label>

                                </div>

                            </div>


                            <div class="col-12 mb-3">

                                <label class="form-label">
                                    Deskripsi
                                </label>

                                <textarea class="form-control" id="unit-description" name="description" rows="3"></textarea>

                            </div>

                        </div>

                    </div>


                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-primary" id="btn-save-unit">
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
                new bootstrap.Modal(document.getElementById('unitImportModal')).show();
            @endif

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

            const createUrl = @json(route('super.units.store'));
            const dtUrl = @json(route('super.units.dt'));


            /*
            |--------------------------------------------------------------------------
            | DATATABLE
            |--------------------------------------------------------------------------
            */

            const table = $('#units-table').DataTable({

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
                        data: 'symbol',
                        name: 'symbol',

                        render: function(data) {
                            return data ?? '-';
                        }
                    },

                    {
                        data: 'description',
                        name: 'description',

                        render: function(data) {
                            return data ?? '-';
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

                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }

                }

            });


            /*
            |--------------------------------------------------------------------------
            | ADD
            |--------------------------------------------------------------------------
            */

            $('#btn-add-unit').on('click', function() {

                const $form = $('#unit-form');

                $form[0].reset();

                $('#unit-id').val('');

                $('#unit-active')
                    .prop('checked', true);

                $('#unitModalTitle')
                    .text('Tambah Satuan');

                $form
                    .data('mode', 'create')
                    .data('action', createUrl);

                $('#unitModal').modal('show');

            });


            /*
            |--------------------------------------------------------------------------
            | EDIT
            |--------------------------------------------------------------------------
            */

            $(document).on(
                'click',
                '.btn-edit-role',
                function() {

                    const $button = $(this);

                    const payload =
                        $button.data('payload') || {};

                    const updateUrl =
                        $button.data('update-url');

                    $('#unit-id')
                        .val(payload.id || '');

                    $('#unit-code')
                        .val(payload.code || '');

                    $('#unit-name')
                        .val(payload.name || '');

                    $('#unit-symbol')
                        .val(payload.symbol || '');

                    $('#unit-description')
                        .val(payload.description || '');

                    $('#unit-active')
                        .prop(
                            'checked',
                            payload.is_active == true ||
                            payload.is_active == 1
                        );

                    $('#unitModalTitle')
                        .text('Edit Satuan');

                    $('#unit-form')
                        .data('mode', 'edit')
                        .data('action', updateUrl);

                    $('#unitModal').modal('show');

                }
            );


            /*
            |--------------------------------------------------------------------------
            | SAVE
            |--------------------------------------------------------------------------
            */

            $('#unit-form').on(
                'submit',
                function(e) {

                    e.preventDefault();

                    const $form = $(this);

                    const mode =
                        $form.data('mode');

                    const action =
                        $form.data('action');

                    const data = {

                        code: $('#unit-code').val(),

                        name: $('#unit-name').val(),

                        symbol: $('#unit-symbol').val(),

                        description: $('#unit-description').val(),

                        is_active: $('#unit-active').is(':checked') ?
                            1 : 0
                    };


                    if (mode === 'edit') {
                        data._method = 'PUT';
                    }


                    const $button =
                        $('#btn-save-unit');

                    $button
                        .prop('disabled', true)
                        .text('Menyimpan...');


                    $.ajax({

                        url: action,

                        type: 'POST',

                        data: data,

                        success: function(response) {

                            $('#unitModal')
                                .modal('hide');

                            table.ajax.reload(
                                null,
                                false
                            );

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message ||
                                    'Data berhasil disimpan.',
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
            | DELETE
            |--------------------------------------------------------------------------
            */

            $(document).on(
                'click',
                '.btn-delete-role',
                function() {

                    const $button = $(this);

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

                        if (!result.isConfirmed) {
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
                                        'Data berhasil dihapus.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });

                            },

                            error: function(xhr) {

                                let message =
                                    'Data gagal dihapus.';

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

            if (typeof feather !== 'undefined') {
                feather.replace();
            }

        });
    </script>
@endpush
