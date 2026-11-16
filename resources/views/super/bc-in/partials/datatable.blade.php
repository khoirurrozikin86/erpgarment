@push('scripts')
<script>
$(function () {
    const fields = @json($fields);
    const names = {
        submission_number: 'd.submission_number', document_number: 'd.document_number',
        document_date: 'd.document_date', receipt_number: 'i.receipt_number', receipt_date: 'i.receipt_date',
        shipment_number: 'i.shipment_number', shipment_date: 'i.shipment_date', customer: 's.name',
        supplier: 's.name', code: 'i.item_code', name: 'i.item_name', quantity: 'i.quantity', unit: 'i.unit',
        total_price: 'i.total_price', currency: 'd.currency', hs_code: 'i.hs_code',
        invoice_number: 'd.invoice_number', invoice_date: 'd.invoice_date',
        tax_invoice_number: 'i.tax_invoice_number', tax_invoice_date: 'i.tax_invoice_date'
    };
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
                name: names[field],
                defaultContent: '—',
                className: ['quantity', 'total_price'].includes(field) ? 'text-end' : '',
                render: function (value, type) {
                    if (type !== 'display') return value;
                    if (value === null || value === '') return '—';
                    if (['quantity', 'total_price'].includes(field)) {
                        return Number(value).toLocaleString('id-ID', {minimumFractionDigits: 4, maximumFractionDigits: 4});
                    }
                    if (field.endsWith('_date') && /^\d{4}-\d{2}-\d{2}/.test(value)) {
                        return value.slice(0, 10).split('-').reverse().join('-');
                    }
                    return text.display(value);
                }
            }))
        ],
        drawCallback: function () { if (window.feather) feather.replace(); }
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
