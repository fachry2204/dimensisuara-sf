@extends('layouts.guest')
@section('title')
    Verify Email
@endsection
@section('content')
    <div class="section-authentication-cover">
        <div class="">
            <div class="row g-0">
                <div class="col-12 col-xl-7 col-xxl-8 auth-cover-left align-items-center justify-content-center d-none d-xl-flex bg-transparent" style="background-image: url({{ URL::asset('build/images/illustration/illustration-2.webp') }}); background-size: cover; background-position: center;"></div>
                <div class="col-12 col-xl-5 col-xxl-4 auth-cover-right align-items-center justify-content-center border-top border-4 border-primary border-gradient-1">
                    <div class="card rounded-0 m-3 mb-0 border-0 shadow-none bg-none">
                        <div class="card-body p-5">
                            <a href="{{ url('/') }}">
                                <img src="{{ URL::asset('build/images/logo-soundfresh-full-white.webp') }}" class="mb-4" width="145" alt="SoundFresh Logo">
                            </a>
                            <h4 class="fw-bold">Verify Your Email Address</h4>
                            <p class="mb-0">Before proceeding, please check your email for a verification link!</p>
                            <p class="mb-0">Please don't forget to check your spam folder as well If you didn't receive the email.</p>

                            @if (session('resent'))
                                <div class="alert alert-success" role="alert">
                                    {{ __('A fresh verification link has been sent to your email address.') }}
                                </div>
                            @endif

                            <div class="form-body mt-4">
                                <form method="POST" action="{{ route('verification.resend') }}" class="row g-3">
                                    @csrf
                                    <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('Click here to request another link') }}</button>
                                </form>
                            </div>

                            <div class="mt-3 text-center">
                                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="btn btn-link p-0 m-0 align-baseline">{{ __('Logout') }}</a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
