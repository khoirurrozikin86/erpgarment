@extends('layouts.admin')

@section('title', __('ui.scrap_stock_card.title'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('super.scrap-stock-card.index') }}">{{ __('ui.scrap_stock_card.title') }}</a></li>
        <li class="breadcrumb-item active">{{ $product->code }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span>{{ __('ui.scrap_stock_card.title') }} : [{{ $product->code }}] {{ $product->name }}</span>
            <a href="{{ route('super.scrap-stock-card.index') }}" class="btn btn-sm btn-light"><i data-feather="check-square" class="icon-sm me-1"></i>{{ __('ui.common.back') }}</a>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('super.scrap-stock-card.movements.export', $product) }}" class="btn btn-sm btn-outline-success"><i data-feather="download" class="icon-sm me-1"></i>{{ __('ui.common.export_excel') }}</a>
            </div>
            <div class="table-responsive"><table id="scrapMovementsTable" class="table table-bordered table-hover align-middle w-100">
                <thead><tr>
                    <th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.date') }}</th><th>{{ __('ui.workflow.document_number') }}</th>
                    <th>{{ __('ui.common.description') }}</th><th class="text-end">{{ __('ui.common.quantity') }}</th>
                    <th>(+/-)</th><th class="text-end">{{ __('ui.common.balance') }}</th><th>{{ __('ui.common.recorded_by') }}</th>
                </tr></thead>
            </table></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#scrapMovementsTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        ajax: @json(route('super.scrap-stock-card.movements.dt', $product)),
        columns: [
            { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'transaction_date', orderable: false, searchable: false },
            { data: 'document_number', orderable: false, searchable: false },
            { data: 'description', orderable: false, searchable: false },
            { data: 'amount', orderable: false, searchable: false, className: 'text-end' },
            { data: 'direction', orderable: false, searchable: false, className: 'text-center' },
            { data: 'balance', orderable: false, searchable: false, className: 'text-end fw-semibold' },
            { data: 'operator', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        drawCallback: function () { if (window.feather) feather.replace(); }
    });
});
</script>
@endpush
