@extends('layouts.admin')

@section('title', __('ui.machine_stock_card.title'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">{{ __('ui.nav.machines') }}</a></li>
        <li class="breadcrumb-item active">{{ __('ui.machine_stock_card.title') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="card">
        <div class="card-header bg-primary text-white">{{ __('ui.machine_stock_card.title') }}</div>
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('super.equipment-stock-card.export') }}" class="btn btn-sm btn-outline-success"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>
            </div>
            <div class="table-responsive"><table id="equipmentProductsTable" class="table table-bordered table-hover align-middle w-100">
                <thead><tr>
                    <th>{{ __('ui.common.no') }}</th><th>{{ __('ui.machine_stock_card.choose') }}</th>
                    <th>{{ __('ui.common.item_code') }}</th><th>{{ __('ui.common.item_name') }}</th><th>{{ __('ui.common.unit') }}</th>
                </tr></thead>
            </table></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#equipmentProductsTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: @json(route('super.equipment-stock-card.products.dt')),
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'actions', orderable: false, searchable: false },
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { data: 'unit_label', orderable: false, searchable: false }
        ],
        order: [[2, 'asc']],
        drawCallback: function () { if (window.feather) feather.replace(); }
    });
});
</script>
@endpush
