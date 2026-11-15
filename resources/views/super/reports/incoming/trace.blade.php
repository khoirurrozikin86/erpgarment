@extends('layouts.admin')
@section('title', 'Lacak Pemasukan Bahan')
@section('content')
<div class="card"><div class="card-header bg-primary text-white">Lacak Pemasukan Barang</div><div class="card-body">
    <form method="GET" class="row g-3 align-items-end mb-4">
        <div class="col-md-4"><label class="form-label" for="code">Kode Barang</label><input id="code" class="form-control" value="{{ $product->code }}" readonly></div>
        <div class="col-md-5"><label class="form-label" for="name">Nama Barang</label><input id="name" class="form-control" value="{{ $product->name }}" readonly></div>
        <div class="col-md-3"><label class="form-label" for="unit">Satuan</label><input id="unit" class="form-control" value="{{ $product->unit?->symbol ?: $product->unit?->name }}" readonly></div>
        <div class="col-md-6"><label class="form-label" for="mo">Pilih MO</label><input type="search" class="form-control mb-2" id="mo-search" placeholder="Cari nomor MO" aria-label="Cari nomor MO"><select class="form-select" id="mo" name="mo"><option value="">ALL</option>@foreach($orders as $order)<option value="{{ $order->id }}" @selected($selected?->id === $order->id)>{{ $order->number }}</option>@endforeach</select></div>
        <div class="col-md-6 d-flex gap-2"><button class="btn btn-primary">Tampilkan Data</button><button class="btn btn-outline-primary" name="export" value="1">Export Excel</button><a class="btn btn-outline-secondary" href="{{ route('super.reports.incoming.index') }}">Kembali</a></div>
    </form>
    @include('super.reports.incoming.sections')
</div></div>
<script>document.getElementById('mo-search').addEventListener('input', function () { const term = this.value.toLowerCase(); for (const option of document.getElementById('mo').options) option.hidden = option.value !== '' && !option.text.toLowerCase().includes(term); });</script>
@endsection
