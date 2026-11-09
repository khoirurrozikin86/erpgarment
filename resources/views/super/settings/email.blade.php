@extends('layouts.admin')

@section('title', 'Email Setting')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Settings</a></li>
            <li class="breadcrumb-item active" aria-current="page">Email Setting</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="page-title mb-1">Email Setting</h4>
                <span class="text-muted">Atur konfigurasi email untuk pengiriman notifikasi sistem ERP.</span>
            </div>
        </div>

        <div class="col-12 col-xxl-8 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">Konfigurasi SMTP</h6>
                </div>

                <form id="emailSettingForm">
                    @csrf
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="mail_mailer">Mailer</label>
                            <select id="mail_mailer" name="mail_mailer" class="form-select">
                                <option value="smtp" {{ $setting->mail_mailer === 'smtp' ? 'selected' : '' }}>SMTP</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="mail_host">SMTP Host</label>
                            <input type="text" id="mail_host" name="mail_host" class="form-control"
                                value="{{ $setting->mail_host }}" placeholder="smtp.example.com">
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="mail_port">Port</label>
                                <input type="number" id="mail_port" name="mail_port" class="form-control"
                                    value="{{ $setting->mail_port }}">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label" for="mail_encryption">Enkripsi</label>
                                <select id="mail_encryption" name="mail_encryption" class="form-select">
                                    <option value="" {{ $setting->mail_encryption === null || $setting->mail_encryption === '' ? 'selected' : '' }}>Tanpa enkripsi</option>
                                    <option value="tls" {{ $setting->mail_encryption === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ $setting->mail_encryption === 'ssl' ? 'selected' : '' }}>SSL</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="mail_username">Username SMTP</label>
                            <input type="text" id="mail_username" name="mail_username" class="form-control"
                                value="{{ $setting->mail_username }}" autocomplete="username">
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="mail_password">Password SMTP</label>
                            <input type="password" id="mail_password" name="mail_password" class="form-control"
                                placeholder="Kosongkan jika tidak ingin mengubah" autocomplete="new-password">
                            <div class="form-text">Password yang tersimpan tidak ditampilkan kembali.</div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Informasi Pengirim</h6>
                        <div class="mb-3">
                            <label class="form-label" for="from_name">Nama Pengirim</label>
                            <input type="text" id="from_name" name="from_name" class="form-control"
                                value="{{ $setting->from_name }}" placeholder="Nama Perusahaan">
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="from_email">Email Pengirim</label>
                            <input type="email" id="from_email" name="from_email" class="form-control"
                                value="{{ $setting->from_email }}" placeholder="noreply@example.com">
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                value="1" {{ $setting->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktifkan layanan email</label>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm" id="btnSaveEmail">
                            <i data-feather="save" class="icon-sm me-1"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-xxl-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">Uji Pengiriman Email</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">Kirim email percobaan untuk memastikan konfigurasi SMTP dapat digunakan.</p>
                    <label for="test_email" class="form-label">Email Tujuan</label>
                    <input type="email" id="test_email" class="form-control mb-3" placeholder="email@example.com">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnTestEmail">
                        <i data-feather="send" class="icon-sm me-1"></i> Kirim Email Percobaan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const saveButtonLabel = '<i data-feather="save" class="icon-sm me-1"></i> Simpan Pengaturan';
            const testButtonLabel = '<i data-feather="send" class="icon-sm me-1"></i> Kirim Email Percobaan';

            $('#emailSettingForm').on('submit', function(e) {
                e.preventDefault();

                const button = $('#btnSaveEmail');
                const data = $(this).serializeArray();
                data.push({ name: '_method', value: 'PUT' });

                if (!$('#is_active').is(':checked')) {
                    data.push({ name: 'is_active', value: '0' });
                }

                button.prop('disabled', true).text('Menyimpan...');

                $.ajax({
                    url: @json(route('super.settings.email.update')),
                    method: 'POST',
                    data: data,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message,
                            timer: 1800,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan pengaturan.'
                        });
                    },
                    complete: function() {
                        button.prop('disabled', false).html(saveButtonLabel);
                        if (typeof feather !== 'undefined') feather.replace();
                    }
                });
            });

            $('#btnTestEmail').on('click', function() {
                const email = $('#test_email').val();
                if (!email) {
                    Swal.fire({ icon: 'warning', title: 'Email belum diisi', text: 'Masukkan alamat email tujuan.' });
                    return;
                }

                const button = $(this);
                button.prop('disabled', true).text('Mengirim...');

                $.ajax({
                    url: @json(route('super.settings.email.test')),
                    method: 'POST',
                    data: { _token: @json(csrf_token()), email: email },
                    success: function(response) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Email gagal dikirim',
                            text: xhr.responseJSON?.message || 'Periksa kembali konfigurasi SMTP.'
                        });
                    },
                    complete: function() {
                        button.prop('disabled', false).html(testButtonLabel);
                        if (typeof feather !== 'undefined') feather.replace();
                    }
                });
            });
        });
    </script>
@endpush
