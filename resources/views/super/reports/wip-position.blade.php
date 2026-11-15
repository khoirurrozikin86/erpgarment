@extends('layouts.admin')
@section('title', __('bc_reports.work_in_process_wip_position_report'))
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">{{ __('bc_reports.work_in_process_wip_position_report') }}</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">{{ __('bc_reports.period') }}</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">{{ __('bc_reports.to') }}</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">{{ __('bc_reports.show_data') }}</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.wip-position.export') }}">{{ __('bc_reports.export_excel') }}</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> {{ __('bc_reports.records') }}</label>
                <div class="d-flex align-items-center gap-2"><label for="search">{{ __('bc_reports.search_2') }}</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('bc_reports.item_code_or_name') }}"><button class="btn btn-outline-primary" type="submit">{{ __('bc_reports.search') }}</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle wip-table">
            <thead><tr><th>{{ __('bc_reports.no') }}</th><th>{{ __('bc_reports.item_code') }}</th><th>{{ __('bc_reports.item_name') }}</th><th>{{ __('bc_reports.item_unit') }}</th><th>{{ __('bc_reports.item_quantity') }}</th><th>{{ __('bc_reports.remarks') }}</th></tr></thead>
            <tbody>@forelse($items as $item)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $item->code }}/{{ $item->position }}</td><td class="item-name">{{ $item->name }}</td><td>{{ $item->symbol ?: $item->unit_name }}</td><td class="text-end">{{ rtrim(rtrim(number_format($item->quantity, 4, ',', '.'), '0'), ',') }}</td><td>{{ (float) $item->quantity < 0 ? __('bc_reports.review_production_transaction_order_or_corrections') : '—' }}</td></tr>
            @empty<tr><td colspan="6" class="text-center py-4">{{ __('bc_reports.no_matching_records_found_8') }}</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>{{ __('bc_reports.pagination', ['first' => $items->firstItem() ?? 0, 'last' => $items->lastItem() ?? 0, 'total' => $items->total()]) }}</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.quantities_show_positions_through_the_end_date_including_earlier_balances_r') }}</p>
    </div>
</div>
<style>.wip-table{font-size:12px}.wip-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.wip-table td{white-space:nowrap;padding:12px}.wip-table .item-name{min-width:360px;white-space:normal}</style>
@endsection
