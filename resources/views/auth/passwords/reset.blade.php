@extends('layouts.guest')
@section('title')
    Reset Password
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
                            <p class="mb-0">Don't worry, we'll help you get back on track. You can reset your password by setting a new one below.</p>
                            <div class="form-body mt-4">
                                <form method="POST" action="{{ route('password.update') }}" class="row g-3">
                                    @csrf

                                    <input type="hidden" name="token" value="{{ $token }}">

                                    <div class="col-12">
                                        <label class="form-label" for="email">Email Address<span class="text-danger">*</span></label>
                                        <input id="email" type="email"
                                            class="form-control @error('email') is-invalid @enderror" name="email"
                                            value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>

                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                                        <input id="password" type="password"
                                            class="form-control @error('password') is-invalid @enderror" name="password"
                                            required autocomplete="new-password">

                                        @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="password-confirm">Confirm Password <span class="text-danger">*</span></label>
                                        <input id="password-confirm" type="password" class="form-control"
                                            name="password_confirmation" required autocomplete="new-password">
                                    </div>

                                    <div class="col-12">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-grd-branding">Change Password</button>
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
@push('script')
    <script>
        $(document).ready(function() {
            $("#show_hide_password a").on('click', function(event) {
                event.preventDefault();
                if ($('#show_hide_password input').attr("type") == "text") {
                    $('#show_hide_password input').attr('type', 'password');
                    $('#show_hide_password i').addClass("bi-eye-slash-fill");
                    $('#show_hide_password i').removeClass("bi-eye-fill");
                } else if ($('#show_hide_password input').attr("type") == "password") {
                    $('#show_hide_password input').attr('type', 'text');
                    $('#show_hide_password i').removeClass("bi-eye-slash-fill");
                    $('#show_hide_password i').addClass("bi-eye-fill");
                }
            });
        });
    </script>
@endpush
