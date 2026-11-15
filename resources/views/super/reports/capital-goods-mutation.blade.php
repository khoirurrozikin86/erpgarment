@extends('layouts.admin')
@section('title', __('bc_reports.capital_and_other_goods_movement_report'))
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">{{ __('bc_reports.capital_and_other_goods_movement_report') }}</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">{{ __('bc_reports.period') }}</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">{{ __('bc_reports.to') }}</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">{{ __('bc_reports.show_data') }}</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.capital-goods-mutation.export') }}">{{ __('bc_reports.export_excel') }}</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> {{ __('bc_reports.records') }}</label>
                <div class="d-flex align-items-center gap-2"><label for="search">{{ __('bc_reports.search_2') }}</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('bc_reports.item_code_or_name') }}"><button class="btn btn-outline-primary" type="submit">{{ __('bc_reports.search') }}</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle mutation-table">
            <thead><tr><th>{{ __('bc_reports.no') }}</th><th>{{ __('bc_reports.item_code') }}</th><th>{{ __('bc_reports.item_name') }}</th><th>{{ __('bc_reports.item_unit') }}</th><th>{{ __('bc_reports.item_quantity') }}</th><th>{{ __('bc_reports.opening_balance') }}</th><th>{{ __('bc_reports.incoming_quantity') }}</th><th>{{ __('bc_reports.outgoing_quantity') }}</th><th>{{ __('bc_reports.adjustment') }}</th><th>{{ __('bc_reports.closing_balance') }}</th><th>{{ __('bc_reports.physical_count') }}</th><th>{{ __('bc_reports.difference') }}</th><th>{{ __('bc_reports.remarks') }}</th></tr></thead>
            <tbody>@forelse($items as $item)
                @php($values = \App\Domain\Inventory\Queries\CapitalGoodsMutationReportQuery::values($item))
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $item->code }}</td><td class="item-name">{{ $item->name }}</td><td>{{ $item->unit?->symbol ?: $item->unit?->name }}</td><td class="text-end" title="{{ __('bc_reports.item_quantity_definition_awaits_confirmation') }}">—</td>
                @foreach(['opening', 'incoming', 'outgoing', 'adjustment', 'closing', 'counted', 'difference'] as $key)<td class="text-end">{{ $values[$key] === null ? '—' : number_format($values[$key], 2, ',', '.') }}</td>@endforeach
                <td>{{ $values['notes'] ?? '—' }}</td></tr>
            @empty<tr><td colspan="13" class="text-center py-4">{{ __('bc_reports.no_matching_records_found_4') }}</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>{{ __('bc_reports.pagination', ['first' => $items->firstItem() ?? 0, 'last' => $items->lastItem() ?? 0, 'total' => $items->total()]) }}</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.machines_equipment_use_receipt_and_issue_document_dates_for_the_active_comp') }}</p>
    </div>
</div>
<style>.mutation-table{font-size:12px}.mutation-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.mutation-table td{white-space:nowrap;padding:12px}.mutation-table .item-name{min-width:360px;white-space:normal}</style>
@endsection
