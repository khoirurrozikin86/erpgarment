<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Responsive HTML Admin Dashboard Template based on Bootstrap 5">
    <meta name="author" content="NobleUI">
    <meta name="keywords" content="nobleui, bootstrap, admin, dashboard, template, responsive, ui">

    <title>{{ __('ui.auth.login') }} — {{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

    {{-- core:css --}}
    <link rel="stylesheet" href="{{ asset('vendor/nobleui/assets/vendors/core/core.css') }}">
    {{-- Plugin css (contoh: DataTables Bootstrap 5) --}}

    <!-- Plugin css for this page -->
    <link rel="stylesheet"
        href="{{ asset('vendor/nobleui/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <!-- End plugin css for this page -->

    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="{{ asset('vendor/nobleui/assets/vendors/sweetalert2/sweetalert2.min.css') }}">
    <!-- End plugin css for this page -->

    @stack('vendor-styles')
    {{-- inject:css --}}
    <link rel="stylesheet" href="{{ asset('vendor/nobleui/assets/fonts/feather-font/css/iconfont.css') }}">
    {{-- Layout styles --}}
    <link rel="stylesheet" href="{{ asset('vendor/nobleui/assets/css/demo1/style.css') }}">

    <link rel="shortcut icon" href="{{ asset('vendor/nobleui/assets/images/favicon.png') }}" />
    <style>
        .login-card {
            max-width: 920px;
            overflow: hidden;
        }

        .login-photo {
            position: relative;
            min-height: 420px;
            background-color: #e3ece8;
        }

        .login-photo img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        @media (max-width: 767.98px) {
            .login-photo {
                min-height: 150px;
            }

            .login-photo img {
                object-position: center 55%;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <div class="page-wrapper full-page">
            <div class="page-content d-flex align-items-center justify-content-center">

                <div class="row w-100 mx-0 auth-page">
                    <div class="col-sm-10 col-lg-9 mx-auto">
                        <div class="card shadow login-card mx-auto">
                            <div class="row g-0">
                                <div class="col-md-5 login-photo">
                                    <img src="{{ asset('images/garment-login.jpg') }}?v={{ filemtime(public_path('images/garment-login.jpg')) }}" alt="" fetchpriority="high">
                                </div>

                                <div class="col-md-7 align-self-center">
                                    <div class="auth-form-wrapper p-4 p-md-5">
                                        <a href="{{ url('/') }}" class="noble-ui-logo d-block mb-2">
                                            {{ config('app.name') }}
                                        </a>
                                        <h5 class="text-muted fw-normal mb-4">{{ __('ui.auth.welcome_back') }}</h5>

                                        {{-- Session Status --}}
                                        @if (session('status'))
                                            <div class="alert alert-success">{{ session('status') }}</div>
                                        @endif

                                        {{-- Login Form --}}
                                        <form class="forms-sample" method="POST" action="{{ route('login', absolute: false) }}">
                                            @csrf

                                            <div class="mb-3">
                                                <label for="email" class="form-label">{{ __('ui.auth.email') }}</label>
                                                <input type="email"
                                                    class="form-control @error('email') is-invalid @enderror"
                                                    id="email" name="email" value="{{ old('email') }}"
                                                    placeholder="{{ __('ui.auth.email') }}" required autofocus autocomplete="username">

                                                @error('email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="password" class="form-label">{{ __('ui.auth.password') }}</label>
                                                <input type="password"
                                                    class="form-control @error('password') is-invalid @enderror"
                                                    id="password" name="password" autocomplete="current-password"
                                                    placeholder="{{ __('ui.auth.password') }}" required>

                                                @error('password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            {{-- <div class="form-check mb-3">
                                                <input type="checkbox" class="form-check-input" id="remember_me"
                                                    name="remember">
                                                <label class="form-check-label" for="remember_me">
                                                    Remember me
                                                </label>
                                            </div> --}}

                                            <div class="d-flex align-items-center justify-content-between">
                                                <button type="submit" class="btn btn-primary text-white">
                                                    {{ __('ui.auth.login') }}
                                                </button>

                                                {{-- @if (Route::has('password.request'))
                                                    <a href="{{ route('password.request') }}" class="text-muted">
                                                        Forgot your password?
                                                    </a>
                                                @endif --}}
                                            </div>

                                            {{-- @if (Route::has('register'))
                                                <a href="{{ route('register') }}" class="d-block mt-3 text-muted">
                                                    Not a user? Sign up
                                                </a>
                                            @endif --}}
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- core:js --}}
    <script src="{{ asset('vendor/nobleui/assets/vendors/core/core.js') }}"></script>
    {{-- Plugins per halaman --}}
    @stack('vendor-scripts')
    {{-- inject:js --}}

    <!-- Plugin js for this page -->
    <script src="{{ asset('vendor/nobleui/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('vendor/nobleui/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>


    <!-- Plugin js for this page -->
    <script src="{{ asset('vendor/nobleui/assets/vendors/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- End plugin js for this page -->


    <!-- Custom js for this page -->
    <script src="{{ asset('vendor/nobleui/assets/js/sweet-alert.js') }}"></script>
    <!-- End custom js for this page -->


    <script src="{{ asset('vendor/nobleui/assets/vendors/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('vendor/nobleui/assets/js/template.js') }}"></script>
    <!-- endinject -->



</body>

</html>
