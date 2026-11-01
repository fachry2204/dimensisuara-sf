<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Semua Rilis — {{ app_name() }}</title>
    <meta name="description" content="Jelajahi katalog rilisan terbaru artis {{ app_name() }}.">
    <meta name="theme-color" content="#080b14">
    <x-favicon />
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;650;700;750;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('build/css/landing.css') }}?v={{ hash_file('sha256', resource_path('css/landing.css')) }}">
    <script src="{{ asset('build/js/landing.js') }}?v={{ hash_file('sha256', resource_path('js/landing.js')) }}" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Langsung ke konten</a>
<header class="site-header wrap">
    <a class="brand" href="{{ route('landing') }}" aria-label="{{ app_name() }} — Beranda"><img src="{{ app_logo() }}" alt="{{ app_name() }}" width="236" height="68"></a>
    <button class="menu-toggle" aria-label="Buka navigasi" aria-controls="navigation" aria-expanded="false"><span></span><span></span></button>
    <nav id="navigation" aria-label="Navigasi utama">
        <a class="nav-link" href="{{ route('landing') }}#beranda">Beranda</a><a class="nav-link" href="{{ route('landing') }}#layanan">Layanan</a><a class="nav-link active" href="{{ route('landing.releases') }}#rilis">Rilis</a><a class="nav-link" href="{{ route('landing') }}#kontak">Kontak</a>
        <div class="mobile-auth"><a href="{{ route('login') }}">Masuk</a><a href="{{ route('register') }}">Daftar Gratis ↗</a></div>
    </nav>
    <div class="header-actions"><a class="button button-outline button-small" href="{{ route('login') }}">Masuk</a><a class="button button-primary button-small" href="{{ route('register') }}">Daftar Gratis <span>↗</span></a></div>
</header>
<main id="main" class="catalogue-page wrap">
<section class="section" id="rilis">
    <div class="section-heading"><div><p class="eyebrow">KATALOG {{ mb_strtoupper(app_name()) }}</p><h1>Semua Rilis</h1><p class="catalogue-description">{{ number_format($releases->total(), 0, ',', '.') }} rilisan yang telah dirilis. Temukan musik dari artis kami.</p></div><a class="button button-outline button-small" href="{{ route('landing') }}">Kembali ke Beranda <x-landing-icon/></a></div>
    <div class="release-grid">@forelse($releases as $release)<x-landing-release-card :release="$release" :index="$loop->index"/>@empty<div class="empty-catalogue"><h2>Belum ada rilisan tersedia.</h2><p>Katalog akan diperbarui setelah musik dirilis.</p></div>@endforelse</div>
    @if($releases->hasPages())<nav class="catalogue-pagination" aria-label="Halaman katalog rilis"><span>Halaman {{ $releases->currentPage() }} dari {{ $releases->lastPage() }}</span><div>@if($releases->previousPageUrl())<a class="button button-outline button-small" rel="prev" href="{{ $releases->previousPageUrl() }}">← Sebelumnya</a>@endif @if($releases->nextPageUrl())<a class="button button-outline button-small" rel="next" href="{{ $releases->nextPageUrl() }}">Berikutnya →</a>@endif</div></nav>@endif
</section>
</main>
<footer class="site-footer"><div class="wrap footer-bottom"><span>© {{ date('Y') }} {{ app_name() }}. Semua hak dilindungi.</span><a href="{{ route('landing') }}">Kembali ke Beranda</a></div></footer>
@foreach($releases as $release)<x-landing-release-dialog :release="$release" :index="$loop->index"/>@endforeach
</body></html>
