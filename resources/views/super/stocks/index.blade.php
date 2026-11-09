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
            <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.category') }}</th><th>{{ __('ui.common.unit') }}</th><th class="text-end">{{ __('ui.workflow.stock_balance') }}</th><th class="text-end">{{ __('ui.workflow.reorder_point') }}</th><th>{{ __('ui.common.status') }}</th></tr></thead>
        </table>
    </div></div></div>
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
                    { data: 'stock_status', orderable: false, searchable: false }
                ],
                order: [[2, 'asc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });
        });
    </script>
@endpush
