@extends('layouts.admin')

@section('title', __('production_progress.title'))

@section('content')
    @php
        $format = fn ($value) => \App\Support\DisplayNumber::format($value, 4);
        $variants = $lines->groupBy(fn ($line) => json_encode([$line->output_product_id, $line->model_style, $line->line_color, $line->line_size]));
        $progress = $variants->map(function ($variantLines) use ($stageTotals, $stages) {
            $target = $variantLines->sum('planned_quantity');
            $quantities = collect($stages)->mapWithKeys(fn ($stage) => [
                $stage => $variantLines->sum(fn ($line) => $stageTotals->get($line->id)?->get($stage)?->quantity ?? 0),
            ]);
            $recorded = collect($stages)->mapWithKeys(fn ($stage) => [
                $stage => $variantLines->contains(fn ($line) => $stageTotals->get($line->id)?->has($stage) ?? false),
            ]);
            return ['line' => $variantLines->first(), 'target' => $target, 'quantities' => $quantities, 'recorded' => $recorded,
                'remaining' => max(0, round($target - $quantities['finishing'], 4))];
        });
        $started = $stageTotals->flatMap(fn ($totals) => $totals->values())->pluck('started_at')->filter()->sort()->first();
        $targetMet = $started && $progress->every(fn ($variant) => $variant['remaining'] <= 0);
    @endphp
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">{{ __('production_progress.title') }} — {{ $order->number }}</h5>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('super.material-orders.index') }}">{{ __('ui.common.back') }}</a>
        </div>
        <div class="card-body">
            <h6>{{ __('production_progress.production') }}</h6>
            <p class="mb-2">
                <span class="badge {{ $started ? 'text-bg-primary' : 'text-bg-secondary' }}">
                    {{ $started ? __('production_progress.started_at', ['date' => \Illuminate\Support\Carbon::parse($started)->format('d/m/Y')]) : __('production_progress.not_started') }}
                </span>
                @if($targetMet)
                    <span class="badge text-bg-success">{{ __('production_progress.target_met') }}</span>
                @endif
            </p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead><tr>
                        <th>{{ __('ui.workflow.style') }}</th>
                        <th>{{ __('variants.stock_parent_code') }}</th>
                        <th>{{ __('variants.stock_sku') }}</th>
                        <th>{{ __('ui.workflow.color') }}</th>
                        <th>{{ __('ui.workflow.size') }}</th>
                        <th class="text-end">{{ __('production_progress.target') }}</th>
                        @foreach($stages as $stage)
                            <th class="text-end">{{ $stage === 'finishing' ? __('production_progress.finished_goods') : __('ui.workflow.stage_'.$stage) }}</th>
                        @endforeach
                        <th class="text-end">{{ __('production_progress.remaining') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach($progress as $variant)
                            @php($line = $variant['line'])
                            <tr>
                                <td>{{ $line->model_style ?: '—' }}</td>
                                <td>{{ $line->outputProduct?->parentProduct?->code ?? $line->outputProduct?->code ?? '—' }}</td>
                                <td>{{ $line->outputProduct?->parent_product_id ? $line->outputProduct->code : '—' }}</td>
                                <td>{{ $line->line_color ?: '—' }}</td>
                                <td>{{ $line->line_size ?: '—' }}</td>
                                <td class="text-end">{{ $format($variant['target']) }}</td>
                                @foreach($stages as $stage)
                                    <td class="text-end">{{ $variant['recorded'][$stage] ? $format($variant['quantities'][$stage]) : '—' }}</td>
                                @endforeach
                                <td class="text-end">{{ $format($variant['remaining']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($documents)
                <h6 class="mt-4 mb-3">{{ __('production_progress.documents') }}</h6>
                <div class="row g-3">
                    @foreach($documents as $key => $rows)
                        <section class="col-xl-6">
                            <h6 class="text-primary">{{ __('production_progress.'.$key) }}</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead><tr>
                                        <th>{{ __('ui.common.number') }}</th>
                                        <th>{{ __('ui.common.date') }}</th>
                                        <th>{{ __('ui.common.status') }}</th>
                                        <th>{{ __('production_progress.item_quantities') }}</th>
                                    </tr></thead>
                                    <tbody>
                                        @forelse($rows as $document)
                                            @php($statusKey = 'production_progress.'.($key === 'mis' ? 'mis_statuses.' : 'statuses.').$document['status'])
                                            <tr>
                                                <td>{{ $document['number'] }}</td>
                                                <td>{{ $document['date'] ?? '—' }}</td>
                                                <td>{{ \Illuminate\Support\Facades\Lang::has($statusKey) ? __($statusKey) : $document['status'] }}</td>
                                                <td>
                                                    @if($document['items']->isNotEmpty())
                                                        <table class="table table-sm table-borderless mb-0">
                                                            <thead><tr class="text-muted">
                                                                <th>{{ __('ui.common.item_name') }}</th>
                                                                <th>{{ __('ui.workflow.color') }}</th>
                                                                <th>{{ __('ui.workflow.size') }}</th>
                                                                <th class="text-end">{{ __('ui.common.quantity') }}</th>
                                                            </tr></thead>
                                                            <tbody>
                                                                @foreach($document['items'] as $item)
                                                                    <tr>
                                                                        <td>{{ $item['name'] }}</td>
                                                                        <td>{{ $item['color'] ?? '—' }}</td>
                                                                        <td>{{ $item['size'] ?? '—' }}</td>
                                                                        <td class="text-end text-nowrap">{{ $item['quantity'] }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted">{{ __('production_progress.no_documents') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
