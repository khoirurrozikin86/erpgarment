@extends('layouts.admin')
@section('title', __('bc_reports.finished_goods_movement_report_production_output'))
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">{{ __('bc_reports.finished_goods_movement_report_production_output') }}</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">{{ __('bc_reports.period') }}</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">{{ __('bc_reports.to') }}</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">{{ __('bc_reports.show_data') }}</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.finished-goods-mutation.export') }}">{{ __('bc_reports.export_excel') }}</button>
            </div>
            <input type="hidden" name="search" id="report-export-search" value="">
        </form>
        <div class="table-responsive"><table id="bc-report-table" class="table table-bordered table-striped align-middle mutation-table">
            <thead><tr><th>{{ __('bc_reports.no') }}</th><th>{{ __('variants.stock_parent_code') }}</th><th>{{ __('variants.stock_sku') }}</th><th>{{ __('bc_reports.item_name') }}</th><th>{{ __('ui.common.category') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('bc_reports.item_unit') }}</th><th>{{ __('bc_reports.item_quantity') }}</th><th>{{ __('bc_reports.opening_balance') }}</th><th>{{ __('bc_reports.incoming_quantity') }}</th><th>{{ __('bc_reports.outgoing_quantity') }}</th><th>{{ __('bc_reports.adjustment') }}</th><th>{{ __('bc_reports.closing_balance') }}</th><th>{{ __('bc_reports.physical_count') }}</th><th>{{ __('bc_reports.difference') }}</th><th>{{ __('bc_reports.remarks') }}</th></tr></thead>
            <tbody></tbody>
        </table></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.the_period_uses_stock_movement_posting_times_adjustments_are_separate_from__2') }}</p>
    </div>
</div>
<style>.mutation-table{font-size:12px}.mutation-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.mutation-table td{white-space:nowrap;padding:12px}.mutation-table .item-name{min-width:360px;white-space:normal}</style>
@endsection

@include('super.reports.partials.datatable', ['reportKey' => 'finished-goods-mutation'])
