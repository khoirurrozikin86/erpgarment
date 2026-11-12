@extends('layouts.admin')
@section('title', __('ui.workflow.bom_title'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.inventory') }}</li><li class="breadcrumb-item active">{{ __('ui.workflow.bom_title') }}</li></ol></nav>@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="page-title mb-1">{{ __('ui.workflow.bom_title') }}</h4><p class="text-muted mb-0">{{ __('ui.workflow.bom_subtitle') }}</p></div>
    @can('boms.create')<a href="{{ route('super.boms.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.workflow.bom_create') }}</a>@endcan
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card"><div class="card-body table-responsive"><table class="table table-bordered table-hover align-middle mb-0">
<thead><tr><th>{{ __('ui.workflow.bom_product') }}</th><th>{{ __('ui.workflow.bom_version') }}</th><th>{{ __('ui.workflow.bom_status') }}</th><th>{{ __('ui.workflow.bom_output_qty') }}</th><th>{{ __('ui.workflow.bom_components') }}</th><th>{{ __('ui.workflow.bom_effective_from') }}</th><th>{{ __('ui.workflow.bom_effective_until') }}</th><th>{{ __('ui.workflow.bom_actions') }}</th></tr></thead>
<tbody>
@forelse($bills as $bom)
<tr>
<td><strong>{{ $bom->product?->code }}</strong> — {{ $bom->product?->name }}</td><td>{{ $bom->version }}</td>
<td><span class="badge {{ $bom->status === 'active' ? 'bg-success' : ($bom->status === 'draft' ? 'bg-secondary' : 'bg-warning text-dark') }}">{{ __('ui.workflow.bom_'.$bom->status) }}</span></td>
<td>{{ number_format((float)$bom->output_quantity, 4, ',', '.') }} {{ $bom->product?->unit?->symbol ?: $bom->product?->unit?->name }}</td><td>{{ $bom->items_count }}</td>
<td>{{ $bom->effective_from?->format('d/m/Y') ?? '—' }}</td><td>{{ $bom->effective_until?->format('d/m/Y') ?? '—' }}</td>
<td class="text-nowrap">
@can('boms.update')<a class="btn btn-sm btn-outline-primary" href="{{ route('super.boms.edit', $bom) }}">{{ __('ui.common.edit') }}</a>@endcan
@can('boms.delete')@if($bom->status !== 'active')<form method="POST" action="{{ route('super.boms.destroy', $bom) }}" class="d-inline" onsubmit="return confirm(@json(__('ui.workflow.bom_delete_confirm')))">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">{{ __('ui.workflow.bom_delete') }}</button></form>@endif @endcan
</td>
</tr>
@empty<tr><td colspan="8" class="text-center text-muted py-4">{{ __('ui.workflow.bom_no_records') }}</td></tr>@endforelse
</tbody></table></div></div>
@endsection
