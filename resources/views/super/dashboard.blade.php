@extends('layouts.admin')
@section('title', __('ui.dashboard.title'))
@section('content')
<div class="erp-overview">
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start mb-4">
        <div><div class="text-primary small fw-bold">{{ $activeCompany ?? __('dashboard_flow.no_company') }}</div><h3 class="mt-1 mb-2">{{ __('dashboard_flow.title') }}</h3><p class="text-muted mb-0">{{ __('dashboard_flow.subtitle') }}</p></div>
        <span class="badge bg-light text-dark border p-3">{{ now()->translatedFormat('d M Y') }}</span>
    </div>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form class="card card-body mb-4" method="GET"><div class="d-flex flex-wrap gap-3 align-items-end"><div><label for="dash-from" class="form-label">{{ __('dashboard_flow.from') }}</label><input id="dash-from" type="date" class="form-control form-control-sm" name="from_date" value="{{ $filters['from_date'] }}" required></div><div><label for="dash-to" class="form-label">{{ __('dashboard_flow.to') }}</label><input id="dash-to" type="date" class="form-control form-control-sm" name="to_date" value="{{ $filters['to_date'] }}" required></div><button class="btn btn-primary btn-sm">{{ __('ui.common.show_data') }}</button><span class="text-muted small pb-1">{{ __('dashboard_flow.count_note') }}</span></div></form>
    @if($productionMonitoring)
        @php
            $productionStatusColors = [
                'draft' => 'secondary',
                'released' => 'info',
                'in_progress' => 'primary',
                'completed' => 'success',
                'cancelled' => 'danger',
            ];
            $formatQty = fn ($value) => \App\Support\DisplayNumber::format($value, 4);
        @endphp
        <section class="card mb-4" aria-labelledby="dashboard-production-monitoring">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 id="dashboard-production-monitoring" class="mb-0">{{ __('dashboard_flow.monitoring') }}</h5>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('super.material-orders.index') }}">{{ __('dashboard_flow.open') }}</a>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">{{ __('dashboard_flow.monitoring_note') }}</p>
                <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-2 mb-3">
                    <div class="col">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted small">{{ __('dashboard_flow.orders') }}</div>
                            <strong class="fs-5">{{ $formatQty($productionMonitoring['counts']->sum()) }}</strong>
                        </div>
                    </div>
                    @foreach($productionStatusColors as $status => $color)
                        <div class="col">
                            <div class="border rounded p-2 h-100">
                                <span class="badge text-bg-{{ $color }}">{{ __('dashboard_flow.status_'.$status) }}</span>
                                <strong class="d-block fs-5 mt-1">{{ $formatQty($productionMonitoring['counts']->get($status, 0)) }}</strong>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="table-responsive border rounded-3">
                    <table class="table table-hover align-middle mb-0 production-monitor-table">
                        <thead><tr>
                            <th>{{ __('dashboard_flow.order_number') }}</th>
                            <th>{{ __('dashboard_flow.buyer') }}</th>
                            <th>{{ __('dashboard_flow.status') }}</th>
                            <th class="text-end">{{ __('dashboard_flow.planned') }}</th>
                            <th class="text-end">{{ __('production_progress.finished_goods') }}</th>
                            <th class="text-end">{{ __('ui.common.actions') }}</th>
                        </tr></thead>
                        <tbody>
                            @forelse($productionMonitoring['recent'] as $order)
                                @php
                                    $progress = $order->target_quantity > 0
                                        ? (int) floor(min(1, max(0, $order->finished_quantity / $order->target_quantity)) * 100)
                                        : null;
                                @endphp
                                <tr>
                                    <th scope="row" class="fw-semibold text-nowrap">{{ $order->number }}</th>
                                    <td>{{ $order->buyer?->name ?? '—' }}</td>
                                    <td><span class="badge rounded-pill text-nowrap text-bg-{{ $productionStatusColors[$order->progress_status] }}">{{ __('dashboard_flow.status_'.$order->progress_status) }}</span></td>
                                    <td class="text-end">{{ $formatQty($order->target_quantity) }}</td>
                                    <td class="text-end">
                                        <div class="fw-semibold">{{ $formatQty($order->finished_quantity) }}</div>
                                        @if($progress !== null)
                                            <div class="production-monitor-progress d-flex align-items-center gap-2 mt-1">
                                                <div class="progress flex-grow-1" role="progressbar" aria-label="{{ __('production_progress.finished_goods') }} — {{ $order->number }}" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar" style="width: {{ $progress }}%"></div>
                                                </div>
                                                <span class="small text-muted" aria-hidden="true">{{ $progress }}%</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('super.material-orders.progress', $order->id) }}">{{ __('production_progress.title') }} <span aria-hidden="true">→</span></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('dashboard_flow.monitoring_empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif
    @if($queues)<div class="card mb-4"><div class="card-body"><h5 class="mb-1">{{ __('dashboard_flow.attention') }}</h5><p class="small text-muted">{{ __('dashboard_flow.queue_note') }}</p><div class="row g-3 mt-1">@foreach($queues as $queue)<div class="col-sm-6 col-xl-3"><a class="queue d-flex justify-content-between align-items-center" href="{{ route($queue['route']) }}"><div>{{ __('dashboard_flow.'.$queue['key']) }}<div class="text-muted small">{{ __('dashboard_flow.'.($queue['shared']?'shared':'company')) }}</div></div><strong class="fs-4">{{ number_format($queue['count']) }}</strong></a></div>@endforeach</div></div></div>@endif
    <div class="card mb-4"><div class="card-body"><div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><h4>{{ __('dashboard_flow.workflow') }}</h4><p class="small text-muted mt-2 mb-0">{{ __('dashboard_flow.flow_note') }}</p></div><span class="small text-muted">{{ __('dashboard_flow.click') }}</span></div>
    <div class="flow-grid">@foreach(['ppic','inbound','warehouse','production','subcontract','outbound'] as $group)@if(isset($flow[$group]))<section class="flow-step step-{{ $group }}"><div class="step-head"><span class="step-index">{{ $loop->iteration }}</span><h5>{{ __('dashboard_flow.'.$group) }}</h5><span aria-hidden="true">→</span></div><div class="step-links">@foreach($flow[$group] as $link)<a href="{{ route($link['route']) }}">{{ __($link['label']) }}<span aria-hidden="true">↗</span></a>@endforeach</div></section>@endif @endforeach</div>
    <div class="flow-branches mt-3">@foreach(['machines','scrap','returns'] as $group)@if(isset($flow[$group]))<section class="branch"><h6 class="mb-2">↳ {{ __('dashboard_flow.'.$group) }}</h6><p class="small text-muted">{{ __('dashboard_flow.'.$group.'_note') }}</p>@foreach($flow[$group] as $link)<a class="branch-link" href="{{ route($link['route']) }}">{{ __($link['label']) }} →</a>@endforeach</section>@endif @endforeach</div>
    </div></div>
    <div class="row g-3 mb-4">@foreach($bc as $direction=>$rows)<div class="col-xl-6"><div class="card h-100"><div class="card-body"><div class="d-flex justify-content-between mb-3"><h5>BC {{ strtoupper($direction) }}</h5><span class="small text-muted">{{ __('dashboard_flow.period_documents') }}</span></div>@foreach($rows as $row)<a href="{{ route($row['route'], $filters) }}" class="bc-row"><span>{{ $row['label'] }}</span><div class="bc-bar"><span style="width:{{ max(array_column($rows,'count'))>0 ? round($row['count']/max(array_column($rows,'count'))*100) : 0 }}%"></span></div><strong>{{ number_format($row['count']) }}</strong></a>@endforeach</div></div></div>@endforeach</div>
    @if(isset($flow['reports']))<div class="card mb-4"><div class="card-body"><h5 class="mb-2">{{ __('dashboard_flow.reports') }}</h5><p class="small text-muted">{{ __('dashboard_flow.report_note') }}</p><div class="report-grid">@foreach($flow['reports'] as $link)<a href="{{ route($link['route']) }}">{{ __($link['label']) }} <span>↗</span></a>@endforeach</div></div></div>@endif
    @if($master)<div class="card"><div class="card-body"><h5>{{ __('dashboard_flow.master') }}</h5><p class="small text-muted mt-2">{{ __('dashboard_flow.master_note') }}</p><div class="row g-3 mt-1">@foreach($master as $type=>$count)<div class="col-6 col-md-3"><div class="text-muted small">{{ __('dashboard_flow.'.$type) }}</div><strong class="fs-4">{{ number_format($count) }}</strong></div>@endforeach</div></div></div>@endif
</div>
@endsection
@push('styles')
<style>
.erp-overview a{text-decoration:none}
.erp-overview .production-monitor-table > :not(caption) > * > * {
    padding: .65rem .85rem;
    font-variant-numeric: tabular-nums;
}
.production-monitor-table thead th {
    --bs-table-bg: var(--bs-tertiary-bg);
    white-space: nowrap;
}
.production-monitor-table tbody tr:last-child > * {
    border-bottom: 0;
}
.production-monitor-progress {
    min-width: 7rem;
    max-width: 10rem;
    margin-inline-start: auto;
}
.production-monitor-progress .progress {
    height: .3rem;
}
.production-monitor-progress span {
    min-width: 2.5rem;
}
.queue{background:#fff9ec;border:1px solid #f3e4c3;padding:14px;border-radius:8px;color:#664c22;gap:12px}.flow-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.flow-step{border:1px solid #dce7f5;border-radius:12px;overflow:hidden;min-width:0}.step-head{background:#eff5ff;display:flex;gap:10px;align-items:center;padding:15px;color:#28549b}.step-head h5{flex:1;font-size:15px;margin:0}.step-index{background:white;border-radius:50%;height:28px;width:28px;display:grid;place-items:center;font-weight:700}.step-ppic .step-head,.step-warehouse .step-head{background:#e7f6f0;color:#18705c}.step-production .step-head,.step-subcontract .step-head{background:#fff4df;color:#95600b}.step-outbound .step-head{background:#fceceb;color:#a7443a}.step-links{padding:8px 15px}.step-links a{display:flex;justify-content:space-between;gap:8px;padding:9px 0;color:#45556b;font-size:13px;border-bottom:1px solid #f0f3f7;overflow-wrap:anywhere}.step-links a:last-child{border:0}.step-links a:hover,.branch-link:hover{color:#377cf6}.flow-branches{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.branch{border:1px dashed #cbd5df;border-radius:10px;padding:16px;background:#fafcfd}.branch-link{display:block;font-size:13px;color:#536275;margin-top:10px}.bc-row{display:flex;align-items:center;gap:16px;padding:11px 0;color:#41516a}.bc-row>span{width:110px;font-size:13px}.bc-row strong{min-width:36px;text-align:right}.bc-bar{height:7px;flex:1;background:#f1f4f8;border-radius:8px;overflow:hidden}.bc-bar span{height:100%;background:#71a0f6;display:block;border-radius:8px}.report-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.report-grid a{background:#f6f8fb;padding:13px;color:#48556b;border-radius:8px;font-size:13px;display:flex;justify-content:space-between;gap:8px}.report-grid a:hover{background:#eaf1fc}@media(max-width:1100px){.flow-grid,.report-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.flow-grid,.flow-branches,.report-grid{grid-template-columns:1fr}.erp-overview h3{font-size:23px}}
</style>
@endpush
