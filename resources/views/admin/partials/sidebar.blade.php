<nav class="sidebar">
    @php
        $companyContext = app(\App\Domain\Companies\Services\CompanyContext::class);
        $activeCompany = $companyContext->has() ? $companyContext->get() : null;
    @endphp

    <div class="sidebar-header">
        <a href="{{ route('super.dashboard') }}" class="sidebar-brand company-brand">

            @if ($activeCompany?->logo)
                <img src="{{ asset('storage/' . $activeCompany->logo) }}" alt="{{ $activeCompany->name }}"
                    class="company-logo">
            @endif

            <span class="company-name">
                {{ $activeCompany?->name ?? 'ERP Garment' }}
            </span>

        </a>

        <div class="sidebar-toggler not-active">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>

    <div class="sidebar-body">
        <ul class="nav">
            {{-- ================= MAIN ================= --}}
            <li class="nav-item nav-category">{{ __('ui.nav.main') }}</li>
            @can('dashboard.view')
                <li class="nav-item">
                    <a href="{{ route('super.dashboard') }}"
                        class="nav-link {{ request()->routeIs('super.dashboard') ? 'active' : '' }}">
                        <i class="link-icon" data-feather="box"></i>
                        <span class="link-title">{{ __('ui.nav.dashboard') }}</span>
                    </a>
                </li>
            @endcan

            {{-- ================= ACCESS CONTROL ================= --}}
            @canany(['user.menu', 'role.menu', 'permission.menu'])
                <li class="nav-item nav-category">{{ __('ui.nav.access_control') }}</li>
            @endcanany

            {{-- Users --}}
            @can('user.menu')
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#menu-users" role="button" aria-expanded="false"
                        aria-controls="menu-users">
                        <i class="link-icon" data-feather="users"></i>
                        <span class="link-title">{{ __('ui.nav.users') }}</span>
                        <i class="link-arrow" data-feather="chevron-down"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('super.user.*') ? 'show' : '' }}" id="menu-users">
                        <ul class="nav sub-menu">
                            <li class="nav-item">
                                <a href="{{ route('super.user.index') }}"
                                    class="nav-link {{ request()->routeIs('super.user.index') ? 'active' : '' }}">
                                    {{ __('ui.nav.show') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            @endcan

            {{-- Roles --}}
            @can('role.menu')
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#menu-roles" role="button" aria-expanded="false"
                        aria-controls="menu-roles">
                        <i class="link-icon" data-feather="shield"></i>
                        <span class="link-title">{{ __('ui.nav.roles') }}</span>
                        <i class="link-arrow" data-feather="chevron-down"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('super.role.*') ? 'show' : '' }}" id="menu-roles">
                        <ul class="nav sub-menu">
                            <li class="nav-item">
                                <a href="{{ route('super.roles.index') }}"
                                    class="nav-link {{ request()->routeIs('super.role.index') ? 'active' : '' }}">
                                    {{ __('ui.nav.show') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            @endcan

            {{-- Permissions --}}
            @can('permission.menu')
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#menu-permissions" role="button"
                        aria-expanded="false" aria-controls="menu-permissions">
                        <i class="link-icon" data-feather="key"></i>
                        <span class="link-title">{{ __('ui.nav.permissions') }}</span>
                        <i class="link-arrow" data-feather="chevron-down"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('super.permission.*') ? 'show' : '' }}"
                        id="menu-permissions">
                        <ul class="nav sub-menu">
                            <li class="nav-item">
                                <a href="{{ route('super.permissions.index') }}"
                                    class="nav-link {{ request()->routeIs('super.permission.index') ? 'active' : '' }}">
                                    {{ __('ui.nav.show') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            @endcan


            {{-- ================= MASTER ================= --}}
            @canany(['categories.view', 'units.view', 'products.view', 'suppliers.view', 'customers.view',
                'pricelists.view', 'company.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.master') }}</li>
            @endcanany



            @can('categories.view')
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#menu-categories" role="button"
                        aria-expanded="{{ request()->routeIs('super.categories.*') ? 'true' : 'false' }}"
                        aria-controls="menu-categories">

                        <i class="link-icon" data-feather="tag"></i>
                        <span class="link-title">{{ __('ui.nav.categories') }}</span>
                        <i class="link-arrow" data-feather="chevron-down"></i>
                    </a>

                    <div class="collapse {{ request()->routeIs('super.categories.*') ? 'show' : '' }}"
                        id="menu-categories">

                        <ul class="nav sub-menu">
                            <li class="nav-item">
                                <a href="{{ route('super.categories.index') }}"
                                    class="nav-link {{ request()->routeIs('super.categories.index') ? 'active' : '' }}">
                                    Show
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            @endcan

            @can('pricelists.view')
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#menu-pricelists" role="button"
                        aria-expanded="{{ request()->routeIs('super.pricelists.*') ? 'true' : 'false' }}"
                        aria-controls="menu-pricelists">
                        <i class="link-icon" data-feather="dollar-sign"></i>
                        <span class="link-title">{{ __('ui.nav.price_lists') }}</span>
                        <i class="link-arrow" data-feather="chevron-down"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('super.pricelists.*') ? 'show' : '' }}"
                        id="menu-pricelists">
                        <ul class="nav sub-menu">
                            <li class="nav-item">
                                <a href="{{ route('super.pricelists.index') }}"
                                    class="nav-link {{ request()->routeIs('super.pricelists.index') ? 'active' : '' }}">{{ __('ui.nav.show') }}</a>
                            </li>
                        </ul>
                    </div>
                </li>
            @endcan



            @can('units.view')
                <li class="nav-item">

                    <a class="nav-link" data-bs-toggle="collapse" href="#menu-units" role="button"
                        aria-expanded="{{ request()->routeIs('super.units.*') ? 'true' : 'false' }}"
                        aria-controls="menu-units">

                        <i class="link-icon" data-feather="box"></i>

                        <span class="link-title">
                            {{ __('ui.nav.units') }}
                        </span>

                        <i class="link-arrow" data-feather="chevron-down"></i>

                    </a>


                    <div class="collapse {{ request()->routeIs('super.units.*') ? 'show' : '' }}" id="menu-units">

                        <ul class="nav sub-menu">

                            <li class="nav-item">

                                <a href="{{ route('super.units.index') }}"
                                    class="nav-link {{ request()->routeIs('super.units.index') ? 'active' : '' }}">

                                    {{ __('ui.nav.show') }}

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>
            @endcan


            @can('suppliers.view')
                <li class="nav-item">

                    <a class="nav-link" data-bs-toggle="collapse" href="#menu-suppliers" role="button"
                        aria-expanded="{{ request()->routeIs('super.suppliers.*') ? 'true' : 'false' }}"
                        aria-controls="menu-suppliers">

                        <i class="link-icon" data-feather="truck"></i>

                        <span class="link-title">
                            {{ __('ui.nav.suppliers') }}
                        </span>

                        <i class="link-arrow" data-feather="chevron-down"></i>

                    </a>

                    <div class="collapse {{ request()->routeIs('super.suppliers.*') ? 'show' : '' }}"
                        id="menu-suppliers">

                        <ul class="nav sub-menu">

                            <li class="nav-item">

                                <a href="{{ route('super.suppliers.index') }}"
                                    class="nav-link {{ request()->routeIs('super.suppliers.index') ? 'active' : '' }}">

                                    {{ __('ui.nav.show') }}

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>
            @endcan

            @can('customers.view')
                <li class="nav-item">

                    <a class="nav-link" data-bs-toggle="collapse" href="#menu-customers" role="button"
                        aria-expanded="{{ request()->routeIs('super.customers.*') ? 'true' : 'false' }}"
                        aria-controls="menu-customers">

                        <i class="link-icon" data-feather="users"></i>

                        <span class="link-title">
                            {{ __('ui.nav.customers') }}
                        </span>

                        <i class="link-arrow" data-feather="chevron-down"></i>

                    </a>

                    <div class="collapse {{ request()->routeIs('super.customers.*') ? 'show' : '' }}"
                        id="menu-customers">

                        <ul class="nav sub-menu">

                            <li class="nav-item">

                                <a href="{{ route('super.customers.index') }}"
                                    class="nav-link {{ request()->routeIs('super.customers.index') ? 'active' : '' }}">

                                    {{ __('ui.nav.show') }}

                                </a>

                            </li>

                        </ul>

                    </div>

                </li>
            @endcan


            @can('products.view')
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#menu-products" role="button"
                        aria-expanded="{{ request()->routeIs('super.products.*') ? 'true' : 'false' }}"
                        aria-controls="menu-products">

                        <i class="link-icon" data-feather="package"></i>

                        <span class="link-title">{{ __('ui.nav.products') }}</span>

                        <i class="link-arrow" data-feather="chevron-down"></i>
                    </a>

                    <div class="collapse {{ request()->routeIs('super.products.*') ? 'show' : '' }}" id="menu-products">

                        <ul class="nav sub-menu">
                            <li class="nav-item">
                                <a href="{{ route('super.products.index') }}"
                                    class="nav-link {{ request()->routeIs('super.products.index') ? 'active' : '' }}">
                                    {{ __('ui.nav.show') }}
                                </a>
                            </li>
                        </ul>

                    </div>
                </li>
            @endcan

            {{-- ================= PURCHASING ================= --}}
            @canany(['material-requests.view', 'purchase-requests.view', 'purchase-orders.view', 'goods-receipts.view',
                'purchase-returns.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.purchase') }}</li>
            @endcanany
            @can('material-requests.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.material-requests.*') ? 'active' : '' }}"
                        href="{{ route('super.material-requests.index') }}">
                        <i class="link-icon" data-feather="clipboard"></i>
                        <span class="link-title">{{ __('ui.nav.material_requests') }}</span>
                    </a>
                </li>
            @endcan
            @can('purchase-requests.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.purchase-requests.*') ? 'active' : '' }}"
                        href="{{ route('super.purchase-requests.index') }}">
                        <i class="link-icon" data-feather="shopping-cart"></i>
                        <span class="link-title">{{ __('ui.nav.purchase_requests') }}</span>
                    </a>
                </li>
            @endcan
            @can('purchase-orders.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.purchase-orders.*') ? 'active' : '' }}"
                        href="{{ route('super.purchase-orders.index') }}">
                        <i class="link-icon" data-feather="shopping-bag"></i>
                        <span class="link-title">{{ __('ui.nav.purchase_orders') }}</span>
                    </a>
                </li>
            @endcan
            @can('goods-receipts.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.goods-receipts.*') ? 'active' : '' }}"
                        href="{{ route('super.goods-receipts.index') }}">
                        <i class="link-icon" data-feather="package"></i>
                        <span class="link-title">{{ __('ui.nav.goods_receipts') }}</span>
                    </a>
                </li>
            @endcan
            @can('purchase-returns.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.purchase-returns.*') ? 'active' : '' }}"
                        href="{{ route('super.purchase-returns.index') }}">
                        <i class="link-icon" data-feather="corner-up-left"></i>
                        <span class="link-title">{{ __('ui.nav.purchase_returns') }}</span>
                    </a>
                </li>
            @endcan

            {{-- ================= SALES ================= --}}
            @canany(['sales-quotations.view', 'sales-orders.view', 'deliveries.view', 'sales-invoices.view',
                'customer-returns.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.sales') }}</li>
            @endcanany
            @can('sales-quotations.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.sales-quotations.*') ? 'active' : '' }}"
                        href="{{ route('super.sales-quotations.index') }}">
                        <i class="link-icon" data-feather="file-text"></i>
                        <span class="link-title">{{ __('ui.nav.sales_quotations') }}</span>
                    </a>
                </li>
            @endcan
            @can('sales-orders.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.sales-orders.*') ? 'active' : '' }}"
                        href="{{ route('super.sales-orders.index') }}">
                        <i class="link-icon" data-feather="shopping-cart"></i><span class="link-title">{{ __('ui.nav.sales_orders') }}</span>
                    </a>
                </li>
            @endcan
            @can('deliveries.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.deliveries.*') ? 'active' : '' }}"
                        href="{{ route('super.deliveries.index') }}">
                        <i class="link-icon" data-feather="truck"></i><span class="link-title">{{ __('ui.nav.deliveries') }}</span>
                    </a>
                </li>
            @endcan
            @can('sales-invoices.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.sales-invoices.*') ? 'active' : '' }}"
                        href="{{ route('super.sales-invoices.index') }}">
                        <i class="link-icon" data-feather="file-text"></i><span class="link-title">{{ __('ui.nav.sales_invoices') }}</span>
                    </a>
                </li>
            @endcan
            @can('customer-returns.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.customer-returns.*') ? 'active' : '' }}"
                        href="{{ route('super.customer-returns.index') }}">
                        <i class="link-icon" data-feather="corner-down-left"></i><span class="link-title">{{ __('ui.nav.customer_returns') }}</span>
                    </a>
                </li>
            @endcan

            @canany(['chart-of-accounts.view', 'journal.view', 'account-receivable.view', 'account-payable.view',
                'cash-bank.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.accounting') }}</li>
            @endcanany
            @can('chart-of-accounts.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.chart-of-accounts.*') ? 'active' : '' }}"
                        href="{{ route('super.chart-of-accounts.index') }}">
                        <i class="link-icon" data-feather="list"></i><span class="link-title">{{ __('ui.nav.chart_of_accounts') }}</span>
                    </a>
                </li>
            @endcan
            @can('journal.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.journals.*') ? 'active' : '' }}"
                        href="{{ route('super.journals.index') }}">
                        <i class="link-icon" data-feather="book-open"></i><span class="link-title">{{ __('ui.nav.journals') }}</span>
                    </a>
                </li>
            @endcan
            @can('account-receivable.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.account-receivable.*') ? 'active' : '' }}"
                        href="{{ route('super.account-receivable.index') }}">
                        <i class="link-icon" data-feather="credit-card"></i><span class="link-title">{{ __('ui.nav.accounts_receivable') }}</span>
                    </a>
                </li>
            @endcan
            @can('account-payable.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.account-payable.*') ? 'active' : '' }}"
                        href="{{ route('super.account-payable.index') }}">
                        <i class="link-icon" data-feather="credit-card"></i><span class="link-title">{{ __('ui.nav.accounts_payable') }}</span>
                    </a>
                </li>
            @endcan
            @can('cash-bank.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.cash-bank.*') ? 'active' : '' }}"
                        href="{{ route('super.cash-bank.index') }}">
                        <i class="link-icon" data-feather="briefcase"></i><span class="link-title">{{ __('ui.nav.cash_bank') }}</span>
                    </a>
                </li>
            @endcan
            @canany(['profit-loss.view', 'sales-report.view', 'purchase-report.view', 'inventory-report.view',
                'cash-bank-report.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.reports') }}</li>
            @endcanany
            @can('purchase-report.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.reports.purchase.*') ? 'active' : '' }}"
                        href="{{ route('super.reports.purchase.index') }}">
                        <i class="link-icon" data-feather="shopping-cart"></i><span class="link-title">{{ __('ui.nav.purchase_report') }}</span>
                    </a>
                </li>
            @endcan
            @can('inventory-report.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.reports.inventory.*') ? 'active' : '' }}"
                        href="{{ route('super.reports.inventory.index') }}">
                        <i class="link-icon" data-feather="package"></i><span class="link-title">{{ __('ui.nav.inventory_report') }}</span>
                    </a>
                </li>
            @endcan
            @can('cash-bank-report.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.reports.cash-bank.*') ? 'active' : '' }}"
                        href="{{ route('super.reports.cash-bank.index') }}">
                        <i class="link-icon" data-feather="dollar-sign"></i><span class="link-title">{{ __('ui.nav.cash_bank_report') }}</span>
                    </a>
                </li>
            @endcan
            @can('sales-report.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.reports.sales.*') ? 'active' : '' }}"
                        href="{{ route('super.reports.sales.index') }}">
                        <i class="link-icon" data-feather="bar-chart-2"></i><span class="link-title">{{ __('ui.nav.sales_report') }}</span>
                    </a>
                </li>
            @endcan
            @can('profit-loss.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.profit-loss.*') ? 'active' : '' }}"
                        href="{{ route('super.profit-loss.index') }}">
                        <i class="link-icon" data-feather="trending-up"></i><span class="link-title">{{ __('ui.nav.profit_loss') }}</span>
                    </a>
                </li>
            @endcan

            @canany(['stocks.view', 'stock-card.view', 'stock-opnames.view'])
                <li class="nav-item nav-category">{{ __('ui.nav.inventory') }}</li>
            @endcanany
            @can('stocks.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.stocks.*') ? 'active' : '' }}"
                        href="{{ route('super.stocks.index') }}">
                        <i class="link-icon" data-feather="archive"></i>
                        <span class="link-title">{{ __('ui.nav.stocks') }}</span>
                    </a>
                </li>
            @endcan
            @can('stock-card.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.stock-card.*') ? 'active' : '' }}"
                        href="{{ route('super.stock-card.index') }}">
                        <i class="link-icon" data-feather="layers"></i>
                        <span class="link-title">{{ __('ui.nav.stock_card') }}</span>
                    </a>
                </li>
            @endcan
            @can('stock-opnames.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('super.stock-opnames.*') ? 'active' : '' }}"
                        href="{{ route('super.stock-opnames.index') }}">
                        <i class="link-icon" data-feather="clipboard"></i>
                        <span class="link-title">{{ __('ui.nav.stock_opname') }}</span>
                    </a>
                </li>
            @endcan

            {{-- ================= SETTINGS ================= --}}
            @canany(['company.view'])
                <li class="nav-item nav-category">
                    {{ __('ui.nav.settings') }}
                </li>
            @endcanany



            {{-- Company --}}
            @can('company.view')
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#menu-companies" role="button"
                        aria-expanded="{{ request()->routeIs('super.companies.*') ? 'true' : 'false' }}"
                        aria-controls="menu-companies">

                        <i class="link-icon" data-feather="briefcase"></i>

                        <span class="link-title">{{ __('ui.nav.companies') }}</span>

                        <i class="link-arrow" data-feather="chevron-down"></i>
                    </a>

                    <div class="collapse {{ request()->routeIs('super.companies.*') ? 'show' : '' }}"
                        id="menu-companies">

                        <ul class="nav sub-menu">

                            <li class="nav-item">
                                <a href="{{ route('super.companies.index') }}"
                                    class="nav-link {{ request()->routeIs('super.companies.index') ? 'active' : '' }}">
                                    {{ __('ui.nav.show') }}
                                </a>
                            </li>

                        </ul>
                    </div>
                </li>
            @endcan


            @can('general-settings.view')
                <li class="nav-item">

                    <a href="{{ route('super.settings.general') }}"
                        class="nav-link {{ request()->routeIs('super.settings.general') ? 'active' : '' }}">

                        <i class="link-icon" data-feather="settings"></i>

                        <span class="link-title">
                            {{ __('ui.nav.general_settings') }}
                        </span>

                    </a>

                </li>
            @endcan

            @can('email-settings.view')
                <li class="nav-item">

                    <a href="{{ route('super.settings.email') }}"
                        class="nav-link {{ request()->routeIs('super.settings.email') ? 'active' : '' }}">

                        <i class="link-icon" data-feather="mail"></i>

                        <span class="link-title">
                            {{ __('ui.nav.email_settings') }}
                        </span>

                    </a>

                </li>
            @endcan


            @can('document-numbering.view')
                <li class="nav-item">
                    <a href="{{ route('super.settings.document-numbering') }}"
                        class="nav-link {{ request()->routeIs('super.settings.document-numbering') ? 'active' : '' }}">
                        <i class="link-icon" data-feather="hash"></i>
                        <span class="link-title">
                            {{ __('ui.nav.document_numbering') }}
                        </span>
                    </a>
                </li>
            @endcan








            {{-- ================= SYSTEM ================= --}}
            @can('audit-logs.view')
                <li class="nav-item nav-category">
                    {{ __('SYSTEM') }}
                </li>

                {{-- Audit Log --}}
                <li class="nav-item">

                    <a href="{{ route('super.audit-logs.index') }}"
                        class="nav-link {{ request()->routeIs('super.audit-logs.*') ? 'active' : '' }}">

                        <i class="link-icon" data-feather="activity"></i>

                        <span class="link-title">
                            {{ __('ui.nav.audit_logs') }}
                        </span>

                    </a>

                </li>
            @endcan











        </ul>

    </div>



</nav>
