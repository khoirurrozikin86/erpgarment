@extends('layouts.admin')
@section('title', __('bc_reports.incoming_goods_report'))
@section('content')
<div class="card incoming-report">
    <div class="card-header bg-primary text-white">{{ __('bc_reports.incoming_goods_report') }}</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">{{ __('bc_reports.document_date') }}</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">{{ __('bc_reports.to') }}</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">{{ __('bc_reports.show_data') }}</button>
                <button class="btn btn-outline-primary" type="submit" formaction="{{ route('super.reports.incoming.export') }}">{{ __('bc_reports.export_excel') }}</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> {{ __('bc_reports.records') }}</label>
                <div class="d-flex align-items-center gap-2"><label for="search">{{ __('bc_reports.search_2') }}</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('bc_reports.item_sender_document') }}"><button class="btn btn-outline-primary" type="submit">{{ __('bc_reports.search') }}</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle incoming-table">
            <thead><tr><th rowspan="2">{{ __('bc_reports.no') }}</th><th colspan="3" class="text-center">{{ __('bc_reports.customs_document_details') }}</th><th colspan="2" class="text-center">{{ __('bc_reports.goods_receipt_note') }}<br>{{ __('bc_reports.equivalent_document') }}</th><th rowspan="2">{{ __('bc_reports.sender') }}</th><th rowspan="2">{{ __('bc_reports.hs_code') }}</th><th rowspan="2">{{ __('bc_reports.item_code') }}</th><th rowspan="2">{{ __('bc_reports.item_name') }}</th><th rowspan="2">{{ __('bc_reports.item_unit') }}</th><th rowspan="2">{{ __('bc_reports.item_quantity') }}</th><th rowspan="2">{{ __('bc_reports.unit_price') }}</th><th rowspan="2">{{ __('bc_reports.total_amount') }}</th><th rowspan="2">{{ __('bc_reports.currency') }}</th></tr><tr><th>{{ __('bc_reports.type') }}</th><th>{{ __('bc_reports.registration_no') }}</th><th>{{ __('bc_reports.registration_date') }}</th><th>{{ __('bc_reports.no') }}</th><th>{{ __('bc_reports.date') }}</th></tr></thead>
            <tbody>@forelse($items as $item)
                @php($receipt = $item->goodsReceipt)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $receipt?->customs_document_code ?? '—' }}</td><td>{{ $receipt?->customs_document_number ?? '—' }}</td><td>{{ $receipt?->customs_document_date?->format('d-m-Y') ?? '—' }}</td><td>{{ $receipt?->number }}</td><td>{{ $receipt?->received_at?->format('d-m-Y') }}</td><td>{{ $receipt?->supplier?->name ?? '—' }}</td><td>—</td><td>@if($item->product)<a href="{{ route('super.reports.incoming.trace', $item->product_id) }}">{{ $item->product->code }}</a>@else — @endif</td><td class="item-name">{{ $item->product?->name ?? '—' }}</td><td>{{ $item->product?->unit?->symbol ?: $item->product?->unit?->name }}</td><td class="text-end">{{ number_format($item->quantity, 4, ',', '.') }}</td><td class="text-end">{{ number_format($item->unit_price, 4, ',', '.') }}</td><td class="text-end">{{ number_format($item->line_total, 4, ',', '.') }}</td><td>{{ $receipt?->currency ?? '—' }}</td></tr>
            @empty<tr><td colspan="15" class="text-center py-4">{{ __('bc_reports.no_matching_records_found_12') }}</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>{{ __('bc_reports.pagination', ['first' => $items->firstItem() ?? 0, 'last' => $items->lastItem() ?? 0, 'total' => $items->total()]) }}</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.document_date_uses_the_customs_document_date_only_approved_receipts_with_cu') }}</p>
    </div>
</div>
<style>.incoming-table {font-size:12px}.incoming-table th {background:#f8f8f8;vertical-align:middle;text-align:center}.incoming-table td,.incoming-table th {white-space:nowrap;padding:12px}.incoming-table .item-name {min-width:260px;white-space:normal}.incoming-report .card-header {font-size:16px}</style>
@endsection
