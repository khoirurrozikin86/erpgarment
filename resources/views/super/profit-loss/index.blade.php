@extends('layouts.admin')
@section('title', 'Laba Rugi')
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#">Accounting</a></li><li class="breadcrumb-item active">Laba Rugi</li></ol></nav>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">Laporan Laba Rugi</h4><span class="text-muted">Berdasarkan jurnal berstatus posted pada periode yang dipilih.</span></div></div>
    <form method="GET" class="card mb-3"><div class="card-body py-3"><div class="row align-items-end g-2"><div class="col-sm-5 col-md-3"><label for="from_date" class="form-label mb-1">Dari Tanggal</label><input id="from_date" name="from_date" type="date" class="form-control form-control-sm" value="{{ $fromDate }}" required></div><div class="col-sm-5 col-md-3"><label for="to_date" class="form-label mb-1">Sampai Tanggal</label><input id="to_date" name="to_date" type="date" class="form-control form-control-sm" value="{{ $toDate }}" required></div><div class="col-sm-2 col-md-auto"><button class="btn btn-sm btn-primary" type="submit"><i data-feather="filter" class="icon-sm me-1"></i>Tampilkan</button></div></div></div></form>
    <div class="row g-3 mb-3"><div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small">Total Pendapatan</div><div class="fs-4 fw-semibold text-success">Rp {{ number_format($revenue, 2, ',', '.') }}</div></div></div></div><div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small">Total Beban</div><div class="fs-4 fw-semibold text-danger">Rp {{ number_format($expense, 2, ',', '.') }}</div></div></div></div><div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small">Laba / (Rugi) Bersih</div><div class="fs-4 fw-semibold {{ $revenue - $expense < 0 ? 'text-danger' : 'text-primary' }}">Rp {{ number_format($revenue - $expense, 2, ',', '.') }}</div></div></div></div></div>
    <div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-bordered table-hover align-middle mb-0"><thead><tr><th style="width:110px">Kode</th><th>Akun</th><th>Kelompok</th><th class="text-end">Jumlah</th></tr></thead><tbody>
        @forelse($rows as $row)
            <tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ $row->account_type === 'revenue' ? 'Pendapatan' : 'Beban' }}</td><td class="text-end">Rp {{ number_format($row->amount, 2, ',', '.') }}</td></tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada jurnal posted untuk akun pendapatan atau beban pada periode ini.</td></tr>
        @endforelse
        </tbody></table></div></div></div>
@endsection
