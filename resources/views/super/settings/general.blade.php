@extends('layouts.admin')

@section('title', 'General Setting')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Settings</a></li>
            <li class="breadcrumb-item active" aria-current="page">General Setting</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="page-title mb-1">General Setting</h4>
                <span class="text-muted">Atur preferensi umum yang digunakan dalam sistem ERP.</span>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Pengaturan Sistem</h6>
                </div>

                <form id="generalSettingForm">
                    @csrf
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label" for="decimal_places">Jumlah Angka Desimal</label>
                            <input type="number" class="form-control" id="decimal_places" name="decimal_places"
                                min="0" max="6" value="{{ $setting->decimal_places }}" required>
                            <div class="form-text">Jumlah angka di belakang koma untuk nilai transaksi.</div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="negative_stock" value="1"
                                    id="negative_stock" {{ $setting->negative_stock ? 'checked' : '' }}>
                                <label class="form-check-label" for="negative_stock">Izinkan stok negatif</label>
                            </div>
                            <div class="form-text">Transaksi dapat disimpan meskipun stok barang kurang dari jumlah yang dibutuhkan.</div>
                        </div>

                        <div class="mb-0">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="tax_included" value="1"
                                    id="tax_included" {{ $setting->tax_included ? 'checked' : '' }}>
                                <label class="form-check-label" for="tax_included">Harga sudah termasuk pajak</label>
                            </div>
                            <div class="form-text">Gunakan pengaturan ini jika harga yang dimasukkan sudah termasuk pajak.</div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm" id="btnSaveGeneralSetting">
                            <i data-feather="save" class="icon-sm me-1"></i> Simpan Pengaturan
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
            $('#generalSettingForm').on('submit', function(e) {
                e.preventDefault();

                const button = $('#btnSaveGeneralSetting');
                const originalButton = '<i data-feather="save" class="icon-sm me-1"></i> Simpan Pengaturan';
                button.prop('disabled', true).text('Menyimpan...');

                $.ajax({
                    url: @json(route('super.settings.general.update')),
                    type: 'POST',
                    data: {
                        _token: @json(csrf_token()),
                        _method: 'PUT',
                        decimal_places: $('#decimal_places').val(),
                        negative_stock: $('#negative_stock').is(':checked') ? 1 : 0,
                        tax_included: $('#tax_included').is(':checked') ? 1 : 0,
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message,
                            timer: 1800,
                            showConfirmButton: false,
                        });
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan pengaturan.';
                        Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalButton);
                        if (typeof feather !== 'undefined') feather.replace();
                    }
                });
            });
        });
    </script>
@endpush
