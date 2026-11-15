@extends('layouts.admin')
@section('title', 'Laporan Pemasukan Barang')
@section('content')
<div class="card incoming-report">
    <div class="card-header bg-primary text-white">Laporan Pemasukan Barang</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">Tanggal Dok</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">S.d.</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">Tampilkan Data</button>
                <button class="btn btn-outline-primary" type="submit" formaction="{{ route('super.reports.incoming.export') }}">Export Excel</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> records</label>
                <div class="d-flex align-items-center gap-2"><label for="search">Search:</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Barang, pengirim, dokumen"><button class="btn btn-outline-primary" type="submit">Cari</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle incoming-table">
            <thead><tr><th rowspan="2">No</th><th colspan="3" class="text-center">Data dok pabean</th><th colspan="2" class="text-center">Bukti Penerimaan Barang / Good Receive Note<br>/ dok lain yg sejenis</th><th rowspan="2">Pengirim Barang</th><th rowspan="2">Kode HS</th><th rowspan="2">Kode Barang</th><th rowspan="2">Nama Barang</th><th rowspan="2">Satuan Barang</th><th rowspan="2">Jumlah Barang</th><th rowspan="2">Harga Satuan</th><th rowspan="2">Jumlah Harga</th><th rowspan="2">Mata Uang</th></tr><tr><th>Jenis</th><th>No. Daftar</th><th>Tgl. Daftar</th><th>No</th><th>Tanggal</th></tr></thead>
            <tbody>@forelse($items as $item)
                @php($receipt = $item->goodsReceipt)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $receipt?->customs_document_code ?? '—' }}</td><td>{{ $receipt?->customs_document_number ?? '—' }}</td><td>{{ $receipt?->customs_document_date?->format('d-m-Y') ?? '—' }}</td><td>{{ $receipt?->number }}</td><td>{{ $receipt?->received_at?->format('d-m-Y') }}</td><td>{{ $receipt?->supplier?->name ?? '—' }}</td><td>—</td><td>@if($item->product)<a href="{{ route('super.reports.incoming.trace', $item->product_id) }}">{{ $item->product->code }}</a>@else — @endif</td><td class="item-name">{{ $item->product?->name ?? '—' }}</td><td>{{ $item->product?->unit?->symbol ?: $item->product?->unit?->name }}</td><td class="text-end">{{ number_format($item->quantity, 4, ',', '.') }}</td><td class="text-end">{{ number_format($item->unit_price, 4, ',', '.') }}</td><td class="text-end">{{ number_format($item->line_total, 4, ',', '.') }}</td><td>{{ $receipt?->currency ?? '—' }}</td></tr>
            @empty<tr><td colspan="15" class="text-center py-4">Tidak ada pemasukan barang pada periode ini.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">Tanggal Dok menggunakan tanggal dokumen BC. Hanya penerimaan disetujui dengan tanggal BC dalam periode yang ditampilkan. Kode HS belum tersedia di master barang.</p>
    </div>
</div>
<style>.incoming-table {font-size:12px}.incoming-table th {background:#f8f8f8;vertical-align:middle;text-align:center}.incoming-table td,.incoming-table th {white-space:nowrap;padding:12px}.incoming-table .item-name {min-width:260px;white-space:normal}.incoming-report .card-header {font-size:16px}</style>
@endsection
