@extends('layouts.admin')
@section('title', __($module.'.form'))
@section('content')
@php
    $outgoing = isset($customers);
    $primaryParty = $outgoing ? 'customer' : 'supplier';
    $secondaryParty = $outgoing ? 'exporter' : 'importer';
    $partyOptions = [$primaryParty => $outgoing ? $customers : $suppliers];
    $secondaryOptions = $outgoing ? ($exporters ?? null) : ($importers ?? null);
    if ($secondaryOptions !== null && !in_array($module, ['bc262', 'bc27', 'bc40'], true)) {
        $partyOptions[$secondaryParty] = $secondaryOptions;
    }
    $moduleRoute = 'super.'.($outgoing ? 'bc-out' : 'bc-in').'.'.$module.'.';
    $documentReference = $outgoing ? 'shipment' : 'receipt';
    $sourceUrl = route($moduleRoute.($outgoing ? 'gsns' : 'grns'));
    $receiptUrl = $module === 'bc41' ? route($moduleRoute.'grns') : null;
    $partyField = $module === 'bc262' ? 'counterparty' : $primaryParty.'_id';
    $details = [
        ...(!$outgoing ? ['country_code'] : []),
        'hs_code',
        ...(in_array($module, ['bc20', 'bc40', 'bc41'], true) ? ['tax_invoice_number', 'tax_invoice_date'] : []),
        ...($module === 'bc25' ? ['item_per_unit'] : []),
        ...($module === 'bc27out' ? ['country_code'] : []),
    ];
    $summaryFields = [
        $documentReference.'_number', $documentReference.'_date', 'parent_code', 'sku_code',
        'name', 'color', 'size', 'unit', 'quantity', 'unit_price', 'total_price',
        ...($module === 'bc27out' || !$outgoing ? ['country_code'] : []),
    ];
    $labels = array_replace(__('bc23'), __($module));
    $label = fn (string $key) => $labels[$key] ?? $key;
@endphp

<div class="card"><div class="card-header d-flex justify-content-between"><h5 class="mb-0">{{ $label('form') }}</h5><a class="btn btn-outline-secondary btn-sm" href="{{ route($moduleRoute.'index') }}">{{ __('ui.common.back') }}</a></div><div class="card-body">
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form method="POST" action="{{ $document ? route($moduleRoute.'update',$document) : route($moduleRoute.'store') }}" id="bc-form">@csrf @if($document) @method('PUT') @endif
    <section aria-labelledby="bc-header-title">
        <h6 id="bc-header-title" class="text-primary mb-3">{{ $label('customs_document') }}</h6>
        <div class="row g-3 g-xl-4">
            <div class="col-xl-7">
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-md-4 col-form-label col-form-label-sm" for="submission_number">{{ $label('submission_number') }} *</label>
                    <div class="col-md-8">
                        <div class="input-group input-group-sm">
                            <input id="submission_number" name="submission_number" class="form-control form-control-sm" maxlength="100" value="{{ old('submission_number', $document?->submission_number) }}" required>
                            @can($module.'.update')
                                <button type="button" class="btn btn-outline-primary" id="bc-document-search" aria-haspopup="dialog" aria-controls="bc-document-picker">
                                    <i data-feather="search" class="icon-sm" aria-hidden="true"></i>
                                    {{ __('ui.common.search') }}
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
                @foreach(['bl_awb', 'document', 'invoice'] as $pair)
                    @php
                        $numberKey = $pair.'_number';
                        $dateKey = $pair.'_date';
                        $required = $pair === 'document';
                    @endphp
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="{{ $numberKey }}">{{ $label($numberKey) }} {{ $required ? '*' : '' }}</label>
                        <div class="col-md-8">
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <input id="{{ $numberKey }}" name="{{ $numberKey }}" class="form-control form-control-sm" maxlength="100" value="{{ old($numberKey, $document?->{$numberKey}) }}" @required($required)>
                                </div>
                                <div class="col-sm-6">
                                    <label class="visually-hidden" for="{{ $dateKey }}">{{ $label($dateKey) }}</label>
                                    <input id="{{ $dateKey }}" name="{{ $dateKey }}" class="form-control form-control-sm" type="date" value="{{ old($dateKey, $document?->{$dateKey}?->toDateString() ?? ($required ? now()->toDateString() : '')) }}" @required($required)>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="col-xl-5">
                @foreach($partyOptions as $kind => $parties)
                    @php
                        $field = $kind === $primaryParty ? $partyField : $kind.'_id';
                        $savedId = $field === 'counterparty'
                            ? ($document?->subcontractor_id ? 'x:'.$document->subcontractor_id : ($document?->supplier_id ? 's:'.$document->supplier_id : null))
                            : $document?->{$field};
                        $selectedId = old($field, $savedId);
                        $selectedParty = $parties->firstWhere('id', $selectedId);
                    @endphp
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="{{ $kind }}-picker-button">{{ $label($kind) }} {{ $kind === $primaryParty ? '*' : '' }}</label>
                        <div class="col-md-8">
                            <input type="hidden" id="{{ $kind }}" name="{{ $field }}" value="{{ $selectedId }}">
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary text-start flex-grow-1 text-wrap" id="{{ $kind }}-picker-button" data-bc-party="{{ $kind }}" aria-haspopup="dialog" aria-controls="bc-party-picker">
                                    <span id="{{ $kind }}-label">{{ $selectedParty?->name ?? __('bc23.choose_'.$kind) }}</span>
                                    <i data-feather="search" class="icon-sm ms-1" aria-hidden="true"></i>
                                </button>
                                @if($kind === $secondaryParty)
                                    <button type="button" class="btn btn-outline-secondary" id="clear-secondary-party" aria-label="{{ $label('clear_party') }}" title="{{ $label('clear_party') }}"><i data-feather="x" class="icon-sm" aria-hidden="true"></i></button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-md-4 col-form-label col-form-label-sm" for="price-type">{{ $label('price_type') }}</label>
                    <div class="col-md-8">
                        <select id="price-type" name="price_type" class="form-select form-select-sm">
                            <option value="">{{ $label('choose') }}</option>
                            @foreach(['CIF', 'CNF', 'FOB'] as $priceType)
                                <option value="{{ $priceType }}" @selected(old('price_type', $document?->price_type) === $priceType)>{{ $priceType }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-2 align-items-center mb-2">
                    <label class="col-md-4 col-form-label col-form-label-sm" for="currency">{{ $label('currency') }} *</label>
                    <div class="col-md-8">
                        <input id="currency" name="currency" class="form-control form-control-sm" maxlength="3" pattern="[A-Z]{3}" value="{{ old('currency', $document?->currency ?? (in_array($module, ['bc25', 'bc27out', 'bc41'], true) ? 'IDR' : 'USD')) }}" required>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-outline-primary btn-sm" name="intent" value="header" formnovalidate>{{ $label('save_header') }}</button>
                </div>
            </div>
        </div>
    </section>

    <section class="border-top mt-4 pt-3" aria-labelledby="bc-items-title">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 id="bc-items-title" class="text-primary mb-0">{{ $label('items') }}</h6>
            <button type="button" class="btn btn-outline-primary btn-sm" id="choose-product"><i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ $label('choose_product') }}</button>
        </div>
        <div id="bc-items"></div>
        <p class="small text-muted mb-0">{{ $label('notice') }}</p>
    </section>

    <div class="d-flex flex-wrap justify-content-end gap-2 border-top mt-3 pt-3">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route($moduleRoute.'index') }}">{{ __('ui.common.back') }}</a>
        @if($receiptUrl)
            <button type="button" class="btn btn-outline-primary btn-sm" id="choose-receipt">{{ $label('get_receipt') }}</button>
        @endif
        <button type="button" class="btn btn-outline-primary btn-sm" id="choose-grn">{{ $label('get_grn') }}</button>
        <button type="submit" class="btn btn-primary btn-sm" name="intent" value="all"><i data-feather="save" class="icon-sm me-1" aria-hidden="true"></i>{{ $label('save_all') }}</button>
    </div>
    <section class="border-top mt-4 pt-3" aria-labelledby="bc-list-title">
        <h6 id="bc-list-title" class="text-primary mb-3">{{ $label('item_list') }}</h6>
        <p class="small text-muted mb-0" id="bc-empty-items">{{ $label('no_items') }}</p>
        <div class="table-responsive" id="bc-summary-table" hidden>
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">{{ __('ui.common.no') }}</th>
                        @foreach($summaryFields as $field)
                            <th scope="col" class="text-nowrap">{{ $label($field) }}</th>
                        @endforeach
                        <th scope="col">{{ __('ui.common.action') }}</th>
                    </tr>
                </thead>
                <tbody id="bc-summary-items"></tbody>
                <tfoot class="table-light">
                    <tr>
                        <th scope="row" colspan="{{ array_search('total_price', $summaryFields, true) + 1 }}" class="text-end">{{ $label('total') }}</th>
                        <td class="text-end fw-semibold"><output id="bc-total">0</output></td>
                        <td colspan="{{ count($summaryFields) - array_search('total_price', $summaryFields, true) }}"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</form></div></div>

<template id="bc-summary-line">
    <tr>
        <td data-row-number></td>
        @foreach($summaryFields as $field)
            <td data-summary-field="{{ $field }}" class="{{ in_array($field, ['quantity', 'unit_price', 'total_price'], true) ? 'text-end text-nowrap' : '' }}"></td>
        @endforeach
        <td>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-outline-primary btn-sm" data-edit-line>{{ __('ui.common.edit') }}</button>
                <button type="button" class="btn btn-outline-danger btn-sm" data-remove-line>{{ $label('remove') }}</button>
            </div>
        </td>
    </tr>
</template>

<template id="bc-line-template">
    <div class="bc-line border rounded p-3 mb-3" hidden>
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <strong data-line-title></strong>
            <button type="button" class="btn btn-outline-danger btn-sm" data-close-line>{{ __('ui.common.close') }}</button>
        </div>
        <div class="row g-3 g-xl-4">
            @foreach([['parent_code', 'sku_code', 'name', 'color', 'size', 'unit', $documentReference.'_number', $documentReference.'_date'], ['quantity', 'unit_price', 'total_price', ...(!$outgoing && $module !== 'bc40' ? ['cif'] : []), ...$details]] as $fields)
                <div class="col-xl-6">
                    @foreach($fields as $key)
                        <div class="row g-2 align-items-center mb-2">
                            <label class="col-sm-5 col-form-label col-form-label-sm" data-line-label="{{ $key }}">{{ $label($key) }}</label>
                            <div class="col-sm-7">
                                <input class="form-control form-control-sm" data-field="{{ $key }}" @readonly(in_array($key, ['parent_code', 'sku_code', 'name', 'color', 'size', 'unit', 'total_price'], true))>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</template>

@can($module.'.update')
<div class="modal fade" id="bc-document-picker" tabindex="-1" aria-labelledby="bc-document-picker-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bc-document-picker-title">{{ $label('find_submission') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
            </div>
            <div class="modal-body">
                <form id="bc-document-dates" class="row g-2 align-items-end mb-3">
                    <div class="col-sm-auto">
                        <label class="form-label" for="bc-document-from">{{ $label('document_date') }}</label>
                        <input id="bc-document-from" type="date" required class="form-control form-control-sm" value="{{ now()->startOfMonth()->toDateString() }}">
                    </div>
                    <div class="col-sm-auto">
                        <label class="form-label" for="bc-document-to">{{ $label('to') }}</label>
                        <input id="bc-document-to" type="date" required class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-sm-auto">
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('ui.common.show_data') }}</button>
                    </div>
                </form>
                <table id="bc-document-table" class="table table-sm table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ui.common.action') }}</th>
                            @foreach(['submission_number', 'bl_awb_number', 'bl_awb_date', 'document_number', 'document_date', 'invoice_number', 'invoice_date', $primaryParty] as $key)
                                <th scope="col">{{ $label($key) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endcan

<div class="modal fade" id="bc-party-picker" tabindex="-1" aria-labelledby="bc-party-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bc-party-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="bc-party-table" class="table table-sm table-hover align-middle w-100">
                        <thead>
                            <tr>
                                <th scope="col" id="bc-party-reference"></th>
                                <th scope="col">{{ __('ui.common.name') }}</th>
                                <th scope="col">{{ __('ui.common.action') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <template id="bc-choose-party-action">
                    <button type="button" class="btn btn-primary btn-sm" data-select-party>{{ $label('select') }}</button>
                </template>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="bc-product-picker" tabindex="-1" aria-labelledby="bc-product-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bc-product-title">{{ $label('choose_product') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="bc-product-table" class="table table-sm table-hover align-middle w-100">
                        <thead>
                            <tr>
                                @foreach(['parent_code', 'sku_code', 'name', 'color', 'size'] as $key)
                                    <th scope="col">{{ $label($key) }}</th>
                                @endforeach
                                <th scope="col">{{ $label('unit') }}</th>
                                <th scope="col">{{ __('ui.common.action') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <template id="bc-choose-product-action">
                    <button type="button" class="btn btn-primary btn-sm" data-select-product>{{ $label('select') }}</button>
                </template>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="bc-picker" tabindex="-1" aria-labelledby="picker-title" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 id="picker-title" class="modal-title">{{ $label('get_grn') }}</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div><div class="modal-body"><form id="picker-search-form" class="d-flex gap-2 mb-3"><input id="picker-search" class="form-control form-control-sm" maxlength="100" aria-label="{{ $label('search') }}"><button class="btn btn-primary btn-sm">{{ __('ui.common.search') }}</button></form><p id="picker-status" class="small text-muted" role="status"></p><div class="table-responsive"><table class="table table-bordered"><thead><tr>@foreach([$documentReference.'_number','parent_code','sku_code','name','color','size','unit'] as $key)<th @if($loop->first) id="picker-source-number" @endif>{{ $label($key) }}</th>@endforeach<th>{{ __('ui.common.action') }}</th></tr></thead><tbody id="picker-results"></tbody></table></div></div></div></div></div>
@endsection
@push('scripts')
<script>
(() => {
    const t = @json($labels);
    @can($module.'.update')
    const documentPicker = document.getElementById('bc-document-picker');
    const documentSearch = document.getElementById('bc-document-search');
    const documentModal = new bootstrap.Modal(documentPicker);
    const from = document.getElementById('bc-document-from');
    const to = document.getElementById('bc-document-to');
    let documentTable;
    let documentDates = {from_date: from.value, to_date: to.value};
    documentSearch.addEventListener('click', () => documentModal.show(documentSearch));
    documentPicker.addEventListener('shown.bs.modal', () => {
        if (documentTable) {
            documentTable.columns.adjust();
            return;
        }
        const text = $.fn.dataTable.render.text();
        const date = (value, type) => {
            if (type !== 'display' || !value) return value;
            return value.slice(0, 10).split('-').reverse().join('-');
        };
        documentTable = $('#bc-document-table').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            lengthMenu: [10, 25, 50],
            searchDelay: 400,
            order: [[5, 'desc'], [1, 'desc']],
            ajax: {
                url: @json(route($moduleRoute.'documents')),
                data: data => Object.assign(data, documentDates),
            },
            columns: [
                {
                    data: 'edit_url', orderable: false, searchable: false,
                    createdCell(cell, url) {
                        const link = document.createElement('a');
                        link.className = 'btn btn-primary btn-sm';
                        link.href = url;
                        link.textContent = t.select;
                        cell.replaceChildren(link);
                    },
                },
                {data: 'submission_number', name: 'customs_documents.submission_number', render: text},
                {data: 'bl_awb_number', name: 'customs_documents.bl_awb_number', render: text, defaultContent: '—'},
                {data: 'bl_awb_date', name: 'customs_documents.bl_awb_date', render: date, defaultContent: '—', searchable: false},
                {data: 'document_number', name: 'customs_documents.document_number', render: text},
                {data: 'document_date', name: 'customs_documents.document_date', render: date, searchable: false},
                {data: 'invoice_number', name: 'customs_documents.invoice_number', render: text, defaultContent: '—'},
                {data: 'invoice_date', name: 'customs_documents.invoice_date', render: date, defaultContent: '—', searchable: false},
                {data: 'party_name', name: 'party_name', orderable: false, render: text, defaultContent: '—'},
            ],
            initComplete: function() {
                this.api().table().container().querySelector('input[type="search"]').maxLength = 100;
            },
        });
    });
    document.getElementById('bc-document-dates').addEventListener('submit', event => {
        event.preventDefault();
        if (from.value > to.value) {
            Swal.fire({icon: 'warning', text: t.invalid_range});
            return;
        }
        documentDates = {from_date: from.value, to_date: to.value};
        documentTable?.ajax.reload();
    });
    documentPicker.addEventListener('hidden.bs.modal', () => documentSearch.focus());
    @endcan

    const products = @json($products);
    const lines = @json($lines);
    const parties = @json($partyOptions);
    const primaryParty = @json($primaryParty);
    const secondaryParty = @json($secondaryParty);
    const partyField = @json($partyField);
    const documentReference = @json($documentReference);
    const receiptUrl = @json($receiptUrl);
    const requiredFields = [documentReference + '_number', documentReference + '_date', 'quantity', 'unit_price'];
    const items = document.getElementById('bc-items');
    const template = document.getElementById('bc-line-template');
    const summary = document.getElementById('bc-summary-items');
    const summaryTemplate = document.getElementById('bc-summary-line');
    const itemRows = new Map();
    const picker = document.getElementById('bc-picker');
    const modal = new bootstrap.Modal(picker);
    const partyPicker = document.getElementById('bc-party-picker');
    const partyModal = new bootstrap.Modal(partyPicker);
    const partyTableElement = $('#bc-party-table');
    const counterparty = document.getElementById(primaryParty);
    let usingReceipt = false;
    const numberFormat = new Intl.NumberFormat(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 4});
    let index = 0;
    let partyKind;
    let partyTrigger;
    let partyTable;
    let pending;

    function cell(row, value) {
        const element = row.insertCell();
        element.textContent = value ?? '';
        return element;
    }

    function selectButton(row, select) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-primary btn-sm';
        button.textContent = t.select;
        button.addEventListener('click', select);
        row.insertCell().append(button);
    }

    function setParty(kind, party) {
        document.getElementById(kind).value = party?.id ?? '';
        document.getElementById(kind + '-label').textContent = party?.name ?? t['choose_' + kind];
    }

    document.querySelectorAll('[data-bc-party]').forEach(button => {
        button.addEventListener('click', () => {
            partyTrigger = button;
            partyKind = button.dataset.bcParty;
            document.getElementById('bc-party-title').textContent = t['choose_' + partyKind];
            document.getElementById('bc-party-reference').textContent = partyKind === secondaryParty ? t.importer_tax_number : t.party_code;
            partyModal.show(button);
        });
    });

    partyPicker.addEventListener('shown.bs.modal', () => {
        const referenceField = partyKind === secondaryParty ? 'tax_number' : 'code';
        const rows = parties[partyKind].map(party => ({
            id: party.id,
            name: party.name,
            reference: party[referenceField] ?? '—',
        }));

        if (partyTable) {
            partyTable.clear().rows.add(rows).search('').order([[1, 'asc']]).draw();
            partyTable.columns.adjust();
        } else {
            const text = $.fn.dataTable.render.text();
            partyTable = partyTableElement.DataTable({
                data: rows,
                lengthMenu: [10, 25, 50],
                pageLength: 10,
                order: [[1, 'asc']],
                columns: [
                    {data: 'reference', render: text},
                    {data: 'name', render: text},
                    {data: null, orderable: false, searchable: false, defaultContent: document.getElementById('bc-choose-party-action').innerHTML},
                ],
            });
        }

        partyTable.table().container().querySelector('input[type="search"]').focus();
    });
    partyTableElement.on('click', '[data-select-party]', function() {
        const party = partyTable.row(this.closest('tr')).data();
        setParty(partyKind, party);
        partyModal.hide();
    });
    partyPicker.addEventListener('hidden.bs.modal', () => partyTrigger?.focus());
    document.getElementById('clear-secondary-party')?.addEventListener('click', () => setParty(secondaryParty, null));

    function totals() {
        let total = 0;
        for (const [line, row] of itemRows) {
            const qty = Number(line.querySelector('[data-field="quantity"]').value) || 0;
            const price = Number(line.querySelector('[data-field="unit_price"]').value) || 0;
            const value = Math.round((qty * price + Number.EPSILON) * 1e4) / 1e4;
            line.querySelector('[data-field="total_price"]').value = numberFormat.format(value);
            for (const cell of row.querySelectorAll('[data-summary-field]')) {
                const key = cell.dataset.summaryField;
                const inputValue = line.querySelector(`[data-field="${key}"]`).value;
                cell.textContent = ['quantity', 'unit_price'].includes(key) && inputValue !== ''
                    ? numberFormat.format(Number(inputValue))
                    : inputValue || '—';
            }
            total += value;
        }
        document.getElementById('bc-total').value = numberFormat.format(total);
        document.getElementById('bc-empty-items').hidden = itemRows.size > 0;
        document.getElementById('bc-summary-table').hidden = itemRows.size === 0;
    }

    function openLine(card, focus = false) {
        for (const line of itemRows.keys()) line.hidden = line !== card;
        if (focus) (card.querySelector('input[required]:not([readonly])') ?? card.querySelector('[data-close-line]')).focus();
    }

    function removeLine(card, row) {
        itemRows.delete(card);
        card.remove();
        row.remove();
        [...summary.rows].forEach((remaining, i) => {
            remaining.querySelector('[data-row-number]').textContent = i + 1;
        });
        totals();
    }

    function add(product, line = {}, openEditor = true) {
        const card = template.content.firstElementChild.cloneNode(true);
        const i = index++;
        const source = {product_id: product.id};
        if (documentReference === 'shipment') {
            source.goods_sent_note_item_id = line.goods_sent_note_item_id ?? '';
        }
        if (documentReference === 'receipt' || receiptUrl) {
            source.goods_receipt_item_id = line.goods_receipt_item_id ?? '';
            source.equipment_receipt_item_id = line.equipment_receipt_item_id ?? '';
        }
        if (partyField === 'counterparty') {
            source.subcontract_receipt_item_id = line.subcontract_receipt_item_id ?? '';
        }
        if (receiptUrl) {
            source.receipt_number = line.receipt_number ?? '';
            source.receipt_date = line.receipt_date ?? '';
        }
        for (const [key, value] of Object.entries(source)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `items[${i}][${key}]`;
            input.value = value;
            card.append(input);
        }
        card.querySelector('[data-line-title]').textContent = product.code;
        const display = {
            parent_code: product.parent_code,
            sku_code: product.sku_code,
            name: product.name,
            color: line.color ?? product.color,
            size: line.size ?? product.size,
            unit: product.unit,
            total_price: '',
        };
        for (const input of card.querySelectorAll('[data-field]')) {
            const key = input.dataset.field;
            input.id = `bc-item-${i}-${key}`;
            card.querySelector(`[data-line-label="${key}"]`).htmlFor = input.id;
            if (key in display) {
                input.value = display[key] ?? '—';
                continue;
            }

            input.name = `items[${i}][${key}]`;
            input.required = requiredFields.includes(key);
            if (key.endsWith('_date')) {
                input.type = 'date';
            } else if (key === 'item_per_unit') {
                input.type = 'number';
                input.step = '1';
                input.min = '0';
                input.max = '2147483647';
            } else if (['quantity', 'unit_price', 'cif'].includes(key)) {
                input.type = 'number';
                input.step = '0.0001';
                input.min = key === 'quantity' ? '0.0001' : '0';
                input.max = key === 'cif' ? '999999999999999.9999' : '999999999.9999';
            } else {
                input.maxLength = {country_code: 3, hs_code: 30}[key] ?? 100;
            }
            input.value = line[key] ?? (key === 'unit_price' ? '0' : '');
            const linked = documentReference === 'shipment'
                ? source.goods_sent_note_item_id
                : source.goods_receipt_item_id || source.equipment_receipt_item_id || source.subcontract_receipt_item_id;
            if (linked && [documentReference + '_number', documentReference + '_date'].includes(key)) {
                input.readOnly = true;
            }
        }
        const row = summaryTemplate.content.firstElementChild.cloneNode(true);
        row.querySelector('[data-row-number]').textContent = itemRows.size + 1;
        row.querySelector('[data-edit-line]').addEventListener('click', () => openLine(card, true));
        row.querySelector('[data-remove-line]').addEventListener('click', () => removeLine(card, row));
        card.querySelector('[data-close-line]').addEventListener('click', () => {
            card.hidden = true;
            row.querySelector('[data-edit-line]').focus();
        });
        itemRows.set(card, row);
        items.append(card);
        summary.append(row);
        if (openEditor) openLine(card);
    }

    for (const line of lines) {
        const product = products[line.product_id];
        if (product) add(product, line, false);
    }
    items.addEventListener('input', totals);
    let firstInvalid = true;
    items.addEventListener('invalid', event => {
        if (!firstInvalid) return;
        firstInvalid = false;
        openLine(event.target.closest('.bc-line'));
        setTimeout(() => { firstInvalid = true; }, 0);
    }, true);
    totals();

    async function search() {
        pending?.abort();
        pending = new AbortController();
        const result = document.getElementById('picker-results');
        const status = document.getElementById('picker-status');
        result.replaceChildren();
        status.textContent = t.loading;
        const url = new URL(usingReceipt ? receiptUrl : @json($sourceUrl));
        url.searchParams.set('search', document.getElementById('picker-search').value);
        if (!usingReceipt) url.searchParams.set(partyField, counterparty.value);
        try {
            const response = await fetch(url, {headers: {Accept: 'application/json'}, signal: pending.signal});
            if (!response.ok) throw new Error();
            const data = await response.json();
            status.textContent = data.length ? t.limit_notice : t.empty;
            for (const product of data) {
                const row = result.insertRow();
                cell(row, product.shipment_number ?? product.receipt_number);
                cell(row, product.parent_code ?? '—');
                cell(row, product.sku_code ?? '—');
                cell(row, product.name);
                cell(row, product.color ?? '—');
                cell(row, product.size ?? '—');
                cell(row, product.unit);
                selectButton(row, () => {
                    add(product, product);
                    totals();
                    modal.hide();
                });
            }
        } catch (error) {
            if (error.name !== 'AbortError') status.textContent = t.load_failed;
        }
    }

    const productPicker = document.getElementById('bc-product-picker');
    const productModal = new bootstrap.Modal(productPicker);
    const chooseProduct = document.getElementById('choose-product');
    const productTableElement = $('#bc-product-table');
    let productTable;

    chooseProduct.addEventListener('click', () => productModal.show(chooseProduct));
    productPicker.addEventListener('shown.bs.modal', () => {
        if (productTable) {
            productTable.columns.adjust();
            return;
        }

        const text = $.fn.dataTable.render.text();
        productTable = productTableElement.DataTable({
            processing: true,
            serverSide: true,
            lengthMenu: [10, 25, 50],
            pageLength: 10,
            searchDelay: 350,
            order: [[2, 'asc']],
            ajax: @json(route($moduleRoute.'products')),
            columns: [
                {data: 'parent_code', orderable: false, defaultContent: '—', render: text},
                {data: 'sku_code', name: 'code', orderable: false, defaultContent: '—', render: text},
                {data: 'name', render: text},
                {data: 'color', defaultContent: '—', render: text},
                {data: 'size', defaultContent: '—', render: text},
                {data: 'unit_label', orderable: false, searchable: false, defaultContent: '—', render: text},
                {data: null, orderable: false, searchable: false, defaultContent: document.getElementById('bc-choose-product-action').innerHTML},
            ],
            initComplete: function() {
                this.api().table().container().querySelector('input[type="search"]').maxLength = 100;
            },
        });
    });
    productTableElement.on('click', '[data-select-product]', function() {
        const product = productTable.row(this.closest('tr')).data();
        add({...product, unit: product.unit_label});
        totals();
        productModal.hide();
    });
    productPicker.addEventListener('hidden.bs.modal', () => chooseProduct.focus());

    document.getElementById('choose-grn').addEventListener('click', () => {
        if (!counterparty.value) {
            Swal.fire({icon: 'warning', text: t[primaryParty + '_required']});
            return;
        }
        openSourcePicker(false);
    });
    document.getElementById('choose-receipt')?.addEventListener('click', () => openSourcePicker(true));

    function openSourcePicker(receipt) {
        usingReceipt = receipt;
        document.getElementById('picker-title').textContent = receipt ? t.get_receipt : t.get_grn;
        document.getElementById('picker-source-number').textContent = t[(receipt ? 'receipt' : documentReference) + '_number'];
        document.getElementById('picker-search').value = '';
        modal.show();
        search();
    }
    document.getElementById('picker-search-form').addEventListener('submit', event => {
        event.preventDefault();
        search();
    });
    picker.addEventListener('hidden.bs.modal', () => pending?.abort());
    document.getElementById('bc-form').addEventListener('submit', event => {
        if (!counterparty.value) {
            event.preventDefault();
            Swal.fire({icon: 'warning', text: t['choose_' + primaryParty]});
        } else if (event.submitter?.value === 'all' && !items.children.length) {
            event.preventDefault();
            Swal.fire({icon: 'warning', text: t.items_required});
        }
    });
})();
</script>
@endpush
