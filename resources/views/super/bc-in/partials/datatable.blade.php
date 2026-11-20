@php
    $outgoing = ($direction ?? 'bc-in') === 'bc-out';
    $documentFields = ['submission_number', 'document_number', 'document_date', $outgoing ? 'customer' : 'supplier', 'items_count', 'total_price', 'currency'];
    $detailFields = [
        ...(!$outgoing || $module === 'bc41' ? ['receipt_number', 'receipt_date'] : []),
        ...($outgoing ? ['shipment_number', 'shipment_date'] : []),
        'parent_code', 'sku_code', 'item_name', 'color', 'size', 'unit', 'quantity', 'unit_price', 'total_price',
        ...(!$outgoing && $module !== 'bc40' ? ['cif'] : []),
        ...(!$outgoing || $module === 'bc27out' ? ['country_code'] : []),
        'hs_code',
        ...(in_array($module, ['bc20', 'bc40', 'bc41'], true) ? ['tax_invoice_number', 'tax_invoice_date'] : []),
        ...($module === 'bc25' ? ['item_per_unit'] : []),
    ];
    $labels = array_replace(__('bc23'), __($module));
@endphp
<table id="bc-in-table" class="table table-bordered table-hover align-middle w-100">
    <thead>
        <tr>
            <th scope="col">{{ __('ui.common.no') }}</th>
            <th scope="col">{{ __('ui.common.action') }}</th>
            @foreach($documentFields as $field)
                <th scope="col">{{ $labels[$field] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody></tbody>
</table>

<div class="modal fade" id="bc-detail-modal" tabindex="-1" aria-labelledby="bc-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="bc-detail-title">{{ $labels['item_list'] }}</h5>
                    <span class="text-muted" id="bc-detail-document"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
            </div>
            <div class="modal-body">
                <p role="status" id="bc-detail-status" class="small text-muted"></p>
                <div class="table-responsive" id="bc-detail-table" hidden>
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">{{ __('ui.common.no') }}</th>
                                @foreach($detailFields as $field)
                                    <th scope="col" class="text-nowrap">{{ $labels[$field === 'item_name' ? 'name' : $field] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id="bc-detail-items"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    const fields = @json($documentFields);
    const detailFields = @json($detailFields);
    const modalElement = document.getElementById('bc-detail-modal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const detailBody = document.getElementById('bc-detail-items');
    const detailStatus = document.getElementById('bc-detail-status');
    const detailTitle = document.getElementById('bc-detail-document');
    const detailTable = document.getElementById('bc-detail-table');
    const amount = new Intl.NumberFormat('id-ID', {minimumFractionDigits: 4, maximumFractionDigits: 4});
    let pending;
    let trigger;

    function display(field, value) {
        if (value === null || value === undefined || value === '') return '—';
        if (['quantity', 'unit_price', 'total_price', 'cif'].includes(field)) return amount.format(Number(value));
        if (field.endsWith('_date') && /^\d{4}-\d{2}-\d{2}/.test(value)) return value.slice(0, 10).split('-').reverse().join('-');
        return value;
    }
    const aliases = {supplier: 'supplier_name', customer: 'customer_name', code: 'item_code', name: 'item_name'};
    const text = $.fn.dataTable.render.text();
    const table = $('#bc-in-table').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        searchDelay: 500,
        search: {search: @json(is_string(request('search')) ? request('search') : '')},
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[4, 'desc']],
        ajax: {
            url: @json(route('super.'.($direction ?? 'bc-in').'.'.$module.'.index')),
            data: function (data) {
                data.from_date = document.getElementById('from').value;
                data.to_date = document.getElementById('to').value;
            }
        },
        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'actions', orderable: false, searchable: false},
            ...fields.map(field => ({
                data: aliases[field] || field,
                name: aliases[field] || field,
                defaultContent: '—',
                className: ['items_count', 'total_price'].includes(field) ? 'text-end' : '',
                render: (value, type) => type === 'display' ? text.display(display(field, value)) : value,
            }))
        ],
        drawCallback: function () { if (window.feather) feather.replace(); }
    });
    $('#bc-in-table').on('click', '[data-bc-detail]', async function () {
        pending?.abort();
        const request = new AbortController();
        pending = request;
        trigger = this;
        detailTitle.textContent = '';
        detailBody.replaceChildren();
        detailTable.hidden = true;
        detailStatus.textContent = @json(__('bc23.loading'));
        modal.show(this);

        try {
            const response = await fetch(this.dataset.bcDetail, {headers: {Accept: 'application/json'}, signal: request.signal});
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (request.signal.aborted) return;

            detailTitle.textContent = [data.submission_number, data.document_number].filter(Boolean).join(' · ');
            for (const [index, item] of data.items.entries()) {
                const row = detailBody.insertRow();
                row.insertCell().textContent = index + 1;
                for (const field of detailFields) {
                    const cell = row.insertCell();
                    cell.textContent = display(field, item[field]);
                    if (['quantity', 'unit_price', 'total_price', 'cif'].includes(field)) cell.className = 'text-end text-nowrap';
                }
            }
            detailTable.hidden = data.items.length === 0;
            detailStatus.textContent = data.items.length ? '' : @json(__('bc23.no_items'));
        } catch (error) {
            if (!request.signal.aborted) detailStatus.textContent = @json(__('bc23.load_failed'));
        }
    });
    modalElement.addEventListener('hidden.bs.modal', () => {
        pending?.abort();
        trigger?.focus();
    });

    table.on('search.dt', function () {
        document.getElementById('bc-export-search').value = table.search();
    });
    document.getElementById('bc-export-search').value = table.search();
    // Delegation keeps confirmation working after pagination and Ajax redraws.
    $('#bc-in-table').on('submit', '.delete-bc', function (event) {
        if (!confirm(@json(__($module.'.confirm_delete')))) event.preventDefault();
    });
});
</script>
@endpush
