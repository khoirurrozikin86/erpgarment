<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @php
        /*
        |--------------------------------------------------------------------------
        | Dynamic Company / Product Branding
        |--------------------------------------------------------------------------
        | Nanti nilai ini bisa berasal dari Company Settings / database.
        */
        $companyName = $company->name ?? config('app.name', 'ERP POS');
        $companyLogo = $company->logo_url ?? null;
        $companyEmail = $company->email ?? null;
        $companyPhone = $company->phone ?? null;
        $companyAddress = $company->address ?? null;
    @endphp

    <title>{{ $companyName }} — ERP & POS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-900: #172554;
            --primary-800: #1e3a8a;
            --primary-700: #1d4ed8;
            --primary-600: #2563eb;
            --primary-500: #3b82f6;
            --sky: #eff6ff;
            --ink: #0f172a;
            --muted: #64748b;
            --border: rgba(37, 99, 235, .12);
            --bg: #f7f9fc;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont,
                "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% 8%, rgba(59, 130, 246, .10), transparent 25%),
                radial-gradient(circle at 92% 16%, rgba(96, 165, 250, .11), transparent 25%),
                var(--bg);
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
        }

        /* =========================================================
           NAVBAR
           ========================================================= */
        .top-nav {
            position: relative;
            z-index: 20;
            padding: 18px 0;
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 17px;
            background: rgba(255, 255, 255, .82);
            box-shadow: 0 12px 35px rgba(15, 23, 42, .06);
            backdrop-filter: blur(16px);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            color: var(--ink);
        }

        .brand:hover {
            color: var(--ink);
        }

        .brand-logo {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(135deg, var(--primary-700), var(--primary-500));
            box-shadow: 0 9px 22px rgba(37, 99, 235, .22);
        }

        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #fff;
        }

        .brand-name strong {
            display: block;
            max-width: 280px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: -.01em;
        }

        .brand-name span {
            display: block;
            margin-top: 2px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 600;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 26px;
        }

        .nav-links a {
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            transition: .2s ease;
        }

        .nav-links a:hover {
            color: var(--primary-600);
        }

        .nav-login {
            display: inline-flex !important;
            align-items: center;
            gap: 7px;
            padding: 10px 15px;
            border-radius: 10px;
            color: #fff !important;
            background: var(--primary-600);
            box-shadow: 0 8px 18px rgba(37, 99, 235, .2);
        }

        .nav-login:hover {
            background: var(--primary-700);
            transform: translateY(-1px);
        }

        /* =========================================================
           HERO
           ========================================================= */
        .landing {
            padding: 24px 0 0;
        }

        .hero {
            position: relative;
            min-height: 545px;
            overflow: hidden;
            display: flex;
            align-items: center;
            padding: 62px 68px;
            border-radius: 32px;
            border: 1px solid rgba(37, 99, 235, .13);
            background:
                radial-gradient(circle at 78% 48%, rgba(255, 255, 255, .84), transparent 22%),
                radial-gradient(circle at 96% 5%, rgba(147, 197, 253, .28), transparent 25%),
                linear-gradient(135deg, #eff6ff 0%, #dbeafe 48%, #bfdbfe 100%);
            box-shadow:
                0 30px 80px rgba(30, 64, 175, .11),
                inset 0 1px 0 rgba(255, 255, 255, .85);
        }

        .hero::before {
            content: "";
            position: absolute;
            width: 520px;
            height: 520px;
            right: -190px;
            top: -280px;
            border-radius: 50%;
            border: 1px solid rgba(37, 99, 235, .10);
            box-shadow:
                0 0 0 65px rgba(37, 99, 235, .035),
                0 0 0 130px rgba(37, 99, 235, .025);
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 390px;
            height: 390px;
            right: 20%;
            bottom: -330px;
            border-radius: 50%;
            border: 1px solid rgba(37, 99, 235, .08);
            box-shadow:
                0 0 0 60px rgba(37, 99, 235, .025),
                0 0 0 120px rgba(37, 99, 235, .018);
        }

        .hero-copy {
            position: relative;
            z-index: 5;
            width: 57%;
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 15px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .78);
            border: 1px solid rgba(37, 99, 235, .09);
            color: var(--primary-700);
            font-size: 11px;
            font-weight: 800;
            box-shadow: 0 8px 24px rgba(30, 64, 175, .06);
            backdrop-filter: blur(8px);
        }

        .hero-pill-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 5px rgba(34, 197, 94, .10);
        }

        .hero h1 {
            margin: 25px 0 16px;
            max-width: 730px;
            color: #0b1f44;
            font-size: clamp(43px, 5vw, 70px);
            line-height: .99;
            letter-spacing: -3.5px;
            font-weight: 800;
        }

        .hero h1 .accent {
            color: var(--primary-600);
        }

        .hero-description {
            max-width: 650px;
            margin: 0;
            color: #52677f;
            font-size: 16px;
            line-height: 1.75;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 11px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .btn-login {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 13px 20px;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(135deg, var(--primary-700), var(--primary-500));
            box-shadow: 0 13px 28px rgba(37, 99, 235, .25);
            font-size: 12px;
            font-weight: 800;
            transition: .25s ease;
        }

        .btn-login:hover {
            color: #fff;
            transform: translateY(-3px);
            box-shadow: 0 18px 35px rgba(37, 99, 235, .30);
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 12px;
            color: var(--primary-700);
            background: rgba(255, 255, 255, .70);
            border: 1px solid rgba(37, 99, 235, .13);
            font-size: 12px;
            font-weight: 700;
            transition: .2s ease;
        }

        .btn-outline:hover {
            color: var(--primary-700);
            background: #fff;
            transform: translateY(-2px);
        }

        /* =========================================================
           ERP VISUAL
           ========================================================= */
        .hero-visual {
            position: absolute;
            z-index: 4;
            width: 43%;
            right: 3.5%;
            top: 50%;
            transform: translateY(-50%);
        }

        .dashboard-window {
            position: relative;
            width: 100%;
            max-width: 485px;
            margin: 0 auto;
            padding: 12px;
            border: 1px solid rgba(15, 23, 42, .09);
            border-radius: 19px;
            background: rgba(255, 255, 255, .88);
            box-shadow: 0 30px 55px rgba(15, 23, 42, .16);
            transform: perspective(1200px) rotateY(-6deg) rotateX(2deg);
        }

        .window-bar {
            height: 25px;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 0 5px;
        }

        .window-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .window-title {
            margin-left: 7px;
            color: #94a3b8;
            font-size: 8px;
            font-weight: 700;
        }

        .erp-preview {
            display: grid;
            grid-template-columns: 92px 1fr;
            min-height: 285px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
        }

        .preview-sidebar {
            padding: 12px 8px;
            background: #0f172a;
        }

        .preview-brand {
            height: 17px;
            margin: 0 7px 15px;
            border-radius: 4px;
            background: #1d4ed8;
        }

        .preview-nav {
            height: 7px;
            margin: 8px 7px;
            border-radius: 3px;
            background: #334155;
        }

        .preview-nav.active {
            background: #3b82f6;
        }

        .preview-main {
            padding: 13px;
        }

        .preview-top {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 12px;
        }

        .preview-heading {
            width: 120px;
            height: 11px;
            border-radius: 4px;
            background: #cbd5e1;
        }

        .preview-search {
            width: 105px;
            height: 18px;
            border-radius: 5px;
            background: #fff;
            border: 1px solid #e2e8f0;
        }

        .preview-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 7px;
            margin-bottom: 9px;
        }

        .preview-stat {
            height: 52px;
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            background: #fff;
        }

        .preview-stat-line {
            width: 45%;
            height: 5px;
            border-radius: 3px;
            background: #cbd5e1;
        }

        .preview-stat-value {
            width: 72%;
            height: 9px;
            margin-top: 8px;
            border-radius: 3px;
            background: #2563eb;
        }

        .preview-body {
            display: grid;
            grid-template-columns: 1.25fr .75fr;
            gap: 8px;
        }

        .preview-card {
            min-height: 125px;
            padding: 9px;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            background: #fff;
        }

        .preview-card-title {
            width: 80px;
            height: 7px;
            margin-bottom: 10px;
            border-radius: 3px;
            background: #cbd5e1;
        }

        .preview-chart {
            height: 83px;
            display: flex;
            align-items: end;
            gap: 7px;
            padding: 4px;
        }

        .bar {
            flex: 1;
            border-radius: 3px 3px 0 0;
            background: #60a5fa;
        }

        .bar:nth-child(1) {
            height: 35%;
        }

        .bar:nth-child(2) {
            height: 58%;
        }

        .bar:nth-child(3) {
            height: 46%;
        }

        .bar:nth-child(4) {
            height: 75%;
        }

        .bar:nth-child(5) {
            height: 64%;
        }

        .bar:nth-child(6) {
            height: 88%;
        }

        .preview-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .preview-list-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 7px;
            border-bottom: 1px solid #f1f5f9;
        }

        .preview-list-name {
            width: 55px;
            height: 6px;
            border-radius: 3px;
            background: #cbd5e1;
        }

        .preview-list-value {
            width: 30px;
            height: 6px;
            border-radius: 3px;
            background: #bfdbfe;
        }

        .floating-badge {
            position: absolute;
            right: -32px;
            top: 28px;
            padding: 13px 16px;
            border-radius: 14px;
            color: #fff;
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
            box-shadow: 0 18px 30px rgba(30, 64, 175, .25);
        }

        .floating-badge small {
            display: block;
            margin-bottom: 3px;
            color: #bfdbfe;
            font-size: 8px;
        }

        .floating-badge strong {
            display: block;
            font-size: 14px;
        }

        .floating-badge span {
            color: #bfdbfe;
        }

        .floating-payment {
            position: absolute;
            left: -35px;
            bottom: 22px;
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 11px 14px;
            border-radius: 13px;
            background: rgba(255, 255, 255, .95);
            border: 1px solid #e2e8f0;
            box-shadow: 0 16px 30px rgba(15, 23, 42, .12);
        }

        .payment-icon {
            width: 29px;
            height: 29px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            color: #15803d;
            background: #dcfce7;
        }

        .floating-payment small {
            display: block;
            color: #94a3b8;
            font-size: 8px;
        }

        .floating-payment strong {
            display: block;
            margin-top: 1px;
            font-size: 11px;
        }

        /* =========================================================
           FEATURES
           ========================================================= */
        .features {
            padding: 24px 0 45px;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 17px;
        }

        .section-heading span {
            color: var(--primary-600);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .section-heading h2 {
            margin: 6px 0 5px;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.03em;
        }

        .section-heading p {
            margin: 0 auto;
            max-width: 580px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.6;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .feature-card {
            padding: 19px;
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 15px;
            background: rgba(255, 255, 255, .9);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .045);
            transition: .22s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: rgba(37, 99, 235, .18);
            box-shadow: 0 16px 35px rgba(37, 99, 235, .09);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            color: var(--primary-600);
            background: var(--sky);
        }

        .feature-card h3 {
            margin: 13px 0 5px;
            font-size: 13px;
            font-weight: 800;
        }

        .feature-card p {
            margin: 0;
            color: var(--muted);
            font-size: 10px;
            line-height: 1.55;
        }

        /* =========================================================
           FOOTER
           ========================================================= */
        .footer {
            padding: 18px 0 22px;
            border-top: 1px solid rgba(148, 163, 184, .16);
            color: #94a3b8;
            text-align: center;
            font-size: 10px;
        }

        /* =========================================================
           RESPONSIVE
           ========================================================= */
        @media (max-width: 1100px) {
            .hero {
                padding: 52px 48px;
            }

            .hero-copy {
                width: 62%;
            }

            .hero-visual {
                width: 39%;
                right: 2%;
            }

            .dashboard-window {
                transform: perspective(1000px) rotateY(-4deg);
            }

            .feature-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 850px) {
            .nav-links a:not(.nav-login) {
                display: none;
            }

            .hero {
                min-height: auto;
                padding: 43px 34px 30px;
            }

            .hero-copy {
                width: 100%;
            }

            .hero-visual {
                position: relative;
                width: 100%;
                right: auto;
                top: auto;
                transform: none;
                margin-top: 40px;
                padding: 0 25px 25px;
            }

            .dashboard-window {
                max-width: 560px;
                transform: none;
            }

            .floating-badge {
                right: 0;
            }

            .floating-payment {
                left: 0;
            }
        }

        @media (max-width: 575px) {
            .top-nav {
                padding: 10px 0;
            }

            .brand-name span {
                display: none;
            }

            .landing {
                padding-top: 8px;
            }

            .hero {
                border-radius: 23px;
                padding: 31px 23px 20px;
            }

            .hero h1 {
                margin-top: 21px;
                font-size: 42px;
                letter-spacing: -2.5px;
            }

            .hero-description {
                font-size: 14px;
            }

            .hero-visual {
                padding: 0 4px 20px;
            }

            .erp-preview {
                grid-template-columns: 63px 1fr;
                min-height: 220px;
            }

            .preview-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .preview-stats .preview-stat:last-child {
                display: none;
            }

            .preview-body {
                grid-template-columns: 1fr;
            }

            .preview-body .preview-card:last-child {
                display: none;
            }

            .floating-badge {
                right: -5px;
                top: 12px;
            }

            .floating-payment {
                left: -5px;
                bottom: 5px;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                transition: none !important;
                animation: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="container-xl">

        {{-- =========================================================
         NAVBAR
         ========================================================= --}}
        <nav class="top-nav">
            <div class="nav-inner">

                <a href="{{ url('/') }}" class="brand">
                    <div class="brand-logo">
                        @if ($companyLogo)
                            <img src="{{ $companyLogo }}" alt="{{ $companyName }}">
                        @else
                            <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <rect x="4" y="4" width="16" height="16" rx="4" stroke-width="1.8" />
                                <path d="M8 12h8M12 8v8" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        @endif
                    </div>

                    <div class="brand-name">
                        <strong>{{ $companyName }}</strong>
                        <span>ERP & POS Management System</span>
                    </div>
                </a>

                <div class="nav-links">
                    <a href="#features">Fitur</a>
                    <a href="#modules">Modul</a>
                    <a href="#about">Tentang Sistem</a>

                    @if (Route::has('login'))
                        <a href="{{ route('login', absolute: false) }}" class="nav-login">
                            Login
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </a>
                    @endif
                </div>

            </div>
        </nav>

        <main class="landing">

            {{-- =====================================================
             HERO / PRE-LOGIN LANDING
             ===================================================== --}}
            <section class="hero">

                <div class="hero-copy">

                    <div class="hero-pill">
                        <span class="hero-pill-dot"></span>
                        ERP & POS BUSINESS PLATFORM
                    </div>

                    <h1>
                        Kelola bisnis lebih
                        <span class="accent">mudah.</span>
                    </h1>

                    <p class="hero-description">
                        Sistem ERP dan POS terintegrasi untuk membantu bisnis
                        mengelola penjualan, kasir, inventory, purchasing,
                        accounting, dan laporan dalam satu platform.
                    </p>

                    <div class="hero-actions">
                        @if (Route::has('login'))
                            <a href="{{ route('login', absolute: false) }}" class="btn-login">
                                Login ke Sistem
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor">
                                    <path d="M5 12h14M13 6l6 6-6 6" />
                                </svg>
                            </a>
                        @endif

                        <a href="#features" class="btn-outline">
                            Jelajahi Fitur
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M6 9l6 6 6-6" />
                            </svg>
                        </a>
                    </div>

                </div>

                {{-- ERP visual, BUKAN dashboard halaman --}}
                <div class="hero-visual">

                    <div class="dashboard-window">

                        <div class="window-bar">
                            <span class="window-dot"></span>
                            <span class="window-dot"></span>
                            <span class="window-dot"></span>
                            <span class="window-title">ERP & POS System</span>
                        </div>

                        <div class="erp-preview">

                            <div class="preview-sidebar">
                                <div class="preview-brand"></div>
                                <div class="preview-nav active"></div>
                                <div class="preview-nav"></div>
                                <div class="preview-nav"></div>
                                <div class="preview-nav"></div>
                                <div class="preview-nav"></div>
                                <div class="preview-nav"></div>
                                <div class="preview-nav"></div>
                            </div>

                            <div class="preview-main">

                                <div class="preview-top">
                                    <div class="preview-heading"></div>
                                    <div class="preview-search"></div>
                                </div>

                                <div class="preview-stats">
                                    <div class="preview-stat">
                                        <div class="preview-stat-line"></div>
                                        <div class="preview-stat-value"></div>
                                    </div>

                                    <div class="preview-stat">
                                        <div class="preview-stat-line"></div>
                                        <div class="preview-stat-value"></div>
                                    </div>

                                    <div class="preview-stat">
                                        <div class="preview-stat-line"></div>
                                        <div class="preview-stat-value"></div>
                                    </div>
                                </div>

                                <div class="preview-body">

                                    <div class="preview-card">
                                        <div class="preview-card-title"></div>

                                        <div class="preview-chart">
                                            <span class="bar"></span>
                                            <span class="bar"></span>
                                            <span class="bar"></span>
                                            <span class="bar"></span>
                                            <span class="bar"></span>
                                            <span class="bar"></span>
                                        </div>
                                    </div>

                                    <div class="preview-card">
                                        <div class="preview-card-title"></div>

                                        <div class="preview-list">
                                            <div class="preview-list-row">
                                                <span class="preview-list-name"></span>
                                                <span class="preview-list-value"></span>
                                            </div>
                                            <div class="preview-list-row">
                                                <span class="preview-list-name"></span>
                                                <span class="preview-list-value"></span>
                                            </div>
                                            <div class="preview-list-row">
                                                <span class="preview-list-name"></span>
                                                <span class="preview-list-value"></span>
                                            </div>
                                            <div class="preview-list-row">
                                                <span class="preview-list-name"></span>
                                                <span class="preview-list-value"></span>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="floating-badge">
                        <small>One Platform</small>
                        <strong>ERP <span>+</span> POS</strong>
                    </div>

                    <div class="floating-payment">
                        <div class="payment-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor">
                                <path d="M3 7h18v13H3z" />
                                <path d="M3 10h18M7 15h4" />
                            </svg>
                        </div>
                        <div>
                            <small>Integrated Payment</small>
                            <strong>Cash · QRIS · Bank</strong>
                        </div>
                    </div>

                </div>

            </section>

            {{-- =====================================================
             FEATURES
             ===================================================== --}}
            <section class="features" id="features">

                <div class="section-heading">
                    <span>Business Solution</span>
                    <h2>Satu sistem untuk seluruh operasional</h2>
                    <p>
                        Dibuat modular agar dapat digunakan oleh berbagai jenis
                        bisnis dan dapat dikembangkan sesuai kebutuhan perusahaan.
                    </p>
                </div>

                <div class="feature-grid">

                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor">
                                <circle cx="9" cy="20" r="1.5" />
                                <circle cx="18" cy="20" r="1.5" />
                                <path d="M3 4h3l2 11h10l2-8H7" />
                            </svg>
                        </div>
                        <h3>POS & Kasir</h3>
                        <p>Transaksi cepat, barcode, discount, berbagai metode pembayaran dan closing kasir.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor">
                                <path d="M3 7h18v13H3z" />
                                <path d="M7 7V4h10v3M7 12h10" />
                            </svg>
                        </div>
                        <h3>Inventory</h3>
                        <p>Kontrol stock, stock movement, stock opname, adjustment, transfer dan valuation.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor">
                                <path d="M4 5h16v14H4z" />
                                <path d="M8 9h8M8 13h6M8 17h4" />
                            </svg>
                        </div>
                        <h3>Purchasing & Sales</h3>
                        <p>Kelola pembelian, supplier, sales order, delivery, invoice dan customer return.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor">
                                <path d="M4 19V5M4 19h16" />
                                <path d="M8 15v-4M12 15V7M16 15V9M20 15V5" />
                            </svg>
                        </div>
                        <h3>Accounting & Report</h3>
                        <p>COA, journal, AP, AR, COGS, profit & loss dan laporan bisnis terintegrasi.</p>
                    </div>

                </div>
            </section>

            {{-- =====================================================
             MODULES
             ===================================================== --}}
            <section class="features pt-0" id="modules">

                <div class="section-heading">
                    <span>Integrated Modules</span>
                    <h2>Modular dan siap berkembang</h2>
                    <p>
                        Modul dapat disesuaikan dengan skala dan kebutuhan masing-masing perusahaan.
                    </p>
                </div>

                <div class="feature-grid">

                    <div class="feature-card">
                        <h3>Master Data</h3>
                        <p>Barang, kategori, satuan, supplier, customer, harga, pajak dan payment method.</p>
                    </div>

                    <div class="feature-card">
                        <h3>Material Request</h3>
                        <p>Kelola kebutuhan barang dari request hingga proses purchasing.</p>
                    </div>

                    <div class="feature-card">
                        <h3>Purchase Order</h3>
                        <p>Kelola PO supplier dan proses penerimaan barang secara terstruktur.</p>
                    </div>

                    <div class="feature-card">
                        <h3>POS & Multi Cashier</h3>
                        <p>Support user kasir berbeda, POS session, payment dan closing kasir.</p>
                    </div>

                    <div class="feature-card">
                        <h3>Stock Management</h3>
                        <p>Stock balance, stock card, movement, opname, adjustment dan transfer.</p>
                    </div>

                    <div class="feature-card">
                        <h3>Sales & Invoice</h3>
                        <p>Sales order, delivery, invoice dan customer return.</p>
                    </div>

                    <div class="feature-card">
                        <h3>Accounting Dasar</h3>
                        <p>Journal, Cash & Bank, AP, AR, COGS dan Profit & Loss.</p>
                    </div>

                    <div class="feature-card">
                        <h3>Automatic Report</h3>
                        <p>Laporan harian dapat dikirim otomatis melalui email sesuai konfigurasi perusahaan.</p>
                    </div>

                </div>
            </section>

            {{-- =====================================================
             ABOUT / CTA
             ===================================================== --}}
            <section class="features pt-0" id="about">
                <div class="hero" style="min-height:auto; padding:38px 42px;">
                    <div class="hero-copy" style="width:100%; max-width:780px;">
                        <div class="hero-pill">
                            <span class="hero-pill-dot"></span>
                            READY FOR YOUR BUSINESS
                        </div>

                        <h1 style="font-size:clamp(30px,4vw,48px);">
                            Satu platform.
                            <span class="accent">Lebih terkontrol.</span>
                        </h1>

                        <p class="hero-description">
                            Sistem dapat dikonfigurasi mengikuti nama perusahaan, logo,
                            outlet, user, warehouse, numbering, email, footer dokumen,
                            payment method dan kebutuhan operasional lainnya.
                        </p>

                        <div class="hero-actions">
                            @if (Route::has('login'))
                                <a href="{{ route('login', absolute: false) }}" class="btn-login">
                                    Login ke Sistem
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor">
                                        <path d="M5 12h14M13 6l6 6-6 6" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

        </main>

        <footer class="footer">
            <div>
                {{ $companyName }} · ERP & POS System
                @if ($companyEmail)
                    · {{ $companyEmail }}
                @endif
                @if ($companyPhone)
                    · {{ $companyPhone }}
                @endif
            </div>

            @if ($companyAddress)
                <div class="mt-1">{{ $companyAddress }}</div>
            @endif
        </footer>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
