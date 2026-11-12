@extends('layouts.admin')
@section('title', $bom->exists ? __('ui.workflow.bom_edit') : __('ui.workflow.bom_create'))
@section('breadcrumb')<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('super.boms.index') }}">{{ __('ui.workflow.bom_title') }}</a></li><li class="breadcrumb-item active">{{ $bom->exists ? __('ui.workflow.bom_edit') : __('ui.workflow.bom_create') }}</li></ol></nav>@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="page-title mb-1">{{ $bom->exists ? __('ui.workflow.bom_edit') : __('ui.workflow.bom_create') }}</h4><p class="text-muted mb-0">{{ __('ui.workflow.bom_subtitle') }}</p></div><a href="{{ route('super.boms.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.workflow.bom_cancel') }}</a></div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $bom->exists ? route('super.boms.update', $bom) : route('super.boms.store') }}">
@csrf @if($bom->exists)@method('PUT')@endif
<div class="card mb-3"><div class="card-header"><strong>{{ __('ui.workflow.bom_title') }}</strong></div><div class="card-body"><div class="row g-3">
<div class="col-md-6"><label class="form-label">{{ __('ui.workflow.bom_product') }} <span class="text-danger">*</span></label><select name="product_id" class="form-select" required><option value="">{{ __('ui.workflow.bom_choose_product') }}</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(old('product_id', $bom->product_id) == $product->id)>{{ $product->code }} — {{ $product->name }} ({{ ($product->garment_item_type === 'general' ? __('ui.common.general') : __('ui.workflow.'.$product->garment_item_type)) }})</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">{{ __('ui.workflow.bom_version') }} *</label><input name="version" class="form-control" maxlength="30" value="{{ old('version', $bom->version ?: '1') }}" required></div>
<div class="col-md-4"><label class="form-label">{{ __('ui.workflow.bom_name') }}</label><input name="name" class="form-control" maxlength="150" value="{{ old('name', $bom->name) }}"></div>
<div class="col-md-3"><label class="form-label">{{ __('ui.workflow.bom_output_qty') }} *</label><input name="output_quantity" type="number" min="0.0001" step="0.0001" class="form-control" value="{{ old('output_quantity', $bom->output_quantity ?: 1) }}" required></div>
<div class="col-md-3"><label class="form-label">{{ __('ui.workflow.bom_status') }} *</label><select name="status" class="form-select" required>@foreach(['draft','active','inactive'] as $status)<option value="{{ $status }}" @selected(old('status', $bom->status ?: 'draft') === $status)>{{ __('ui.workflow.bom_'.$status) }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">{{ __('ui.workflow.bom_effective_from') }}</label><input type="date" name="effective_from" class="form-control" value="{{ old('effective_from', $bom->effective_from?->format('Y-m-d')) }}"></div>
<div class="col-md-3"><label class="form-label">{{ __('ui.workflow.bom_effective_until') }}</label><input type="date" name="effective_until" class="form-control" value="{{ old('effective_until', $bom->effective_until?->format('Y-m-d')) }}"></div>
<div class="col-12"><label class="form-label">{{ __('ui.workflow.bom_notes') }}</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $bom->notes) }}</textarea></div>
</div></div></div>
<div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center"><strong>{{ __('ui.workflow.bom_components') }}</strong><button class="btn btn-sm btn-outline-primary" id="addBomComponent" type="button"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.workflow.bom_add_component') }}</button></div><div class="card-body"><div id="bomComponents" class="vstack gap-3">
@php $oldComponents=old('items', $components->map(fn($row)=>['product_id'=>$row->product_id,'quantity'=>$row->quantity,'wastage_percent'=>$row->wastage_percent,'operation'=>$row->operation,'notes'=>$row->notes])->all()); @endphp
@foreach($oldComponents as $i=>$row)
<div class="row g-2 align-items-end bom-component-row">
<div class="col-md-4"><label class="form-label">{{ __('ui.workflow.bom_component') }} *</label><select name="items[{{ $i }}][product_id]" class="form-select" required><option value="">{{ __('ui.workflow.bom_choose_component') }}</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(($row['product_id'] ?? '') == $product->id)>{{ $product->code }} — {{ $product->name }}</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">{{ __('ui.workflow.bom_qty') }} *</label><input name="items[{{ $i }}][quantity]" type="number" min="0.0001" step="0.0001" class="form-control" value="{{ $row['quantity'] ?? '' }}" required></div>
<div class="col-md-2"><label class="form-label">{{ __('ui.workflow.bom_wastage') }}</label><input name="items[{{ $i }}][wastage_percent]" type="number" min="0" max="100" step="0.001" class="form-control" value="{{ $row['wastage_percent'] ?? 0 }}"></div>
<div class="col-md-3"><label class="form-label">{{ __('ui.workflow.bom_operation') }}</label><input name="items[{{ $i }}][operation]" maxlength="40" class="form-control" placeholder="{{ __('ui.workflow.bom_operation_placeholder') }}" value="{{ $row['operation'] ?? '' }}"></div>
<div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-bom-component w-100" aria-label="{{ __('ui.workflow.bom_remove_component') }}">×</button></div>
<div class="col-12"><input name="items[{{ $i }}][notes]" maxlength="1000" class="form-control form-control-sm" placeholder="{{ __('ui.workflow.bom_notes') }}" value="{{ $row['notes'] ?? '' }}"></div>
</div>
@endforeach
</div></div></div>
<div class="d-flex justify-content-end gap-2"><a href="{{ route('super.boms.index') }}" class="btn btn-outline-secondary">{{ __('ui.workflow.bom_cancel') }}</a><button class="btn btn-primary" type="submit">{{ __('ui.workflow.bom_save') }}</button></div>
</form>
<template id="bomComponentTemplate"><div class="row g-2 align-items-end bom-component-row">
<div class="col-md-4"><label class="form-label">{{ __('ui.workflow.bom_component') }} *</label><select name="items[__INDEX__][product_id]" class="form-select" required><option value="">{{ __('ui.workflow.bom_choose_component') }}</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->code }} — {{ $product->name }}</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">{{ __('ui.workflow.bom_qty') }} *</label><input name="items[__INDEX__][quantity]" type="number" min="0.0001" step="0.0001" class="form-control" required></div>
<div class="col-md-2"><label class="form-label">{{ __('ui.workflow.bom_wastage') }}</label><input name="items[__INDEX__][wastage_percent]" type="number" min="0" max="100" step="0.001" class="form-control" value="0"></div>
<div class="col-md-3"><label class="form-label">{{ __('ui.workflow.bom_operation') }}</label><input name="items[__INDEX__][operation]" maxlength="40" class="form-control" placeholder="{{ __('ui.workflow.bom_operation_placeholder') }}"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-bom-component w-100">×</button></div><div class="col-12"><input name="items[__INDEX__][notes]" maxlength="1000" class="form-control form-control-sm" placeholder="{{ __('ui.workflow.bom_notes') }}"></div>
</div></template>
@endsection
@push('scripts')<script>
(() => { const list=document.getElementById('bomComponents'), template=document.getElementById('bomComponentTemplate'); let nextIndex={{ count($oldComponents) }}; document.getElementById('addBomComponent').addEventListener('click',()=>{ list.insertAdjacentHTML('beforeend',template.innerHTML.replaceAll('__INDEX__',nextIndex++)); }); list.addEventListener('click',event=>{ const button=event.target.closest('.remove-bom-component'); if(button) button.closest('.bom-component-row').remove(); }); })();
</script>@endpush
