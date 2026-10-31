@extends('layouts.guest')
@section('title')
    Forget Password
@endsection
@section('content')
    <div class="section-authentication-cover">
        <div class="">
            <div class="row g-0">
                <div class="col-12 col-xl-7 col-xxl-8 auth-cover-left align-items-center justify-content-center d-none d-xl-flex bg-transparent" style="background-image: url({{ URL::asset('build/images/illustration/illustration-1.webp') }}); background-size: cover; background-position: left;"></div>
                <div class="col-12 col-xl-5 col-xxl-4 auth-cover-right align-items-center justify-content-center">
                    <div class="card rounded-0 m-3 mb-0 border-0 shadow-none bg-none">
                        <div class="card-body p-5">
                            <a href="{{ url('/') }}">
                                <img src="{{ app_logo('build/images/logo-soundfresh-full-white.webp') }}" class="mb-4" width="145" alt="{{ app_name() }} Logo">
                            </a>
                            <h4 class="fw-bold">Lost Your Rhythm?</h4>
                            <p class="mb-0">Don't worry, we'll help you get back on track. Reset your password below by entering the email address associated with your account.</p>

                            @if (session('status'))
                                <div class="alert alert-success" role="alert">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <div class="form-body mt-4">
                                <form method="POST" action="{{ route('password.email') }}" class="row g-3">
                                    @csrf

                                    <div class="col-12">
                                        <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
                                        <input id="email" type="email"
                                            class="form-control @error('email') is-invalid @enderror" name="email"
                                            value="{{ old('email') }}" required autocomplete="email" autofocus
                                            placeholder="Enter your email">

                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-grd-branding">Send Password Reset Link</button>
                                            <a href="{{ route('login') }}" class="btn btn-grd-royal">Back to Login</a>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
