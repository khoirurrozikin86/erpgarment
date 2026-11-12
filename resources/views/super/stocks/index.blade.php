@extends('layouts.admin')

@section('title', __( 'ui.common.item' ))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">{{ __('ui.common.inventory') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.item') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="page-title mb-1">{{ __('ui.common.item') }}</h4><span class="text-muted">{{ __('ui.workflow.stock_balance_subtitle') }}</span></div>
        <div class="d-flex gap-2">
            <a href="{{ route('super.stocks.export') }}" class="btn btn-outline-success btn-sm"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>
            @can('stock-opnames.create')
                <a href="{{ route('super.stock-opnames.create') }}" class="btn btn-primary btn-sm"><i data-feather="clipboard" class="icon-sm me-1"></i>{{ __('ui.common.create_stock_count') }}</a>
            @endcan
        </div>
    </div>

    <div class="card"><div class="card-body"><div class="table-responsive">
        <table id="stock-balances-table" class="table table-bordered table-hover align-middle w-100">
            <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.category') }}</th><th>{{ __('ui.common.unit') }}</th><th class="text-end">{{ __('ui.workflow.stock_balance') }}</th><th class="text-end">{{ __('ui.workflow.reorder_point') }}</th><th>{{ __('ui.common.status') }}</th><th>{{ __('ui.workflow.lot_roll') }}</th></tr></thead>
        </table>
    </div></div></div>

    <div class="modal fade" id="itemLotsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="itemLotsTitle">{{ __('ui.workflow.lot_balances') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body"><div class="table-responsive"><table class="table table-bordered table-sm"><thead><tr><th>{{ __('ui.workflow.lot_number') }}</th><th>{{ __('ui.common.supplier') }}</th><th>{{ __('ui.workflow.customs_document_number') }}</th><th>{{ __('ui.common.date') }}</th><th class="text-end">{{ __('ui.workflow.initial_quantity') }}</th><th class="text-end">{{ __('ui.workflow.current_quantity') }}</th></tr></thead><tbody id="itemLotsBody"></tbody></table></div></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#stock-balances-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: @json(route('super.stocks.dt')),
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    {
                        data: 'code', name: 'code',
                        render: (data, type, row) => {
                            if (type !== 'display') return data;
                            const code = $('<div>').text(data ?? '').html();
                            return `<a href="${@json(route('super.stock-card.index'))}?product_id=${encodeURIComponent(row.id)}" title="{{ __('ui.workflow.stock_card_link') }}">${code}</a>`;
                        }
                    },
                    { data: 'name', name: 'name' },
                    { data: 'category_name', name: 'category.name', orderable: false },
                    { data: 'unit_label', orderable: false, searchable: false },
                    { data: 'stock_quantity', orderable: false, searchable: false, className: 'text-end', render: data => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(data ?? 0) },
                    { data: 'reorder_point', name: 'reorder_point', className: 'text-end', render: (data, type, row) => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(Number(data) > 0 ? data : (row.min_stock || 0)) },
                    { data: 'stock_status', orderable: false, searchable: false },
                    { data: 'lot_action', orderable: false, searchable: false }
                ],
                order: [[2, 'asc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            $(document).on('click', '.view-item-lots', function() {
                const button = $(this);
                const url = @json(route('super.stocks.lots', ['product' => '__ID__'])).replace('__ID__', button.data('id'));
                $('#itemLotsTitle').text(`{{ __('ui.workflow.lot_balances') }} — ${button.data('name')}`);
                $('#itemLotsBody').html(`<tr><td colspan="6" class="text-center">{{ __('ui.workflow.loading_lots') }}</td></tr>`);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('itemLotsModal')).show();
                $.get(url).done(lots => {
                    const rows = lots.map(lot => `<tr><td>${$('<div>').text(lot.lot_number).html()}</td><td>${$('<div>').text(lot.supplier).html()}</td><td>${$('<div>').text(lot.customs_documents).html()}</td><td>${$('<div>').text(lot.received_at).html()}</td><td class="text-end">${lot.initial_quantity}</td><td class="text-end">${lot.current_quantity}</td></tr>`).join('');
                    $('#itemLotsBody').html(rows || `<tr><td colspan="6" class="text-center text-muted">{{ __('ui.workflow.no_open_lots') }}</td></tr>`);
                }).fail(() => $('#itemLotsBody').html(`<tr><td colspan="6" class="text-center text-danger">{{ __('ui.workflow.lots_load_failed') }}</td></tr>`));
            });
        });
    </script>
@endpush
