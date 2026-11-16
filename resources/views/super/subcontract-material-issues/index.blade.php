@extends('layouts.admin')
@section('title', __($translation.'.title'))
@section('breadcrumb')
<nav class="page-breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item">{{ __('ui.nav.subcontract') }}</li><li class="breadcrumb-item active">{{ __($translation.'.title') }}</li></ol></nav>
@endsection
@section('content')
<div class="card">
 <div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">{{ __($translation.'.title') }}</h5>@can($permissionPrefix.'.create')<a class="btn btn-primary btn-sm" href="{{ route($routePrefix.'.create') }}"><i data-feather="plus" class="icon-sm me-1"></i>{{ __($translation.'.add') }}</a>@endcan</div>
 <div class="card-body">
 @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
 @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
 <form method="GET" class="mb-3"><div class="row g-2 align-items-end mb-3">
 <div class="col-md-3"><label class="form-label" for="from">{{ __('ui.common.from_date') }}</label><input id="from" type="date" name="from_date" class="form-control form-control-sm" value="{{ $filters['from_date'] }}" required></div>
 <div class="col-md-3"><label class="form-label" for="to">{{ __('ui.common.to_date') }}</label><input id="to" type="date" name="to_date" class="form-control form-control-sm" value="{{ $filters['to_date'] }}" required></div>
 <div class="col-auto"><button class="btn btn-primary btn-sm">{{ __('ui.common.show_data') }}</button> <button class="btn btn-outline-success btn-sm" formaction="{{ route($routePrefix.'.export') }}">{{ __('ui.common.export_excel') }}</button></div></div>
 <input type="hidden" name="search" id="subcontract-export-search" value=""></form>
 <div class="table-responsive"><table id="subcontract-list-table" class="table table-bordered table-hover align-middle"><thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.action') }}</th>@foreach(['number','date','receiver','contract',...($isReceipt?['invoice_number','invoice_date']:[]),'style','code','name','unit','quantity','status','confirmed_by','confirmed_at'] as $key)<th>{{ __($translation.'.'.$key) }}</th>@endforeach</tr></thead><tbody></tbody></table></div><p class="small text-muted mt-2 mb-0">{{ __($translation.'.notice') }}</p>
 </div></div>
@endsection
@include('super.subcontract.partials.datatable', ['tableKind' => $isReceipt ? 'receipt' : 'issue', 'dateFromId' => 'from', 'dateToId' => 'to'])
