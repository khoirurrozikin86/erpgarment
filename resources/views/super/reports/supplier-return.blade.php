@extends('layouts.admin')
@section('title', __('bc_reports.supplier_return_report'))
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">{{ __('bc_reports.supplier_return_report') }}</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">{{ __('bc_reports.period') }}</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">{{ __('bc_reports.to') }}</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">{{ __('bc_reports.show_data') }}</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.supplier-return.export') }}">{{ __('bc_reports.export_excel') }}</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> {{ __('bc_reports.records') }}</label>
                <div class="d-flex align-items-center gap-2"><label for="search">{{ __('bc_reports.search_2') }}</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('bc_reports.return_number_item_code_or_name') }}"><button class="btn btn-outline-primary" type="submit">{{ __('bc_reports.search') }}</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle supplier-return-table">
            <thead><tr><th>{{ __('bc_reports.no') }}</th><th>{{ __('bc_reports.return_no') }}</th><th>{{ __('bc_reports.date') }}</th><th>{{ __('bc_reports.item_code') }}</th><th>{{ __('bc_reports.item_name') }}</th><th>{{ __('bc_reports.unit') }}</th><th>{{ __('bc_reports.quantity') }}</th></tr></thead>
            <tbody>@forelse($items as $item)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $item->purchaseReturn?->number ?? '—' }}</td><td>{{ $item->purchaseReturn?->returned_at?->format('Y-m-d') }}</td><td>{{ $item->product?->code ?? '—' }}</td><td class="item-name">{{ $item->product?->name ?? '—' }}</td><td>{{ $item->product?->unit?->symbol ?: $item->product?->unit?->name }}</td><td class="text-end">{{ number_format($item->quantity, 4, ',', '.') }}</td></tr>
            @empty<tr><td colspan="7" class="text-center py-4">{{ __('bc_reports.no_matching_records_found_2') }}</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>{{ __('bc_reports.pagination', ['first' => $items->firstItem() ?? 0, 'last' => $items->lastItem() ?? 0, 'total' => $items->total()]) }}</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.the_period_uses_the_return_date_each_row_is_an_item_on_a_purchase_return_do') }}</p>
    </div>
</div>
<style>.supplier-return-table{font-size:12px}.supplier-return-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.supplier-return-table td{white-space:nowrap;padding:12px}.supplier-return-table .item-name{min-width:360px;white-space:normal}</style>
@endsection
