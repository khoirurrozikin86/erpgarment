@extends('layouts.admin')
@include('super.boms.partials.feedback')
@section('title', __('ui.workflow.bom_title'))
@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">{{ __('ui.nav.inventory') }}</li>
            <li class="breadcrumb-item active">{{ __('ui.workflow.bom_title') }}</li>
        </ol>
    </nav>
@endsection
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h4 class="page-title mb-1">{{ __('ui.workflow.bom_title') }}</h4><p class="text-muted mb-0">{{ __('variants.bom_style_overview') }}</p></div>
        <div class="d-flex gap-2">@can('boms.create')<a class="btn btn-primary btn-sm" href="{{ route('super.boms.create') }}">+ {{ __('ui.workflow.bom_create') }}</a>@endcan
        <a href="{{ route('super.boms.export') }}" class="btn btn-sm btn-outline-success">{{ __('ui.common.export_excel') }}</a></div>
    </div>
    <div class="card border-0 shadow-sm bom-card"><div class="card-body"><div class="table-responsive">
        <table id="bom-products-table" class="table table-hover align-middle w-100 mb-0"><thead><tr>
            <th>No</th><th>{{ __('variants.bom_number') }}</th><th>Style</th><th>{{ __('variants.bom_type') }}</th><th>{{ __('variants.process') }}</th><th>{{ __('ui.workflow.size') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('variants.stock_parent_code') }}</th><th>{{ __('variants.stock_sku') }}</th><th>{{ __('ui.common.item') }}</th><th>{{ __('ui.common.category') }}</th><th>{{ __('ui.common.unit') }}</th><th>Consumption</th><th>{{ __('ui.common.actions') }}</th>
        </tr></thead><tbody>
        @foreach($boms as $bom)
            <tr><td></td><td class="align-top text-nowrap">{{ $bom->number }}</td><td class="align-top text-nowrap">{{ $bom->style_code ?: $bom->product?->style_code }}<small class="d-block text-muted">{{ $bom->name }}</small></td>
                <td class="align-top">{{ __('variants.bom_type_'.$bom->bom_type) }}</td>
            @foreach(['process','size','color','parent_code','sku_code','name','category','unit','consumption'] as $column)
                <td class="align-top">@forelse($bom->items as $component)<div class="bom-component-line">
                    @switch($column)
                        @case('process') {{ $component->operation ?: '—' }} @break
                        @case('color') {{ $component->applies_to_color ?: __('variants.all') }} @break
                        @case('size') {{ $component->applies_to_size ?: __('variants.all') }} @break
                        @case('parent_code') {{ $component->product?->parentProduct?->code ?? $component->product?->code ?? '—' }} @break
                        @case('sku_code') {{ $component->product?->parent_product_id ? $component->product->code : '—' }} @break
                        @case('name') {{ $component->product?->name }} @break
                        @case('category') {{ $component->product?->category?->name }} @break
                        @case('unit') {{ $component->product?->unit?->symbol ?: $component->product?->unit?->name ?: '—' }} @break
                        @case('consumption') {{ $component->quantity }} @break
                    @endswitch
                </div>@empty — @endforelse</td>
            @endforeach
            <td class="align-top text-nowrap text-end">
                @can('boms.update')<a class="btn btn-sm btn-outline-primary" href="{{ route('super.boms.edit', $bom) }}">{{ __('ui.common.edit') }}</a>@endcan
                @can('boms.delete')<form method="POST" action="{{ route('super.boms.destroy', $bom) }}" data-confirm="{{ __('variants.bom_delete_confirm') }}" class="d-inline bom-delete-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">{{ __('ui.common.delete') }}</button></form>@endcan
            </td></tr>
        @endforeach
        </tbody></table>
    </div></div></div>
@endsection
@push('styles')
    <style>
        .bom-delete-message { white-space: pre-line; overflow-wrap: anywhere; }
        .bom-component-line { height: 2.25rem; display: flex; align-items: center; white-space: nowrap; border-bottom: 1px solid #eef2f6; }
        .bom-card {
            border-radius: .75rem;
        }

        .bom-card .table thead th {
            color: #64748b;
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .025em;
            text-transform: uppercase;
            border-bottom-color: #e9eef5;
            white-space: nowrap;
        }

        .bom-card .table tbody td {
            border-color: #eef2f6;
        }

        .bom-action {
            --bs-btn-padding-y: .2rem;
            --bs-btn-padding-x: .5rem;
            --bs-btn-font-size: .75rem;
            --bs-btn-border-radius: .4rem;
            margin: .08rem;
        }

        #bom-products-table_wrapper .dataTables_length label,
        #bom-products-table_wrapper .dataTables_filter label {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: 0;
            white-space: nowrap;
        }

        #bom-products-table_wrapper .dataTables_length select {
            width: auto !important;
            min-width: 4.25rem;
            height: 2rem;
            box-sizing: border-box;
            margin: 0 !important;
            padding: .2rem 1.75rem .2rem .5rem;
            border: 1px solid #dfe5ec;
            border-radius: .45rem;
            font-size: .8125rem;
            line-height: 1.25;
        }

        #bom-products-table_wrapper .dataTables_filter input {
            height: 2rem;
            margin-left: 0;
            padding: .25rem .55rem;
            border: 1px solid #dfe5ec;
            border-radius: .45rem;
            font-size: .8125rem;
        }

        #bom-products-table_wrapper .dataTables_info,
        #bom-products-table_wrapper .dataTables_paginate {
            color: #748094;
            font-size: .82rem;
        }
    </style>
@endpush
@push('scripts')
    <script>
        $(function() {
            @if(session('bom_delete_error'))
                const blocked = @json(session('bom_delete_error'));
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.common.cannot_delete')),
                    text: blocked.message + '\n\n' + blocked.references.join('\n'),
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#6571ff',
                    customClass: {htmlContainer: 'bom-delete-message'}
                });
            @endif
            $('#bom-products-table').DataTable({
                pageLength: 10,
                lengthMenu: [5, 10, 50, 100],
                order: [
                    [1, 'desc']
                ],
                columnDefs: [{
                    targets: [0, 13],
                    orderable: false,
                    searchable: false
                }],
                drawCallback: function() {
                    const api = this.api();
                    api.column(0, {
                        page: 'current'
                    }).nodes().each((cell, index) => {
                        cell.textContent = api.page.info().start + index + 1;
                    });
                    if (typeof feather !== 'undefined') feather.replace();
                },
            });
        });
    </script>
@endpush
