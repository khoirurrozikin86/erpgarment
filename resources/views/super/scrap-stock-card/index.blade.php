@extends('layouts.admin')

@section('title', __('ui.scrap_stock_card.title'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">{{ __('ui.nav.scrap_materials') }}</a></li>
        <li class="breadcrumb-item active">{{ __('ui.scrap_stock_card.title') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="card">
        <div class="card-header bg-primary text-white">{{ __('ui.scrap_stock_card.title') }}</div>
        <div class="card-body">
            <div class="table-responsive"><table id="scrapProductsTable" class="table table-bordered table-hover align-middle w-100">
                <thead><tr>
                    <th>{{ __('ui.common.no') }}</th><th>{{ __('ui.scrap_stock_card.choose') }}</th>
                    <th>{{ __('ui.scrap_stock_card.group') }}</th><th>{{ __('ui.common.category') }}</th>
                    <th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.unit') }}</th>
                </tr></thead>
            </table></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#scrapProductsTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: @json(route('super.scrap-stock-card.products.dt')),
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'actions', orderable: false, searchable: false },
            { data: 'group_label', orderable: false, searchable: false },
            { data: 'category_label', orderable: false, searchable: false },
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { data: 'unit_label', orderable: false, searchable: false }
        ],
        order: [[4, 'asc']],
        drawCallback: function () { if (window.feather) feather.replace(); }
    });
});
</script>
@endpush
