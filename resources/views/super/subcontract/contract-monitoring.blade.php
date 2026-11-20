@extends('layouts.admin')
@section('title', __('subcontract_contract_monitoring.title'))
@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.subcontract') }}</li><li class="breadcrumb-item active">{{ __('subcontract_contract_monitoring.title') }}</li></ol></nav>
@endsection
@section('content')
<div class="card"><div class="card-header"><h5 class="mb-0">{{ __('subcontract_contract_monitoring.title') }}</h5></div><div class="card-body">
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="GET" class="mb-3"><div class="row g-2 align-items-end mb-3">
<div class="col-md-3"><label for="contract-from" class="form-label">{{ __('subcontract_contract_monitoring.date') }}</label><input id="contract-from" class="form-control form-control-sm" type="date" name="from_date" value="{{ $filters['from_date'] }}" required></div>
<div class="col-md-3"><label for="contract-to" class="form-label">{{ __('subcontract_contract_monitoring.to') }}</label><input id="contract-to" class="form-control form-control-sm" type="date" name="to_date" value="{{ $filters['to_date'] }}" required></div>
<div class="col-auto"><button class="btn btn-primary btn-sm"><i data-feather="search" class="icon-sm me-1"></i>{{ __('ui.common.show_data') }}</button></div></div>
<input type="hidden" name="search" id="subcontract-export-search" value=""></form>
<div class="table-responsive"><table id="subcontract-list-table" class="table table-bordered table-hover align-middle" style="width: 100%"><thead><tr><th>{{ __('ui.common.no') }}</th>@foreach(['subcontractor','contract','decree','description','start','end','quota'] as $key)<th>{{ __('subcontract_contract_monitoring.'.$key) }}</th>@endforeach</tr></thead><tbody></tbody></table></div><p class="small text-muted mt-2 mb-0">{{ __('subcontract_contract_monitoring.notice') }}</p>
</div></div>
@endsection

@include('super.subcontract.partials.datatable', ['tableKind' => 'contract-monitoring', 'dateFromId' => 'contract-from', 'dateToId' => 'contract-to'])
