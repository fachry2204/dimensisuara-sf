<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ app_name() }}</title>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-favicon />

    @include('layouts.head-css')
    @vite('resources/js/dimensisuara.js')
</head>
<body class="sf-app">

    @include('layouts.topbar')

    @include('layouts.sidebar')
    
    <!--start main wrapper-->
    <main class="main-wrapper">
        @if(session()->has('impersonate'))
            <div class="impersonation-spacer" aria-hidden="true"></div>
        @endif
        <div class="main-content @yield('content-class')">
            @if(Route::is('home'))
                <div data-vue-app data-app-name="{{ app_name() }}"></div>
            @endif
            @yield('header')

            {{-- Errors and Success Messages --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible">
                    <div class="d-flex align-items-center">
                        <div class="ms-3">
                            <h6 class="mb-0 text-danger">You have error(s) in your request!</h6>
                            <div>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success alert-dismissible">
                    <div class="d-flex align-items-center">
                        <div class="ms-3">
                            <h6 class="mb-0 text-success">{{ session('success') }}</h6>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </div>

    </main>

    {{-- Modals --}}
    @stack('modals')
    
    <!--start overlay-->
    <div class="overlay btn-toggle"></div>
    <!--end overlay-->
    
    @include('layouts.offcanvas')
    @include('layouts.common-scripts')
    <script src="{{ asset('build/js/main.js') }}?v={{ file_exists(public_path('build/js/main.js')) ? filemtime(public_path('build/js/main.js')) : '1' }}"></script>
</body>

</html>
