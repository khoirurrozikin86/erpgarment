@extends('layouts.admin')
@section('title', __('bc_reports.trace_incoming_materials_2'))
@section('content')
<div class="card"><div class="card-header bg-primary text-white">{{ __('bc_reports.trace_incoming_goods') }}</div><div class="card-body">
    <form method="GET" class="row g-3 align-items-end mb-4">
        <div class="col-md-4"><label class="form-label" for="code">{{ __('bc_reports.item_code') }}</label><input id="code" class="form-control" value="{{ $product->code }}" readonly></div>
        <div class="col-md-5"><label class="form-label" for="name">{{ __('bc_reports.item_name') }}</label><input id="name" class="form-control" value="{{ $product->name }}" readonly></div>
        <div class="col-md-3"><label class="form-label" for="unit">{{ __('bc_reports.unit') }}</label><input id="unit" class="form-control" value="{{ $product->unit?->symbol ?: $product->unit?->name }}" readonly></div>
        <div class="col-md-6"><label class="form-label" for="mo">{{ __('bc_reports.select_mo') }}</label><input type="search" class="form-control mb-2" id="mo-search" placeholder="{{ __('bc_reports.search_mo_number') }}" aria-label="{{ __('bc_reports.search_mo_number') }}"><select class="form-select" id="mo" name="mo"><option value="">{{ __('bc_reports.all') }}</option>@foreach($orders as $order)<option value="{{ $order->id }}" @selected($selected?->id === $order->id)>{{ $order->number }}</option>@endforeach</select></div>
        <div class="col-md-6 d-flex gap-2"><button class="btn btn-primary">{{ __('bc_reports.show_data') }}</button><button class="btn btn-outline-primary" name="export" value="1">{{ __('bc_reports.export_excel') }}</button><a class="btn btn-outline-secondary" href="{{ route('super.reports.incoming.index') }}">{{ __('bc_reports.back') }}</a></div>
    </form>
    @include('super.reports.incoming.sections')
</div></div>
<script>document.getElementById('mo-search').addEventListener('input', function () { const term = this.value.toLowerCase(); for (const option of document.getElementById('mo').options) option.hidden = option.value !== '' && !option.text.toLowerCase().includes(term); });</script>
@endsection
