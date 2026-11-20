@extends('layouts.admin')

@section('title', __('variants.master_'.$kind))

@section('content')
    <h4 class="page-title mb-3">{{ __('variants.master_'.$kind) }}</h4>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (($entry && auth()->user()->can('products.update')) || (!$entry && auth()->user()->can('products.create')))
        <form class="card mb-3" method="post" action="{{ $entry ? route('super.variant-dimensions.update', [$kind, $entry->id]) : route('super.variant-dimensions.store', $kind) }}">
            @csrf
            @if ($entry)
                @method('PUT')
            @endif
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <div class="row g-2 align-items-end">
                    <div class="col-sm-3 col-lg-2">
                        <label for="dimension-code" class="form-label">{{ __('variants.dimension_code') }}</label>
                        <input id="dimension-code" class="form-control" name="code" value="{{ old('code', $entry?->code) }}" maxlength="{{ $kind === 'color' ? 80 : 40 }}" @readonly($entry) required>
                    </div>
                    <div class="col-sm-5 col-lg-3">
                        <label for="dimension-name" class="form-label">{{ __('variants.dimension_name') }}</label>
                        <input id="dimension-name" class="form-control" name="name" value="{{ old('name', $entry?->name) }}" maxlength="120" required>
                    </div>
                    <div class="col-sm-4 col-lg-2">
                        <label for="dimension-sort" class="form-label">{{ __('variants.dimension_sort') }}</label>
                        <input id="dimension-sort" class="form-control" type="number" name="sort_order" min="0" max="65535" value="{{ old('sort_order', $entry?->sort_order ?? 0) }}" required>
                    </div>
                    <div class="col-sm-8 col-lg-3">
                        <label for="dimension-aliases" class="form-label">{{ __('variants.dimension_aliases') }}</label>
                        <input id="dimension-aliases" class="form-control" name="aliases" value="{{ old('aliases', $entry?->aliases->pluck('code')->implode(', ')) }}" maxlength="2000">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary">{{ __('ui.common.save') }}</button>
                        @if ($entry)
                            <a class="btn btn-light" href="{{ route('super.variant-dimensions.index', $kind) }}">{{ __('ui.common.cancel') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    @endif

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr>
                    <th>{{ __('variants.dimension_code') }}</th>
                    <th>{{ __('variants.dimension_name') }}</th>
                    <th>{{ __('variants.dimension_aliases_title') }}</th>
                    <th>{{ __('variants.dimension_sort') }}</th>
                    @can('products.update')
                        <th>{{ __('ui.common.action') }}</th>
                    @endcan
                </tr></thead>
                <tbody>
                    @forelse ($entries as $item)
                        <tr>
                            <td>{{ $item->code }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->aliases->pluck('code')->implode(', ') ?: '—' }}</td>
                            <td>{{ $item->sort_order }}</td>
                            @can('products.update')
                                <td><a class="btn btn-outline-primary btn-sm" href="{{ route('super.variant-dimensions.index', ['kind' => $kind, 'edit' => $item->id]) }}">{{ __('ui.common.edit') }}</a></td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('ui.workflow.empty_table') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $entries->links() }}
        </div>
    </div>
@endsection
