@php($bodyClass = 'email-verification-page')
@extends('layouts.guest')
@section('title', 'Verifikasi Email')
@push('css')
<style>
body.email-verification-page{--verify-bg:#0b1220;--verify-card:#111c2d;--verify-ink:#edf3fb;--verify-muted:#b2bfd0;--verify-line:#334155;--verify-inset:#0b1524;margin:0;background:var(--verify-bg);color:var(--verify-ink)}
html[data-bs-theme="light"] body.email-verification-page{--verify-bg:#f4f6fa;--verify-card:#fff;--verify-ink:#172338;--verify-muted:#526278;--verify-line:#dce3ed;--verify-inset:#f6f8fc}
.verification-shell{display:grid;place-items:center;min-height:100vh;min-height:100svh;padding:20px 16px}
.verification-card{width:100%;max-width:480px;padding:26px;border:1px solid var(--verify-line);border-radius:18px;background:var(--verify-card);box-shadow:0 18px 50px rgba(0,0,0,.12)}
.verification-brand{display:block;text-align:center;margin-bottom:16px}.verification-brand img{width:160px;height:38px;object-fit:contain;max-width:100%}
.verification-brand .verification-logo-light{display:none}html[data-bs-theme="light"] .verification-brand .verification-logo-light{display:inline-block}html[data-bs-theme="light"] .verification-brand .verification-logo-dark{display:none}
.verification-icon{display:grid;place-items:center;width:44px;height:44px;margin:0 auto 12px;border-radius:16px;background:rgba(124,77,255,.13);color:#a78bfa}.verification-icon span{font-size:26px}
.verification-card h1{font-size:24px;font-weight:700;text-align:center;line-height:1.3;color:var(--verify-ink);margin:0 0 12px}
.verification-intro{color:var(--verify-muted);font-size:13px;text-align:center;line-height:1.6;margin-bottom:18px}
.verification-address{padding:10px 14px;border:1px solid var(--verify-line);border-radius:10px;background:var(--verify-inset);margin-bottom:18px}.verification-address span{display:block;font-size:12px;color:var(--verify-muted);margin-bottom:4px}.verification-address strong{display:block;overflow-wrap:anywhere;font-size:14px;color:var(--verify-ink)}
.verification-steps{padding-left:20px;margin-bottom:18px;color:var(--verify-muted);font-size:13px;line-height:1.65}.verification-steps li{padding-left:5px;margin-bottom:9px}
.verification-card form{margin:0}.verification-card .verification-resend,.verification-card .verification-logout{display:block;width:100%;min-height:44px;border-radius:9px;font-weight:600;font-size:14px;text-align:center;text-decoration:none;padding:11px 16px}
.verification-card .verification-resend{background:var(--bs-primary,#6d28d9);border:1px solid var(--bs-primary,#6d28d9);color:#fff}.verification-card .verification-resend:hover{filter:brightness(1.12)}
.verification-card .verification-continue{display:block;text-align:center;margin:12px 0;color:#a78bfa;font-size:13px;text-decoration:underline}
html[data-bs-theme="light"] .verification-card .verification-continue{color:#6d28d9}
html[data-bs-theme] body.email-verification-page .verification-card form>button.verification-logout[type="submit"]{background:transparent!important;background-image:none!important;color:var(--verify-ink)!important;border:1px solid var(--verify-line)!important;box-shadow:none!important}
.verification-note{padding-top:14px;margin:16px 0 0;border-top:1px solid var(--verify-line);font-size:12px;line-height:1.6;color:var(--verify-muted);text-align:center}
.verification-feedback{border-radius:9px;padding:12px;font-size:13px;line-height:1.6;margin-bottom:18px;background:rgba(16,185,129,.12);color:var(--verify-ink);border:1px solid #159d77}.verification-errors{border-color:#d86b65}
@media(max-width:480px){.verification-shell{padding:20px 12px}.verification-card{padding:26px 22px}.verification-card h1{font-size:23px}}
</style>
@endpush
@section('content')
<main class="verification-shell">
    <section class="verification-card" aria-labelledby="verification-title">
        <a class="verification-brand" href="{{ route('landing') }}"><img class="verification-logo-dark" src="{{ app_logo() }}" alt="{{ app_name() }}"><img class="verification-logo-light" src="{{ app_logo('build/images/logo-soundfresh-full.webp') }}" alt="{{ app_name() }}"></a>
        <div class="verification-icon" aria-hidden="true"><span class="material-icons-outlined">mark_email_unread</span></div>
        <h1 id="verification-title">Verifikasi email Anda</h1>
        <p class="verification-intro">Klik tautan verifikasi pada email Anda untuk melanjutkan ke akun {{ app_name() }}.</p>
        <div class="verification-address"><span>Alamat email akun</span><strong>{{ auth()->user()->email }}</strong></div>
        @if(session('resent'))<div class="verification-feedback" role="status">Tautan verifikasi baru telah dikirim. Silakan periksa kotak masuk dan folder spam Anda.</div>@endif
        @if($errors->any())<div class="verification-feedback verification-errors" role="alert">@foreach($errors->all() as $error)<p class="mb-0">{{ $error }}</p>@endforeach</div>@endif
        <ol class="verification-steps"><li>Buka kotak masuk untuk alamat email di atas.</li><li>Cari pesan dari {{ app_name() }}. Periksa juga folder Spam atau Promosi.</li><li>Klik tautan verifikasi, lalu lanjutkan ke dashboard.</li></ol>
        <form method="POST" action="{{ route('verification.resend') }}">@csrf<button class="verification-resend" type="submit">Kirim ulang email verifikasi</button></form>
        <a class="verification-continue" href="{{ route('home') }}">Sudah verifikasi? Lanjutkan ke akun</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="verification-logout" type="submit">Keluar dari akun</button></form>
        <p class="verification-note">Verifikasi email tidak menggantikan pemeriksaan profil dan dokumen.</p>
    </section>
</main>
@endsection
