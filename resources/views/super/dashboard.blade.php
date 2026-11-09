@extends('layouts.admin')

@section('title', 'Dashboard')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="dashboard-shell">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
            <div>
                <div class="text-uppercase fw-semibold text-primary small letter-spacing-1">Overview</div>
                <h4 class="mb-1">Dashboard</h4>
                <p class="text-muted mb-0">Ringkasan data master ERP dan status operasional utama.</p>
            </div>

            <div class="dashboard-date-badge">
                <i data-feather="calendar" class="icon-sm me-2"></i>
                {{ now()->translatedFormat('d M Y') }}
            </div>
        </div>

        <div class="row g-3 mb-4">
            @php
                $stats = [
                    'products' => [
                        'label' => 'Barang',
                        'route' => 'super.products.index',
                        'icon' => 'box',
                        'permission' => 'products.view',
                        'accent' => 'primary',
                    ],
                    'pricelists' => [
                        'label' => 'Pricelist',
                        'route' => 'super.pricelists.index',
                        'icon' => 'dollar-sign',
                        'permission' => 'pricelists.view',
                        'accent' => 'success',
                    ],
                    'categories' => [
                        'label' => 'Kategori',
                        'route' => 'super.categories.index',
                        'icon' => 'tag',
                        'permission' => 'categories.view',
                        'accent' => 'warning',
                    ],
                    'units' => [
                        'label' => 'Satuan',
                        'route' => 'super.units.index',
                        'icon' => 'package',
                        'permission' => 'units.view',
                        'accent' => 'info',
                    ],
                    'suppliers' => [
                        'label' => 'Supplier',
                        'route' => 'super.suppliers.index',
                        'icon' => 'truck',
                        'permission' => 'suppliers.view',
                        'accent' => 'danger',
                    ],
                    'customers' => [
                        'label' => 'Pelanggan',
                        'route' => 'super.customers.index',
                        'icon' => 'users',
                        'permission' => 'customers.view',
                        'accent' => 'secondary',
                    ],
                ];
            @endphp

            @foreach ($stats as $key => $stat)
                @can($stat['permission'])
                    <div class="col-sm-6 col-xl-4">
                        <a href="{{ route($stat['route']) }}" class="text-decoration-none">
                            <div class="card border-0 shadow-sm dashboard-stat-card dashboard-stat-{{ $stat['accent'] }} h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="text-muted small fw-semibold text-uppercase tracking-wide">
                                                {{ $stat['label'] }}</div>
                                            <div class="mt-2 fs-3 fw-bold text-dark">{{ number_format($totals[$key]) }}</div>
                                        </div>
                                        <div class="stat-icon bg-{{ $stat['accent'] }}-soft text-{{ $stat['accent'] }}">
                                            <i data-feather="{{ $stat['icon'] }}"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3 small text-muted">
                                        <span
                                            class="fw-semibold text-{{ $stat['accent'] }}">{{ $totals[$key] > 0 ? 'Siap digunakan' : 'Belum ada data' }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endcan
            @endforeach
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm h-100 dashboard-panel">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase tracking-wide">Data Master</div>
                                <h6 class="mb-0 mt-1">Ringkasan sistem</h6>
                            </div>
                            <span class="badge bg-primary-subtle text-primary rounded-pill">Live</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="summary-mini-box summary-box-primary">
                                    <div class="summary-mini-label">Barang</div>
                                    <div class="summary-mini-value">{{ number_format($totals['products']) }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="summary-mini-box summary-box-success">
                                    <div class="summary-mini-label">Supplier</div>
                                    <div class="summary-mini-value">{{ number_format($totals['suppliers']) }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="summary-mini-box summary-box-warning">
                                    <div class="summary-mini-label">Pelanggan</div>
                                    <div class="summary-mini-value">{{ number_format($totals['customers']) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 small text-muted">
                            <div
                                class="d-flex align-items-center justify-content-between py-2 border-top border-light-subtle">
                                <span>Jumlah kategori produk</span>
                                <strong class="text-dark">{{ number_format($totals['categories']) }}</strong>
                            </div>
                            <div
                                class="d-flex align-items-center justify-content-between py-2 border-top border-light-subtle">
                                <span>Jumlah satuan barang</span>
                                <strong class="text-dark">{{ number_format($totals['units']) }}</strong>
                            </div>
                            <div
                                class="d-flex align-items-center justify-content-between py-2 border-top border-light-subtle">
                                <span>Jumlah pricelist</span>
                                <strong class="text-dark">{{ number_format($totals['pricelists']) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100 dashboard-panel">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase tracking-wide">Aksi cepat</div>
                                <h6 class="mb-0 mt-1">Navigasi</h6>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            @can('products.view')
                                <a href="{{ route('super.products.index') }}"
                                    class="btn btn-light text-start dashboard-quick-action">
                                    <i data-feather="box" class="me-2"></i> Kelola Barang
                                </a>
                            @endcan

                            @can('suppliers.view')
                                <a href="{{ route('super.suppliers.index') }}"
                                    class="btn btn-light text-start dashboard-quick-action">
                                    <i data-feather="truck" class="me-2"></i> Daftar Supplier
                                </a>
                            @endcan

                            @can('customers.view')
                                <a href="{{ route('super.customers.index') }}"
                                    class="btn btn-light text-start dashboard-quick-action">
                                    <i data-feather="users" class="me-2"></i> Pelanggan
                                </a>
                            @endcan

                            @can('categories.view')
                                <a href="{{ route('super.categories.index') }}"
                                    class="btn btn-light text-start dashboard-quick-action">
                                    <i data-feather="tag" class="me-2"></i> Kategori Produk
                                </a>
                            @endcan
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
