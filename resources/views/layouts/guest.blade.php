<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | SoundFresh</title>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ URL::asset('favicon.ico') }}" type="image/png">

    @include('layouts.head-css')
    @vite('resources/js/soundfresh.js')
</head>

<body class="{{ isset($bodyClass) ? $bodyClass : '' }}">

    @yield('content')

    @stack('modals')

    @include('layouts.common-scripts')
    
</body>

</html>
