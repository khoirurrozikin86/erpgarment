@push('scripts')
<script>
$(function () {
    function createTable(selector, sortable, details) {
        return $(selector).DataTable({
            processing: true, serverSide: true, scrollX: true, searchDelay: 500,
            lengthMenu: [10, 25, 50, 100], pageLength: 10, order: [],
            ajax: {
                url: @json(url()->current()),
                data: function (data) {
                    data.from_date = document.getElementById(@json($dateFromId)).value;
                    data.to_date = document.getElementById(@json($dateToId)).value;
                    if (details) {
                        data.table = 'details';
                        data.contract_id = @json(request('contract_id'));
                        data.direction = @json(request('direction', 'issue'));
                    }
                }
            },
            columns: sortable.map((column, index) => ({data: 'cells.' + index, orderable: column !== null})),
            createdRow: function (row, data) {
                data.classes.forEach((className, index) => { row.cells[index].className = className; });
            },
            drawCallback: function () { if (window.feather) feather.replace(); }
        });
    }
    if (document.getElementById('subcontract-list-table')) {
        const table = createTable('#subcontract-list-table', @json(\App\Domain\Subcontract\Queries\SubcontractDataTable::columns($tableKind)), false);
        const exportSearch = document.getElementById('subcontract-export-search');
        if (exportSearch) table.on('search.dt', () => { exportSearch.value = table.search(); });
    }
    if (document.getElementById('subcontract-detail-table')) {
        createTable('#subcontract-detail-table', @json(\App\Domain\Subcontract\Queries\SubcontractDataTable::columns('goods-details')), true);
    }
    @isset($translation)
    $('#subcontract-list-table').on('submit', '.delete-issue, .confirm-issue', function (event) {
        const confirmation = this.matches('.delete-issue') ? @json(__($translation.'.confirm_delete')) : @json(__($translation.'.confirm_prompt'));
        if (!confirm(confirmation)) { event.preventDefault(); return; }
        this.querySelector('button').disabled = true;
    });
    @endisset
});
</script>
@endpush
