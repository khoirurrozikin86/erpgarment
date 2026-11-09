@extends('layouts.admin')

@section('title', 'Company')

@section('content')
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Company</h4>
                <p class="text-muted mb-0">Kelola data company</p>
            </div>

            @can('company.create')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#companyModal"
                    onclick="openCreateModal()">
                    <i data-feather="plus"></i>
                    Tambah Company
                </button>
            @endcan
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="companiesTable" class="table table-bordered table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th width="80">Logo</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Kota</th>
                                <th>Currency</th>
                                <th>Status</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="companyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="companyModalTitle">Tambah Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="companyForm">
                    @csrf

                    <input type="hidden" id="company_id">

                    <div class="modal-body company-modal-body">
                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="code" class="form-control" required>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Nama Company <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="phone" id="phone" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Website</label>
                                <input type="text" name="website" id="website" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tax Number</label>
                                <input type="text" name="tax_number" id="tax_number" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Logo</label>
                                <input type="file" name="logo" id="logo" class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp">
                                <small class="text-muted">Maksimal 2 MB.</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" id="address" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Kota</label>
                                <input type="text" name="city" id="city" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Provinsi</label>
                                <input type="text" name="province" id="province" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" name="postal_code" id="postal_code" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Currency</label>
                                <input type="text" name="currency" id="currency" class="form-control"
                                    value="IDR" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Timezone</label>
                                <select name="timezone" id="timezone" class="form-select" required>
                                    <option value="Asia/Jakarta">Asia/Jakarta</option>
                                    <option value="Asia/Makassar">Asia/Makassar</option>
                                    <option value="Asia/Jayapura">Asia/Jayapura</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Date Format</label>
                                <input type="text" name="date_format" id="date_format" class="form-control"
                                    value="d/m/Y" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Invoice Header</label>
                                <textarea name="invoice_header" id="invoice_header" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Invoice Footer</label>
                                <textarea name="invoice_footer" id="invoice_footer" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Receipt Header</label>
                                <textarea name="receipt_header" id="receipt_header" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Receipt Footer</label>
                                <textarea name="receipt_footer" id="receipt_footer" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-md-4">
                                <div class="form-check form-switch mt-3">
                                    <input type="checkbox" name="is_active" id="is_active" value="1"
                                        class="form-check-input" checked>
                                    <label class="form-check-label" for="is_active">Company Aktif</label>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveCompany">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


<style>
    #companyModal .modal-dialog {
        max-width: 1100px;
    }

    #companyModal .modal-content {
        max-height: 90vh;
        overflow: hidden;
    }

    #companyModal .company-modal-body {
        overflow-y: auto;
        max-height: calc(90vh - 140px);
    }

    #companyModal .modal-header,
    #companyModal .modal-footer {
        flex-shrink: 0;
    }
</style>

@push('scripts')
    <script>
        $(function() {

            const $form = $('#companyForm');
            const $modal = $('#companyModal');
            const modal = bootstrap.Modal.getOrCreateInstance(
                document.getElementById('companyModal')
            );

            const $btnSave = $('#btnSaveCompany');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            function reloadTable() {
                companyTable.ajax.reload(null, false);
            }

            function showError(xhr) {
                let html = 'Terjadi kesalahan.';

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    html = Object.values(xhr.responseJSON.errors)
                        .flat()
                        .join('<br>');
                } else if (xhr.responseJSON?.message) {
                    html = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    html: html
                });
            }

            function resetForm() {
                $form[0].reset();

                $form
                    .removeData('mode')
                    .removeData('action');

                $('#company_id').val('');
                $('#currency').val('IDR');
                $('#timezone').val('Asia/Jakarta');
                $('#date_format').val('d/m/Y');
                $('#is_active').prop('checked', true);
                $('#logo').val('');

                $('#companyModalTitle').text('Tambah Company');
                $btnSave.text('Simpan');
            }

            window.openCreateModal = function() {
                resetForm();

                $form
                    .data('mode', 'create')
                    .data(
                        'action',
                        "{{ route('super.companies.store') }}"
                    );

                $('#companyModalTitle').text('Tambah Company');
                $btnSave.text('Simpan');

                modal.show();
            };

            const companyTable = $('#companiesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('super.companies.dt') }}",
                    type: 'GET'
                },
                columns: [{
                        data: null,
                        name: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row, meta) {
                            return meta.row +
                                meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'logo',
                        name: 'logo',
                        orderable: false,
                        searchable: false
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
                        data: 'email',
                        name: 'email',
                        defaultContent: '-'
                    },
                    {
                        data: 'phone',
                        name: 'phone',
                        defaultContent: '-'
                    },
                    {
                        data: 'city',
                        name: 'city',
                        defaultContent: '-'
                    },
                    {
                        data: 'currency',
                        name: 'currency'
                    },
                    {
                        data: 'is_active',
                        name: 'is_active',
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [3, 'asc']
                ],
                pageLength: 10,
                responsive: true
            });

            companyTable.on('draw.dt', function() {
                if (window.feather) {
                    feather.replace();
                }
            });

            /*
            |--------------------------------------------------------------------------
            | EDIT
            |--------------------------------------------------------------------------
            */
            $(document).on('click', '.btn-edit-role', function(e) {
                e.preventDefault();

                const $button = $(this);

                let payload = {};

                try {
                    payload = JSON.parse(
                        $button.attr('data-payload') || '{}'
                    );
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Data company tidak dapat dibaca.'
                    });
                    return;
                }

                resetForm();

                $form
                    .data('mode', 'edit')
                    .data(
                        'action',
                        $button.attr('data-update-url')
                    );

                $('#companyModalTitle').text('Edit Company');
                $btnSave.text('Update');

                $('#company_id').val(payload.id ?? '');
                $('#code').val(payload.code ?? '');
                $('#name').val(payload.name ?? '');
                $('#email').val(payload.email ?? '');
                $('#phone').val(payload.phone ?? '');
                $('#website').val(payload.website ?? '');
                $('#tax_number').val(payload.tax_number ?? '');
                $('#address').val(payload.address ?? '');
                $('#city').val(payload.city ?? '');
                $('#province').val(payload.province ?? '');
                $('#postal_code').val(payload.postal_code ?? '');
                $('#currency').val(payload.currency ?? 'IDR');
                $('#timezone').val(payload.timezone ?? 'Asia/Jakarta');
                $('#date_format').val(payload.date_format ?? 'd/m/Y');
                $('#invoice_header').val(payload.invoice_header ?? '');
                $('#invoice_footer').val(payload.invoice_footer ?? '');
                $('#receipt_header').val(payload.receipt_header ?? '');
                $('#receipt_footer').val(payload.receipt_footer ?? '');
                $('#is_active').prop(
                    'checked',
                    Boolean(payload.is_active)
                );

                modal.show();
            });

            /*
            |--------------------------------------------------------------------------
            | CREATE / UPDATE
            |--------------------------------------------------------------------------
            */
            $form.on('submit', function(e) {
                e.preventDefault();

                const mode = $form.data('mode') || 'create';
                const action =
                    $form.data('action') ||
                    "{{ route('super.companies.store') }}";

                const formData = new FormData(this);

                if (mode === 'edit') {
                    formData.append('_method', 'PUT');
                }

                formData.set(
                    'is_active',
                    $('#is_active').is(':checked') ? '1' : '0'
                );

                $btnSave
                    .prop('disabled', true)
                    .text(
                        mode === 'edit' ?
                        'Mengupdate...' :
                        'Menyimpan...'
                    );

                $.ajax({
                        url: action,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false
                    })
                    .done(function(response) {

                        modal.hide();

                        reloadTable();

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message ||
                                (
                                    mode === 'edit' ?
                                    'Company berhasil diupdate.' :
                                    'Company berhasil dibuat.'
                                ),
                            timer: 1800,
                            showConfirmButton: false
                        });

                        resetForm();
                    })
                    .fail(function(xhr) {
                        showError(xhr);
                    })
                    .always(function() {
                        $btnSave
                            .prop('disabled', false)
                            .text(
                                mode === 'edit' ?
                                'Update' :
                                'Simpan'
                            );
                    });
            });

            /*
            |--------------------------------------------------------------------------
            | DELETE
            |--------------------------------------------------------------------------
            */
            $(document).on('click', '.btn-delete-role', function(e) {
                e.preventDefault();

                const $button = $(this);
                const url = $button.attr('data-url');
                const message =
                    $button.attr('data-confirm') ||
                    'Company akan dihapus.';

                Swal.fire({
                    icon: 'warning',
                    title: 'Hapus Company?',
                    text: message,
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then(function(result) {

                    if (!result.isConfirmed) {
                        return;
                    }

                    $button
                        .prop('disabled', true)
                        .addClass('disabled');

                    $.ajax({
                            url: url,
                            method: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                _method: 'DELETE'
                            }
                        })
                        .done(function(response) {

                            reloadTable();

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message ||
                                    'Company berhasil dihapus.',
                                timer: 1800,
                                showConfirmButton: false
                            });
                        })
                        .fail(function(xhr) {
                            showError(xhr);
                        })
                        .always(function() {
                            $button
                                .prop('disabled', false)
                                .removeClass('disabled');
                        });
                });
            });

            /*
            |--------------------------------------------------------------------------
            | RESET AFTER MODAL CLOSED
            |--------------------------------------------------------------------------
            */
            $modal.on('hidden.bs.modal', function() {
                resetForm();
            });
        });
    </script>
@endpush
