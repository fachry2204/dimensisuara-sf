<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | SoundFresh</title>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ URL::asset('favicon.ico') }}" type="image/png">
    <script>
        // Apply saved theme before CSS loads to prevent a flash of wrong color.
        const savedTheme = localStorage.getItem('theme');
        const theme = savedTheme === 'light' || savedTheme === 'dark' ? savedTheme : 'dark';
        document.documentElement.setAttribute('data-bs-theme', theme);
    </script>

    @include('layouts.head-css')
    @vite('resources/js/soundfresh.js')
</head>
<body class="sf-app">

    @include('layouts.topbar')

    @include('layouts.sidebar')
    
    <!--start main wrapper-->
    <main class="main-wrapper">
        @if(session()->has('impersonate'))
            <div class="impersonation-spacer" aria-hidden="true"></div>
        @endif
        <div class="main-content">
            @if(Route::is('home'))
                <div data-vue-app></div>
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
    <script src="{{ URL::asset('build/js/main.js') }}"></script>
</body>

</html>
