@extends('layouts.admin')
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="page-title mb-1">{{ __('ui.workflow.bom_title') }}</h4>
            <p class="text-muted mb-0">Finished Goods dan Style</p>
        </div>
        <a href="{{ route('super.boms.export') }}" class="btn btn-sm btn-outline-success"><i data-feather="download"
                class="icon-sm me-1"></i>Export Excel</a>
    </div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">{{ session('success') }}<button
                type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif
    <div class="card border-0 shadow-sm bom-card">

        <div class="card-body">
            <div class="table-responsive">
                <table id="bom-products-table" class="table table-hover align-middle w-100 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Aksi</th>
                            <th>Style</th>
                            <th>Item Code</th>
                            <th>Finished Goods</th>
                            <th>Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            @php($bom = $product->billsOfMaterial->first())
                            <tr>
                                <td></td>
                                <td class="text-nowrap">
                                    @if ($bom)
                                        <a class="btn btn-sm btn-outline-info bom-action"
                                            href="{{ route('super.boms.detail', $product) }}">Detail</a>
                                        @can('boms.update')
                                            <a class="btn btn-sm btn-outline-primary bom-action"
                                                href="{{ route('super.boms.edit', $bom) }}">Edit</a>
                                        @endcan
                                        @can('boms.delete')
                                            <form method="POST" action="{{ route('super.boms.destroy', $bom) }}"
                                                class="d-inline"
                                                onsubmit="return confirm('Hapus BOM ini beserta rincian materialnya?')">@csrf
                                                @method('DELETE')<button class="btn btn-sm btn-outline-danger bom-action"
                                                    type="submit">Hapus</button></form>
                                        @endcan
                                    @else
                                        @can('boms.create')
                                            <a class="btn btn-sm btn-outline-success bom-action"
                                                href="{{ route('super.boms.input', $product) }}">Input</a>
                                        @endcan
                                    @endif
                                </td>
                                <td>{{ $product->style_code }}</td>
                                <td>{{ $product->code }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->unit?->symbol ?: $product->unit?->name ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('styles')
    <style>
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
            $('#bom-products-table').DataTable({
                pageLength: 10,
                lengthMenu: [5, 10, 50, 100],
                order: [
                    [2, 'asc'],
                    [3, 'asc']
                ],
                columnDefs: [{
                    targets: [0, 1],
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
