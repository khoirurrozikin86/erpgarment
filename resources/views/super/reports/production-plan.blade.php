@extends('layouts.admin')
@section('title', 'Laporan Rencana Produksi')
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">Laporan Rencana Produksi</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">Periode</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">S.d.</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">Tampilkan Data</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.production-plan.export') }}">Export Excel</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> records</label>
                <div class="d-flex align-items-center gap-2"><label for="search">Search:</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Kode atau nama barang"><button class="btn btn-outline-primary" type="submit">Cari</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle production-plan-table">
            <thead><tr><th rowspan="2">No</th><th colspan="2">Dokumen Rencana Produksi</th><th rowspan="2">Kode Barang</th><th rowspan="2">Nama Barang</th><th rowspan="2">Jumlah Order</th><th rowspan="2">Jumlah Reject</th><th rowspan="2">Jumlah Prod</th><th rowspan="2">Jumlah Export</th></tr><tr><th>No</th><th>Tanggal</th></tr></thead>
            <tbody>@forelse($items as $item)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $item->number }}</td><td>{{ $item->po_date?->format('d-m-Y') ?? '—' }}</td><td>{{ $item->outputProduct?->code ?? '—' }}</td><td class="item-name">{{ $item->outputProduct?->name ?? '—' }}</td><td class="text-end">{{ number_format($item->planned_quantity, 4, ',', '.') }}</td><td class="text-end">{{ rtrim(rtrim(number_format($item->report_rejected ?? 0, 4, ',', '.'), '0'), ',') }}</td><td class="text-end">{{ rtrim(rtrim(number_format($item->report_produced ?? 0, 4, ',', '.'), '0'), ',') }}</td><td class="text-end">—</td></tr>
            @empty<tr><td colspan="9" class="text-center py-4">Tidak ada rencana produksi yang sesuai pada periode ini.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">Periode menggunakan tanggal PO pada MO perusahaan aktif. Reject adalah total reject tahap produksi; produksi adalah hasil baik penerimaan barang jadi, dihitung sampai tanggal akhir. Jumlah Export belum tersedia karena pengiriman ekspor belum dibedakan dari pengiriman lokal.</p>
    </div>
</div>
<style>.production-plan-table{font-size:12px}.production-plan-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.production-plan-table td{white-space:nowrap;padding:12px}.production-plan-table .item-name{min-width:360px;white-space:normal}</style>
@endsection
