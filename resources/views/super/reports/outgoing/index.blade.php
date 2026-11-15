@extends('layouts.admin')
@section('title', 'Laporan Pengeluaran Barang')
@section('content')
<div class="card outgoing-report">
    <div class="card-header bg-primary text-white">Laporan Pengeluaran Barang</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">Tanggal Pengeluaran</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">S.d.</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">Tampilkan Data</button>
                <button class="btn btn-outline-primary" type="submit" formaction="{{ route('super.reports.outgoing.export') }}">Export Excel</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> records</label>
                <div class="d-flex align-items-center gap-2"><label for="search">Search:</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Barang, penerima, dokumen"><button class="btn btn-outline-primary" type="submit">Cari</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle outgoing-table">
            <thead><tr><th rowspan="2">No</th><th colspan="3">Data dok pabean</th><th colspan="2">Bukti Pengeluaran Barang / dok lain yg sejenis</th><th rowspan="2">Penerima Barang</th><th rowspan="2">Kode HS</th><th rowspan="2">Kode Barang</th><th rowspan="2">Nama Barang</th><th rowspan="2">Satuan Barang</th><th rowspan="2">Jumlah Barang</th><th rowspan="2">Harga Satuan</th><th rowspan="2">Jumlah Harga</th></tr><tr><th>Jenis</th><th>No. Daftar</th><th>Tgl. Daftar</th><th>No</th><th>Tanggal</th></tr></thead>
            <tbody>@forelse($items as $item)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>—</td><td>—</td><td>—</td><td>{{ $item->number }}</td><td>{{ \Carbon\Carbon::parse($item->document_date)->format('d-m-Y') }}</td><td>{{ $item->recipient ?? '—' }}</td><td>—</td><td>@if($item->product_id)<a class="js-trace-product" href="{{ route('super.reports.outgoing.trace', $item->product_id) }}" data-code="{{ $item->item_code }}" data-name="{{ $item->item_name }}" data-unit="{{ $item->unit }}">{{ $item->item_code }}</a>@else {{ $item->item_code ?? '—' }} @endif</td><td class="item-name">{{ $item->item_name }}</td><td>{{ $item->unit }}</td><td class="text-end">{{ number_format($item->quantity, 4, ',', '.') }}</td><td>—</td><td>—</td></tr>
            @empty<tr><td colspan="14" class="text-center py-4">Tidak ada pengeluaran barang pada periode ini.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">Sumber: surat jalan disetujui dan pengeluaran peralatan perusahaan aktif. Periode memakai tanggal pengeluaran. Data BC, HS, dan harga pengeluaran belum tersedia. Penerima peralatan belum tersedia. Penelusuran MO tersedia untuk barang hasil produksi.</p>
    </div>
</div>
<dialog id="trace-dialog" aria-labelledby="trace-title">
    <div class="bg-primary text-white p-3 d-flex justify-content-between"><span id="trace-title">Lacak Pengeluaran Barang</span><button type="button" class="btn-close btn-close-white" id="trace-close" aria-label="Tutup"></button></div>
    <form method="GET" id="trace-form" class="p-4">
        <label for="trace-code" class="form-label">Kode Barang:</label><input id="trace-code" class="form-control mb-3" readonly>
        <label for="trace-name" class="form-label">Nama Barang:</label><input id="trace-name" class="form-control mb-3" readonly>
        <label for="trace-unit" class="form-label">Satuan:</label><input id="trace-unit" class="form-control mb-3" readonly>
        <label for="trace-mo" class="form-label">Pilih MO:</label><input id="trace-search" type="search" class="form-control mb-2" placeholder="Cari nomor MO" aria-label="Cari nomor MO"><select name="mo" id="trace-mo" class="form-select mb-3"></select>
        <p id="trace-status" role="status" class="small text-muted"></p><button id="trace-submit" class="btn btn-primary">Tampilkan Data</button>
    </form>
</dialog>
<style>.outgoing-table{font-size:12px}.outgoing-table th{background:#f8f8f8;vertical-align:middle;text-align:center}.outgoing-table td,.outgoing-table th{white-space:nowrap;padding:12px}.outgoing-table .item-name{min-width:260px;white-space:normal}#trace-dialog{border:1px solid #abc9ff;padding:0;width:760px;max-width:95vw}#trace-dialog::backdrop{background:rgba(0,0,0,.35)}</style>
<script>
(() => {
    const dialog = document.getElementById('trace-dialog'), form = document.getElementById('trace-form'), select = document.getElementById('trace-mo'), submit = document.getElementById('trace-submit'), status = document.getElementById('trace-status');
    let pending;
    document.getElementById('trace-close').addEventListener('click', () => { pending?.abort(); dialog.close(); });
    document.querySelectorAll('.js-trace-product').forEach(link => link.addEventListener('click', async event => {
        event.preventDefault(); pending?.abort(); pending = new AbortController();
        form.action = link.href;
        for (const key of ['code','name','unit']) document.getElementById('trace-' + key).value = link.dataset[key] || '';
        document.getElementById('trace-search').value = ''; select.replaceChildren(); submit.disabled = true; status.textContent = 'Memuat MO…'; dialog.showModal();
        try {
            const response = await fetch(link.href, {headers: {Accept: 'application/json'}, signal: pending.signal});
            if (!response.ok) throw new Error('load');
            const data = await response.json(); select.add(new Option('ALL', ''));
            for (const order of data.orders) select.add(new Option(order.number, order.id));
            status.textContent = data.orders.length ? '' : 'Belum ada MO yang menghasilkan barang ini.'; submit.disabled = false;
        } catch (error) { if (error.name !== 'AbortError') status.textContent = 'Gagal memuat MO. Tutup dan klik kode barang untuk mencoba kembali.'; }
    }));
    document.getElementById('trace-search').addEventListener('input', function () { for (const option of select.options) option.hidden = option.value !== '' && !option.text.toLowerCase().includes(this.value.toLowerCase()); });
})();
</script>
@endsection
