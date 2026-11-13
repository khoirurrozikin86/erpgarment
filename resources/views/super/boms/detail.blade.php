@extends('layouts.admin')
@section('title', __('ui.workflow.bom_title'))
@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('super.boms.index') }}">{{ __('ui.workflow.bom_title') }}</a></li><li class="breadcrumb-item active">Detail Material</li></ol></nav>
@endsection
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h4 class="page-title mb-1">Rincian BOM</h4><p class="text-muted mb-0">{{ $product->style_code }} · {{ $product->code }} — {{ $product->name }}</p></div>
    <div class="d-flex gap-2">
        @if($bills->isNotEmpty())@can('boms.update')<a href="{{ route('super.boms.edit', $bills->first()) }}" class="btn btn-sm btn-outline-primary">Edit BOM</a>@endcan @endif
        <a href="{{ route('super.boms.index') }}" class="btn btn-sm btn-light border">Kembali</a>
    </div>
</div>
@if(session('success'))<div class="alert alert-success alert-dismissible fade show py-2" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
@forelse($bills as $bom)
<div class="card border-0 shadow-sm bom-card mb-3">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3"><div><h6 class="mb-0">Consumption · {{ $product->style_code }} · {{ $product->code }}</h6><small class="text-muted">{{ $product->name }}</small></div><span class="badge rounded-pill text-bg-light">{{ $bom->items->count() }} material</span></div>
    <div class="card-body"><div class="table-responsive">
        <table class="table table-hover align-middle w-100 bom-detail-table mb-0">
            <thead class="table-light"><tr><th>No</th><th>Aksi</th><th>Item Code</th><th>Name Material/Support</th><th>Unit</th><th>Consumption/PCS</th><th>Span</th><th>Scrap (%)</th><th>User</th></tr></thead>
            <tbody>
            @foreach($bom->items as $item)
                <tr><td></td><td class="text-nowrap">
                    @can('boms.update')<a class="btn btn-sm btn-outline-primary bom-action" href="{{ route('super.boms.edit', $bom) }}" title="Edit rincian">Edit</a>@endcan
                    @can('boms.delete')<form method="POST" action="{{ route('super.boms.items.destroy', [$bom, $item]) }}" class="d-inline" onsubmit="return confirm('Hapus rincian material ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger bom-action" type="submit">Hapus</button></form>@endcan
                </td><td>{{ $item->product?->code }}</td><td>{{ $item->product?->name }}</td><td>{{ $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—' }}</td><td>{{ number_format((float) $item->quantity, 4, ',', '.') }}</td><td>{{ number_format((float) $item->span, 4, ',', '.') }}</td><td>{{ number_format((float) $item->wastage_percent, 3, ',', '.') }}</td><td>{{ $bom->creator?->name ?: '—' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div></div>
</div>
@empty<div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">BOM untuk item ini belum diinput.</div></div>@endforelse
@endsection
@push('styles')
<style>
    .bom-card { border-radius: .75rem; }
    .bom-card .table thead th { color: #64748b; font-size: .75rem; font-weight: 600; letter-spacing: .025em; text-transform: uppercase; border-bottom-color: #e9eef5; white-space: nowrap; }
    .bom-card .table tbody td { border-color: #eef2f6; }
    .bom-action { --bs-btn-padding-y: .2rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem; --bs-btn-border-radius: .4rem; margin: .08rem; }
    .dataTables_wrapper .dataTables_length label, .dataTables_wrapper .dataTables_filter label { display: inline-flex; align-items: center; gap: .45rem; margin-bottom: 0; white-space: nowrap; }
    .dataTables_wrapper .dataTables_length select { width: auto !important; min-width: 4.25rem; height: 2rem; box-sizing: border-box; margin: 0 !important; padding: .2rem 1.75rem .2rem .5rem; border: 1px solid #dfe5ec; border-radius: .45rem; font-size: .8125rem; line-height: 1.25; }
    .dataTables_wrapper .dataTables_filter input { height: 2rem; margin-left: 0; padding: .25rem .55rem; border: 1px solid #dfe5ec; border-radius: .45rem; font-size: .8125rem; }
    .dataTables_info, .dataTables_paginate { color: #748094; font-size: .82rem; }
</style>
@endpush
@push('scripts')
<script>
$(function () {
    $('.bom-detail-table').each(function () {
        const table = $(this).DataTable({
            pageLength: 10, lengthMenu: [5, 10, 50, 100], order: [[2, 'asc']],
            columnDefs: [{ targets: [0, 1], orderable: false, searchable: false }],
            drawCallback: function () {
                const api = this.api();
                api.column(0, { page: 'current' }).nodes().each((cell, index) => cell.textContent = api.page.info().start + index + 1);
            },
        });
    });
});
</script>
@endpush
