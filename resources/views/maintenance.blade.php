@extends('layouts.guest')
@php($bodyClass = 'sf-maintenance')
@section('title', 'Pemeliharaan Sistem')
@section('content')
<main class="maintenance-screen">
    <section class="maintenance-card" aria-labelledby="maintenance-title">
        <img src="{{ app_logo() }}" alt="{{ app_name() }}" class="maintenance-logo">
        <div class="maintenance-symbol" aria-hidden="true"><span class="material-icons-outlined">engineering</span></div>
        <p class="maintenance-eyebrow">PEMELIHARAAN SISTEM</p>
        <h1 id="maintenance-title">Kami sedang melakukan perbaikan</h1>
        <p class="maintenance-intro">Kami menyiapkan layanan yang lebih baik untuk Anda.</p>
        <div class="maintenance-reason"><strong>Informasi maintenance</strong><p>{{ $reason }}</p></div>
        <div class="maintenance-actions"><a href="{{ request()->fullUrl() }}" class="btn btn-primary">Coba lagi</a>
            @auth<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn btn-outline-primary">Keluar</button></form>
            @else<a href="{{ route('login') }}" class="btn btn-outline-primary">Masuk</a>@endauth
        </div>
        <small>Terima kasih atas kesabaran Anda. — {{ app_name() }}</small>
    </section>
</main>
<style>
.sf-maintenance .pace{display:none!important}
.maintenance-screen{min-height:100vh;display:grid;place-items:center;padding:32px 20px;background:var(--sf-canvas);color:var(--sf-text)}
.maintenance-card{width:min(680px,100%);padding:44px;border:1px solid var(--sf-border);border-radius:24px;background:var(--sf-surface);box-shadow:0 24px 80px rgba(0,0,0,.12);text-align:center}
.maintenance-logo{max-width:220px;max-height:75px;object-fit:contain;margin-bottom:30px}.maintenance-symbol{display:grid;place-items:center;width:88px;height:88px;margin:0 auto 24px;border-radius:24px;background:var(--sf-accent-soft);color:var(--sf-accent)}.maintenance-symbol span{font-size:48px}.maintenance-eyebrow{font-size:12px;letter-spacing:.16em;color:var(--sf-accent);font-weight:700}.maintenance-card h1{font-size:clamp(25px,4vw,36px);color:var(--sf-text);line-height:1.25}.maintenance-intro,.maintenance-card small{color:var(--sf-muted)}.maintenance-reason{margin:28px 0;padding:20px;border:1px solid var(--sf-border);border-radius:14px;background:var(--sf-surface-soft);text-align:left}.maintenance-reason p{margin:8px 0 0;white-space:pre-wrap;overflow-wrap:anywhere;color:var(--sf-muted)}.maintenance-actions{display:flex;gap:12px;justify-content:center;margin:0 0 28px}.maintenance-actions form{margin:0}@media(max-width:575px){.maintenance-card{padding:28px 20px}}
</style>
@endsection
