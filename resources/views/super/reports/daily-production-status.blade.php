@extends('layouts.admin')
@section('title', __('bc_reports.daily_production_status_report'))
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">{{ __('bc_reports.daily_production_status_report') }}</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">{{ __('bc_reports.period') }}</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">{{ __('bc_reports.to') }}</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">{{ __('bc_reports.show_data') }}</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.daily-production-status.export') }}">{{ __('bc_reports.export_excel') }}</button>
            </div>
            <input type="hidden" name="search" id="report-export-search" value="">
        </form>
        <div class="table-responsive"><table id="bc-report-table" class="table table-bordered table-striped align-middle daily-production-table">
            <thead><tr><th rowspan="2">{{ __('bc_reports.buyer') }}</th><th colspan="2">{{ __('bc_reports.document') }}</th><th rowspan="2">{{ __('bc_reports.style') }}</th><th rowspan="2">{{ __('bc_reports.item_code') }}</th><th rowspan="2">{{ __('bc_reports.item_name') }}</th><th rowspan="2">{{ __('ui.workflow.color') }}</th><th rowspan="2">{{ __('ui.workflow.size') }}</th><th rowspan="2">{{ __('bc_reports.order_quantity_1') }}</th><th colspan="3">{{ __('bc_reports.cutting') }}</th><th colspan="3">{{ __('bc_reports.sewing') }}</th><th colspan="2">{{ __('ui.packing.title') }}</th><th colspan="3">{{ __('bc_reports.finishing') }}</th><th colspan="2">{{ __('bc_reports.delivery') }}</th></tr><tr><th>{{ __('bc_reports.planning_no') }}</th><th>{{ __('bc_reports.buyer_po') }}</th><th>{{ __('bc_reports.today_2') }}</th><th>{{ __('bc_reports.total_3') }}</th><th>4 = 3 - 1</th><th>{{ __('bc_reports.today_5') }}</th><th>{{ __('bc_reports.total_6') }}</th><th>7 = 6 - 3</th><th>{{ __('variants.stage_today') }}</th><th>{{ __('variants.stage_total') }}</th><th>{{ __('bc_reports.today_8') }}</th><th>{{ __('bc_reports.total_9') }}</th><th>10 = 9 - 6</th><th>{{ __('bc_reports.today_11') }}</th><th>{{ __('bc_reports.total_12') }}</th></tr></thead>
            <tbody></tbody>
        </table></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.the_period_selects_mos_by_po_date_for_the_active_company_today_is_activity_') }}</p>
    </div>
</div>
<style>.daily-production-table{font-size:12px}.daily-production-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.daily-production-table td{white-space:nowrap;padding:12px}.daily-production-table .item-name{min-width:360px;white-space:normal}</style>
@endsection

@include('super.reports.partials.datatable', ['reportKey' => 'daily-production-status'])
