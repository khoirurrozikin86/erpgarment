@extends('layouts.admin')
@section('title', __('bc_reports.finished_goods_return_report'))
@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">{{ __('bc_reports.finished_goods_return_report') }}</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">{{ __('bc_reports.period') }}</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">{{ __('bc_reports.to') }}</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">{{ __('bc_reports.show_data') }}</button>
                <button class="btn btn-primary" type="submit" formaction="{{ route('super.reports.finished-goods-return.export') }}">{{ __('bc_reports.export_excel') }}</button>
            </div>
            <input type="hidden" name="search" id="report-export-search" value="">
        </form>
        <div class="table-responsive"><table id="bc-report-table" class="table table-bordered table-striped align-middle finished-goods-return-table">
            <thead><tr><th>{{ __('bc_reports.no') }}</th><th>{{ __('bc_reports.reject_no') }}</th><th>{{ __('bc_reports.date') }}</th><th>{{ __('bc_reports.item_code') }}</th><th>{{ __('bc_reports.item_name') }}</th><th>{{ __('bc_reports.unit') }}</th><th>{{ __('bc_reports.quantity') }}</th></tr></thead>
            <tbody></tbody>
        </table></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.source_customer_return_movements_for_finished_goods_in_the_shared_stock_led') }}</p>
    </div>
</div>
<style>.finished-goods-return-table{font-size:12px}.finished-goods-return-table th{background:#f8f8f8;vertical-align:middle;text-align:center;min-width:105px;white-space:normal}.finished-goods-return-table td{white-space:nowrap;padding:12px}.finished-goods-return-table .item-name{min-width:360px;white-space:normal}</style>
@endsection

@include('super.reports.partials.datatable', ['reportKey' => 'finished-goods-return'])
