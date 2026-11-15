@extends('layouts.admin')
@section('title', 'Laporan Mutasi Barang yang Ditolak (Reject) dan Sisa Proses Produksi (Scrap/Waste)')
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">Laporan Mutasi Barang yang Ditolak (Reject) dan Sisa Proses Produksi (Scrap/Waste)</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">Periode</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">S.d.</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">Tampilkan Data</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.reject-scrap-mutation.export') }}">Export Excel</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> records</label>
                <div class="d-flex align-items-center gap-2"><label for="search">Search:</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Kode atau nama barang"><button class="btn btn-outline-primary" type="submit">Cari</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle mutation-table">
            <thead><tr><th>No</th><th>Kode Barang</th><th>Nama Barang</th><th>Satuan Barang</th><th>Jumlah Barang</th><th>Saldo Awal</th><th>Jumlah Pemasukan Barang</th><th>Jumlah Pengeluaran Barang</th><th>Penyesuaian (Adjustment)</th><th>Saldo Akhir</th><th>Hasil Pencacahan</th><th>Jumlah Selisih</th><th>Keterangan</th></tr></thead>
            <tbody>@forelse($items as $item)
                @php($values = \App\Domain\Inventory\Queries\RejectScrapMutationReportQuery::values($item))
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $item->code }}</td><td class="item-name">{{ $item->name }}</td><td>{{ $item->unit?->symbol ?: $item->unit?->name }}</td><td class="text-end" title="Definisi Jumlah Barang menunggu konfirmasi">—</td>
                @foreach(['opening', 'incoming', 'outgoing', 'adjustment', 'closing', 'counted', 'difference'] as $key)<td class="text-end">{{ $values[$key] === null ? '—' : number_format($values[$key], 2, ',', '.') }}</td>@endforeach
                <td>{{ $values['notes'] ?? '—' }}</td></tr>
            @empty<tr><td colspan="13" class="text-center py-4">Tidak ada barang reject atau scrap/waste yang sesuai.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">Periode mengikuti waktu pencatatan mutasi stok. Penyesuaian dipisahkan dari pemasukan/pengeluaran. Pencacahan dan selisih mengikuti opname terakhir yang diposting dalam periode. Laporan mengikuti cakupan stok gabungan aplikasi. Hanya stok kategori WASTE/REJECT; angka reject pada proses produksi belum termasuk jika belum dicatat sebagai transaksi stok reject.</p>
    </div>
</div>
<style>.mutation-table{font-size:12px}.mutation-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.mutation-table td{white-space:nowrap;padding:12px}.mutation-table .item-name{min-width:360px;white-space:normal}</style>
@endsection
