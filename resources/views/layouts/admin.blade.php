<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'Dashboard') — {{ config('app.name') }}
    </title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">


    {{-- =========================
         CORE CSS
    ========================== --}}
    <link rel="stylesheet" href="{{ asset('vendor/nobleui/assets/vendors/core/core.css') }}">


    {{-- =========================
         ICON CSS
    ========================== --}}
    <link rel="stylesheet" href="{{ asset('vendor/nobleui/assets/fonts/feather-font/css/iconfont.css') }}">

    <link rel="stylesheet" href="{{ asset('vendor/nobleui/assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">


    {{-- =========================
         NOBLEUI MAIN CSS
    ========================== --}}
    <link rel="stylesheet" href="{{ asset('vendor/nobleui/assets/css/demo1/style.css') }}">


    {{-- =========================
         DATATABLES CSS
    ========================== --}}
    <link rel="stylesheet"
        href="{{ asset('vendor/nobleui/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">


    {{-- =========================
         SWEETALERT CSS
    ========================== --}}
    <link rel="stylesheet" href="{{ asset('vendor/nobleui/assets/vendors/sweetalert2/sweetalert2.min.css') }}">


    {{-- CSS khusus halaman --}}
    @stack('vendor-styles')

    @stack('styles')


    <link rel="shortcut icon" href="{{ asset('vendor/nobleui/assets/images/favicon.png') }}">




    <style>
        @if (request()->routeIs('super.pos.index'))
        html, body.pos-screen { min-height: 100%; }
        body.pos-screen { margin: 0; overflow-x: hidden; }
        body.pos-screen .sidebar,
        body.pos-screen .settings-sidebar,
        body.pos-screen .navbar,
        body.pos-screen .footer { display: none !important; }
        body.pos-screen .main-wrapper { padding-left: 0 !important; }
        body.pos-screen .page-wrapper { margin-left: 0 !important; width: 100% !important; min-height: 100vh; padding-top: 0 !important; }
        body.pos-screen .page-content { margin: 0 !important; padding: 1.25rem !important; min-height: 100vh; }
        body.pos-screen .pos-screen-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: -1.25rem -1.25rem 1.25rem; padding: .75rem 1.25rem; background: #fff; border-bottom: 1px solid #e5e7eb; }
        @media (max-width: 767.98px) { body.pos-screen .page-content { padding: .75rem !important; } body.pos-screen .pos-screen-header { margin: -.75rem -.75rem .75rem; padding: .75rem; } }
        @endif

        /* =========================================================
   SIDEBAR - OSIL GREEN THEME
   ========================================================= */

        .sidebar {
            background: #ffffff !important;
            border-right: 1px solid #e5eee8;
        }


        /* =========================================================
   SIDEBAR LOGO / BRAND
   ========================================================= */

        .sidebar .sidebar-header {
            background: #ffffff;
            border-bottom: 1px solid #edf4ef;
        }


        /* =========================================================
   CATEGORY
   ========================================================= */

        .sidebar .nav-category {
            color: #82a08e !important;

            font-size: 10px;
            font-weight: 700;

            letter-spacing: .8px;

            padding-left: 20px;
        }


        /* =========================================================
   MENU DEFAULT
   ========================================================= */

        .sidebar .nav-link {
            color: #52665a !important;

            border-radius: 10px;

            margin: 3px 10px;

            padding: 10px 13px;

            transition:
                background .2s ease,
                color .2s ease,
                transform .2s ease;
        }


        /* ICON DEFAULT */

        .sidebar .nav-link .link-icon {
            color: #789084 !important;

            width: 18px;
            height: 18px;

            margin-right: 10px;

            transition: color .2s ease;
        }


        /* =========================================================
   HOVER
   ========================================================= */

        .sidebar .nav-link:hover {

            background: #f0fdf4 !important;

            color: #15803d !important;

            transform: translateX(2px);
        }

        .sidebar .nav-link:hover .link-icon {

            color: #15803d !important;
        }


        /* =========================================================
   ACTIVE MENU
   ========================================================= */

        .sidebar .nav-link.active {

            background:
                linear-gradient(135deg,
                    #dcfce7,
                    #d1fae5) !important;

            color: #15803d !important;

            font-weight: 700;

            box-shadow:
                0 4px 12px rgba(21, 128, 61, .08);
        }


        .sidebar .nav-link.active .link-icon {

            color: #15803d !important;
        }


        /* =========================================================
   ARROW
   ========================================================= */

        .sidebar .link-arrow {

            color: #8aa095 !important;

            transition:
                transform .2s ease,
                color .2s ease;
        }


        .sidebar .nav-link:hover .link-arrow {

            color: #15803d !important;
        }


        /* =========================================================
   SUB MENU
   ========================================================= */

        .sidebar .sub-menu {

            margin-left: 15px;

            border-left:
                1px solid #dcebe1;
        }


        .sidebar .sub-menu .nav-link {

            margin-top: 2px;
            margin-bottom: 2px;

            padding-top: 8px;
            padding-bottom: 8px;

            font-size: 12px;
        }


        /* ACTIVE SUB MENU */

        .sidebar .sub-menu .nav-link.active {

            background: #f0fdf4 !important;

            color: #15803d !important;

            box-shadow: none;
        }


        /* =========================================================
   SIDEBAR SCROLLBAR
   ========================================================= */

        .sidebar::-webkit-scrollbar {

            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {

            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {

            background: #d1fae5;

            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {

            background: #86efac;
        }







        .sidebar-header {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px 0 20px;
        }

        .company-brand {
            display: flex !important;
            align-items: center;
            gap: 9px;
            min-width: 0;
            width: calc(100% - 35px);
            text-decoration: none;
        }

        .company-logo {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px;
            object-fit: contain;
            margin: 0 !important;
        }

        .company-name {
            display: block;
            min-width: 0;
            max-width: 125px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;

            font-size: 15px !important;
            font-weight: 500 !important;
            line-height: 1.2 !important;
            color: #53627a !important;
        }

        .sidebar-toggler {
            flex-shrink: 0;
            width: 24px;
            margin-left: 5px;
        }
    </style>

</head>


<body class="{{ request()->routeIs('super.pos.index') ? 'pos-screen' : '' }}">
    <div class="main-wrapper">
        {{-- Sidebar --}}
        @unless(request()->routeIs('super.pos.index'))
            @include('admin.partials.sidebar')

            <nav class="settings-sidebar">@includeWhen(View::exists('admin.partials.settings'), 'admin.partials.settings')</nav>
        @endunless

        <div class="page-wrapper">
            {{-- Navbar --}}
            @unless(request()->routeIs('super.pos.index'))
                @include('admin.partials.navbar')
            @endunless

            <div class="page-content">
                @yield('breadcrumb')
                @yield('content')
            </div>

            {{-- Footer --}}
            @unless(request()->routeIs('super.pos.index'))
                @include('admin.partials.footer')
            @endunless
        </div>
    </div>



    {{-- Core JS --}}
    <script src="{{ asset('vendor/nobleui/assets/vendors/core/core.js') }}"></script>


    {{-- DataTables --}}
    <script src="{{ asset('vendor/nobleui/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>

    <script src="{{ asset('vendor/nobleui/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>


    {{-- SweetAlert --}}
    <script src="{{ asset('vendor/nobleui/assets/vendors/sweetalert2/sweetalert2.min.js') }}"></script>


    {{-- Feather --}}
    <script src="{{ asset('vendor/nobleui/assets/vendors/feather-icons/feather.min.js') }}"></script>


    {{-- NobleUI --}}
    <script src="{{ asset('vendor/nobleui/assets/js/template.js') }}"></script>


    {{-- SweetAlert custom --}}
    <script src="{{ asset('vendor/nobleui/assets/js/sweet-alert.js') }}"></script>


    {{-- Vendor khusus halaman --}}
    @stack('vendor-scripts')


    {{-- Script khusus halaman --}}
    @stack('scripts')



    <script>
        (function exactActiveSidebar() {
            function normalizePath(u) {
                try {
                    const url = new URL(u, location.origin);
                    return (url.pathname || '/').replace(/\/+$/, '') || '/';
                } catch {
                    return '/';
                }
            }

            function run() {
                const current = normalizePath(location.href);

                // Hanya di sidebar
                const $sidebar = $('.sidebar, .sidebar-body, nav.sidebar'); // sesuaikan wrapper sidebarmu
                if (!$sidebar.length) return;

                // Bersihkan state auto-active bawaan tema
                $sidebar.find('.nav-link.active').removeClass('active');
                $sidebar.find('.nav-item.active').removeClass('active');
                $sidebar.find('.collapse.show').removeClass('show');

                // Set active berdasar exact path
                $sidebar.find('.nav-link[href]').each(function() {
                    const $a = $(this);
                    const hrefPath = normalizePath($a.attr('href'));
                    if (hrefPath === current) {
                        $a.addClass('active');
                        $a.closest('.collapse').addClass('show');
                        $a.parents('.nav-item').last().addClass('active');
                    }
                });

                // Opsional: kalau mau grup terbuka saat di area /super/xxx/*
                // tinggal tambahkan aturan startsWith di sini per grup bila perlu.
            }

            // Jalankan setelah vendor inisialisasi
            if (document.readyState === 'complete') setTimeout(run, 0);
            else window.addEventListener('load', () => setTimeout(run, 0));

            // Ulangi bila SPA/Livewire/Turbo
            document.addEventListener('turbo:load', run);
            document.addEventListener('pjax:end', run);
            document.addEventListener('livewire:navigated', run);
        })();
    </script>

</body>

</html>
