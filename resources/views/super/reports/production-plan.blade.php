@extends('layouts.admin')
@section('title', __('bc_reports.production_plan_report'))
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">{{ __('bc_reports.production_plan_report') }}</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">{{ __('bc_reports.period') }}</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">{{ __('bc_reports.to') }}</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">{{ __('bc_reports.show_data') }}</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.production-plan.export') }}">{{ __('bc_reports.export_excel') }}</button>
            </div>
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <label class="d-flex gap-2 align-items-center"><select name="per_page" class="form-select w-auto" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>@endforeach</select> {{ __('bc_reports.records') }}</label>
                <div class="d-flex align-items-center gap-2"><label for="search">{{ __('bc_reports.search_2') }}</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('bc_reports.item_code_or_name') }}"><button class="btn btn-outline-primary" type="submit">{{ __('bc_reports.search') }}</button></div>
            </div>
        </form>
        <div class="table-responsive"><table class="table table-bordered table-striped align-middle production-plan-table">
            <thead><tr><th rowspan="2">{{ __('bc_reports.no') }}</th><th colspan="2">{{ __('bc_reports.production_plan_document') }}</th><th rowspan="2">{{ __('bc_reports.item_code') }}</th><th rowspan="2">{{ __('bc_reports.item_name') }}</th><th rowspan="2">{{ __('bc_reports.order_quantity') }}</th><th rowspan="2">{{ __('bc_reports.rejected_quantity') }}</th><th rowspan="2">{{ __('bc_reports.produced_quantity') }}</th><th rowspan="2">{{ __('bc_reports.export_quantity') }}</th></tr><tr><th>{{ __('bc_reports.no') }}</th><th>{{ __('bc_reports.date') }}</th></tr></thead>
            <tbody>@forelse($items as $item)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $item->number }}</td><td>{{ $item->po_date?->format('d-m-Y') ?? '—' }}</td><td>{{ $item->outputProduct?->code ?? '—' }}</td><td class="item-name">{{ $item->outputProduct?->name ?? '—' }}</td><td class="text-end">{{ number_format($item->planned_quantity, 4, ',', '.') }}</td><td class="text-end">{{ rtrim(rtrim(number_format($item->report_rejected ?? 0, 4, ',', '.'), '0'), ',') }}</td><td class="text-end">{{ rtrim(rtrim(number_format($item->report_produced ?? 0, 4, ',', '.'), '0'), ',') }}</td><td class="text-end">—</td></tr>
            @empty<tr><td colspan="9" class="text-center py-4">{{ __('bc_reports.no_matching_records_found_3') }}</td></tr>@endforelse</tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3"><span>{{ __('bc_reports.pagination', ['first' => $items->firstItem() ?? 0, 'last' => $items->lastItem() ?? 0, 'total' => $items->total()]) }}</span><div>{{ $items->links() }}</div></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.the_period_uses_the_po_date_on_mos_for_the_active_company_reject_is_the_tot') }}</p>
    </div>
</div>
<style>.production-plan-table{font-size:12px}.production-plan-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.production-plan-table td{white-space:nowrap;padding:12px}.production-plan-table .item-name{min-width:360px;white-space:normal}</style>
@endsection
