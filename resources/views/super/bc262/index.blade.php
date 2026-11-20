@extends('layouts.admin')
@section('title', __('bc262.title'))
@section('content')
<div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">{{ __('bc262.title') }}</h5>@can('bc262.create')<a href="{{ route('super.bc-in.bc262.create') }}" class="btn btn-primary btn-sm">{{ __('bc262.add') }}</a>@endcan</div><div class="card-body">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="GET" class="mb-3"><div class="row g-2 align-items-end mb-3"><div class="col-md-3"><label for="from" class="form-label">{{ __('bc262.document_date') }}</label><input id="from" name="from_date" class="form-control form-control-sm" type="date" value="{{ $filters['from_date'] }}" required></div><div class="col-md-3"><label for="to" class="form-label">{{ __('bc262.to') }}</label><input id="to" name="to_date" class="form-control form-control-sm" type="date" value="{{ $filters['to_date'] }}" required></div><div class="col-auto"><button class="btn btn-primary btn-sm">{{ __('ui.common.show_data') }}</button> <button class="btn btn-outline-success btn-sm" formaction="{{ route('super.bc-in.bc262.export') }}">{{ __('ui.common.export_excel') }}</button></div></div><input type="hidden" name="search" id="bc-export-search" value=""></form>
@include('super.bc-in.partials.datatable', ['module' => 'bc262'])<p class="small text-muted mt-2 mb-0">{{ __('bc262.notice') }}</p></div></div>
@endsection
