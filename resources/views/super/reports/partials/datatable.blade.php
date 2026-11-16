@push('scripts')
<script>
$(function () {
    const sortable = @json(array_map(fn ($column) => $column !== null, \App\Domain\Customs\Queries\BcReportDataTable::columns($reportKey)));
    const table = $('#bc-report-table').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        searchDelay: 500,
        search: {search: @json(is_string(request('search')) ? request('search') : '')},
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [],
        ajax: {
            url: @json(url()->current()),
            data: function (data) {
                data.from_date = document.getElementById('from_date').value;
                data.to_date = document.getElementById('to_date').value;
            }
        },
        columns: sortable.map((orderable, index) => ({data: 'cells.' + index, orderable: orderable})),
        createdRow: function (row, data) {
            data.classes.forEach((className, index) => { row.cells[index].className = className; });
        }
    });
    const exportSearch = document.getElementById('report-export-search');
    table.on('search.dt', function () { exportSearch.value = table.search(); });
    exportSearch.value = table.search();
});
</script>
@endpush
