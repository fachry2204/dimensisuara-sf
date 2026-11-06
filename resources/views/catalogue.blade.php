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
    <form class="catalogue-search" method="GET" action="{{ route('landing.releases') }}" role="search">
        <div class="catalogue-search-field"><label for="catalogue-query">Cari musik favorit Anda</label><input id="catalogue-query" type="search" name="q" value="{{ $search }}" maxlength="120" placeholder="Cari judul lagu atau nama artis…" aria-describedby="catalogue-search-help"></div>
        <button type="submit" class="button button-primary button-small">Cari Rilis <x-landing-icon/></button>
        @if($search !== '')<a class="catalogue-reset" href="{{ route('landing.releases') }}">Reset pencarian</a>@endif
        <p id="catalogue-search-help">Gabungkan kata dari judul dan nama artis. Urutan kata bebas, pencarian mencakup seluruh katalog.</p>
        @error('q')<p class="catalogue-search-error" role="alert">{{ $message }}</p>@enderror
    </form>
    @if($search !== '')<p class="catalogue-results" role="status">{{ number_format($releases->total(), 0, ',', '.') }} hasil untuk “{{ $search }}”</p>@endif
    <div class="release-grid">@forelse($releases as $release)<x-landing-release-card :release="$release" :index="$loop->index"/>@empty<div class="empty-catalogue">@if($search !== '')<h2>Rilisan belum ditemukan.</h2><p>Coba potongan judul atau nama artis yang lain.</p><a class="button button-outline button-small" href="{{ route('landing.releases') }}">Lihat Semua Rilis</a>@else<h2>Belum ada rilisan tersedia.</h2><p>Katalog akan diperbarui setelah musik dirilis.</p>@endif</div>@endforelse</div>
    @if($releases->total() > 0)
    <nav class="catalogue-pagination" aria-label="Halaman katalog rilis">
        <span>Halaman {{ $releases->currentPage() }} dari {{ $releases->lastPage() }}</span>
        <div class="catalogue-page-links">
            @if($releases->previousPageUrl())<a class="catalogue-page-control" rel="prev" aria-label="Halaman sebelumnya" href="{{ $releases->previousPageUrl() }}#rilis">←</a>@else<span class="catalogue-page-control is-disabled" aria-disabled="true">←</span>@endif
            @php($windowPage = min($releases->currentPage(), $releases->lastPage()))
            @php($pageNumbers = collect([1, $releases->lastPage()])->merge(range(max(1, $windowPage - 1), min($releases->lastPage(), $windowPage + 1)))->unique()->sort()->values())
            @foreach($pageNumbers as $pageNumber)
                @if($loop->index > 0 && $pageNumber > $pageNumbers[$loop->index - 1] + 1)<span class="catalogue-page-gap" aria-hidden="true">…</span>@endif
                @if($pageNumber === $releases->currentPage())<span class="catalogue-page-control is-current" aria-current="page" aria-label="Halaman {{ $pageNumber }}">{{ $pageNumber }}</span>@else<a class="catalogue-page-control" aria-label="Halaman {{ $pageNumber }}" href="{{ $releases->url($pageNumber) }}#rilis">{{ $pageNumber }}</a>@endif
            @endforeach
            @if($releases->nextPageUrl())<a class="catalogue-page-control" rel="next" aria-label="Halaman berikutnya" href="{{ $releases->nextPageUrl() }}#rilis">→</a>@else<span class="catalogue-page-control is-disabled" aria-disabled="true">→</span>@endif
        </div>
    </nav>
    @endif
</section>
</main>
<footer class="site-footer"><div class="wrap footer-bottom"><span>© {{ date('Y') }} {{ app_name() }}. Semua hak dilindungi.</span><a href="{{ route('landing') }}">Kembali ke Beranda</a></div></footer>
@foreach($releases as $release)<x-landing-release-dialog :release="$release" :index="$loop->index"/>@endforeach
</body></html>
