@extends('layouts.admin')
@section('title', 'Buat Sales Quotation')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Sales</a></li><li class="breadcrumb-item"><a href="{{ route('super.sales-quotations.index') }}">Quotation</a></li><li class="breadcrumb-item active">Buat</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Buat Sales Quotation</h4><span class="text-muted">Harga awal mengikuti pricelist aktif customer atau harga jual barang.</span></div><a href="{{ route('super.sales-quotations.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i>Kembali</a></div>
    <form id="salesQuotationForm"><div class="card mb-3"><div class="card-header"><h6 class="mb-0">Informasi Penawaran</h6></div><div class="card-body"><div class="row">
        <div class="col-md-6 mb-3"><label class="form-label" for="customer_id">Customer <span class="text-danger">*</span></label><select id="customer_id" class="form-select" required><option value="">-- Pilih Customer --</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->code }} — {{ $customer->name }}</option>@endforeach</select><small class="text-muted">Pilih customer sebelum menambahkan barang agar harga pricelist yang sesuai digunakan.</small></div>
        <div class="col-md-3 mb-3"><label class="form-label" for="quote_date">Tanggal Quotation</label><input type="date" id="quote_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
        <div class="col-md-3 mb-3"><label class="form-label" for="valid_until">Berlaku Sampai</label><input type="date" id="valid_until" class="form-control" value="{{ now()->addDays(14)->toDateString() }}"></div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Barang Penawaran</h6><button type="button" id="openProductPicker" class="btn btn-outline-primary btn-sm"><i data-feather="search" class="icon-sm me-1"></i>Pilih Barang</button></div>
    <div class="table-responsive"><table class="table table-sm table-bordered align-middle"><thead><tr><th>Barang</th><th style="width:110px">Jumlah</th><th style="width:160px">Harga Satuan</th><th style="width:150px">Diskon (Rp)</th><th class="text-end">Pajak</th><th class="text-end">Total</th><th class="text-center" style="width:60px">Action</th></tr></thead><tbody id="quotationItems"><tr><td colspan="7" class="text-center text-muted py-4">Pilih customer, lalu tambahkan barang.</td></tr></tbody><tfoot><tr><th colspan="5" class="text-end">Subtotal</th><th class="text-end" id="quotationSubtotal">Rp 0</th><th></th></tr><tr><th colspan="5" class="text-end">Pajak</th><th class="text-end" id="quotationTax">Rp 0</th><th></th></tr><tr><th colspan="5" class="text-end">Total</th><th class="text-end fw-bold" id="quotationTotal">Rp 0</th><th></th></tr></tfoot></table></div>
    <div class="mt-3"><label class="form-label" for="quotation_notes">Catatan</label><textarea id="quotation_notes" class="form-control" rows="3" maxlength="5000" placeholder="Catatan atau syarat penawaran"></textarea></div></div><div class="card-footer d-flex justify-content-between"><a href="{{ route('super.sales-quotations.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a><button type="submit" id="submitQuotation" class="btn btn-primary btn-sm" disabled><i data-feather="save" class="icon-sm me-1"></i>Simpan Draft</button></div></div></form>
    <div class="modal fade" id="quotationProductPicker" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Pilih Barang untuk Quotation</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="table-responsive"><table id="quotationProductsTable" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>No</th><th>Kode</th><th>Barang</th><th>Satuan</th><th class="text-end">Harga Penawaran</th><th>Action</th></tr></thead></table></div></div></div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const selected = new Map();
    const picker = bootstrap.Modal.getOrCreateInstance(document.getElementById('quotationProductPicker'));
    const safe = value => $('<div>').text(value ?? '').html();
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const pickerTable = $('#quotationProductsTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: @json(route('super.sales-quotations.products.dt')), data: data => data.customer_id = $('#customer_id').val() },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'code', name: 'code' }, { data: 'name', name: 'name' },
            { data: 'unit_label', orderable: false, searchable: false },
            { data: 'quote_price', orderable: false, searchable: false, className: 'text-end', render: data => money(data) },
            { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[2, 'asc']],
        drawCallback: function() {
            $('#quotationProductsTable .btn-add-quotation-product').each(function() {
                const button = $(this), exists = selected.has(Number(button.data('id')));
                button.prop('disabled', exists).toggleClass('btn-primary', !exists).toggleClass('btn-outline-secondary', exists)
                    .html(exists ? '<i data-feather="check" class="icon-sm me-1"></i>Ditambahkan' : '<i data-feather="plus" class="icon-sm me-1"></i>Pilih');
            });
            if (window.feather) feather.replace();
        }
    });

    function recalculate() {
        let subtotal = 0, taxes = 0;
        $('#quotationItems tr[data-product-id]').each(function() {
            const row = $(this), qty = Number(row.find('.quotation-quantity').val()) || 0;
            const price = Number(row.find('.quotation-price').val()) || 0;
            const discount = Number(row.find('.quotation-discount').val()) || 0;
            const net = Math.max(0, Math.round((qty * price - discount) * 100) / 100);
            const rate = Number(row.data('tax-rate')) || 0, tax = Math.round(net * rate) / 100;
            subtotal += net; taxes += tax;
            row.find('.quotation-tax-cell').text(rate ? `${rate}% · ${money(tax)}` : '—');
            row.find('.quotation-total-cell').text(money(net + tax));
        });
        $('#quotationSubtotal').text(money(subtotal)); $('#quotationTax').text(money(taxes)); $('#quotationTotal').text(money(subtotal + taxes));
        $('#submitQuotation').prop('disabled', selected.size === 0 || !$('#customer_id').val());
        $('#customer_id').prop('disabled', selected.size > 0);
    }

    function renderRows() {
        const body = $('#quotationItems').empty();
        if (!selected.size) body.html('<tr><td colspan="7" class="text-center text-muted py-4">Pilih customer, lalu tambahkan barang.</td></tr>');
        selected.forEach(item => {
            const row = $('<tr>').attr('data-product-id', item.id).attr('data-tax-rate', item.taxable ? item.taxRate : 0);
            row.append($('<td>').html(`<strong>${safe(item.code)}</strong><br>${safe(item.name)} <span class="text-muted">(${safe(item.unit || '—')})</span>`));
            row.append($('<td>').append($('<input>', { type: 'number', min: '0.0001', step: '0.0001', value: '1', class: 'form-control form-control-sm text-end quotation-quantity' })));
            row.append($('<td>').append($('<input>', { type: 'number', min: '0', step: '0.01', value: item.price, class: 'form-control form-control-sm text-end quotation-price' })));
            row.append($('<td>').append($('<input>', { type: 'number', min: '0', step: '0.01', value: '0', class: 'form-control form-control-sm text-end quotation-discount' })));
            row.append($('<td class="text-end quotation-tax-cell">').text('—'));
            row.append($('<td class="text-end quotation-total-cell">').text('—'));
            row.append($('<td class="text-center">').append($('<button type="button" class="btn btn-sm btn-outline-danger btn-remove-quotation-product" title="Hapus">').html('<i data-feather="x" class="icon-sm"></i>')));
            body.append(row);
        });
        recalculate(); if (window.feather) feather.replace();
    }

    $('#openProductPicker').on('click', function() {
        if (!$('#customer_id').val()) { Swal.fire({ icon: 'info', title: 'Pilih customer terlebih dahulu' }); return; }
        pickerTable.ajax.reload(); picker.show();
    });
    $('#customer_id').on('change', () => pickerTable.ajax.reload());
    $(document).on('click', '.btn-add-quotation-product', function() {
        const button = $(this), id = Number(button.data('id'));
        if (selected.has(id)) return;
        selected.set(id, { id, code: button.data('code'), name: button.data('name'), unit: button.data('unit'), price: Number(button.data('price')) || 0, taxable: String(button.data('taxable')) === '1', taxRate: Number(button.data('tax-rate')) || 0 });
        renderRows(); button.prop('disabled', true).removeClass('btn-primary').addClass('btn-outline-secondary').html('<i data-feather="check" class="icon-sm me-1"></i>Ditambahkan');
        if (window.feather) feather.replace();
    });
    $(document).on('click', '.btn-remove-quotation-product', function() { selected.delete(Number($(this).closest('tr').data('product-id'))); renderRows(); pickerTable.ajax.reload(null, false); });
    $(document).on('input', '.quotation-quantity, .quotation-price, .quotation-discount', recalculate);
    $('#salesQuotationForm').on('submit', function(event) {
        event.preventDefault();
        const items = [];
        $('#quotationItems tr[data-product-id]').each(function() {
            const row = $(this);
            items.push({ product_id: Number(row.data('product-id')), quantity: row.find('.quotation-quantity').val(), unit_price: row.find('.quotation-price').val(), discount_amount: row.find('.quotation-discount').val() });
        });
        const button = $('#submitQuotation').prop('disabled', true);
        $.ajax({
            url: @json(route('super.sales-quotations.store')), method: 'POST',
            data: { _token: @json(csrf_token()), customer_id: $('#customer_id').val(), quote_date: $('#quote_date').val(), valid_until: $('#valid_until').val(), notes: $('#quotation_notes').val(), items },
            success: response => Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }).then(() => window.location.href = response.url),
            error: xhr => { const errors = xhr.responseJSON?.errors; Swal.fire({ icon: 'error', title: 'Gagal', text: errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Quotation gagal disimpan.') }); button.prop('disabled', false); }
        });
    });
});
</script>
@endpush
