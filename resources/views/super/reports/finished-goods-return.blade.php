@extends('layouts.admin')
@section('title', 'Laporan Retur Barang Jadi')
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">Laporan Retur Barang Jadi</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">Periode</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">S.d.</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">Tampilkan Data</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.finished-goods-return.export') }}">Export Excel</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> records</label>
                <div class="d-flex align-items-center gap-2"><label for="search">Search:</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Kode atau nama barang"><button class="btn btn-outline-primary" type="submit">Cari</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle finished-goods-return-table">
            <thead><tr><th>No</th><th>No Reject</th><th>Tanggal</th><th>Kode Barang</th><th>Nama Barang</th><th>Satuan</th><th>Jumlah</th></tr></thead>
            <tbody>@forelse($items as $item)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>—</td><td>{{ $item->created_at?->format('Y-m-d') }}</td><td>{{ $item->product?->code ?? '—' }}</td><td class="item-name">{{ $item->product?->name ?? '—' }}</td><td>{{ $item->product?->unit?->symbol ?: $item->product?->unit?->name }}</td><td class="text-end">{{ number_format($item->quantity, 4, ',', '.') }}</td></tr>
            @empty<tr><td colspan="7" class="text-center py-4">Tidak ada retur barang jadi yang sesuai pada periode ini.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">Sumber: mutasi customer_return untuk barang jadi pada ledger stok gabungan aplikasi. Periode berdasarkan waktu pencatatan mutasi. Dokumen dan nomor retur khusus belum tersedia; No Reject ditampilkan “—”.</p>
    </div>
</div>
<style>.finished-goods-return-table{font-size:12px}.finished-goods-return-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.finished-goods-return-table td{white-space:nowrap;padding:12px}.finished-goods-return-table .item-name{min-width:360px;white-space:normal}</style>
@endsection
