@extends('layouts.admin')
@section('title', 'Laporan Posisi Barang dalam Proses (work in process/WIP)')
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">Laporan Posisi Barang dalam Proses (work in process/WIP)</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">Periode</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">S.d.</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">Tampilkan Data</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.wip-position.export') }}">Export Excel</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> records</label>
                <div class="d-flex align-items-center gap-2"><label for="search">Search:</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Kode atau nama barang"><button class="btn btn-outline-primary" type="submit">Cari</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle wip-table">
            <thead><tr><th>No</th><th>Kode Barang</th><th>Nama Barang</th><th>Satuan Barang</th><th>Jumlah Barang</th><th>Keterangan</th></tr></thead>
            <tbody>@forelse($items as $item)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $item->code }}/{{ $item->position }}</td><td class="item-name">{{ $item->name }}</td><td>{{ $item->symbol ?: $item->unit_name }}</td><td class="text-end">{{ rtrim(rtrim(number_format($item->quantity, 4, ',', '.'), '0'), ',') }}</td><td>{{ (float) $item->quantity < 0 ? 'Periksa urutan atau koreksi transaksi produksi' : '—' }}</td></tr>
            @empty<tr><td colspan="6" class="text-center py-4">Tidak ada posisi WIP atau aktivitas produksi yang sesuai pada periode ini.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">Jumlah menunjukkan posisi sampai tanggal akhir, termasuk saldo sebelum periode. Baris ditampilkan jika memiliki saldo atau aktivitas selama periode. CUT: hasil cutting belum masuk sewing; SEW: hasil sewing belum masuk packing; PKG: hasil packing belum diterima sebagai barang jadi. Data mengikuti perusahaan aktif.</p>
    </div>
</div>
<style>.wip-table{font-size:12px}.wip-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.wip-table td{white-space:nowrap;padding:12px}.wip-table .item-name{min-width:360px;white-space:normal}</style>
@endsection
