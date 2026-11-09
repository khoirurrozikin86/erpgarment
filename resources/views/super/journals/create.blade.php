@extends('layouts.admin')
@section('title', 'Buat Journal')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Accounting</a></li><li class="breadcrumb-item"><a href="{{ route('super.journals.index') }}">Journal</a></li><li class="breadcrumb-item active">Buat</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Buat Journal</h4><span class="text-muted">Total debit dan kredit harus seimbang untuk menyimpan draft.</span></div><a href="{{ route('super.journals.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i>Kembali</a></div>
    <form id="journalForm">
        @csrf
        <div class="card mb-3"><div class="card-body"><div class="row g-3"><div class="col-md-3"><label class="form-label">Tanggal Jurnal</label><input type="date" name="journal_date" class="form-control" value="{{ now()->toDateString() }}" required></div><div class="col-md-4"><label class="form-label">Nomor Referensi</label><input name="reference_number" class="form-control" maxlength="150" placeholder="Opsional"></div><div class="col-md-5"><label class="form-label">Keterangan</label><input name="description" class="form-control" maxlength="255" required placeholder="Keterangan transaksi jurnal"></div></div></div></div>
        <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h6 class="mb-0">Baris Jurnal</h6><button type="button" id="addJournalLine" class="btn btn-outline-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>Tambah Baris</button></div><div class="card-body"><div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th style="min-width:240px">Akun</th><th style="min-width:220px">Keterangan Baris</th><th style="min-width:150px" class="text-end">Debit</th><th style="min-width:150px" class="text-end">Kredit</th><th style="width:60px"></th></tr></thead><tbody id="journalLines"></tbody><tfoot><tr><th colspan="2" class="text-end">Total</th><th class="text-end" id="journalDebitTotal">Rp 0</th><th class="text-end" id="journalCreditTotal">Rp 0</th><th></th></tr><tr><th colspan="2" class="text-end">Selisih</th><th colspan="2" class="text-end" id="journalDifference">Rp 0</th><th></th></tr></tfoot></table></div><div class="d-flex justify-content-end gap-2"><a href="{{ route('super.journals.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a><button type="submit" id="saveJournal" class="btn btn-primary btn-sm"><i data-feather="save" class="icon-sm me-1"></i>Simpan Draft</button></div></div></div>
    </form>
    <template id="journalLineTemplate"><tr><td><select class="form-select form-select-sm journal-account" required><option value="">Pilih akun...</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></td><td><input class="form-control form-control-sm journal-line-description" maxlength="255" placeholder="Opsional"></td><td><input type="number" min="0" step="0.01" class="form-control form-control-sm text-end journal-debit" value="0" required></td><td><input type="number" min="0" step="0.01" class="form-control form-control-sm text-end journal-credit" value="0" required></td><td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-journal-line" title="Hapus baris"><i data-feather="x"></i></button></td></tr></template>
@endsection
@push('scripts')
<script>
$(function() {
    const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(value || 0);
    let lineIndex = 0;
    const addLine = () => {
        const fragment = document.getElementById('journalLineTemplate').content.cloneNode(true);
        const row = fragment.querySelector('tr');
        row.dataset.index = lineIndex++;
        $('#journalLines').append(row);
        if (window.feather) feather.replace();
    };
    const updateTotals = () => {
        let debit = 0, credit = 0;
        $('#journalLines tr').each(function() { debit += Number($(this).find('.journal-debit').val()) || 0; credit += Number($(this).find('.journal-credit').val()) || 0; });
        debit = Math.round(debit * 100) / 100; credit = Math.round(credit * 100) / 100;
        $('#journalDebitTotal').text(money(debit)); $('#journalCreditTotal').text(money(credit));
        $('#journalDifference').text(money(Math.abs(debit - credit))).toggleClass('text-danger', debit !== credit).toggleClass('text-success', debit === credit);
        $('#saveJournal').prop('disabled', debit <= 0 || debit !== credit);
    };
    const addInitialLine = () => { addLine(); addLine(); };
    addInitialLine(); updateTotals();
    $('#addJournalLine').on('click', addLine);
    $('#journalLines').on('click', '.remove-journal-line', function() { if ($('#journalLines tr').length > 2) $(this).closest('tr').remove(); updateTotals(); });
    $('#journalLines').on('input', '.journal-debit', function() { if (Number(this.value) > 0) $(this).closest('tr').find('.journal-credit').val(0); updateTotals(); });
    $('#journalLines').on('input', '.journal-credit', function() { if (Number(this.value) > 0) $(this).closest('tr').find('.journal-debit').val(0); updateTotals(); });
    $('#journalForm').on('submit', function(event) {
        event.preventDefault();
        let debit = 0, credit = 0; const lines = [];
        $('#journalLines tr').each(function(index) {
            const row = $(this); const d = Number(row.find('.journal-debit').val()) || 0; const c = Number(row.find('.journal-credit').val()) || 0;
            debit += d; credit += c;
            lines.push({ chart_of_account_id: row.find('.journal-account').val(), description: row.find('.journal-line-description').val(), debit: d.toFixed(2), credit: c.toFixed(2) });
        });
        if (Math.round(debit * 100) !== Math.round(credit * 100) || debit <= 0) { Swal.fire({ icon: 'warning', title: 'Jurnal belum seimbang', text: 'Pastikan total debit dan kredit sama dan lebih dari nol.' }); return; }
        const payload = { journal_date: this.elements.journal_date.value, reference_number: this.elements.reference_number.value, description: this.elements.description.value, lines, _token: @json(csrf_token()) };
        const button = $('#saveJournal').prop('disabled', true);
        $.ajax({ url: @json(route('super.journals.store')), method: 'POST', data: payload })
            .done(response => Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message }).then(() => window.location.href = response.url))
            .fail(xhr => Swal.fire({ icon: 'error', title: 'Gagal', text: xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {}).flat()[0] || 'Jurnal gagal disimpan.' }))
            .always(() => button.prop('disabled', false));
    });
});
</script>
@endpush
