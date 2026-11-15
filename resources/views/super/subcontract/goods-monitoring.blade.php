@extends('layouts.admin')
@section('title', __('subcontract_goods_monitoring.title'))
@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.subcontract') }}</li><li class="breadcrumb-item active">{{ __('subcontract_goods_monitoring.title') }}</li></ol></nav>
@endsection
@section('content')
<div class="card"><div class="card-header"><h5 class="mb-0">{{ __('subcontract_goods_monitoring.title') }}</h5></div><div class="card-body">
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="GET" class="mb-3"><div class="row g-2 align-items-end mb-3">
<div class="col-md-3"><label for="contract-from" class="form-label">{{ __('subcontract_contract_monitoring.date') }}</label><input id="contract-from" class="form-control form-control-sm" type="date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
<div class="col-md-3"><label for="contract-to" class="form-label">{{ __('subcontract_contract_monitoring.to') }}</label><input id="contract-to" class="form-control form-control-sm" type="date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
<div class="col-auto"><button class="btn btn-primary btn-sm"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div></div>
<div class="d-flex flex-wrap justify-content-between gap-2"><label class="d-flex gap-2 align-items-center"><select class="form-select form-select-sm w-auto" name="per_page" onchange="this.form.requestSubmit()">@foreach([10,25,50,100] as $size)<option @selected(($filters['per_page']??10)==$size)>{{ $size }}</option>@endforeach</select>{{ __('subcontract_contract_monitoring.records') }}</label><div class="d-flex gap-2 align-items-center"><label for="contract-search">{{ __('ui.common.search') }}</label><input id="contract-search" class="form-control form-control-sm" name="search" maxlength="100" value="{{ $filters['search']??'' }}"><button class="btn btn-outline-primary btn-sm">{{ __('ui.common.search') }}</button></div></div></form>
<div class="table-responsive"><table class="table table-bordered table-hover align-middle"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th>@foreach(['subcontractor','contract','decree','start','end','quota','description'] as $key)<th>{{ __('subcontract_contract_monitoring.'.$key) }}</th>@endforeach</tr></thead><tbody>
@forelse($contracts as $contract)<tr><td>{{ $contracts->firstItem()+$loop->index }}</td><td><div class="d-flex gap-2"><a class="btn btn-primary btn-sm text-nowrap" href="{{ route('super.subcontract.goods-monitoring.index',array_merge($filters,['contract_id'=>$contract->id,'direction'=>'receipt','page'=>$contracts->currentPage()])) }}#goods-details">{{ __('subcontract_goods_monitoring.receipt') }}</a><a class="btn btn-primary btn-sm text-nowrap" href="{{ route('super.subcontract.goods-monitoring.index',array_merge($filters,['contract_id'=>$contract->id,'direction'=>'issue','page'=>$contracts->currentPage()])) }}#goods-details">{{ __('subcontract_goods_monitoring.issue') }}</a></div></td><td>{{ $contract->subcontractor?->name ?? '—' }}</td><td>{{ $contract->contract_number }}</td><td>{{ $contract->bc_decree_number ?: '—' }}</td><td>{{ $contract->starts_on?->format('d-m-Y') ?? '—' }}</td><td>{{ $contract->ends_on?->format('d-m-Y') ?? '—' }}</td><td class="text-end">{{ $contract->quota === null ? '—' : rtrim(rtrim(number_format($contract->quota,4,',','.'),'0'),',') }}</td><td>{{ $contract->description ?: '—' }}</td></tr>
@empty<tr><td colspan="9" class="text-center py-4">{{ __('subcontract_contract_monitoring.empty') }}</td></tr>@endforelse
</tbody></table></div><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"><span class="small">{{ __('subcontract_contract_monitoring.pagination',['first'=>$contracts->firstItem()??0,'last'=>$contracts->lastItem()??0,'total'=>$contracts->total()]) }}</span>{{ $contracts->links() }}</div><p class="small text-muted mt-2 mb-0">{{ __('subcontract_goods_monitoring.notice') }}</p>
</div></div>
@if($selected)
<div class="card mt-4" id="goods-details"><div class="card-header"><h6 class="mb-0">{{ __('subcontract_goods_monitoring.'.$direction.'_title') }}: {{ $selected->contract_number }} — {{ $selected->subcontractor?->name }}</h6></div><div class="card-body">
@if($ambiguous && $direction==='receipt')<div class="alert alert-warning">{{ __('subcontract_goods_monitoring.ambiguous') }}</div>@endif
<div class="table-responsive"><table class="table table-bordered table-hover align-middle"><thead><tr><th>{{ __('ui.common.no') }}</th>@foreach(['style','code','name','unit','contract_quantity','realized','remaining'] as $key)<th>{{ __('subcontract_goods_monitoring.'.$key) }}</th>@endforeach</tr></thead><tbody>
@forelse($details as $detail)<tr><td>{{ $details->firstItem()+$loop->index }}</td><td>{{ $detail->style ?: '—' }}</td><td>{{ $detail->product?->code ?? '—' }}</td><td>{{ $detail->product?->name ?? '—' }}</td><td>{{ $detail->product?->unit?->symbol ?: $detail->product?->unit?->name }}</td><td>—</td><td class="text-end">{{ number_format($detail->realized_quantity,4,',','.') }}</td><td>—</td></tr>
@empty<tr><td colspan="8" class="text-center py-4">{{ __('subcontract_goods_monitoring.empty_details') }}</td></tr>@endforelse
</tbody></table></div><div class="mt-3">{{ $details->links() }}</div><p class="small text-muted mb-0">{{ __('subcontract_goods_monitoring.details_notice') }}</p>
</div></div>
@endif
@endsection
