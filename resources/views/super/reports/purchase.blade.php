@extends('layouts.admin')
@section('title', 'Laporan Pembelian')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Report</a></li><li class="breadcrumb-item active">Pembelian</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Laporan Pembelian</h4><span class="text-muted">Nilai Purchase Order dan penerimaan barang pada periode terpilih.</span></div></div>
    <form method="GET" class="card mb-3"><div class="card-body py-3"><div class="row align-items-end g-2"><div class="col-sm-5 col-md-3"><label for="from_date" class="form-label mb-1">Dari Tanggal</label><input id="from_date" name="from_date" type="date" class="form-control form-control-sm" value="{{ $fromDate }}" required></div><div class="col-sm-5 col-md-3"><label for="to_date" class="form-label mb-1">Sampai Tanggal</label><input id="to_date" name="to_date" type="date" class="form-control form-control-sm" value="{{ $toDate }}" required></div><div class="col-sm-2 col-md-auto"><button class="btn btn-sm btn-primary" type="submit"><i data-feather="filter" class="icon-sm me-1"></i>Tampilkan</button> <button class="btn btn-sm btn-outline-success" type="submit" formaction="{{ route('super.reports.purchase.export') }}"><i data-feather="download" class="icon-sm me-1"></i>Export Excel</button></div></div></div></form>
    <div class="row g-3 mb-3"><div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Purchase Order</div><div class="fs-5 fw-semibold">{{ number_format($summary['order_count']) }} dokumen</div></div></div></div><div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Nilai Pesanan</div><div class="fs-5 fw-semibold">Rp {{ number_format($summary['ordered_value'], 2, ',', '.') }}</div></div></div></div><div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Nilai Barang Diterima</div><div class="fs-5 fw-semibold text-success">Rp {{ number_format($summary['received_value'], 2, ',', '.') }}</div></div></div></div><div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Retur Pembelian</div><div class="fs-5 fw-semibold text-danger">− Rp {{ number_format($summary['return_value'], 2, ',', '.') }}</div><div class="small text-muted">Penerimaan bersih: Rp {{ number_format($summary['net_received_value'], 2, ',', '.') }}</div></div></div></div></div>
    <div class="card mb-3"><div class="card-header"><h6 class="mb-0">Purchase Order</h6></div><div class="card-body"><div class="table-responsive"><table class="table table-bordered table-hover align-middle mb-0"><thead><tr><th>Nomor PO</th><th>Tanggal</th><th>Supplier</th><th>Status</th><th class="text-end">Nilai</th></tr></thead><tbody>
        @forelse($orders as $order)
            <tr><td>{{ $order->number }}</td><td>{{ $order->order_date?->format('d/m/Y') }}</td><td>{{ $order->supplier?->name ?? 'Supplier dihapus' }}</td><td>{{ str($order->status)->replace('_', ' ')->title() }}</td><td class="text-end">Rp {{ number_format($order->total_amount, 2, ',', '.') }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada Purchase Order terbit pada periode ini.</td></tr>
        @endforelse
        </tbody></table></div></div></div>
    <div class="card"><div class="card-header"><h6 class="mb-0">Penerimaan Barang</h6></div><div class="card-body"><div class="table-responsive"><table class="table table-bordered table-hover align-middle mb-0"><thead><tr><th>Nomor Penerimaan</th><th>Tanggal Terima</th><th>Nomor PO</th><th>Supplier</th><th class="text-end">Nilai Diterima</th></tr></thead><tbody>
        @forelse($receipts as $receipt)
            <tr><td>{{ $receipt->number }}</td><td>{{ $receipt->received_at?->format('d/m/Y H:i') }}</td><td>{{ $receipt->purchaseOrder?->number ?? '—' }}</td><td>{{ $receipt->supplier?->name ?? 'Supplier dihapus' }}</td><td class="text-end">Rp {{ number_format($receipt->received_value, 2, ',', '.') }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada penerimaan barang pada periode ini.</td></tr>
        @endforelse
        </tbody></table></div></div></div>
    <div class="card mt-3"><div class="card-header"><h6 class="mb-0">Retur Pembelian</h6></div><div class="card-body"><div class="table-responsive"><table class="table table-bordered table-hover align-middle mb-0"><thead><tr><th>Nomor Retur</th><th>Tanggal Retur</th><th>Nomor PO</th><th>Supplier</th><th class="text-end">Nilai Retur</th></tr></thead><tbody>
        @forelse($returns as $return)
            <tr><td>{{ $return->number }}</td><td>{{ $return->returned_at?->format('d/m/Y H:i') }}</td><td>{{ $return->goodsReceipt?->purchaseOrder?->number ?? '—' }}</td><td>{{ $return->supplier?->name ?? 'Supplier dihapus' }}</td><td class="text-end text-danger">− Rp {{ number_format($return->total_amount, 2, ',', '.') }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada retur pembelian pada periode ini.</td></tr>
        @endforelse
        </tbody></table></div></div></div>
@endsection
