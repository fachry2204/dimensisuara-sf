<!doctype html>
<html lang="en">
<body style="background-color: rgb(24, 28, 31); font-size: 14px; font-family: 'Open Sans', Arial, sans-serif; letter-spacing: .5px;">
    <div style="max-width: 1100px; margin: auto; padding: 1.5rem">
        <div style="background-color: black; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, .075); border-radius: 1rem;">
            <div style="display: flex; flex-direction: column; align-items: center">
                <a href="{{ url('/') }}">
                    <img src="{{ app_logo('build/images/logo-soundfresh-full-white.webp') }}" alt="{{ app_name() }} Logo" width="200">
                </a>
                <h4 style="font-weight: 700; font-size: 1.5rem; color: white; text-align:center">
                    @yield('title')
                </h4>
                <div style="text-align: center; margin-bottom: 1.5em; color: #DEE2E6!important"
                    >@yield('content')
                </div>
                <div style="text-align: center; margin-top: 1.5em">
                    <a href="{{ url('/') }}" style="text-decoration: none; color: #FF6A00">{{ app_name() }} | {{ date('Y') }}</a>
                </div>
            </div>
        </div>
    </div>
</body>