<nav class="sidebar">
    @php
        $companyContext = app(\App\Domain\Companies\Services\CompanyContext::class);
        $activeCompany = $companyContext->has() ? $companyContext->get() : null;
    @endphp

    <div class="sidebar-header">
        <a href="{{ route('super.dashboard') }}" class="sidebar-brand company-brand">
            @if ($activeCompany?->logo)
                <img src="{{ asset('storage/' . $activeCompany->logo) }}" alt="{{ $activeCompany->name }}" class="company-logo">
            @endif
            <span class="company-name">{{ $activeCompany?->name ?? 'ERP Garment' }}</span>
        </a>
        <div class="sidebar-toggler not-active"><span></span><span></span><span></span></div>
    </div>

    <div class="sidebar-search">
        <label class="visually-hidden" for="sidebar-menu-search">{{ __('ui.menu_search.label') }}</label>
        <div class="input-group input-group-sm">
            <input type="search" id="sidebar-menu-search" class="form-control" placeholder="{{ __('ui.menu_search.placeholder') }}" autocomplete="off" aria-controls="sidebar-menu-list">
        </div>
        <p id="sidebar-search-empty" class="small text-muted mt-2 mb-0" role="status" hidden>{{ __('ui.menu_search.empty') }}</p>
    </div>
    <div class="sidebar-body">
        <ul class="nav" id="sidebar-menu-list">
            @can('dashboard.view')
                <li class="nav-item nav-category">{{ __('ui.nav.main') }}</li>
                <li class="nav-item">
                    <a href="{{ route('super.dashboard') }}" class="nav-link {{ request()->routeIs('super.dashboard') ? 'active' : '' }}">
                        <i class="link-icon" data-feather="home"></i><span class="link-title">{{ __('ui.nav.dashboard') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Pengelolaan akun dan hak akses. --}}
            @canany(['user.menu', 'role.menu', 'permission.menu'])
                <li class="nav-item nav-category">{{ __('ui.nav.access') }}</li>
            @endcanany
            @can('user.menu')
                <li class="nav-item"><a href="{{ route('super.user.index') }}" class="nav-link {{ request()->routeIs('super.user.*') ? 'active' : '' }}"><i class="link-icon" data-feather="users"></i><span class="link-title">{{ __('ui.nav.users') }}</span></a></li>
            @endcan
            @can('role.menu')
                <li class="nav-item"><a href="{{ route('super.roles.index') }}" class="nav-link {{ request()->routeIs('super.role.*') ? 'active' : '' }}"><i class="link-icon" data-feather="shield"></i><span class="link-title">{{ __('ui.nav.roles') }}</span></a></li>
            @endcan
            @can('permission.menu')
                <li class="nav-item"><a href="{{ route('super.permissions.index') }}" class="nav-link {{ request()->routeIs('super.permission.*') ? 'active' : '' }}"><i class="link-icon" data-feather="key"></i><span class="link-title">{{ __('ui.nav.permissions') }}</span></a></li>
            @endcan

            {{-- Master konfigurasi yang ditempatkan di Setting sesuai permintaan. --}}
            @canany(['categories.view', 'units.view', 'products.view', 'importers.view', 'exporters.view', 'company.view', 'general-settings.view', 'email-settings.view', 'document-numbering.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.settings') }}</li>
            @endcanany
            @can('categories.view')
                <li class="nav-item"><a href="{{ route('super.categories.index') }}" class="nav-link {{ request()->routeIs('super.categories.*') ? 'active' : '' }}"><i class="link-icon" data-feather="tag"></i><span class="link-title">{{ __('ui.nav.categories') }}</span></a></li>
            @endcan
            @can('units.view')
                <li class="nav-item"><a href="{{ route('super.units.index') }}" class="nav-link {{ request()->routeIs('super.units.*') ? 'active' : '' }}"><i class="link-icon" data-feather="box"></i><span class="link-title">{{ __('ui.nav.units') }}</span></a></li>
            @endcan
            @can('products.view')
                @foreach (['color' => 'droplet', 'size' => 'grid'] as $dimensionKind => $dimensionIcon)
                    <li class="nav-item"><a href="{{ route('super.variant-dimensions.index', $dimensionKind) }}" class="nav-link {{ request()->routeIs('super.variant-dimensions.index') && request()->route('kind') === $dimensionKind ? 'active' : '' }}"><i class="link-icon" data-feather="{{ $dimensionIcon }}"></i><span class="link-title">{{ __('variants.master_'.$dimensionKind) }}</span></a></li>
                @endforeach
            @endcan
            @can('importers.view')
                <li class="nav-item"><a href="{{ route('super.importers.index') }}" class="nav-link {{ request()->routeIs('super.importers.*') ? 'active' : '' }}"><i class="link-icon" data-feather="globe"></i><span class="link-title">{{ __('ui.master.importers') }}</span></a></li>
            @endcan
            @can('exporters.view')
                <li class="nav-item"><a href="{{ route('super.exporters.index') }}" class="nav-link {{ request()->routeIs('super.exporters.*') ? 'active' : '' }}"><i class="link-icon" data-feather="globe"></i><span class="link-title">{{ __('ui.master.exporters') }}</span></a></li>
            @endcan
            @can('company.view')
                <li class="nav-item"><a href="{{ route('super.companies.index') }}" class="nav-link {{ request()->routeIs('super.companies.*') ? 'active' : '' }}"><i class="link-icon" data-feather="briefcase"></i><span class="link-title">{{ __('ui.nav.companies') }}</span></a></li>
            @endcan
            @can('general-settings.view')
                <li class="nav-item"><a href="{{ route('super.settings.general') }}" class="nav-link {{ request()->routeIs('super.settings.general') ? 'active' : '' }}"><i class="link-icon" data-feather="settings"></i><span class="link-title">{{ __('ui.nav.general_settings') }}</span></a></li>
            @endcan
            @can('email-settings.view')
                <li class="nav-item"><a href="{{ route('super.settings.email') }}" class="nav-link {{ request()->routeIs('super.settings.email') ? 'active' : '' }}"><i class="link-icon" data-feather="mail"></i><span class="link-title">{{ __('ui.nav.email_settings') }}</span></a></li>
            @endcan
            @can('document-numbering.view')
                <li class="nav-item"><a href="{{ route('super.settings.document-numbering') }}" class="nav-link {{ request()->routeIs('super.settings.document-numbering') ? 'active' : '' }}"><i class="link-icon" data-feather="hash"></i><span class="link-title">{{ __('ui.nav.document_numbering') }}</span></a></li>
            @endcan

            {{-- Urutan PPIC: master, BOM, MO, lalu pengadaan. --}}
            @canany(['suppliers.view', 'customers.view', 'styles.view', 'products.view', 'boms.view', 'production-orders.view', 'purchase-orders.view', 'purchase-requests.view', 'material-requests.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.ppic') }}</li>
            @endcanany
            @can('suppliers.view')
                <li class="nav-item"><a href="{{ route('super.suppliers.index') }}" class="nav-link {{ request()->routeIs('super.suppliers.*') ? 'active' : '' }}"><i class="link-icon" data-feather="truck"></i><span class="link-title">{{ __('ui.nav.suppliers') }}</span></a></li>
            @endcan
            @can('customers.view')
                <li class="nav-item"><a href="{{ route('super.customers.index') }}" class="nav-link {{ request()->routeIs('super.customers.*') ? 'active' : '' }}"><i class="link-icon" data-feather="users"></i><span class="link-title">{{ __('ui.nav.customers') }}</span></a></li>
            @endcan
            @can('products.view')
                <li class="nav-item"><a href="{{ route('super.products.index') }}" class="nav-link {{ request()->routeIs('super.products.*') ? 'active' : '' }}"><i class="link-icon" data-feather="package"></i><span class="link-title">{{ __('ui.nav.item') }}</span></a></li>
            @endcan
            @can('boms.view')
                <li class="nav-item"><a href="{{ route('super.boms.styles') }}" class="nav-link {{ request()->routeIs('super.boms.styles') ? 'active' : '' }}"><i class="link-icon" data-feather="tag"></i><span class="link-title">{{ __('variants.master_style') }}</span></a></li>
                <li class="nav-item"><a href="{{ route('super.boms.index') }}" class="nav-link {{ request()->routeIs('super.boms.*') && !request()->routeIs('super.boms.styles') ? 'active' : '' }}"><i class="link-icon" data-feather="git-branch"></i><span class="link-title">BOM</span></a></li>
            @endcan
            @can('production-orders.view')
                <li class="nav-item"><a href="{{ route('super.material-orders.index') }}" class="nav-link {{ request()->routeIs('super.material-orders.*') ? 'active' : '' }}"><i class="link-icon" data-feather="clipboard"></i><span class="link-title">{{ __('ui.workflow.material_order') }}</span></a></li>
            @endcan
            @can('material-requests.view')
                <li class="nav-item"><a href="{{ route('super.material-requests.index') }}" class="nav-link {{ request()->routeIs('super.material-requests.*') ? 'active' : '' }}"><i class="link-icon" data-feather="clipboard"></i><span class="link-title">{{ __('ui.nav.material_requests') }}</span></a></li>
            @endcan
            @can('purchase-requests.view')
                <li class="nav-item"><a href="{{ route('super.purchase-requests.index') }}" class="nav-link {{ request()->routeIs('super.purchase-requests.*') ? 'active' : '' }}"><i class="link-icon" data-feather="shopping-cart"></i><span class="link-title">{{ __('ui.nav.purchase_requests') }}</span></a></li>
            @endcan
            @can('purchase-orders.view')
                <li class="nav-item"><a href="{{ route('super.purchase-orders.index') }}" class="nav-link {{ request()->routeIs('super.purchase-orders.*') ? 'active' : '' }}"><i class="link-icon" data-feather="shopping-bag"></i><span class="link-title">{{ __('ui.nav.purchase_orders') }}</span></a></li>
            @endcan

            @canany(['bc23.view','bc262.view','bc27.view','bc40.view','bc20.view'])
                <li class="nav-item nav-category">{{ __('bc23.group') }}</li>
            @endcanany
            @can('bc23.view')
                <li class="nav-item"><a href="{{ route('super.bc-in.bc23.index') }}" class="nav-link {{ request()->routeIs('super.bc-in.bc23.*') ? 'active' : '' }}"><i class="link-icon" data-feather="file-text"></i><span class="link-title">BC 2.3</span></a></li>
            @endcan
            @can('bc262.view')
                <li class="nav-item"><a href="{{ route('super.bc-in.bc262.index') }}" class="nav-link {{ request()->routeIs('super.bc-in.bc262.*') ? 'active' : '' }}"><i class="link-icon" data-feather="file-text"></i><span class="link-title">BC 2.6.2</span></a></li>
            @endcan
            @can('bc27.view')
                <li class="nav-item"><a href="{{ route('super.bc-in.bc27.index') }}" class="nav-link {{ request()->routeIs('super.bc-in.bc27.*') ? 'active' : '' }}"><i class="link-icon" data-feather="file-text"></i><span class="link-title">{{ __('bc27.menu') }}</span></a></li>
            @endcan
            @can('bc40.view')
                <li class="nav-item"><a href="{{ route('super.bc-in.bc40.index') }}" class="nav-link {{ request()->routeIs('super.bc-in.bc40.*') ? 'active' : '' }}"><i class="link-icon" data-feather="file-text"></i><span class="link-title">{{ __('bc40.menu') }}</span></a></li>
            @endcan
            @can('bc20.view')
                <li class="nav-item"><a href="{{ route('super.bc-in.bc20.index') }}" class="nav-link {{ request()->routeIs('super.bc-in.bc20.*') ? 'active' : '' }}"><i class="link-icon" data-feather="file-text"></i><span class="link-title">{{ __('bc20.menu') }}</span></a></li>
            @endcan

            @canany(['goods-receipts.view', 'purchase-returns.view', 'material-issues.view', 'warehouse-production-receipts.view', 'damaged-items.view', 'lost-items.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.warehouse') }}</li>
            @endcanany
            @can('warehouse-production-receipts.view')
                <li class="nav-item"><a href="{{ route('super.warehouse-production-receipts.index') }}" class="nav-link {{ request()->routeIs('super.warehouse-production-receipts.*') ? 'active' : '' }}"><i class="link-icon" data-feather="inbox"></i><span class="link-title">{{ __('ui.production_return.receipt_title') }}</span></a></li>
            @endcan
            @can('material-issues.view')
                <li class="nav-item"><a href="{{ route('super.material-issues.index') }}" class="nav-link {{ request()->routeIs('super.material-issues.*') ? 'active' : '' }}"><i class="link-icon" data-feather="send"></i><span class="link-title">{{ __('ui.material_issue.title') }}</span></a></li>
            @endcan
            @can('goods-receipts.view')
                <li class="nav-item"><a href="{{ route('super.goods-receipts.index') }}" class="nav-link {{ request()->routeIs('super.goods-receipts.*') ? 'active' : '' }}"><i class="link-icon" data-feather="package"></i><span class="link-title">{{ __('ui.nav.goods_receipts') }}</span></a></li>
            @endcan
            @can('purchase-returns.view')
                <li class="nav-item"><a href="{{ route('super.purchase-returns.index') }}" class="nav-link {{ request()->routeIs('super.purchase-returns.*') ? 'active' : '' }}"><i class="link-icon" data-feather="corner-up-left"></i><span class="link-title">{{ __('ui.nav.purchase_returns') }}</span></a></li>
            @endcan
            @can('damaged-items.view')
                <li class="nav-item"><a href="{{ route('super.damaged-items.index') }}" class="nav-link {{ request()->routeIs('super.damaged-items.*') ? 'active' : '' }}"><i class="link-icon" data-feather="alert-triangle"></i><span class="link-title">{{ __('ui.damage.title') }}</span></a></li>
            @endcan
            @can('lost-items.view')
                <li class="nav-item"><a href="{{ route('super.lost-items.index') }}" class="nav-link {{ request()->routeIs('super.lost-items.*') ? 'active' : '' }}"><i class="link-icon" data-feather="search"></i><span class="link-title">{{ __('ui.lost.title') }}</span></a></li>
            @endcan


            {{-- Warehouse contains receipt, issue, return, and loss/damage transactions. --}}
            @canany(['raw-material-orders.view', 'support-material-orders.view', 'production-receipts.view', 'non-production-material-usages.view', 'return-to-warehouse.view', 'cutting.view', 'sewing.view', 'packing.view', 'finished-goods.view', 'goods-sent-notes.view', 'goods-sent-approvals.view', 'wip-issues.view', 'stock-card.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.production') }}</li>
            @endcanany
            @can('raw-material-orders.view')
                <li class="nav-item"><a href="{{ route('super.raw-material-orders.index') }}" class="nav-link {{ request()->routeIs('super.raw-material-orders.*') ? 'active' : '' }}"><i class="link-icon" data-feather="layers"></i><span class="link-title">{{ __('ui.raw_material_order.title') }}</span></a></li>
            @endcan
            @can('support-material-orders.view')
                <li class="nav-item"><a href="{{ route('super.support-material-orders.index') }}" class="nav-link {{ request()->routeIs('super.support-material-orders.*') ? 'active' : '' }}"><i class="link-icon" data-feather="package"></i><span class="link-title">{{ __('ui.support_material_order.title') }}</span></a></li>
            @endcan
            @can('production-receipts.view')
                <li class="nav-item"><a href="{{ route('super.production-receipts.index') }}" class="nav-link {{ request()->routeIs('super.production-receipts.*') ? 'active' : '' }}"><i class="link-icon" data-feather="inbox"></i><span class="link-title">{{ __('ui.production_receipt.title') }}</span></a></li>
            @endcan
            @can('non-production-material-usages.view')
                <li class="nav-item"><a href="{{ route('super.non-production-material-usages.index') }}" class="nav-link {{ request()->routeIs('super.non-production-material-usages.*') ? 'active' : '' }}"><i class="link-icon" data-feather="package" ></i><span class="link-title">{{ __('ui.non_production_usage.title') }}</span></a></li>
            @endcan
            @can('return-to-warehouse.view')
                <li class="nav-item"><a href="{{ route('super.return-to-warehouse.index') }}" class="nav-link {{ request()->routeIs('super.return-to-warehouse.*') ? 'active' : '' }}"><i class="link-icon" data-feather="corner-up-left"></i><span class="link-title">{{ __('ui.production_return.return_title') }}</span></a></li>
            @endcan
            @can('cutting.view')
                <li class="nav-item"><a href="{{ route('super.cutting.index') }}" class="nav-link {{ request()->routeIs('super.cutting.*') ? 'active' : '' }}"><i class="link-icon" data-feather="scissors"></i><span class="link-title">{{ __('ui.cutting.title') }}</span></a></li>
            @endcan
            @can('sewing.view')
                <li class="nav-item"><a href="{{ route('super.sewing.index') }}" class="nav-link {{ request()->routeIs('super.sewing.*') ? 'active' : '' }}"><i class="link-icon" data-feather="activity"></i><span class="link-title">{{ __('ui.sewing.title') }}</span></a></li>
            @endcan
            @can('packing.view')
                <li class="nav-item"><a href="{{ route('super.packing.index') }}" class="nav-link {{ request()->routeIs('super.packing.*') ? 'active' : '' }}"><i class="link-icon" data-feather="archive"></i><span class="link-title">{{ __('ui.packing.title') }}</span></a></li>
            @endcan
            @can('finished-goods.view')
                <li class="nav-item"><a href="{{ route('super.finished-goods.index') }}" class="nav-link {{ request()->routeIs('super.finished-goods.*') ? 'active' : '' }}"><i class="link-icon" data-feather="package"></i><span class="link-title">{{ __('ui.finished_goods.title') }}</span></a></li>
            @endcan
            @can('goods-sent-notes.view')
                <li class="nav-item"><a href="{{ route('super.goods-sent-notes.index') }}" class="nav-link {{ request()->routeIs('super.goods-sent-notes.*') ? 'active' : '' }}"><i class="link-icon" data-feather="truck"></i><span class="link-title">{{ __('ui.gsn.title') }}</span></a></li>
            @endcan
            @can('goods-sent-approvals.view')
                <li class="nav-item"><a href="{{ route('super.delivery-note-approvals.index') }}" class="nav-link {{ request()->routeIs('super.delivery-note-approvals.*') ? 'active' : '' }}"><i class="link-icon" data-feather="check-circle"></i><span class="link-title">{{ __('ui.delivery_note_approval.title') }}</span></a></li>
            @endcan
            @can('wip-issues.view')
                <li class="nav-item"><a href="{{ route('super.wip-issues.index') }}" class="nav-link {{ request()->routeIs('super.wip-issues.*') ? 'active' : '' }}"><i class="link-icon" data-feather="corner-down-right"></i><span class="link-title">{{ __('ui.wip_issue.title') }}</span></a></li>
            @endcan
            @can('stock-card.view')
                <li class="nav-item"><a href="{{ route('super.finished-goods-stock-card.index') }}" class="nav-link {{ request()->routeIs('super.finished-goods-stock-card.*') ? 'active' : '' }}"><i class="link-icon" data-feather="layers"></i><span class="link-title">{{ __('ui.finished_goods.stock_card') }}</span></a></li>
            @endcan
            {{-- Saldo, stock opname, dan kartu stok lainnya. --}}
            @canany(['scrap-receipts.view', 'scrap-issues.view', 'stock-card.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.scrap_materials') }}</li>
            @endcanany
            @can('scrap-receipts.view')
                <li class="nav-item"><a href="{{ route('super.scrap-receipts.index') }}" class="nav-link {{ request()->routeIs('super.scrap-receipts.*') ? 'active' : '' }}"><i class="link-icon" data-feather="package"></i><span class="link-title">{{ __('ui.scrap_receipt.title') }}</span></a></li>
            @endcan
            @can('scrap-issues.view')
                <li class="nav-item"><a href="{{ route('super.scrap-issues.index') }}" class="nav-link {{ request()->routeIs('super.scrap-issues.*') ? 'active' : '' }}"><i class="link-icon" data-feather="corner-up-right"></i><span class="link-title">{{ __('ui.scrap_issue.title') }}</span></a></li>
            @endcan
            @can('stock-card.view')
                <li class="nav-item"><a href="{{ route('super.scrap-stock-card.index') }}" class="nav-link {{ request()->routeIs('super.scrap-stock-card.*') ? 'active' : '' }}"><i class="link-icon" data-feather="layers"></i><span class="link-title">{{ __('ui.scrap_stock_card.title') }}</span></a></li>
            @endcan
            @canany(['equipment-receipts.view', 'equipment-issues.view', 'stock-card.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.machines') }}</li>
            @endcanany
            @can('equipment-receipts.view')
                <li class="nav-item"><a href="{{ route('super.equipment-receipts.index') }}" class="nav-link {{ request()->routeIs('super.equipment-receipts.*') ? 'active' : '' }}"><i class="link-icon" data-feather="tool"></i><span class="link-title">{{ __('ui.machine_receipt.title') }}</span></a></li>
            @endcan
            @can('equipment-issues.view')
                <li class="nav-item"><a href="{{ route('super.equipment-issues.index') }}" class="nav-link {{ request()->routeIs('super.equipment-issues.*') ? 'active' : '' }}"><i class="link-icon" data-feather="log-out"></i><span class="link-title">{{ __('ui.machine_issue.title') }}</span></a></li>
            @endcan
            @can('stock-card.view')
                <li class="nav-item"><a href="{{ route('super.equipment-stock-card.index') }}" class="nav-link {{ request()->routeIs('super.equipment-stock-card.*') ? 'active' : '' }}"><i class="link-icon" data-feather="layers"></i><span class="link-title">{{ __('ui.machine_stock_card.title') }}</span></a></li>
            @endcan
            @canany(['subcontractors.view', 'subcontract-contracts.view', 'subcontract-semi-finished.view', 'subcontract-material-issues.view', 'subcontract-wip-issues.view', 'subcontract-receipts.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.subcontract_group') }}</li>
            @endcanany
            @canany(['subcontractors.view', 'subcontract-contracts.view'])
                <li class="nav-item"><a href="{{ route('super.subcontract.index') }}" class="nav-link {{ request()->routeIs('super.subcontract.index', 'super.subcontract.subcontractors.*', 'super.subcontract.contracts.*') ? 'active' : '' }}"><i class="link-icon" data-feather="briefcase"></i><span class="link-title">{{ __('ui.nav.subcontract_master_contracts') }}</span></a></li>
            @endcanany
            @can('subcontract-semi-finished.view')
                <li class="nav-item"><a href="{{ route('super.subcontract.semi-finished.index') }}" class="nav-link {{ request()->routeIs('super.subcontract.semi-finished.*') ? 'active' : '' }}"><i class="link-icon" data-feather="layers"></i><span class="link-title">{{ __('ui.nav.subcontract_semi_finished') }}</span></a></li>
            @endcan
            @can('subcontract-material-issues.view')
                <li class="nav-item"><a href="{{ route('super.subcontract.material-issues.index') }}" class="nav-link {{ request()->routeIs('super.subcontract.material-issues.*') ? 'active' : '' }}"><i class="link-icon" data-feather="send"></i><span class="link-title">{{ __('subcontract_material_issue.title') }}</span></a></li>
            @endcan
            @can('subcontract-wip-issues.view')
                <li class="nav-item"><a href="{{ route('super.subcontract.wip-issues.index') }}" class="nav-link {{ request()->routeIs('super.subcontract.wip-issues.*') ? 'active' : '' }}"><i class="link-icon" data-feather="send"></i><span class="link-title">{{ __('subcontract_wip_issue.title') }}</span></a></li>
            @endcan
            @can('subcontract-receipts.view')
                <li class="nav-item"><a href="{{ route('super.subcontract.receipts.index') }}" class="nav-link {{ request()->routeIs('super.subcontract.receipts.*') ? 'active' : '' }}"><i class="link-icon" data-feather="inbox"></i><span class="link-title">{{ __('subcontract_receipt.title') }}</span></a></li>
            @endcan
            @can('subcontract-contracts.view')
                <li class="nav-item"><a href="{{ route('super.subcontract.contract-monitoring.index') }}" class="nav-link {{ request()->routeIs('super.subcontract.contract-monitoring.*') ? 'active' : '' }}"><i class="link-icon" data-feather="clipboard"></i><span class="link-title">{{ __('subcontract_contract_monitoring.title') }}</span></a></li>
                <li class="nav-item"><a href="{{ route('super.subcontract.goods-monitoring.index') }}" class="nav-link {{ request()->routeIs('super.subcontract.goods-monitoring.*') ? 'active' : '' }}"><i class="link-icon" data-feather="package"></i><span class="link-title">{{ __('subcontract_goods_monitoring.title') }}</span></a></li>
            @endcan

            @canany(['bc30.view','bc261.view','bc27out.view','bc41.view','bc25.view'])
                <li class="nav-item nav-category">BC OUT</li>
            @endcanany
            @can('bc30.view')
                <li class="nav-item"><a href="{{ route('super.bc-out.bc30.index') }}" class="nav-link {{ request()->routeIs('super.bc-out.bc30.*') ? 'active' : '' }}"><i class="link-icon" data-feather="file-text"></i><span class="link-title">BC 3.0</span></a></li>
            @endcan
            @can('bc261.view')
                <li class="nav-item"><a href="{{ route('super.bc-out.bc261.index') }}" class="nav-link {{ request()->routeIs('super.bc-out.bc261.*') ? 'active' : '' }}"><i class="link-icon" data-feather="file-text"></i><span class="link-title">BC 2.6.1 Out</span></a></li>
            @endcan
            @can('bc27out.view')
                <li class="nav-item"><a href="{{ route('super.bc-out.bc27out.index') }}" class="nav-link {{ request()->routeIs('super.bc-out.bc27out.*') ? 'active' : '' }}"><i class="link-icon" data-feather="file-text"></i><span class="link-title">BC 2.7 Out</span></a></li>
            @endcan
            @can('bc41.view')
                <li class="nav-item"><a href="{{ route('super.bc-out.bc41.index') }}" class="nav-link {{ request()->routeIs('super.bc-out.bc41.*') ? 'active' : '' }}"><i class="link-icon" data-feather="file-text"></i><span class="link-title">BC 4.1 Out</span></a></li>
            @endcan
            @can('bc25.view')
                <li class="nav-item"><a href="{{ route('super.bc-out.bc25.index') }}" class="nav-link {{ request()->routeIs('super.bc-out.bc25.*') ? 'active' : '' }}"><i class="link-icon" data-feather="file-text"></i><span class="link-title">BC 2.5 Out</span></a></li>
            @endcan

            @canany(['purchase-report.view', 'inventory-report.view', 'audit-logs.view'])
                <li class="nav-item nav-category">{{ __('ui.report_menu.bc') }}</li>
            @endcanany
            @can('purchase-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.incoming.index') }}" class="nav-link {{ request()->routeIs('super.reports.incoming.*') ? 'active' : '' }}"><i class="link-icon" data-feather="download"></i><span class="link-title">{{ __('ui.report_menu.incoming') }}</span></a></li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.outgoing.index') }}" class="nav-link {{ request()->routeIs('super.reports.outgoing.*') ? 'active' : '' }}"><i class="link-icon" data-feather="upload"></i><span class="link-title">{{ __('ui.report_menu.outgoing') }}</span></a></li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.material-mutation.index') }}" class="nav-link {{ request()->routeIs('super.reports.material-mutation.*') ? 'active' : '' }}"><i class="link-icon" data-feather="repeat"></i><span class="link-title">{{ __('ui.report_menu.material_mutation') }}</span></a></li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.wip-position.index') }}" class="nav-link {{ request()->routeIs('super.reports.wip-position.*') ? 'active' : '' }}"><i class="link-icon" data-feather="layers"></i><span class="link-title">{{ __('ui.report_menu.wip_position') }}</span></a></li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.finished-goods-mutation.index') }}" class="nav-link {{ request()->routeIs('super.reports.finished-goods-mutation.*') ? 'active' : '' }}"><i class="link-icon" data-feather="box"></i><span class="link-title">{{ __('ui.report_menu.finished_goods_mutation') }}</span></a></li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.capital-goods-mutation.index') }}" class="nav-link {{ request()->routeIs('super.reports.capital-goods-mutation.*') ? 'active' : '' }}"><i class="link-icon" data-feather="tool"></i><span class="link-title">{{ __('ui.report_menu.capital_goods_mutation') }}</span></a></li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.reject-scrap-mutation.index') }}" class="nav-link {{ request()->routeIs('super.reports.reject-scrap-mutation.*') ? 'active' : '' }}"><i class="link-icon" data-feather="archive"></i><span class="link-title">{{ __('ui.report_menu.reject_scrap_mutation') }}</span></a></li>
            @endcan
            @can('audit-logs.view')
                <li class="nav-item"><a href="{{ route('super.audit-logs.index') }}" class="nav-link {{ request()->routeIs('super.audit-logs.*') ? 'active' : '' }}"><i class="link-icon" data-feather="activity"></i><span class="link-title">{{ __('ui.nav.audit_logs') }}</span></a></li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.production-plan.index') }}" class="nav-link {{ request()->routeIs('super.reports.production-plan.*') ? 'active' : '' }}"><i class="link-icon" data-feather="calendar"></i><span class="link-title">{{ __('ui.report_menu.production_plan') }}</span></a></li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.daily-production-status.index') }}" class="nav-link {{ request()->routeIs('super.reports.daily-production-status.*') ? 'active' : '' }}"><i class="link-icon" data-feather="bar-chart-2"></i><span class="link-title">{{ __('ui.report_menu.daily_production_status') }}</span></a></li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.production-result.index') }}" class="nav-link {{ request()->routeIs('super.reports.production-result.*') ? 'active' : '' }}"><i class="link-icon" data-feather="check-square"></i><span class="link-title">{{ __('ui.report_menu.production_result') }}</span></a></li>
            @endcan
            @can('purchase-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.supplier-return.index') }}" class="nav-link {{ request()->routeIs('super.reports.supplier-return.*') ? 'active' : '' }}"><i class="link-icon" data-feather="corner-up-left"></i><span class="link-title">{{ __('ui.report_menu.supplier_return') }}</span></a></li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.warehouse-return.index') }}" class="nav-link {{ request()->routeIs('super.reports.warehouse-return.*') ? 'active' : '' }}"><i class="link-icon" data-feather="corner-down-left"></i><span class="link-title">{{ __('ui.report_menu.warehouse_return') }}</span></a></li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.finished-goods-return.index') }}" class="nav-link {{ request()->routeIs('super.reports.finished-goods-return.*') ? 'active' : '' }}"><i class="link-icon" data-feather="rotate-ccw"></i><span class="link-title">{{ __('ui.report_menu.finished_goods_return') }}</span></a></li>
            @endcan

            @canany(['stocks.view', 'stock-card.view', 'stock-opnames.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.inventory') }}</li>
            @endcanany
            @can('stocks.view')
                <li class="nav-item"><a href="{{ route('super.stocks.index') }}" class="nav-link {{ request()->routeIs('super.stocks.*') ? 'active' : '' }}"><i class="link-icon" data-feather="archive"></i><span class="link-title">{{ __('ui.nav.stocks') }}</span></a></li>
            @endcan
            @can('stock-opnames.view')
                <li class="nav-item"><a href="{{ route('super.stock-opnames.index') }}" class="nav-link {{ request()->routeIs('super.stock-opnames.*') ? 'active' : '' }}"><i class="link-icon" data-feather="clipboard"></i><span class="link-title">{{ __('ui.nav.stock_opname') }}</span></a></li>
            @endcan
            @can('stock-card.view')
                <li class="nav-item"><a href="{{ route('super.stock-card.index') }}" class="nav-link {{ request()->routeIs('super.stock-card.*') ? 'active' : '' }}"><i class="link-icon" data-feather="layers"></i><span class="link-title">{{ __('ui.nav.stock_card') }}</span></a></li>
            @endcan

            @canany(['purchase-report.view', 'inventory-report.view', 'cash-bank-report.view', 'profit-loss.view'])
                <li class="nav-item nav-category">{{ __('ui.report_menu.operational') }}</li>
            @endcanany
            @can('purchase-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.purchase.index') }}" class="nav-link {{ request()->routeIs('super.reports.purchase.*') ? 'active' : '' }}"><i class="link-icon" data-feather="shopping-cart"></i><span class="link-title">{{ __('ui.nav.purchase_report') }}</span></a></li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.inventory.index') }}" class="nav-link {{ request()->routeIs('super.reports.inventory.*') ? 'active' : '' }}"><i class="link-icon" data-feather="package"></i><span class="link-title">{{ __('ui.nav.inventory_report') }}</span></a></li>
            @endcan
            @can('cash-bank-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.cash-bank.index') }}" class="nav-link {{ request()->routeIs('super.reports.cash-bank.*') ? 'active' : '' }}"><i class="link-icon" data-feather="dollar-sign"></i><span class="link-title">{{ __('ui.nav.cash_bank_report') }}</span></a></li>
            @endcan

            @can('profit-loss.view')
                <li class="nav-item"><a href="{{ route('super.profit-loss.index') }}" class="nav-link {{ request()->routeIs('super.profit-loss.*') ? 'active' : '' }}"><i class="link-icon" data-feather="trending-up"></i><span class="link-title">{{ __('ui.nav.profit_loss') }}</span></a></li>
            @endcan

            @canany(['chart-of-accounts.view', 'journal.view', 'account-payable.view', 'cash-bank.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.accounting') }}</li>
            @endcanany
            @can('chart-of-accounts.view')
                <li class="nav-item"><a href="{{ route('super.chart-of-accounts.index') }}" class="nav-link {{ request()->routeIs('super.chart-of-accounts.*') ? 'active' : '' }}"><i class="link-icon" data-feather="list"></i><span class="link-title">{{ __('ui.nav.chart_of_accounts') }}</span></a></li>
            @endcan
            @can('journal.view')
                <li class="nav-item"><a href="{{ route('super.journals.index') }}" class="nav-link {{ request()->routeIs('super.journals.*') ? 'active' : '' }}"><i class="link-icon" data-feather="book-open"></i><span class="link-title">{{ __('ui.nav.journals') }}</span></a></li>
            @endcan
            @can('account-payable.view')
                <li class="nav-item"><a href="{{ route('super.account-payable.index') }}" class="nav-link {{ request()->routeIs('super.account-payable.*') ? 'active' : '' }}"><i class="link-icon" data-feather="credit-card"></i><span class="link-title">{{ __('ui.nav.accounts_payable') }}</span></a></li>
            @endcan
            @can('cash-bank.view')
                <li class="nav-item"><a href="{{ route('super.cash-bank.index') }}" class="nav-link {{ request()->routeIs('super.cash-bank.*') ? 'active' : '' }}"><i class="link-icon" data-feather="briefcase"></i><span class="link-title">{{ __('ui.nav.cash_bank') }}</span></a></li>
            @endcan

        </ul>
    </div>
</nav>

@push('scripts')
<script>
(() => {
    const input = document.getElementById('sidebar-menu-search');
    const list = document.getElementById('sidebar-menu-list');
    const empty = document.getElementById('sidebar-search-empty');
    const normalize = text => text.toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim();
    const groups = [];
    let group;
    [...list.children].forEach(item => {
        if (item.classList.contains('nav-category')) {
            group = {heading: item, items: []}; groups.push(group);
        } else {
            if (!group) { group = {heading: null, items: []}; groups.push(group); }
            group.items.push({element: item, label: normalize(item.querySelector('.link-title')?.textContent || item.textContent)});
        }
    });
    function filter() {
        const words = normalize(input.value).split(' ').filter(Boolean);
        let count = 0;
        groups.forEach(group => {
            let visible = 0;
            const category = normalize(group.heading?.textContent || '');
            group.items.forEach(item => {
                const match = words.every(word => (category + ' ' + item.label).includes(word));
                item.element.hidden = !match;
                if (match) visible++;
            });
            if (group.heading) group.heading.hidden = visible === 0;
            count += visible;
        });
        empty.hidden = count > 0 || words.length === 0;
    }
    input.addEventListener('input', filter);
    function clear() { input.value = ''; filter(); input.focus(); }
    input.addEventListener('keydown', event => { if (event.key === 'Escape') { event.preventDefault(); clear(); } });
})();
</script>
@endpush
