@extends('layouts.admin')
@section('title', 'Laporan Status Produksi Harian')
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">Laporan Status Produksi Harian</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">Periode</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">S.d.</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">Tampilkan Data</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.daily-production-status.export') }}">Export Excel</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> records</label>
                <div class="d-flex align-items-center gap-2"><label for="search">Search:</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Buyer, MO, PO, style, barang"><button class="btn btn-outline-primary" type="submit">Cari</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle daily-production-table">
            <thead><tr><th rowspan="2">Buyer</th><th colspan="2">Dokumen</th><th rowspan="2">Style</th><th rowspan="2">Kode Barang</th><th rowspan="2">Nama Barang</th><th rowspan="2">Jumlah Order (1)</th><th colspan="3">Cutting</th><th colspan="3">Sewing</th><th colspan="3">Finishing</th><th colspan="2">Delivery</th></tr><tr><th>No Planning</th><th>PO Buyer</th><th>Today (2)</th><th>Total (3)</th><th>4 = 3 - 1</th><th>Today (5)</th><th>Total (6)</th><th>7 = 6 - 3</th><th>Today (8)</th><th>Total (9)</th><th>10 = 9 - 6</th><th>Today (11)</th><th>Total (12)</th></tr></thead>
            <tbody>@forelse($items as $item)
                <tr><td>{{ $item->buyer?->name ?? '—' }}</td><td>{{ $item->number }}</td><td>{{ $item->buyer_po_number ?: '—' }}</td><td>{{ $item->outputProduct?->style_code ?: '—' }}</td><td>{{ $item->outputProduct?->code ?? '—' }}</td><td class="item-name">{{ $item->outputProduct?->name ?? '—' }}</td><td class="text-end">{{ rtrim(rtrim(number_format($item->planned_quantity, 4, ',', '.'), '0'), ',') }}</td>
                @foreach(\App\Domain\Manufacturing\Queries\DailyProductionStatusReportQuery::values($item) as $value)<td class="text-end">{{ rtrim(rtrim(number_format($value, 4, ',', '.'), '0'), ',') }}</td>@endforeach</tr>
            @empty<tr><td colspan="18" class="text-center py-4">Tidak ada rencana produksi yang sesuai pada periode ini.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">Periode memilih MO berdasarkan tanggal PO perusahaan aktif. Today adalah aktivitas pada tanggal akhir yang dipilih; Total bersifat kumulatif sampai tanggal tersebut. Cutting/Sewing memakai hasil baik, Finishing memakai penerimaan barang jadi, dan Delivery memakai surat jalan disetujui. Selisih mengikuti rumus pada header.</p>
    </div>
</div>
<style>.daily-production-table{font-size:12px}.daily-production-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.daily-production-table td{white-space:nowrap;padding:12px}.daily-production-table .item-name{min-width:360px;white-space:normal}</style>
@endsection
