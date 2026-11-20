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
            <input type="hidden" name="search" id="report-export-search" value="">
        </form>
        <div class="table-responsive"><table id="bc-report-table" class="table table-bordered table-striped align-middle production-plan-table">
            <thead><tr><th rowspan="2">{{ __('bc_reports.no') }}</th><th colspan="2">{{ __('bc_reports.production_plan_document') }}</th><th rowspan="2">{{ __('bc_reports.item_code') }}</th><th rowspan="2">{{ __('bc_reports.item_name') }}</th><th rowspan="2">{{ __('ui.workflow.color') }}</th><th rowspan="2">{{ __('ui.workflow.size') }}</th><th rowspan="2">{{ __('bc_reports.order_quantity') }}</th><th rowspan="2">{{ __('bc_reports.rejected_quantity') }}</th><th rowspan="2">{{ __('bc_reports.produced_quantity') }}</th><th rowspan="2">{{ __('ui.gsn.quantity') }}</th></tr><tr><th>{{ __('bc_reports.no') }}</th><th>{{ __('bc_reports.date') }}</th></tr></thead>
            <tbody></tbody>
        </table></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.the_period_uses_the_po_date_on_mos_for_the_active_company_reject_is_the_tot') }}</p>
    </div>
</div>
<style>.production-plan-table{font-size:12px}.production-plan-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.production-plan-table td{white-space:nowrap;padding:12px}.production-plan-table .item-name{min-width:360px;white-space:normal}</style>
@endsection

@include('super.reports.partials.datatable', ['reportKey' => 'production-plan'])
