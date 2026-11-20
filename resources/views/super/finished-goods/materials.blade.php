@extends('layouts.admin')

@section('title', __('variants.finished_materials_title'))
@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('ui.nav.production') }}</li>
            <li class="breadcrumb-item"><a href="{{ route('super.finished-goods.index') }}">{{ __('ui.finished_goods.title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('ui.common.detail') }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="page-title mb-0">{{ __('variants.finished_materials_title') }}</h4>
        <a class="btn btn-light btn-sm" href="{{ route('super.finished-goods.index') }}">{{ __('ui.common.back') }}</a>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-1">{{ $order->outputProduct?->code ?? '—' }} · {{ $order->outputProduct?->variant_display_name ?? '—' }}</h5>
            <div class="small text-muted">
                {{ $entry->finished_goods_number }} · {{ $entry->processed_at?->format('d/m/Y') ?? '—' }}
                · {{ __('ui.finished_goods.mo') }} {{ $order->number }}
            </div>
        </div>
        <div class="card-body">
            <dl class="row g-2 mb-3">
                <div class="col-sm-3">
                    <dt>{{ __('ui.workflow.color') }}</dt><dd class="mb-0">{{ $order->line_color ?: '—' }}</dd>
                </div>
                <div class="col-sm-3">
                    <dt>{{ __('ui.workflow.size') }}</dt><dd class="mb-0">{{ $order->line_size ?: '—' }}</dd>
                </div>
                <div class="col-sm-3">
                    <dt>{{ __('ui.finished_goods.receive_from_packing') }}</dt><dd class="mb-0">{{ number_format((float) $entry->input_quantity, 4, ',', '.') }} PCS</dd>
                </div>
                <div class="col-sm-3">
                    <dt>{{ __('ui.finished_goods.finished_good_quantity') }}</dt><dd class="mb-0">{{ number_format((float) $entry->good_quantity, 4, ',', '.') }} PCS</dd>
                </div>
            </dl>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">{{ __('ui.common.no') }}</th>
                            <th scope="col">{{ __('ui.packing.material_code') }}</th>
                            <th scope="col">{{ __('ui.packing.material_name') }}</th>
                            <th scope="col">{{ __('ui.common.unit') }}</th>
                            <th scope="col" class="text-end">{{ __('variants.finished_consumption_per_piece') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materials as $material)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $material['code'] ?? '—' }}</td>
                                <td>{{ $material['name'] ?? '—' }}</td>
                                <td>{{ $material['unit'] ?? '—' }}</td>
                                <td class="text-end">{{ $material['consumption_per_piece'] === null ? '—' : number_format($material['consumption_per_piece'], 4, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ __('variants.finished_materials_empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mb-0 mt-2">{{ __('variants.finished_materials_hint') }}</p>
        </div>
    </div>
@endsection
