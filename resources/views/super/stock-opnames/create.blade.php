@extends('layouts.admin')

@section('title', 'Buat Stock Opname')

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('super.stock-opnames.index') }}">Stock Opname</a></li>
        <li class="breadcrumb-item active" aria-current="page">Buat Opname</li>
    </ol></nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="page-title mb-1">Buat Stock Opname</h4><span class="text-muted">Pilih barang yang akan dihitung. Saldo sistem disimpan sebagai snapshot saat dokumen dibuat.</span></div>
        <a href="{{ route('super.stock-opnames.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i>Kembali</a>
    </div>

    <form id="createStockOpnameForm">
        @csrf
        <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Barang yang Dihitung</h6>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#selectProductsModal"><i data-feather="search" class="icon-sm me-1"></i>Pilih Barang</button>
        </div><div class="card-body">
            <div class="table-responsive"><table class="table table-sm table-bordered align-middle">
                <thead><tr><th>Kode</th><th>Nama Barang</th><th>Satuan</th><th class="text-center" style="width:80px">Action</th></tr></thead>
                <tbody id="selectedProducts"><tr><td colspan="4" class="text-center text-muted">Belum ada barang yang dipilih.</td></tr></tbody>
            </table></div>
            <div class="mb-3"><label class="form-label" for="notes">Catatan</label><textarea id="notes" class="form-control" rows="2" maxlength="5000" placeholder="Contoh: Opname gudang utama September"></textarea></div>
        </div></div>
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('super.stock-opnames.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a><button type="submit" id="submitStockOpname" class="btn btn-primary btn-sm" disabled><i data-feather="check" class="icon-sm me-1"></i>Buat Dokumen Opname</button></div>
    </form>

    <div class="modal fade" id="selectProductsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Pilih Barang Stok</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><div class="table-responsive"><table id="stockOpnameProductsTable" class="table table-bordered table-hover align-middle w-100">
                <thead><tr><th>No</th><th>Kode</th><th>Nama Barang</th><th>Satuan</th><th>Action</th></tr></thead>
            </table></div></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const selected = new Map();
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('selectProductsModal'));
            const safe = value => $('<div>').text(value ?? '').html();

            function renderSelected() {
                if (!selected.size) {
                    $('#selectedProducts').html('<tr><td colspan="4" class="text-center text-muted">Belum ada barang yang dipilih.</td></tr>');
                    $('#submitStockOpname').prop('disabled', true);
                    return;
                }
                const rows = [...selected.values()].map(product => `<tr><td>${safe(product.code)}</td><td>${safe(product.name)}</td><td>${safe(product.unit || '—')}</td><td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-opname-product" data-id="${product.id}" title="Hapus"><i data-feather="x" class="icon-sm"></i></button></td></tr>`).join('');
                $('#selectedProducts').html(rows);
                $('#submitStockOpname').prop('disabled', false);
                if (window.feather) feather.replace();
            }

            $('#stockOpnameProductsTable').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: @json(route('super.stock-opnames.products.dt')),
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'code', name: 'code' },
                    { data: 'name', name: 'name' },
                    { data: 'unit_label', orderable: false, searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[2, 'asc']],
                drawCallback: function() {
                    $('#stockOpnameProductsTable .btn-toggle-opname-product').each(function() {
                        const button = $(this);
                        const isSelected = selected.has(Number(button.data('id')));
                        button.toggleClass('btn-primary', isSelected)
                            .toggleClass('btn-outline-primary', !isSelected)
                            .html(isSelected
                                ? '<i data-feather="check" class="icon-sm me-1"></i>Dipilih'
                                : '<i data-feather="plus" class="icon-sm me-1"></i>Pilih');
                    });
                    if (window.feather) feather.replace();
                }
            });

            $(document).on('click', '.btn-toggle-opname-product', function() {
                const button = $(this);
                const id = Number(button.data('id'));
                if (selected.has(id)) {
                    selected.delete(id);
                    button.removeClass('btn-primary').addClass('btn-outline-primary').html('<i data-feather="plus" class="icon-sm me-1"></i>Pilih');
                } else {
                    selected.set(id, { id, code: button.data('code'), name: button.data('name'), unit: button.data('unit') });
                    button.removeClass('btn-outline-primary').addClass('btn-primary').html('<i data-feather="check" class="icon-sm me-1"></i>Dipilih');
                }
                renderSelected();
                if (window.feather) feather.replace();
            });

            $(document).on('click', '.btn-remove-opname-product', function() {
                selected.delete(Number($(this).data('id')));
                renderSelected();
            });

            $('#createStockOpnameForm').on('submit', function(event) {
                event.preventDefault();
                if (!selected.size) return;
                $('#submitStockOpname').prop('disabled', true);
                $.ajax({
                    url: @json(route('super.stock-opnames.store')),
                    method: 'POST',
                    data: { _token: @json(csrf_token()), product_ids: [...selected.keys()], notes: $('#notes').val() },
                    success: response => Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }).then(() => window.location.href = response.url),
                    error: xhr => {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Stock Opname gagal dibuat.');
                        Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                        $('#submitStockOpname').prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
