@extends('layouts.admin')
@section('title', __('bc_reports.outgoing_goods_report'))
@section('content')
<div class="card outgoing-report">
    <div class="card-header bg-primary text-white">{{ __('bc_reports.outgoing_goods_report') }}</div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="GET" class="mb-4">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div><label class="form-label" for="from_date">{{ __('bc_reports.issue_date') }}</label><input class="form-control" type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
                <div><label class="form-label" for="to_date">{{ __('bc_reports.to') }}</label><input class="form-control" type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
                <button class="btn btn-primary" type="submit">{{ __('bc_reports.show_data') }}</button>
                <button class="btn btn-outline-primary" type="submit" formaction="{{ route('super.reports.outgoing.export') }}">{{ __('bc_reports.export_excel') }}</button>
            </div>
            <input type="hidden" name="search" id="report-export-search" value="">
        </form>
        <div class="table-responsive"><table id="bc-report-table" class="table table-bordered table-striped align-middle outgoing-table">
            <thead><tr><th rowspan="2">{{ __('bc_reports.no') }}</th><th colspan="3">{{ __('bc_reports.customs_document_details') }}</th><th colspan="2">{{ __('bc_reports.goods_issue_note_equivalent_document') }}</th><th rowspan="2">{{ __('bc_reports.recipient') }}</th><th rowspan="2">{{ __('bc_reports.hs_code') }}</th><th rowspan="2">{{ __('variants.stock_parent_code') }}</th><th rowspan="2">{{ __('variants.stock_sku') }}</th><th rowspan="2">{{ __('bc_reports.item_name') }}</th><th rowspan="2">{{ __('ui.common.category') }}</th><th rowspan="2">{{ __('ui.workflow.color') }}</th><th rowspan="2">{{ __('ui.workflow.size') }}</th><th rowspan="2">{{ __('bc_reports.item_unit') }}</th><th rowspan="2">{{ __('bc_reports.item_quantity') }}</th><th rowspan="2">{{ __('bc_reports.unit_price') }}</th><th rowspan="2">{{ __('bc_reports.total_amount') }}</th></tr><tr><th>{{ __('bc_reports.type') }}</th><th>{{ __('bc_reports.registration_no') }}</th><th>{{ __('bc_reports.registration_date') }}</th><th>{{ __('bc_reports.no') }}</th><th>{{ __('bc_reports.date') }}</th></tr></thead>
            <tbody></tbody>
        </table></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.source_approved_delivery_notes_and_equipment_issues_for_the_active_company_') }}</p>
    </div>
</div>
<style>.outgoing-table{font-size:12px}.outgoing-table th{background:#f8f8f8;vertical-align:middle;text-align:center}.outgoing-table td,.outgoing-table th{white-space:nowrap;padding:12px}.outgoing-table .item-name{min-width:260px;white-space:normal}</style>
@endsection

@include('super.reports.partials.datatable', ['reportKey' => 'outgoing'])
