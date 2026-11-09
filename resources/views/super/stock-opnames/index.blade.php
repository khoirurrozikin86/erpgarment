@extends('layouts.admin')

@section('title', __( 'ui.common.stock_opname' ))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">{{ __('ui.common.inventory') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.stock_opname') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h4 class="page-title mb-1">{{ __('ui.common.stock_opname') }}</h4><span class="text-muted">{{ __('ui.workflow.stocktake_subtitle') }}</span></div>
            @can('stock-opnames.create')
                <a href="{{ route('super.stock-opnames.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.common.create_opname') }}</a>
            @endcan
        </div>
        <div class="col-12">
            @include('super.purchasing.date-filter')
            <div class="card"><div class="card-body"><div class="table-responsive">
                <table id="stock-opnames-table" class="table table-bordered table-hover align-middle w-100">
                    <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.workflow.stocktake_number') }}</th><th>{{ __('ui.common.start_time') }}</th><th>{{ __('ui.common.item_count') }}</th><th>{{ __('ui.common.status') }}</th><th>{{ __('ui.common.created_by') }}</th><th>{{ __('ui.common.counted_by') }}</th><th>{{ __('ui.common.posted_by') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead>
                </table>
            </div></div></div>
        </div>
    </div>

    <div class="modal fade" id="viewStockOpnameModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewStockOpnameTitle">{{ __('ui.workflow.stocktake_detail') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body" id="viewStockOpnameBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const modal = new bootstrap.Modal(document.getElementById('viewStockOpnameModal'));
            const safe = value => $('<div>').text(value ?? '').html();
            const table = $('#stock-opnames-table').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: {
                    url: @json(route('super.stock-opnames.dt')),
                    data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'number', name: 'number' },
                    { data: 'counted_at', name: 'counted_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
                    { data: 'items_count', searchable: false },
                    { data: 'status_label', name: 'status', searchable: false },
                    { data: 'starter_name', orderable: false, searchable: false },
                    { data: 'counter_name', orderable: false, searchable: false },
                    { data: 'poster_name', orderable: false, searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[2, 'desc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            $('#applyDateFilter').on('click', () => table.ajax.reload());
            $('#todayDateFilter').on('click', () => {
                const today = @json(now()->toDateString());
                $('#from_date, #to_date').val(today);
                table.ajax.reload();
            });

            $(document).on('click', '.btn-view-stock-opname', function() {
                $.get($(this).data('url')).done(function(opname) {
                    const rows = opname.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.name)}</td><td class="text-end">${item.system_quantity} ${safe(item.unit)}</td><td class="text-end">${item.counted_quantity} ${safe(item.unit)}</td><td class="text-end">${item.difference}</td></tr>`).join('');
                    $('#viewStockOpnameTitle').text(`{{ __('ui.workflow.stocktake_name') }} ${opname.number}`);
                    const statusLabels = { draft: '{{ __('ui.workflow.draft') }}', counting: '{{ __('ui.workflow.counting') }}', posted: '{{ __('ui.workflow.posted') }}' };
                    $('#viewStockOpnameBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-3">{{ __('ui.common.start_time') }}</dt><dd class="col-sm-9">${safe(opname.counted_at)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.status') }}</dt><dd class="col-sm-9">${statusLabels[opname.status]}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.created_by') }}</dt><dd class="col-sm-9">${safe(opname.starter)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.counted_by') }}</dt><dd class="col-sm-9">${safe(opname.counter)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.posted_by') }}</dt><dd class="col-sm-9">${safe(opname.poster)} (${safe(opname.posted_at)})</dd>
                        <dt class="col-sm-3">{{ __('ui.common.notes') }}</dt><dd class="col-sm-9 text-break">${safe(opname.notes)}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.workflow.system_balance') }}</th><th class="text-end">{{ __('ui.workflow.physical_count') }}</th><th class="text-end">{{ __('ui.common.difference') }}</th></tr></thead><tbody>${rows}</tbody></table></div>`);
                    modal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.stocktake_detail_load_failed') }}' }));
            });

            $(document).on('click', '.btn-post-stock-opname', function() {
                const button = $(this);
                Swal.fire({
                    icon: 'warning', title: `Posting ${button.data('number')}?`,
                    text: '{{ __('ui.workflow.post_stock_difference_confirmation') }}',
                    showCancelButton: true, confirmButtonText: '{{ __('ui.workflow.posting') }}', cancelButtonText: '{{ __('ui.common.cancel') }}'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: button.data('url'), method: 'POST',
                        data: { _token: @json(csrf_token()), _method: 'PUT' },
                        success: response => { Swal.fire({ icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message }); table.ajax.reload(null, false); },
                        error: xhr => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: xhr.responseJSON?.message || '{{ __('ui.workflow.stocktake_post_failed') }}' })
                    });
                });
            });
        });
    </script>
@endpush
