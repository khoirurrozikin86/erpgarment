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
            <thead>
                <tr>
                    <th scope="col">{{ __('bc_reports.buyer') }}</th>
                    <th scope="col">{{ __('bc_reports.planning_no') }}</th>
                    <th scope="col">{{ __('bc_reports.buyer_po') }}</th>
                    <th scope="col">{{ __('bc_reports.style') }}</th>
                    <th scope="col">{{ __('variants.stock_parent_code') }}</th>
                    <th scope="col">{{ __('variants.stock_sku') }}</th>
                    <th scope="col">{{ __('bc_reports.item_name') }}</th>
                    <th scope="col">{{ __('ui.workflow.color') }}</th>
                    <th scope="col">{{ __('ui.workflow.size') }}</th>
                    <th scope="col">{{ __('bc_reports.cutting') }}</th>
                    <th scope="col">{{ __('bc_reports.sewing') }}</th>
                    <th scope="col">{{ __('ui.packing.title') }}</th>
                    <th scope="col">{{ __('bc_reports.finishing') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table></div>
    </div>
</div>
<style>.daily-production-table{font-size:12px}.daily-production-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.daily-production-table td{white-space:nowrap;padding:12px}.daily-production-table .item-name{min-width:360px;white-space:normal}</style>
@endsection

@include('super.reports.partials.datatable', ['reportKey' => 'daily-production-status'])
