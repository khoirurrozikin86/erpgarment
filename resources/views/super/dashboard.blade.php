@extends('layouts.admin')

@section('title', __('ui.dashboard.title'))

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">{{ __('ui.dashboard.title') }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="dashboard-shell">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
            <div>
                <div class="text-uppercase fw-semibold text-primary small letter-spacing-1">{{ __('ui.welcome.feature_overview') }}</div>
                <h4 class="mb-1">{{ __('ui.dashboard.title') }}</h4>
                <p class="text-muted mb-0">{{ __('ui.dashboard.subtitle') }}</p>
            </div>
            <div class="dashboard-date-badge"><i data-feather="calendar" class="icon-sm me-2"></i>{{ now()->translatedFormat('d M Y') }}</div>
        </div>

        @unless ($companyConfigured)
            <div class="alert alert-warning d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div><strong>{{ __('ui.dashboard.company_missing') }}</strong><br>{{ __('ui.dashboard.company_missing_help') }}</div>
                @can('company.view')
                    <a href="{{ route('super.companies.index') }}" class="btn btn-warning">{{ __('ui.dashboard.manage_company') }}</a>
                @endcan
            </div>
        @endunless

        <div class="row g-3 mb-4">
            @php
                $stats = [
                    ['key' => 'products', 'label' => 'ui.dashboard.all_items', 'icon' => 'box', 'permission' => 'products.view'],
                    ['key' => 'fabrics', 'label' => 'ui.dashboard.fabric', 'icon' => 'layers', 'permission' => 'products.view'],
                    ['key' => 'accessories', 'label' => 'ui.dashboard.accessories', 'icon' => 'paperclip', 'permission' => 'products.view'],
                    ['key' => 'work_in_process', 'label' => 'ui.dashboard.wip', 'icon' => 'settings', 'permission' => 'products.view'],
                    ['key' => 'finished_goods', 'label' => 'ui.dashboard.finished_goods', 'icon' => 'package', 'permission' => 'products.view'],
                    ['key' => 'material_requests', 'label' => 'ui.dashboard.mr_pending', 'icon' => 'clipboard', 'permission' => 'material-requests.view'],
                    ['key' => 'purchase_requests', 'label' => 'ui.dashboard.pr_pending', 'icon' => 'file-text', 'permission' => 'purchase-requests.view'],
                    ['key' => 'purchase_orders', 'label' => 'ui.dashboard.po_open', 'icon' => 'shopping-cart', 'permission' => 'purchase-orders.view'],
                    ['key' => 'goods_receipts', 'label' => 'ui.dashboard.receipts', 'icon' => 'inbox', 'permission' => 'goods-receipts.view'],
                ];
            @endphp

            @foreach ($stats as $stat)
                @can($stat['permission'])
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="text-muted small fw-semibold">{{ __($stat['label']) }}</div>
                                    <div class="mt-2 fs-3 fw-bold text-dark">{{ number_format($totals[$stat['key']]) }}</div>
                                </div>
                                <span class="dashboard-icon"><i data-feather="{{ $stat['icon'] }}"></i></span>
                            </div>
                        </div>
                    </div>
                @endcan
            @endforeach
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small fw-semibold text-uppercase">{{ __('ui.dashboard.workflow') }}</div>
                        <h5 class="mt-1 mb-3">{{ __('ui.dashboard.workflow_title') }}</h5>
                        <div class="garment-flow">
                            <span>BOM &amp; {{ __('ui.dashboard.production_order') }}</span><b>→</b><span>{{ __('ui.dashboard.material_request') }}</span><b>→</b><span>{{ __('ui.dashboard.purchase_request') }}</span><b>→</b><span>{{ __('ui.dashboard.purchase_order') }}</span><b>→</b><span>{{ __('ui.dashboard.goods_receipt') }} &amp; {{ __('ui.dashboard.inventory_label') }}</span>
                        </div>
                        <p class="text-muted small mt-3 mb-0">{{ __('ui.dashboard.workflow_note') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small fw-semibold text-uppercase">{{ __('ui.dashboard.quick_actions') }}</div>
                        <h5 class="mt-1 mb-3">{{ __('ui.dashboard.manage_data') }}</h5>
                        <div class="d-grid gap-2">
                            @can('products.view')<a class="btn btn-light text-start" href="{{ route('super.products.index') }}"><i data-feather="box" class="me-2"></i>{{ __('ui.dashboard.items_materials') }}</a>@endcan
                            @can('material-requests.view')<a class="btn btn-light text-start" href="{{ route('super.material-requests.index') }}"><i data-feather="clipboard" class="me-2"></i>{{ __('ui.dashboard.material_request') }}</a>@endcan
                            @can('purchase-requests.view')<a class="btn btn-light text-start" href="{{ route('super.purchase-requests.index') }}"><i data-feather="file-text" class="me-2"></i>{{ __('ui.dashboard.purchase_request') }}</a>@endcan
                            @can('purchase-orders.view')<a class="btn btn-light text-start" href="{{ route('super.purchase-orders.index') }}"><i data-feather="shopping-cart" class="me-2"></i>{{ __('ui.dashboard.purchase_order') }}</a>@endcan
                            @can('goods-receipts.view')<a class="btn btn-light text-start" href="{{ route('super.goods-receipts.index') }}"><i data-feather="inbox" class="me-2"></i>{{ __('ui.dashboard.goods_receipt') }}</a>@endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .dashboard-shell {
            padding-bottom: 8px;
        }

        .dashboard-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #edf4ff;
            color: #2563eb;
        }

        .garment-flow {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .65rem;
        }

        .garment-flow span {
            padding: .55rem .75rem;
            border: 1px solid #e7edf5;
            border-radius: 10px;
            background: #f8fafc;
            color: #334155;
            font-size: .88rem;
            font-weight: 600;
        }

        .garment-flow b {
            color: #94a3b8;
        }

        .letter-spacing-1 {
            letter-spacing: 0.12em;
        }

        .dashboard-date-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #eef5ff 0%, #f7f9ff 100%);
            color: #1d4ed8;
            border: 1px solid rgba(29, 78, 216, 0.14);
            border-radius: 12px;
            padding: 0.7rem 1rem;
            font-weight: 600;
        }

        .dashboard-stat-card {
            border-radius: 18px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .dashboard-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08) !important;
        }

        .dashboard-stat-primary {
            background: linear-gradient(135deg, #f8fbff 0%, #edf5ff 100%);
        }

        .dashboard-stat-success {
            background: linear-gradient(135deg, #f4fff9 0%, #ebfff5 100%);
        }

        .dashboard-stat-warning {
            background: linear-gradient(135deg, #fffaf2 0%, #fff5e8 100%);
        }

        .dashboard-stat-info {
            background: linear-gradient(135deg, #f3fbff 0%, #ebf8ff 100%);
        }

        .dashboard-stat-danger {
            background: linear-gradient(135deg, #fff7f7 0%, #fff0f0 100%);
        }

        .dashboard-stat-secondary {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        }

        .stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 14px;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.04);
        }

        .stat-icon svg {
            width: 20px;
            height: 20px;
        }

        .bg-primary-soft {
            background: rgba(13, 110, 253, 0.1);
        }

        .bg-success-soft {
            background: rgba(25, 135, 84, 0.1);
        }

        .bg-warning-soft {
            background: rgba(255, 193, 7, 0.12);
        }

        .bg-info-soft {
            background: rgba(13, 202, 240, 0.12);
        }

        .bg-danger-soft {
            background: rgba(220, 53, 69, 0.1);
        }

        .bg-secondary-soft {
            background: rgba(108, 117, 125, 0.12);
        }

        .text-primary {
            color: #0d6efd !important;
        }

        .text-success {
            color: #198754 !important;
        }

        .text-warning {
            color: #d58c00 !important;
        }

        .text-info {
            color: #0dcaf0 !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .text-secondary {
            color: #6c757d !important;
        }

        .dashboard-panel {
            border-radius: 18px;
            background: #fff;
        }

        .summary-mini-box {
            border-radius: 14px;
            padding: 1rem;
            min-height: 110px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .summary-box-primary {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.08), rgba(13, 110, 253, 0.02));
        }

        .summary-box-success {
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.08), rgba(25, 135, 84, 0.02));
        }

        .summary-box-warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.12), rgba(255, 193, 7, 0.02));
        }

        .summary-mini-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6c757d;
            font-weight: 700;
        }

        .summary-mini-value {
            font-size: 1.7rem;
            font-weight: 700;
            color: #111827;
            margin-top: 0.35rem;
        }

        .dashboard-quick-action {
            border: 1px solid #edf2f7;
            border-radius: 12px;
            padding: 0.8rem 0.9rem;
            background: #f8fafc;
            color: #1f2937;
            font-weight: 600;
        }

        .dashboard-quick-action:hover {
            background: #eef5ff;
            border-color: rgba(13, 110, 253, 0.18);
            color: #0d6efd;
        }

        .dashboard-quick-action svg {
            width: 16px;
            height: 16px;
        }
    </style>
@endpush
