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
            <input type="hidden" name="search" id="report-export-search" value="">
        </form>
        <div class="table-responsive"><table id="bc-report-table" class="table table-bordered table-striped align-middle wip-table">
            <thead><tr><th>{{ __('bc_reports.no') }}</th><th>{{ __('bc_reports.planning_no') }}</th><th>{{ __('bc_reports.style') }}</th><th>{{ __('bc_reports.item_code') }}</th><th>{{ __('bc_reports.item_name') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('bc_reports.order_quantity') }}</th><th>{{ __('bc_reports.item_unit') }}</th><th>{{ __('bc_reports.item_quantity') }}</th><th>{{ __('bc_reports.remarks') }}</th></tr></thead>
            <tbody></tbody>
        </table></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.quantities_show_positions_through_the_end_date_including_earlier_balances_r') }}</p>
    </div>
</div>
<style>.wip-table{font-size:12px}.wip-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.wip-table td{white-space:nowrap;padding:12px}.wip-table .item-name{min-width:360px;white-space:normal}</style>
@endsection

@include('super.reports.partials.datatable', ['reportKey' => 'wip-position'])
