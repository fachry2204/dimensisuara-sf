@extends('layouts.guest')
@section('title', 'Daftar')
@section('content')
    <div data-vue-register="{{ json_encode([
        'action' => route('register'),
        'csrf' => csrf_token(),
        'loginUrl' => route('login'),
        'pendingUrl' => route('pending'),
        'termsUrl' => route('register.tnc'),
        'logoUrl' => asset('build/images/logo-soundfresh-full-white.webp'),
        'studioUrl' => asset('build/images/auth/login-studio.png'),
        'countries' => Monarobase\CountryList\CountryListFacade::getList(),
    ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"></div>
@endsection
