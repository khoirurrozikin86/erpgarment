@extends('layouts.admin')
@section('title', 'Laporan Hasil Produksi')
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">Laporan Hasil Produksi</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">Periode</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">S.d.</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">Tampilkan Data</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.production-result.export') }}">Export Excel</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> records</label>
                <div class="d-flex align-items-center gap-2"><label for="search">Search:</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Kode atau nama barang"><button class="btn btn-outline-primary" type="submit">Cari</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle production-result-table">
            <thead><tr><th rowspan="2">No</th><th colspan="2">Dokumen</th><th rowspan="2">No Produksi</th><th rowspan="2">Tanggal</th><th rowspan="2">Kode Barang</th><th rowspan="2">Nama Barang</th><th rowspan="2">Satuan</th><th rowspan="2">Dari Sewing</th><th rowspan="2">Hasil Produksi</th><th rowspan="2">Gudang</th></tr><tr><th>No Planning</th><th>PO Buyer</th></tr></thead>
            <tbody>@forelse($items as $item)
                @php($product = $item->productionOrder?->outputProduct)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $item->productionOrder?->number ?? '—' }}</td><td>{{ $item->productionOrder?->buyer_po_number ?: '—' }}</td><td>{{ $item->packing_number ?? '—' }}</td><td>{{ $item->processed_at?->format('d-m-Y') }}</td><td>{{ $product?->code ?? '—' }}</td><td class="item-name">{{ $product?->name ?? '—' }}</td><td>{{ $product?->unit?->symbol ?: $product?->unit?->name }}</td><td class="text-end">{{ rtrim(rtrim(number_format($item->input_quantity, 4, ',', '.'), '0'), ',') }}</td><td class="text-end">{{ rtrim(rtrim(number_format($item->good_quantity, 4, ',', '.'), '0'), ',') }}</td><td>—</td></tr>
            @empty<tr><td colspan="11" class="text-center py-4">Tidak ada hasil produksi yang sesuai pada periode ini.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">Sumber: transaksi packing perusahaan aktif, berdasarkan tanggal proses. Dari Sewing adalah input per transaksi; Hasil Produksi adalah hasil baik packing. Data gudang belum tersedia pada transaksi ini.</p>
    </div>
</div>
<style>.production-result-table{font-size:12px}.production-result-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.production-result-table td{white-space:nowrap;padding:12px}.production-result-table .item-name{min-width:360px;white-space:normal}</style>
@endsection
