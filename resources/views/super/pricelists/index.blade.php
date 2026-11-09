@extends('layouts.admin')

@section('title', 'Harga / Pricelist')

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item">Master Data</li>
        <li class="breadcrumb-item active" aria-current="page">Harga / Pricelist</li>
    </ol></nav>
@endsection

@section('content')
    <div class="row">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Harga / Pricelist</h4>
            <p class="text-muted mb-0">Kelola harga jual atau harga beli barang dalam satu daftar.</p>
        </div>
        @can('pricelists.create')
            <button type="button" class="btn btn-primary" id="btn-add-pricelist">
                <i data-feather="plus" class="icon-sm me-1"></i> Tambah Pricelist
            </button>
        @endcan
    </div>

    <div class="card"><div class="card-body"><div class="table-responsive">
        <table id="pricelists-table" class="table table-bordered table-hover align-middle w-100">
            <thead><tr>
                <th width="50">No</th><th>Kode</th><th>Nama Pricelist</th><th>Jenis</th><th>Customer / Supplier</th>
                <th>Periode</th><th>Jumlah Barang</th><th>Status</th><th width="80">Action</th>
            </tr></thead><tbody></tbody>
        </table>
    </div></div></div>
    </div>

    <div class="modal fade" id="priceListModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <form id="priceListForm">
                @csrf
                <div class="modal-header">
                    <div><h5 class="modal-title" id="priceListModalTitle">Tambah Pricelist</h5>
                        <small class="text-muted">Tentukan informasi daftar dan harga setiap barang.</small></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div id="price-list-alert"></div>
                    <input type="hidden" id="price_list_id">
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Kode <span class="text-danger">*</span></label><input class="form-control" name="code" id="price_list_code" maxlength="50" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Nama <span class="text-danger">*</span></label><input class="form-control" name="name" id="price_list_name" maxlength="150" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Jenis Harga <span class="text-danger">*</span></label>
                            <select class="form-select" name="type" id="price_list_type" required><option value="sales">Harga Jual</option><option value="purchase">Harga Beli</option></select>
                        </div>
                        <div class="col-md-6 mb-3" id="customer-field"><label class="form-label">Customer (opsional)</label>
                            <select class="form-select" name="customer_id" id="price_list_customer"><option value="">Umum</option>
                                @foreach ($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 d-none" id="supplier-field"><label class="form-label">Supplier (opsional)</label>
                            <select class="form-select" name="supplier_id" id="price_list_supplier"><option value="">Umum</option>
                                @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3"><label class="form-label">Berlaku Mulai</label><input type="date" class="form-control" name="valid_from" id="price_list_valid_from"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Berlaku Sampai</label><input type="date" class="form-control" name="valid_until" id="price_list_valid_until"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                        <h6 class="fw-bold mb-0">Daftar Harga Barang</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-price-item"><i data-feather="plus" class="me-1"></i>Tambah Barang</button>
                    </div>
                    <div class="table-responsive"><table class="table table-sm table-bordered align-middle">
                        <thead><tr><th style="min-width:260px">Barang</th><th style="width:220px">Harga</th><th style="width:70px">Aksi</th></tr></thead>
                        <tbody id="price-list-items"></tbody>
                    </table></div>
                    <div class="row align-items-center">
                        <div class="col-md-3 mb-3"><input type="hidden" name="is_active" value="0"><div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="price_list_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="price_list_active">Aktif</label>
                        </div></div>
                        <div class="col-md-9 mb-3"><label class="form-label">Catatan</label><input class="form-control" name="notes" id="price_list_notes" maxlength="2000"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-pricelist"><i data-feather="save" class="icon-sm me-1"></i>Simpan</button>
                </div>
            </form>
        </div></div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const modal = new bootstrap.Modal(document.getElementById('priceListModal'));
    let nextItemIndex = 0;
    const products = @json($products->map(fn ($product) => ['id' => $product->id, 'label' => $product->code . ' — ' . $product->name . ($product->is_active ? '' : ' (Nonaktif)')])->values());
    const table = $('#pricelists-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: @json(route('super.pricelists.dt')),
        columns: [
            { data: 'id', name: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { data: 'type_label', name: 'type' },
            { data: 'party_name', name: 'party_name', orderable: false, searchable: false, defaultContent: 'Umum' },
            { data: 'period', name: 'valid_from', orderable: false },
            { data: 'items_count', name: 'items_count', searchable: false },
            { data: 'status', name: 'is_active', searchable: false },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'asc']],
        drawCallback: function () {
            if (window.feather) feather.replace();
        }
    });

    function addItem(item = {}) {
        const itemIndex = nextItemIndex++;
        const row = $('<tr>');
        const select = $('<select>', { class: 'form-select product-select', name: `items[${itemIndex}][product_id]`, required: true })
            .append($('<option>', { value: '', text: '-- Pilih Barang --' }));
        products.forEach(product => select.append($('<option>', { value: product.id, text: product.label })));
        if (item.product_id) select.val(item.product_id);
        row.append($('<td>').append(select));
        row.append($('<td>').append($('<input>', {
            type: 'number', class: 'form-control', name: `items[${itemIndex}][price]`, min: 0, step: '0.01', required: true,
            value: item.price ?? 0
        })));
        const removeButton = $('<button>', { type: 'button', class: 'btn btn-sm btn-outline-danger btn-remove-price-item', title: 'Hapus barang' });
        removeButton.append($('<i>', { 'data-feather': 'x' }));
        row.append($('<td class="text-center">').append(removeButton));
        $('#price-list-items').append(row);
        if (window.feather) feather.replace();
    }

    function resetForm() {
        $('#priceListForm')[0].reset();
        $('#priceListForm').removeData('update-url');
        nextItemIndex = 0;
        $('#price_list_id').val('');
        $('#price_list_type').val('sales');
        $('#price_list_customer').val('');
        $('#price_list_supplier').val('');
        $('#customer-field').removeClass('d-none');
        $('#supplier-field').addClass('d-none');
        $('#price_list_active').prop('checked', true);
        $('#price-list-items').empty();
        $('#price-list-alert').empty();
        addItem();
    }

    $('#btn-add-pricelist').on('click', function () {
        resetForm();
        $('#priceListModalTitle').text('Tambah Pricelist');
        modal.show();
    });

    $('#btn-add-price-item').on('click', () => addItem());
    $(document).on('click', '.btn-remove-price-item', function () {
        $(this).closest('tr').remove();
    });

    $(document).on('click', '.btn-edit-pricelist', function () {
        let data;
        try { data = JSON.parse($(this).attr('data-payload')); } catch (error) { return; }
        resetForm();
        $('#priceListModalTitle').text('Edit Pricelist');
        $('#priceListForm').data('update-url', $(this).data('update-url'));
        $('#price_list_id').val(data.id);
        $('#price_list_code').val(data.code);
        $('#price_list_name').val(data.name);
        $('#price_list_type').val(data.type);
        $('#price_list_type').trigger('change');
        $('#price_list_customer').val(data.customer_id || '');
        $('#price_list_supplier').val(data.supplier_id || '');
        $('#price_list_valid_from').val(data.valid_from || '');
        $('#price_list_valid_until').val(data.valid_until || '');
        $('#price_list_active').prop('checked', Boolean(data.is_active));
        $('#price_list_notes').val(data.notes || '');
        $('#price-list-items').empty();
        nextItemIndex = 0;
        (data.items || []).forEach(item => addItem(item));
        modal.show();
    });

    $('#price_list_type').on('change', function () {
        const purchase = $(this).val() === 'purchase';
        $('#customer-field').toggleClass('d-none', purchase);
        $('#supplier-field').toggleClass('d-none', !purchase);
        if (purchase) $('#price_list_customer').val('');
        else $('#price_list_supplier').val('');
    });

    $('#priceListForm').on('submit', function (event) {
        event.preventDefault();
        const id = $('#price_list_id').val();
        const url = id ? $('#priceListForm').data('update-url') : @json(route('super.pricelists.store'));
        const data = $(this).serializeArray();
        data.push({ name: 'is_active', value: $('#price_list_active').is(':checked') ? 1 : 0 });
        $('#btn-save-pricelist').prop('disabled', true);
        $.ajax({ url, method: id ? 'PUT' : 'POST', data })
            .done(function (response) {
                modal.hide();
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, confirmButtonText: 'OK' });
            })
            .fail(function (xhr) {
                const errors = xhr.responseJSON?.errors || {};
                const message = Object.values(errors).flat().join('<br>') || xhr.responseJSON?.message || 'Pricelist gagal disimpan.';
                $('#price-list-alert').html($('<div class="alert alert-danger">').html(message));
            })
            .always(() => $('#btn-save-pricelist').prop('disabled', false));
    });

    $(document).on('click', '.btn-delete-pricelist', function () {
        const button = $(this);
        Swal.fire({ icon: 'warning', title: 'Konfirmasi', text: button.data('confirm'), showCancelButton: true,
            confirmButtonText: 'Hapus', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'DELETE', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } })
                .done(response => {
                    table.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message });
                })
                .fail(xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Pricelist tidak dapat dihapus.' }));
        });
    });
});
</script>
@endpush
