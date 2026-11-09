@extends('layouts.admin')
@section('title', 'Cash & Bank')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Accounting</a></li><li class="breadcrumb-item active">Cash &amp; Bank</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Cash &amp; Bank</h4><span class="text-muted">Saldo akun dan mutasi kas/bank, termasuk penerimaan pembayaran customer.</span></div>@can('cash-bank.create')<div><button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#cashBankAccountModal"><i data-feather="plus" class="icon-sm me-1"></i>Tambah Akun</button> <button type="button" id="createCashBankTransaction" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#cashBankTransactionModal"><i data-feather="repeat" class="icon-sm me-1"></i>Catat Mutasi</button></div>@endcan</div>
    <div class="card mb-3"><div class="card-header"><h6 class="mb-0">Akun Kas &amp; Bank</h6></div><div class="card-body"><div class="table-responsive"><table id="cash-bank-accounts-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>No</th><th>Kode</th><th>Nama Akun</th><th>Jenis</th><th>Informasi</th><th>COA</th><th class="text-end">Saldo</th><th>Status</th></tr></thead></table></div></div></div>
    @include('super.purchasing.date-filter')
    <div class="card"><div class="card-header"><h6 class="mb-0">Mutasi Kas &amp; Bank</h6></div><div class="card-body"><div class="table-responsive"><table id="cash-bank-transactions-table" class="table table-bordered table-hover align-middle w-100"><thead><tr><th>No</th><th>Tanggal</th><th>Akun</th><th>Jenis</th><th>Sumber</th><th>Akun Lawan</th><th>Keterangan</th><th>Referensi</th><th class="text-end">Jumlah</th><th>Dicatat Oleh</th><th>Aksi</th></tr></thead></table></div></div></div>
    <div class="modal fade" id="cashBankAccountModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form id="cashBankAccountForm" class="modal-content"><div class="modal-header"><h5 class="modal-title">Tambah Akun Kas/Bank</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="mb-2"><label class="form-label">Kode Akun</label><input name="code" class="form-control" maxlength="50" required placeholder="Contoh: KAS-001"></div><div class="mb-2"><label class="form-label">Nama Akun</label><input name="name" class="form-control" maxlength="150" required placeholder="Contoh: Kas Utama"></div><div class="mb-2"><label class="form-label">Jenis Akun</label><select name="type" id="cashBankType" class="form-select" required><option value="cash">Kas</option><option value="bank">Bank</option></select></div><div class="mb-2"><label class="form-label">Akun COA</label><select name="chart_of_account_id" id="cashBankCoa" class="form-select" required><option value="">Pilih akun aset...</option>@foreach($cashBankChartAccounts as $chartAccount)<option value="{{ $chartAccount->id }}" data-code="{{ $chartAccount->code }}">{{ $chartAccount->code }} · {{ $chartAccount->name }}</option>@endforeach</select></div><div id="cashBankDetails" class="d-none"><div class="mb-2"><label class="form-label">Nama Bank</label><input name="bank_name" class="form-control" maxlength="100"></div><div class="mb-2"><label class="form-label">Nomor Rekening</label><input name="account_number" class="form-control" maxlength="100"></div><div class="mb-2"><label class="form-label">Nama Pemilik Rekening</label><input name="account_name" class="form-control" maxlength="150"></div></div><div class="mb-2"><label class="form-label">Saldo Awal</label><input name="opening_balance" type="number" min="0" step="0.01" class="form-control" value="0" required></div><div class="small text-muted">Akun kas memakai COA kelompok 111, akun bank memakai kelompok 112. Saldo berjalan dihitung dari saldo awal dan transaksi.</div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary btn-sm">Simpan Akun</button></div></form></div></div>
    <div class="modal fade" id="cashBankTransactionModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form id="cashBankTransactionForm" class="modal-content"><div class="modal-header"><h5 class="modal-title" id="cashBankTransactionTitle">Catat Mutasi Kas/Bank</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><div class="mb-2"><label class="form-label">Akun Kas/Bank</label><select name="cash_bank_account_id" class="form-select" required><option value="">Pilih akun...</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></div><div class="mb-2"><label class="form-label">Jenis Mutasi</label><select name="direction" class="form-select" required><option value="in">Kas Masuk</option><option value="out">Kas Keluar</option></select></div><div class="mb-2"><label class="form-label">Akun Lawan (COA)</label><select name="counter_chart_account_id" class="form-select" required><option value="">Pilih akun lawan...</option>@foreach($chartAccounts as $chartAccount)<option value="{{ $chartAccount->id }}">{{ $chartAccount->code }} · {{ $chartAccount->name }}</option>@endforeach</select><div class="form-text">Kas masuk: akun pendapatan atau sumber dana. Kas keluar: akun beban atau penggunaan dana.</div></div><div class="mb-2"><label class="form-label">Tanggal</label><input name="transaction_date" type="date" class="form-control" value="{{ now()->toDateString() }}" required></div><div class="mb-2"><label class="form-label">Jumlah</label><input name="amount" type="number" min="0.01" step="0.01" class="form-control" required></div><div class="mb-2"><label class="form-label">Keterangan</label><input name="description" maxlength="255" class="form-control" required placeholder="Contoh: Pembelian perlengkapan kantor"></div><div class="mb-2"><label class="form-label">Nomor Referensi</label><input name="reference_number" maxlength="150" class="form-control" placeholder="Opsional"></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button type="submit" id="cashBankTransactionSubmit" class="btn btn-primary btn-sm">Simpan Mutasi</button></div></form></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    const safe = value => $('<div>').text(value ?? '').html();
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
    const accounts = $('#cash-bank-accounts-table').DataTable({
        processing: true, serverSide: true, responsive: true, ajax: @json(route('super.cash-bank.accounts.dt')),
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'code', name: 'code' }, { data: 'name', name: 'name' }, { data: 'type_label', orderable: false, searchable: false },
            { data: 'account_info', orderable: false, searchable: false }, { data: 'chart_account_label', orderable: false, searchable: false }, { data: 'balance', orderable: false, searchable: false, className: 'text-end', render: money },
            { data: 'status_label', orderable: false, searchable: false }
        ], order: [[1, 'asc']], drawCallback: () => window.feather && feather.replace()
    });
    const transactions = $('#cash-bank-transactions-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: { url: @json(route('super.cash-bank.transactions.dt')), data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); } },
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'transaction_date', name: 'transaction_date', render: data => data ? String(data).slice(0,10).split('-').reverse().join('/') : '—' },
            { data: 'account_name', orderable: false, searchable: false }, { data: 'direction_label', orderable: false, searchable: false },
            { data: 'source_number', orderable: false, searchable: false }, { data: 'counter_account_name', orderable: false, searchable: false }, { data: 'description', name: 'description' },
            { data: 'reference_number', name: 'reference_number', defaultContent: '—' },
            { data: 'amount_signed', orderable: false, searchable: false, className: 'text-end', render: (value, type, row) => `<span class="${Number(value) < 0 ? 'text-danger' : 'text-success'}">${money(Math.abs(Number(value)))}</span>` },
            { data: 'creator_name', orderable: false, searchable: false }, { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ], order: [[1, 'desc']], drawCallback: () => window.feather && feather.replace()
    });
    $('#applyDateFilter').on('click', () => transactions.ajax.reload());
    $('#todayDateFilter').on('click', () => { const today = @json(now()->toDateString()); $('#from_date, #to_date').val(today); transactions.ajax.reload(); });
    const filterCashBankCoa = () => {
        const prefix = $('#cashBankType').val() === 'cash' ? '111' : '112';
        $('#cashBankCoa option').each(function() { if (this.value) this.hidden = !String($(this).data('code')).startsWith(prefix); });
        if ($('#cashBankCoa option:selected').prop('hidden')) $('#cashBankCoa').val('');
    };
    $('#cashBankType').on('change', function() { $('#cashBankDetails').toggleClass('d-none', this.value !== 'bank'); filterCashBankCoa(); });
    filterCashBankCoa();
    $('#cashBankAccountForm').on('submit', function(event) {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());
        $.ajax({ url: @json(route('super.cash-bank.accounts.store')), method: 'POST', data: { ...data, _token: @json(csrf_token()) } })
            .done(response => { bootstrap.Modal.getOrCreateInstance(document.getElementById('cashBankAccountModal')).hide(); this.reset(); $('#cashBankDetails').addClass('d-none'); accounts.ajax.reload(); Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); })
            .fail(xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {}).flat()[0] || 'Akun gagal disimpan.' }));
    });
    $('#cashBankTransactionForm').on('submit', function(event) {
        event.preventDefault();
        const form = this;
        const data = Object.fromEntries(new FormData(form).entries());
        const isEdit = Boolean(form.dataset.url);
        $.ajax({ url: form.dataset.url || @json(route('super.cash-bank.transactions.store')), method: isEdit ? 'PUT' : 'POST', data: { ...data, _token: @json(csrf_token()) } })
            .done(response => { bootstrap.Modal.getOrCreateInstance(document.getElementById('cashBankTransactionModal')).hide(); form.reset(); delete form.dataset.url; form.elements.transaction_date.value = @json(now()->toDateString()); $('#cashBankTransactionTitle').text('Catat Mutasi Kas/Bank'); $('#cashBankTransactionSubmit').text('Simpan Mutasi'); accounts.ajax.reload(); transactions.ajax.reload(null, false); Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); })
            .fail(xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {}).flat()[0] || 'Mutasi gagal disimpan.' }));
    });
    $('#createCashBankTransaction').on('click', function() {
        const form = document.getElementById('cashBankTransactionForm'); form.reset(); delete form.dataset.url;
        form.elements.transaction_date.value = @json(now()->toDateString());
        $('#cashBankTransactionTitle').text('Catat Mutasi Kas/Bank'); $('#cashBankTransactionSubmit').text('Simpan Mutasi');
    });
    $(document).on('click', '.btn-edit-cash-transaction', function() {
        const button = $(this); const form = document.getElementById('cashBankTransactionForm');
        form.reset(); form.dataset.url = button.data('url');
        form.elements.cash_bank_account_id.value = button.data('account');
        form.elements.counter_chart_account_id.value = button.data('counter-account') || '';
        form.elements.transaction_date.value = button.data('date'); form.elements.direction.value = button.data('direction');
        form.elements.amount.value = button.data('amount'); form.elements.description.value = button.data('description');
        form.elements.reference_number.value = button.data('reference') || '';
        $('#cashBankTransactionTitle').text('Edit Mutasi Kas/Bank'); $('#cashBankTransactionSubmit').text('Simpan Perubahan');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('cashBankTransactionModal')).show();
    });
    $(document).on('click', '.btn-delete-cash-transaction', function() {
        const button = $(this);
        Swal.fire({ icon: 'warning', title: 'Hapus mutasi ini?', text: 'Saldo akun akan dihitung ulang setelah mutasi dihapus.', showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal' }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({ url: button.data('url'), method: 'DELETE', data: { _token: @json(csrf_token()) } })
                .done(response => { accounts.ajax.reload(); transactions.ajax.reload(null, false); Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }); })
                .fail(xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || 'Mutasi gagal dihapus.' }));
        });
    });
});
</script>
@endpush
