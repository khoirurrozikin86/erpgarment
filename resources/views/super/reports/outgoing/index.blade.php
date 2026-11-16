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
            <thead><tr><th rowspan="2">{{ __('bc_reports.no') }}</th><th colspan="3">{{ __('bc_reports.customs_document_details') }}</th><th colspan="2">{{ __('bc_reports.goods_issue_note_equivalent_document') }}</th><th rowspan="2">{{ __('bc_reports.recipient') }}</th><th rowspan="2">{{ __('bc_reports.hs_code') }}</th><th rowspan="2">{{ __('bc_reports.item_code') }}</th><th rowspan="2">{{ __('bc_reports.item_name') }}</th><th rowspan="2">{{ __('bc_reports.item_unit') }}</th><th rowspan="2">{{ __('bc_reports.item_quantity') }}</th><th rowspan="2">{{ __('bc_reports.unit_price') }}</th><th rowspan="2">{{ __('bc_reports.total_amount') }}</th></tr><tr><th>{{ __('bc_reports.type') }}</th><th>{{ __('bc_reports.registration_no') }}</th><th>{{ __('bc_reports.registration_date') }}</th><th>{{ __('bc_reports.no') }}</th><th>{{ __('bc_reports.date') }}</th></tr></thead>
            <tbody></tbody>
        </table></div>
        <p class="small text-muted mt-3 mb-0">{{ __('bc_reports.source_approved_delivery_notes_and_equipment_issues_for_the_active_company_') }}</p>
    </div>
</div>
<dialog id="trace-dialog" aria-labelledby="trace-title">
    <div class="bg-primary text-white p-3 d-flex justify-content-between"><span id="trace-title">{{ __('bc_reports.trace_outgoing_goods_2') }}</span><button type="button" class="btn-close btn-close-white" id="trace-close" aria-label="{{ __('bc_reports.close') }}"></button></div>
    <form method="GET" id="trace-form" class="p-4">
        <label for="trace-code" class="form-label">{{ __('bc_reports.item_code_2') }}</label><input id="trace-code" class="form-control mb-3" readonly>
        <label for="trace-name" class="form-label">{{ __('bc_reports.item_name_2') }}</label><input id="trace-name" class="form-control mb-3" readonly>
        <label for="trace-unit" class="form-label">{{ __('bc_reports.unit_2') }}</label><input id="trace-unit" class="form-control mb-3" readonly>
        <label for="trace-mo" class="form-label">{{ __('bc_reports.select_mo_2') }}</label><input id="trace-search" type="search" class="form-control mb-2" placeholder="{{ __('bc_reports.search_mo_number') }}" aria-label="{{ __('bc_reports.search_mo_number') }}"><select name="mo" id="trace-mo" class="form-select mb-3"></select>
        <p id="trace-status" role="status" class="small text-muted"></p><button id="trace-submit" class="btn btn-primary">{{ __('bc_reports.show_data') }}</button>
    </form>
</dialog>
<style>.outgoing-table{font-size:12px}.outgoing-table th{background:#f8f8f8;vertical-align:middle;text-align:center}.outgoing-table td,.outgoing-table th{white-space:nowrap;padding:12px}.outgoing-table .item-name{min-width:260px;white-space:normal}#trace-dialog{border:1px solid #abc9ff;padding:0;width:760px;max-width:95vw}#trace-dialog::backdrop{background:rgba(0,0,0,.35)}</style>
<script>
(() => {
    const dialog = document.getElementById('trace-dialog'), form = document.getElementById('trace-form'), select = document.getElementById('trace-mo'), submit = document.getElementById('trace-submit'), status = document.getElementById('trace-status');
    let pending;
    document.getElementById('trace-close').addEventListener('click', () => { pending?.abort(); dialog.close(); });
    document.addEventListener('click', async event => {
        const link = event.target.closest('.js-trace-product');
        if (!link) return;
        event.preventDefault(); pending?.abort(); pending = new AbortController();
        form.action = link.href;
        for (const key of ['code','name','unit']) document.getElementById('trace-' + key).value = link.dataset[key] || '';
        document.getElementById('trace-search').value = ''; select.replaceChildren(); submit.disabled = true; status.textContent = @json(__('bc_reports.loading_mos')); dialog.showModal();
        try {
            const response = await fetch(link.href, {headers: {Accept: 'application/json'}, signal: pending.signal});
            if (!response.ok) throw new Error('load');
            const data = await response.json(); select.add(new Option(@json(__('bc_reports.all')), ''));
            for (const order of data.orders) select.add(new Option(order.number, order.id));
            status.textContent = data.orders.length ? '' : @json(__('bc_reports.no_mo_produces_this_item_yet')); submit.disabled = false;
        } catch (error) { if (error.name !== 'AbortError') status.textContent = @json(__('bc_reports.could_not_load_mos_close_and_click_the_item_code_to_retry')); }
    });
    document.getElementById('trace-search').addEventListener('input', function () { for (const option of select.options) option.hidden = option.value !== '' && !option.text.toLowerCase().includes(this.value.toLowerCase()); });
})();
</script>
@endsection

@include('super.reports.partials.datatable', ['reportKey' => 'outgoing'])
