@extends('layouts.admin')
@section('title', __('bc_reports.production_output_report'))
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">{{ __('bc_reports.production_output_report') }}</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">{{ __('bc_reports.period') }}</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">{{ __('bc_reports.to') }}</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">{{ __('bc_reports.show_data') }}</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.production-result.export') }}">{{ __('bc_reports.export_excel') }}</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> {{ __('bc_reports.records') }}</label>
                <div class="d-flex align-items-center gap-2"><label for="search">{{ __('bc_reports.search_2') }}</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('bc_reports.item_code_or_name') }}"><button class="btn btn-outline-primary" type="submit">{{ __('bc_reports.search') }}</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle production-result-table">
            <thead><tr><th rowspan="2">{{ __('bc_reports.no') }}</th><th colspan="2">{{ __('bc_reports.document') }}</th><th rowspan="2">{{ __('bc_reports.production_no') }}</th><th rowspan="2">{{ __('bc_reports.date') }}</th><th rowspan="2">{{ __('bc_reports.item_code') }}</th><th rowspan="2">{{ __('bc_reports.item_name') }}</th><th rowspan="2">{{ __('bc_reports.unit') }}</th><th rowspan="2">{{ __('bc_reports.received_from_sewing') }}</th><th rowspan="2">{{ __('bc_reports.production_output') }}</th><th rowspan="2">{{ __('bc_reports.warehouse') }}</th></tr><tr><th>{{ __('bc_reports.planning_no') }}</th><th>{{ __('bc_reports.buyer_po') }}</th></tr></thead>
            <tbody>@forelse($items as $item)
                @php($product = $item->productionOrder?->outputProduct)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $item->productionOrder?->number ?? '—' }}</td><td>{{ $item->productionOrder?->buyer_po_number ?: '—' }}</td><td>{{ $item->packing_number ?? '—' }}</td><td>{{ $item->processed_at?->format('d-m-Y') }}</td><td>{{ $product?->code ?? '—' }}</td><td class="item-name">{{ $product?->name ?? '—' }}</td><td>{{ $product?->unit?->symbol ?: $product?->unit?->name }}</td><td class="text-end">{{ rtrim(rtrim(number_format($item->input_quantity, 4, ',', '.'), '0'), ',') }}</td><td class="text-end">{{ rtrim(rtrim(number_format($item->good_quantity, 4, ',', '.'), '0'), ',') }}</td><td>—</td></tr>
            @empty<tr><td colspan="11" class="text-center py-4">{{ __('bc_reports.no_matching_records_found_10') }}</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>{{ __('bc_reports.pagination', ['first' => $items->firstItem() ?? 0, 'last' => $items->lastItem() ?? 0, 'total' => $items->total()]) }}</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.source_packing_transactions_for_the_active_company_using_process_dates_rece') }}</p>
    </div>
</div>
<style>.production-result-table{font-size:12px}.production-result-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.production-result-table td{white-space:nowrap;padding:12px}.production-result-table .item-name{min-width:360px;white-space:normal}</style>
@endsection
