@extends('layouts.admin')

@section('title', 'Catat Customer Return')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('super.customer-returns.index') }}">Customer Return</a></li>
            <li class="breadcrumb-item active" aria-current="page">Catat Retur</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="page-title mb-1">Catat Customer Return</h4><span class="text-muted">Retur mengacu pada invoice terbit.
                Nilai refund dibayar dari akun kas/bank yang dipilih.</span>
        </div>
        <a href="{{ route('super.customer-returns.index') }}" class="btn btn-outline-secondary btn-sm"><i
                data-feather="arrow-left" class="icon-sm me-1"></i>Kembali</a>
    </div>

    <form id="customerReturnForm">
        @csrf
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Invoice Asal</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#selectInvoiceModal"><i data-feather="search" class="icon-sm me-1"></i>Cari
                    Invoice</button>
            </div>
            <div class="card-body">
                <input type="hidden" name="sales_invoice_id" id="sales_invoice_id">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Nomor Invoice</label><input
                            id="selected_invoice_number" class="form-control" readonly placeholder="Pilih invoice terbit">
                    </div>
                    <div class="col-md-4 mb-3"><label class="form-label">Customer</label><input id="selected_customer"
                            class="form-control" readonly placeholder="Otomatis dari invoice"></div>
                    <div class="col-md-4 mb-3"><label class="form-label" for="cash_bank_account_id">Bayarkan Refund
                            Dari</label><select name="cash_bank_account_id" id="cash_bank_account_id" class="form-select"
                            required>
                            <option value="">Pilih akun kas/bank</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}
                                    ({{ strtoupper($account->type) }})</option>
                            @endforeach
                        </select></div>
                    <div class="col-md-4 mb-3"><label class="form-label" for="returned_at">Tanggal Retur</label><input
                            type="datetime-local" name="returned_at" id="returned_at" class="form-control"
                            value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Barang yang Diretur</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th class="text-end">Terjual</th>
                                <th class="text-end">Sudah Diretur</th>
                                <th class="text-end">Sisa Dapat Diretur</th>
                                <th style="width:180px">Diretur Sekarang</th>
                            </tr>
                        </thead>
                        <tbody id="returnItems">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Pilih invoice untuk memuat daftar
                                    barang.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="row align-items-start">
                    <div class="col-md-6 mb-3"><label class="form-label" for="reason">Alasan Retur</label>
                        <textarea name="reason" id="reason" class="form-control" rows="2" maxlength="2000" required
                            placeholder="Contoh: barang rusak atau tidak sesuai pesanan"></textarea>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label" for="notes">Catatan</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2" maxlength="5000"
                            placeholder="Catatan tambahan"></textarea>
                    </div>
                    <div class="col-md-6 ms-auto">
                        <div class="border rounded p-3 text-end">
                            <div class="text-muted small">Estimasi refund termasuk pajak</div><strong id="refundEstimate"
                                class="fs-5">Rp 0,00</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('super.customer-returns.index') }}"
                class="btn btn-outline-secondary btn-sm">Batal</a><button type="submit" id="submitReturn"
                class="btn btn-primary btn-sm" disabled><i data-feather="corner-down-left" class="icon-sm me-1"></i>Simpan
                dan Bayar Refund</button></div>
    </form>

    <div class="modal fade" id="selectInvoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Sales Invoice</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table id="eligibleInvoicesTable" class="table table-bordered table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nomor Invoice</th>
                                    <th>Customer</th>
                                    <th>Tanggal Invoice</th>
                                    <th>Jumlah Baris</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const invoiceModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('selectInvoiceModal'));
            const safe = value => $('<div>').text(value ?? '').html();
            const money = value => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 2
            }).format(Number(value || 0));
            let selectedItems = [];

            $('#eligibleInvoicesTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: @json(route('super.customer-returns.eligible')),
                columns: [{
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        data: 'number',
                        name: 'number'
                    },
                    {
                        data: 'customer_name',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'invoice_date',
                        name: 'invoice_date',
                        render: data => data ? new Date(data).toLocaleDateString('id-ID') : '—'
                    },
                    {
                        data: 'items_count',
                        searchable: false
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                order: [
                    [3, 'desc']
                ],
                drawCallback: function() {
                    if (window.feather) feather.replace();
                }
            });

            function updateEstimate() {
                let total = 0;
                $('.return-quantity').each(function(index) {
                    const quantity = Number(this.value || 0);
                    const item = selectedItems[index];
                    if (!item || quantity <= 0) return;
                    const discount = item.sold > 0 ? item.discount_amount * quantity / item.sold : 0;
                    const subtotal = Math.max(0, Math.round((quantity * item.unit_price - discount) * 100) /
                        100);
                    const tax = Math.round(subtotal * item.tax_rate) / 100;
                    total += subtotal + tax;
                });
                $('#refundEstimate').text(money(total));
            }

            $(document).on('click', '.btn-select-return-invoice', function() {
                const url = @json(route('super.customer-returns.source', ['salesInvoiceId' => '__ID__'])).replace('__ID__', $(this).data('id'));
                $.get(url).done(function(invoice) {
                    $('#sales_invoice_id').val(invoice.id);
                    $('#selected_invoice_number').val(invoice.number);
                    $('#selected_customer').val(invoice.customer);
                    selectedItems = invoice.items;
                    const rows = invoice.items.map((item, index) => `
                        <tr>
                            <td>${safe(item.code)} — ${safe(item.name)}${item.track_stock ? '' : '<div class="small text-muted">Barang non-stok</div>'}</td>
                            <td class="text-end">${item.sold} ${safe(item.unit)}</td>
                            <td class="text-end">${item.returned} ${safe(item.unit)}</td>
                            <td class="text-end">${item.available} ${safe(item.unit)}</td>
                            <td><input type="hidden" name="items[${index}][sales_invoice_item_id]" value="${item.id}"><input type="number" class="form-control form-control-sm text-end return-quantity" name="items[${index}][quantity]" min="0" max="${item.available}" step="0.0001" value="0"><div class="small text-muted mt-1">Maks. ${item.available}</div></td>
                        </tr>`).join('');
                    $('#returnItems').html(rows ||
                        '<tr><td colspan="5" class="text-center text-muted">Tidak ada barang yang masih dapat diretur.</td></tr>'
                        );
                    $('#submitReturn').prop('disabled', !rows);
                    updateEstimate();
                    invoiceModal.hide();
                }).fail(() => Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Detail invoice tidak dapat dimuat.'
                }));
            });

            $(document).on('input', '.return-quantity', updateEstimate);
            $('#customerReturnForm').on('submit', function(event) {
                event.preventDefault();
                if (!$('.return-quantity').toArray().some(input => Number(input.value || 0) > 0)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Jumlah belum diisi',
                        text: 'Masukkan jumlah retur untuk minimal satu barang.'
                    });
                    return;
                }
                const button = $('#submitReturn').prop('disabled', true);
                $.ajax({
                    url: @json(route('super.customer-returns.store')),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: response => Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message
                    }).then(() => window.location.href = @json(route('super.customer-returns.index'))),
                    error: xhr => {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors ? Object.values(errors).flat().join('\n') : (xhr
                            .responseJSON?.message || 'Customer Return gagal disimpan.');
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: message
                        });
                        button.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
