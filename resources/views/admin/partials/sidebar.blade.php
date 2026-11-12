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

    <div class="sidebar-body">
        <ul class="nav">
            @can('dashboard.view')
                <li class="nav-item nav-category">{{ __('ui.nav.main') }}</li>
                <li class="nav-item">
                    <a href="{{ route('super.dashboard') }}" class="nav-link {{ request()->routeIs('super.dashboard') ? 'active' : '' }}">
                        <i class="link-icon" data-feather="home"></i><span class="link-title">{{ __('ui.nav.dashboard') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Referensi: Pengaturan & akses, termasuk data master. --}}
            @canany(['user.menu', 'role.menu', 'permission.menu', 'categories.view', 'units.view', 'products.view', 'suppliers.view', 'importers.view', 'customers.view', 'pricelists.view', 'boms.view', 'stock-opnames.view', 'company.view', 'general-settings.view', 'email-settings.view', 'document-numbering.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.settings_access') }}</li>
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
            @can('categories.view')
                <li class="nav-item"><a href="{{ route('super.categories.index') }}" class="nav-link {{ request()->routeIs('super.categories.*') ? 'active' : '' }}"><i class="link-icon" data-feather="tag"></i><span class="link-title">{{ __('ui.nav.categories') }}</span></a></li>
            @endcan
            @can('units.view')
                <li class="nav-item"><a href="{{ route('super.units.index') }}" class="nav-link {{ request()->routeIs('super.units.*') ? 'active' : '' }}"><i class="link-icon" data-feather="box"></i><span class="link-title">{{ __('ui.nav.units') }}</span></a></li>
            @endcan
            @can('importers.view')
                <li class="nav-item"><a href="{{ route('super.importers.index') }}" class="nav-link {{ request()->routeIs('super.importers.*') ? 'active' : '' }}"><i class="link-icon" data-feather="globe"></i><span class="link-title">{{ __('ui.master.importers') }}</span></a></li>
            @endcan
            @can('suppliers.view')
                <li class="nav-item"><a href="{{ route('super.suppliers.index') }}" class="nav-link {{ request()->routeIs('super.suppliers.*') ? 'active' : '' }}"><i class="link-icon" data-feather="truck"></i><span class="link-title">{{ __('ui.nav.suppliers') }}</span></a></li>
            @endcan
            @can('customers.view')
                <li class="nav-item"><a href="{{ route('super.customers.index') }}" class="nav-link {{ request()->routeIs('super.customers.*') ? 'active' : '' }}"><i class="link-icon" data-feather="users"></i><span class="link-title">{{ __('ui.nav.customers') }}</span></a></li>
            @endcan
            @can('products.view')
                <li class="nav-item"><a href="{{ route('super.products.index') }}" class="nav-link {{ request()->routeIs('super.products.*') ? 'active' : '' }}"><i class="link-icon" data-feather="package"></i><span class="link-title">{{ __('ui.nav.products') }}</span></a></li>
            @endcan
            @can('pricelists.view')
                <li class="nav-item"><a href="{{ route('super.pricelists.index') }}" class="nav-link {{ request()->routeIs('super.pricelists.*') ? 'active' : '' }}"><i class="link-icon" data-feather="dollar-sign"></i><span class="link-title">{{ __('ui.nav.price_lists') }}</span></a></li>
            @endcan
            @can('boms.view')
                <li class="nav-item"><a href="{{ route('super.boms.index') }}" class="nav-link {{ request()->routeIs('super.boms.*') ? 'active' : '' }}"><i class="link-icon" data-feather="git-branch"></i><span class="link-title">BOM</span></a></li>
            @endcan
            @can('stock-opnames.view')
                <li class="nav-item"><a href="{{ route('super.stock-opnames.index') }}" class="nav-link {{ request()->routeIs('super.stock-opnames.*') ? 'active' : '' }}"><i class="link-icon" data-feather="clipboard"></i><span class="link-title">{{ __('ui.nav.stock_opname') }}</span></a></li>
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

            {{-- Referensi: PPIC dan pengadaan. --}}
            @canany(['material-requests.view', 'purchase-requests.view', 'purchase-orders.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.ppic_purchasing') }}</li>
            @endcanany
            @can('material-requests.view')
                <li class="nav-item"><a href="{{ route('super.material-requests.index') }}" class="nav-link {{ request()->routeIs('super.material-requests.*') ? 'active' : '' }}"><i class="link-icon" data-feather="clipboard"></i><span class="link-title">{{ __('ui.nav.material_requests') }}</span></a></li>
            @endcan
            @can('purchase-requests.view')
                <li class="nav-item"><a href="{{ route('super.purchase-requests.index') }}" class="nav-link {{ request()->routeIs('super.purchase-requests.*') ? 'active' : '' }}"><i class="link-icon" data-feather="shopping-cart"></i><span class="link-title">{{ __('ui.nav.purchase_requests') }}</span></a></li>
            @endcan
            @can('purchase-orders.view')
                <li class="nav-item"><a href="{{ route('super.purchase-orders.index') }}" class="nav-link {{ request()->routeIs('super.purchase-orders.*') ? 'active' : '' }}"><i class="link-icon" data-feather="shopping-bag"></i><span class="link-title">{{ __('ui.nav.purchase_orders') }}</span></a></li>
            @endcan

            {{-- Referensi: gudang dan persediaan. --}}
            @canany(['goods-receipts.view', 'purchase-returns.view', 'stocks.view', 'stock-card.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.warehouse_inventory') }}</li>
            @endcanany
            @can('goods-receipts.view')
                <li class="nav-item"><a href="{{ route('super.goods-receipts.index') }}" class="nav-link {{ request()->routeIs('super.goods-receipts.*') ? 'active' : '' }}"><i class="link-icon" data-feather="package"></i><span class="link-title">{{ __('ui.nav.goods_receipts') }}</span></a></li>
            @endcan
            @can('purchase-returns.view')
                <li class="nav-item"><a href="{{ route('super.purchase-returns.index') }}" class="nav-link {{ request()->routeIs('super.purchase-returns.*') ? 'active' : '' }}"><i class="link-icon" data-feather="corner-up-left"></i><span class="link-title">{{ __('ui.nav.purchase_returns') }}</span></a></li>
            @endcan
            @can('stocks.view')
                <li class="nav-item"><a href="{{ route('super.stocks.index') }}" class="nav-link {{ request()->routeIs('super.stocks.*') ? 'active' : '' }}"><i class="link-icon" data-feather="archive"></i><span class="link-title">{{ __('ui.nav.stocks') }}</span></a></li>
            @endcan
            @can('stock-card.view')
                <li class="nav-item"><a href="{{ route('super.stock-card.index') }}" class="nav-link {{ request()->routeIs('super.stock-card.*') ? 'active' : '' }}"><i class="link-icon" data-feather="layers"></i><span class="link-title">{{ __('ui.nav.stock_card') }}</span></a></li>
            @endcan
            {{-- Referensi: produksi. Halaman BC, scrap/mesin dan subkon belum tersedia di project. --}}
            @canany(['production-orders.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.production') }}</li>
            @endcanany
            @can('production-orders.view')
                <li class="nav-item"><a href="{{ route('super.production-orders.index') }}" class="nav-link {{ request()->routeIs('super.production-orders.*') ? 'active' : '' }}"><i class="link-icon" data-feather="clipboard"></i><span class="link-title">{{ __('ui.workflow.production_order') }}</span></a></li>
            @endcan
            {{-- Modul ERP yang telah ada tetap tersedia. --}}
            @canany(['sales-quotations.view', 'sales-orders.view', 'deliveries.view', 'sales-invoices.view', 'customer-returns.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.sales_shipping') }}</li>
            @endcanany
            @can('sales-quotations.view')
                <li class="nav-item"><a href="{{ route('super.sales-quotations.index') }}" class="nav-link {{ request()->routeIs('super.sales-quotations.*') ? 'active' : '' }}"><i class="link-icon" data-feather="file-text"></i><span class="link-title">{{ __('ui.nav.sales_quotations') }}</span></a></li>
            @endcan
            @can('sales-orders.view')
                <li class="nav-item"><a href="{{ route('super.sales-orders.index') }}" class="nav-link {{ request()->routeIs('super.sales-orders.*') ? 'active' : '' }}"><i class="link-icon" data-feather="shopping-cart"></i><span class="link-title">{{ __('ui.nav.sales_orders') }}</span></a></li>
            @endcan
            @can('deliveries.view')
                <li class="nav-item"><a href="{{ route('super.deliveries.index') }}" class="nav-link {{ request()->routeIs('super.deliveries.*') ? 'active' : '' }}"><i class="link-icon" data-feather="truck"></i><span class="link-title">{{ __('ui.nav.deliveries') }}</span></a></li>
            @endcan
            @can('sales-invoices.view')
                <li class="nav-item"><a href="{{ route('super.sales-invoices.index') }}" class="nav-link {{ request()->routeIs('super.sales-invoices.*') ? 'active' : '' }}"><i class="link-icon" data-feather="file-text"></i><span class="link-title">{{ __('ui.nav.sales_invoices') }}</span></a></li>
            @endcan
            @can('customer-returns.view')
                <li class="nav-item"><a href="{{ route('super.customer-returns.index') }}" class="nav-link {{ request()->routeIs('super.customer-returns.*') ? 'active' : '' }}"><i class="link-icon" data-feather="corner-down-left"></i><span class="link-title">{{ __('ui.nav.customer_returns') }}</span></a></li>
            @endcan

            @canany(['chart-of-accounts.view', 'journal.view', 'account-receivable.view', 'account-payable.view', 'cash-bank.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.accounting') }}</li>
            @endcanany
            @can('chart-of-accounts.view')
                <li class="nav-item"><a href="{{ route('super.chart-of-accounts.index') }}" class="nav-link {{ request()->routeIs('super.chart-of-accounts.*') ? 'active' : '' }}"><i class="link-icon" data-feather="list"></i><span class="link-title">{{ __('ui.nav.chart_of_accounts') }}</span></a></li>
            @endcan
            @can('journal.view')
                <li class="nav-item"><a href="{{ route('super.journals.index') }}" class="nav-link {{ request()->routeIs('super.journals.*') ? 'active' : '' }}"><i class="link-icon" data-feather="book-open"></i><span class="link-title">{{ __('ui.nav.journals') }}</span></a></li>
            @endcan
            @can('account-receivable.view')
                <li class="nav-item"><a href="{{ route('super.account-receivable.index') }}" class="nav-link {{ request()->routeIs('super.account-receivable.*') ? 'active' : '' }}"><i class="link-icon" data-feather="credit-card"></i><span class="link-title">{{ __('ui.nav.accounts_receivable') }}</span></a></li>
            @endcan
            @can('account-payable.view')
                <li class="nav-item"><a href="{{ route('super.account-payable.index') }}" class="nav-link {{ request()->routeIs('super.account-payable.*') ? 'active' : '' }}"><i class="link-icon" data-feather="credit-card"></i><span class="link-title">{{ __('ui.nav.accounts_payable') }}</span></a></li>
            @endcan
            @can('cash-bank.view')
                <li class="nav-item"><a href="{{ route('super.cash-bank.index') }}" class="nav-link {{ request()->routeIs('super.cash-bank.*') ? 'active' : '' }}"><i class="link-icon" data-feather="briefcase"></i><span class="link-title">{{ __('ui.nav.cash_bank') }}</span></a></li>
            @endcan

            @canany(['purchase-report.view', 'inventory-report.view', 'cash-bank-report.view', 'sales-report.view', 'profit-loss.view', 'audit-logs.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.bc_operational_reports') }}</li>
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
            @can('sales-report.view')
                <li class="nav-item"><a href="{{ route('super.reports.sales.index') }}" class="nav-link {{ request()->routeIs('super.reports.sales.*') ? 'active' : '' }}"><i class="link-icon" data-feather="bar-chart-2"></i><span class="link-title">{{ __('ui.nav.sales_report') }}</span></a></li>
            @endcan
            @can('profit-loss.view')
                <li class="nav-item"><a href="{{ route('super.profit-loss.index') }}" class="nav-link {{ request()->routeIs('super.profit-loss.*') ? 'active' : '' }}"><i class="link-icon" data-feather="trending-up"></i><span class="link-title">{{ __('ui.nav.profit_loss') }}</span></a></li>
            @endcan
            @can('audit-logs.view')
                <li class="nav-item"><a href="{{ route('super.audit-logs.index') }}" class="nav-link {{ request()->routeIs('super.audit-logs.*') ? 'active' : '' }}"><i class="link-icon" data-feather="activity"></i><span class="link-title">{{ __('ui.nav.audit_logs') }}</span></a></li>
            @endcan
        </ul>
    </div>
</nav>
