@extends('layouts.admin')

@section('content')
    <div class="page-header">
        <div>
            <h4 class="page-title mb-1">Document Numbering</h4>
            <span class="text-muted">
                Pengaturan nomor dokumen ERP
            </span>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            <div class="card">

                <div class="card-header">
                    <h6 class="mb-0">
                        Nomor Dokumen
                    </h6>
                </div>

                <div class="card-body">

                    {{-- Information --}}
                    <div class="alert alert-info d-flex align-items-start mb-4">
                        <i data-feather="info" class="me-2 mt-1"></i>

                        <div>
                            <strong>Format nomor dokumen</strong>
                            <div class="mt-1">
                                Gunakan placeholder berikut:
                                <code>{PREFIX}</code>,
                                <code>{YYYY}</code>,
                                <code>{YY}</code>,
                                <code>{MM}</code>,
                                <code>{DD}</code>,
                                <code>{NUMBER}</code>.
                            </div>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive">

                        <table class="table table-bordered align-middle mb-0">

                            <thead>
                                <tr>
                                    <th style="min-width: 180px;">
                                        Document
                                    </th>

                                    <th style="min-width: 120px;">
                                        Prefix
                                    </th>

                                    <th style="min-width: 280px;">
                                        Format
                                    </th>

                                    <th style="min-width: 130px;">
                                        Next Number
                                    </th>

                                    <th style="min-width: 100px;">
                                        Digit
                                    </th>

                                    <th style="min-width: 130px;">
                                        Reset
                                    </th>

                                    <th style="min-width: 90px;" class="text-center">
                                        Active
                                    </th>

                                    <th style="min-width: 100px;" class="text-center">
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($numberings as $numbering)
                                    <tr data-id="{{ $numbering->id }}"
                                        data-update-url="{{ route('super.settings.document-numbering.update', ['numbering' => $numbering->id]) }}">

                                        {{-- Document --}}
                                        <td>
                                            <div class="fw-semibold">
                                                {{ ucwords(str_replace('_', ' ', $numbering->document_type)) }}
                                            </div>

                                            <small class="text-muted">
                                                {{ $numbering->document_type }}
                                            </small>
                                        </td>

                                        {{-- Prefix --}}
                                        <td>
                                            <input type="text" class="form-control form-control-sm numbering-input"
                                                data-field="prefix" value="{{ $numbering->prefix }}" maxlength="20"
                                                autocomplete="off">
                                        </td>

                                        {{-- Format --}}
                                        <td>
                                            <input type="text" class="form-control form-control-sm numbering-input"
                                                data-field="format" value="{{ $numbering->format }}" maxlength="100"
                                                autocomplete="off">

                                            <small class="text-muted">
                                                Contoh:
                                                <span class="format-preview">
                                                    {{ str_replace(
                                                        ['{PREFIX}', '{YYYY}', '{YY}', '{MM}', '{DD}', '{NUMBER}'],
                                                        [
                                                            $numbering->prefix,
                                                            now()->format('Y'),
                                                            now()->format('y'),
                                                            now()->format('m'),
                                                            now()->format('d'),
                                                            str_pad($numbering->next_number, $numbering->number_length, '0', STR_PAD_LEFT),
                                                        ],
                                                        $numbering->format,
                                                    ) }}
                                                </span>
                                            </small>
                                        </td>

                                        {{-- Next Number --}}
                                        <td>
                                            <input type="number" class="form-control form-control-sm numbering-input"
                                                data-field="next_number" value="{{ $numbering->next_number }}"
                                                min="1" step="1">
                                        </td>

                                        {{-- Digit --}}
                                        <td>
                                            <input type="number" class="form-control form-control-sm numbering-input"
                                                data-field="number_length" value="{{ $numbering->number_length }}"
                                                min="1" max="10" step="1">
                                        </td>

                                        {{-- Reset --}}
                                        <td>
                                            <select class="form-select form-select-sm numbering-input"
                                                data-field="reset_period">
                                                <option value="never" @selected($numbering->reset_period === 'never')>
                                                    Never
                                                </option>

                                                <option value="yearly" @selected($numbering->reset_period === 'yearly')>
                                                    Yearly
                                                </option>

                                                <option value="monthly" @selected($numbering->reset_period === 'monthly')>
                                                    Monthly
                                                </option>
                                            </select>
                                        </td>

                                        {{-- Active --}}
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input type="checkbox" class="form-check-input numbering-input"
                                                    data-field="is_active" @checked($numbering->is_active)>
                                            </div>
                                        </td>

                                        {{-- Action --}}
                                        <td class="text-center">

                                            <button type="button" class="btn btn-primary btn-sm btn-save-numbering"
                                                title="Simpan perubahan">
                                                <i data-feather="save"></i>
                                                <span class="save-text d-none">
                                                    Simpan
                                                </span>
                                            </button>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i data-feather="file-text" style="width:40px;height:40px;" class="mb-2"></i>

                                            <div>
                                                Belum ada document numbering.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>
    </div>
@endsection


@push('scripts')
    <script>
        $(document).ready(function() {

            /*
            |--------------------------------------------------------------------------
            | Feather Icons
            |--------------------------------------------------------------------------
            */

            if (typeof feather !== 'undefined') {
                feather.replace();
            }


            /*
            |--------------------------------------------------------------------------
            | Helper - Success
            |--------------------------------------------------------------------------
            */

            function showSuccess(message) {

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: message,
                    timer: 1500,
                    showConfirmButton: false
                });

            }


            /*
            |--------------------------------------------------------------------------
            | Helper - Error
            |--------------------------------------------------------------------------
            */

            function showError(message) {

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: message
                });

            }


            /*
            |--------------------------------------------------------------------------
            | Format Preview
            |--------------------------------------------------------------------------
            */

            function updatePreview($row) {

                const prefix = $.trim(
                    $row.find('[data-field="prefix"]').val()
                );

                const format = $.trim(
                    $row.find('[data-field="format"]').val()
                );

                const nextNumber = parseInt(
                    $row.find('[data-field="next_number"]').val()
                ) || 1;

                const numberLength = parseInt(
                    $row.find('[data-field="number_length"]').val()
                ) || 5;

                const formattedNumber = String(nextNumber)
                    .padStart(numberLength, '0');

                const preview = format
                    .replaceAll('{PREFIX}', prefix)
                    .replaceAll('{YYYY}', '{{ now()->format('Y') }}')
                    .replaceAll('{YY}', '{{ now()->format('y') }}')
                    .replaceAll('{MM}', '{{ now()->format('m') }}')
                    .replaceAll('{DD}', '{{ now()->format('d') }}')
                    .replaceAll('{NUMBER}', formattedNumber);

                $row.find('.format-preview').text(preview);
            }


            /*
            |--------------------------------------------------------------------------
            | Update Preview ketika input berubah
            |--------------------------------------------------------------------------
            */

            $(document).on(
                'input change',
                '.numbering-input',
                function() {

                    const $row = $(this).closest('tr');

                    updatePreview($row);
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Save Document Numbering
            |--------------------------------------------------------------------------
            */

            $(document).on(
                'click',
                '.btn-save-numbering',
                function() {

                    const $button = $(this);
                    const $row = $button.closest('tr');

                    const updateUrl = $row.data('update-url');

                    /*
                    |--------------------------------------------------------------------------
                    | Pastikan URL tersedia
                    |--------------------------------------------------------------------------
                    */

                    if (!updateUrl) {

                        showError(
                            'URL update document numbering tidak ditemukan.'
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Ambil data
                    |--------------------------------------------------------------------------
                    */

                    const prefix = $.trim(
                        $row.find('[data-field="prefix"]').val()
                    );

                    const format = $.trim(
                        $row.find('[data-field="format"]').val()
                    );

                    const nextNumber = parseInt(
                        $row.find('[data-field="next_number"]').val()
                    );

                    const numberLength = parseInt(
                        $row.find('[data-field="number_length"]').val()
                    );

                    const resetPeriod = $row
                        .find('[data-field="reset_period"]')
                        .val();

                    const isActive = $row
                        .find('[data-field="is_active"]')
                        .is(':checked') ? 1 : 0;


                    /*
                    |--------------------------------------------------------------------------
                    | Client Validation
                    |--------------------------------------------------------------------------
                    */

                    if (!prefix) {

                        $row.find('[data-field="prefix"]').focus();

                        showError('Prefix wajib diisi.');

                        return;
                    }


                    if (!format) {

                        $row.find('[data-field="format"]').focus();

                        showError('Format nomor wajib diisi.');

                        return;
                    }


                    if (!nextNumber || nextNumber < 1) {

                        $row.find('[data-field="next_number"]').focus();

                        showError(
                            'Next Number minimal adalah 1.'
                        );

                        return;
                    }


                    if (
                        !numberLength ||
                        numberLength < 1 ||
                        numberLength > 10
                    ) {

                        $row.find('[data-field="number_length"]').focus();

                        showError(
                            'Digit harus antara 1 sampai 10.'
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Confirmation
                    |--------------------------------------------------------------------------
                    */

                    Swal.fire({

                        title: 'Simpan perubahan?',

                        html: 'Prefix: <strong>' +
                            $('<div>').text(prefix).html() +
                            '</strong><br>' +
                            'Format: <strong>' +
                            $('<div>').text(format).html() +
                            '</strong>',

                        icon: 'question',

                        showCancelButton: true,

                        confirmButtonText: 'Ya, Simpan',

                        cancelButtonText: 'Batal',

                        reverseButtons: true

                    }).then(function(result) {

                        if (!result.isConfirmed) {
                            return;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Simpan kondisi button
                        |--------------------------------------------------------------------------
                        */

                        const originalHtml = $button.html();

                        $button
                            .prop('disabled', true)
                            .html(
                                '<span class="spinner-border spinner-border-sm" ' +
                                'role="status" aria-hidden="true"></span>'
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | AJAX
                        |--------------------------------------------------------------------------
                        */

                        $.ajax({

                            url: updateUrl,

                            type: 'POST',

                            data: {

                                _token: '{{ csrf_token() }}',

                                _method: 'PUT',

                                prefix: prefix,

                                format: format,

                                next_number: nextNumber,

                                number_length: numberLength,

                                reset_period: resetPeriod,

                                is_active: isActive

                            },

                            dataType: 'json',

                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },


                            /*
                            |--------------------------------------------------------------------------
                            | Success
                            |--------------------------------------------------------------------------
                            */

                            success: function(response) {

                                if (response.success) {

                                    updatePreview($row);

                                    showSuccess(
                                        response.message ||
                                        'Document numbering berhasil diperbarui.'
                                    );

                                } else {

                                    showError(
                                        response.message ||
                                        'Document numbering gagal diperbarui.'
                                    );

                                }

                            },


                            /*
                            |--------------------------------------------------------------------------
                            | Error
                            |--------------------------------------------------------------------------
                            */

                            error: function(xhr) {

                                let message =
                                    'Terjadi kesalahan saat menyimpan data.';


                                /*
                                | Validation 422
                                */

                                if (
                                    xhr.status === 422 &&
                                    xhr.responseJSON
                                ) {

                                    const errors =
                                        xhr.responseJSON.errors;

                                    if (errors) {

                                        const messages = [];

                                        $.each(
                                            errors,
                                            function(field, fieldErrors) {

                                                if (
                                                    Array.isArray(fieldErrors)
                                                ) {

                                                    $.each(
                                                        fieldErrors,
                                                        function(
                                                            index,
                                                            errorMessage
                                                        ) {

                                                            messages.push(
                                                                errorMessage
                                                            );

                                                        }
                                                    );

                                                }

                                            }
                                        );

                                        if (messages.length) {

                                            message =
                                                messages.join('\n');

                                        }

                                    } else if (
                                        xhr.responseJSON.message
                                    ) {

                                        message =
                                            xhr.responseJSON.message;

                                    }

                                }


                                /*
                                | Unauthorized
                                */
                                else if (xhr.status === 401) {

                                    message =
                                        'Session login sudah berakhir. Silakan login kembali.';

                                }


                                /*
                                | Forbidden
                                */
                                else if (xhr.status === 403) {

                                    message =
                                        'Anda tidak memiliki permission untuk mengubah document numbering.';

                                }


                                /*
                                | Not Found
                                */
                                else if (xhr.status === 404) {

                                    message =
                                        'Document numbering tidak ditemukan.';

                                }


                                /*
                                | Server Error
                                */
                                else if (
                                    xhr.responseJSON &&
                                    xhr.responseJSON.message
                                ) {

                                    message =
                                        xhr.responseJSON.message;

                                }


                                console.error(
                                    'Document Numbering Error:',
                                    xhr
                                );

                                showError(message);

                            },


                            /*
                            |--------------------------------------------------------------------------
                            | Complete
                            |--------------------------------------------------------------------------
                            */

                            complete: function() {

                                $button
                                    .prop('disabled', false)
                                    .html(originalHtml);

                                if (
                                    typeof feather !== 'undefined'
                                ) {

                                    feather.replace();

                                }

                            }

                        });

                    });

                }
            );


            /*
            |--------------------------------------------------------------------------
            | Initial Preview
            |--------------------------------------------------------------------------
            */

            $('tbody tr[data-id]').each(function() {

                updatePreview(
                    $(this)
                );

            });

        });
    </script>
@endpush
