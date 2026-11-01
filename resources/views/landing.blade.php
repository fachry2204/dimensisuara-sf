<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ app_name() }} — Distribusi Musik & Publishing</title>
    <meta name="description" content="Sebarkan karya musik Anda bersama Dimensi Suara. Distribusi digital, publishing, dan laporan royalti dalam satu platform.">
    <meta name="theme-color" content="#080b14">
    <link rel="icon" href="{{ app_favicon() }}">
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
        <a class="nav-link active" href="#beranda">Beranda</a><a class="nav-link" href="#layanan">Layanan</a><a class="nav-link" href="#rilis">Rilis</a><a class="nav-link" href="#kontak">Kontak</a>
        <div class="mobile-auth"><a href="{{ route('login') }}">Masuk</a><a href="{{ route('register') }}">Daftar Gratis ↗</a></div>
    </nav>
    <div class="header-actions"><a class="button button-outline button-small" href="{{ route('login') }}">Masuk</a><a class="button button-primary button-small" href="{{ route('register') }}">Daftar Gratis <span>↗</span></a></div>
</header>
<main id="main">
<section class="hero wrap" id="beranda">
    <div class="hero-copy"><p class="eyebrow">DISTRIBUSI MUSIK & PUBLISHING</p>
        <h1>Sebarkan Karya<br><span>Musik Anda</span> ke<br>Seluruh Dunia</h1>
        <p class="hero-description">Distribusikan musik, kelola hak cipta, dan pantau royalti dalam satu platform.</p>
        <div class="hero-actions"><a class="button button-primary" href="{{ route('register') }}">Mulai Sekarang <x-landing-icon/></a><a class="button button-outline" href="#layanan">Lihat Layanan</a></div>
        <div class="hero-benefits"><span><x-landing-icon name="globe"/>Distribusi Digital</span><span><x-landing-icon name="chart"/>Royalti Transparan</span><span><x-landing-icon name="data"/>Data Terpusat</span></div>
    </div>
    <div class="hero-visual" aria-label="Ilustrasi dashboard dan katalog musik">
        <div class="orbital orbital-one"></div><div class="orbital orbital-two"></div><div class="visual-halo"></div>
        <div class="demo-dashboard">
            <div class="demo-caption"><i></i> Contoh dashboard <span>ILUSTRASI</span></div>
            <div class="dashboard-body"><aside class="demo-sidebar"><img src="{{ app_logo() }}" alt="" width="90" height="28"><span class="selected">⌂ <b>Beranda</b></span><span>♫ <b>Rilis Saya</b></span><span>▥ <b>Analitik</b></span><span>▤ <b>Royalti</b></span><span>▧ <b>Katalog</b></span><span>⚙ <b>Pengaturan</b></span><div class="demo-avatar">DS <small>Artist workspace</small></div></aside>
            <div class="demo-main"><div class="demo-heading">Ringkasan Rilis <span>30 Hari Terakhir⌄</span></div><div class="demo-metrics"><div><b>12</b><small>Total Rilis</small></div><div><b>8</b><small>Platform Aktif</small></div><div><b>3</b><small>Negara Teratas</small></div></div>
            <div class="demo-chart-title">Performa Streaming <span>Streaming⌄</span></div>
            <div class="line-chart"><div class="axis"><span>300K</span><span>200K</span><span>100K</span><span>0</span></div><svg viewBox="0 0 360 150" preserveAspectRatio="none" aria-hidden="true"><defs><linearGradient id="chart-line"><stop stop-color="#dd44ff"/><stop offset="1" stop-color="#20d9fa"/></linearGradient><linearGradient id="chart-fill" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#6346ff" stop-opacity=".4"/><stop offset="1" stop-color="#6346ff" stop-opacity="0"/></linearGradient></defs><path d="M0 133 C20 130 24 100 45 104S75 125 95 88S125 60 145 78S180 95 195 66S230 80 250 46S280 53 300 26S335 28 360 9V150H0Z" fill="url(#chart-fill)"/><path d="M0 133 C20 130 24 100 45 104S75 125 95 88S125 60 145 78S180 95 195 66S230 80 250 46S280 53 300 26S335 28 360 9" fill="none" stroke="url(#chart-line)" stroke-width="3"/></svg><div class="chart-dates"><span>1 Jan</span><span>8 Jan</span><span>15 Jan</span><span>22 Jan</span><span>31 Jan</span></div></div>
            <div class="demo-chart-title">Laporan Royalti <span>Lihat Detail ↗</span></div><div class="bar-chart" aria-hidden="true">@foreach([30,60,42,85,55,40,72,95,48,65,80,57,96,70,90,66] as $height)<i style="--bar:{{ $height }}%"></i>@endforeach</div></div>
            <div class="demo-platforms"><b>Platform</b>@foreach([['Spotify',42,'#1ed760'],['Apple Music',24,'#ffffff'],['YouTube Music',18,'#ff384d'],['TikTok',10,'#36d9df'],['Deezer',6,'#b650ef']] as [$platform,$percent,$color])<div><i style="--platform-color:{{ $color }}">{{ mb_substr($platform,0,1) }}</i><span>{{ $platform }}<small><em style="width:{{ $percent * 2 }}%"></em></small></span><sup>{{ $percent }}%</sup></div>@endforeach</div></div>
        </div>
        <div class="floating-covers">@foreach($releases->take(3) as $release)<div class="floating-cover"><x-landing-artwork :release="$release" :index="$loop->index"/></div>@endforeach</div>
    </div>
</section>
<div class="platform-strip"><div class="wrap platform-inner"><p>Musik Anda di platform<br>digital favorit</p><div class="platform-names"><span class="spotify"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="12"/><g fill="none" stroke="#080b14" stroke-linecap="round" stroke-width="1.8"><path d="M5 8q8-3 14 1M6 12q7-2 12 1M7 16q5-1 10 1"/></g></svg>Spotify</span><span class="apple">● <b>Music</b></span><span class="youtube"><i>▶</i> YouTube Music</span><span class="tiktok">♪ <b>TikTok</b></span><span class="deezer"><i>▥</i> DEEZER</span></div></div></div>
<section class="services wrap section" id="layanan"><p class="eyebrow">LAYANAN KAMI</p><h2>Satu Platform untuk<br class="mobile-break"> Perjalanan Musik Anda</h2>
    <div class="service-grid">@foreach([['music','Distribusi Digital','Distribusikan musik Anda ke platform streaming global. Kelola dan ajukan rilis dari satu tempat.'],['file','Publishing Royalti','Daftarkan karya dan kelola kebutuhan publishing Anda dengan informasi hak cipta yang tertata.'],['chart','Analitik Rilis','Pantau performa streaming dan laporan royalti melalui dashboard yang mudah dipahami.']] as [$icon,$title,$description])<a class="service-card" href="{{ route('register') }}"><span class="icon-frame"><x-landing-icon :name="$icon"/></span><h3>{{ $title }}</h3><p>{{ $description }}</p><span class="round-arrow"><x-landing-icon/></span></a>@endforeach</div>
</section>
<section class="releases wrap section" id="rilis"><div class="section-heading"><div><p class="eyebrow">DARI KATALOG KAMI</p><h2>Karya Terbaru Artis Kami</h2></div><a class="button button-outline button-small" href="{{ route('landing.releases') }}">Lihat Semua Rilis <x-landing-icon/></a></div>
    <div class="release-grid" id="release-grid">@forelse($releases as $release)<x-landing-release-card :release="$release" :index="$loop->index"/>
    @empty<div class="empty-catalogue"><x-landing-icon name="music"/><h3>Karya berikutnya bisa jadi milik Anda.</h3><p>Mulai perjalanan rilis Anda bersama {{ app_name() }}.</p><a href="{{ route('register') }}" class="button button-outline">Daftarkan karya</a></div>@endforelse</div>
</section>
<section class="cta-section wrap"><div class="cta-band"><div><p class="eyebrow">DARI KARYA, MENJADI CERITA DUNIA</p><h2>Saatnya Musik Anda<br>Didengar Dunia</h2><p>Bergabunglah dengan {{ app_name() }} dan bawa karya Anda<br class="desktop-break"> ke berbagai platform streaming global.</p></div><a class="button button-primary" href="{{ route('register') }}">Daftar Sekarang <x-landing-icon/></a><div class="digital-globe" aria-hidden="true"><svg viewBox="0 0 300 300"><defs><radialGradient id="globe-fill" cx="30%" cy="25%"><stop stop-color="#162f69"/><stop offset="1" stop-color="#080c25"/></radialGradient><linearGradient id="globe-stroke"><stop stop-color="#24dbff"/><stop offset="1" stop-color="#d241f6"/></linearGradient><clipPath id="sphere"><circle cx="150" cy="150" r="130"/></clipPath></defs><circle cx="150" cy="150" r="130" fill="url(#globe-fill)" stroke="url(#globe-stroke)" stroke-width="2"/><g clip-path="url(#sphere)" fill="none" stroke="#687fe1" stroke-opacity=".4"><ellipse cx="150" cy="150" rx="40" ry="130"/><ellipse cx="150" cy="150" rx="90" ry="130"/><ellipse cx="150" cy="150" rx="130" ry="40"/><ellipse cx="150" cy="150" rx="130" ry="90"/><path d="M20 150h260M150 20v260"/></g><g fill="#78bbff"><circle cx="70" cy="100" r="3"/><circle cx="120" cy="65" r="3"/><circle cx="183" cy="104" r="3"/><circle cx="220" cy="153" r="3"/><circle cx="154" cy="192" r="3"/><circle cx="98" cy="164" r="3"/></g><path d="m70 100 50-35 63 39 37 49-66 39-56-28-28-64 113 4-85 60 122-11" fill="none" stroke="#83afff" stroke-opacity=".5"/></svg></div></div></section>
<section class="contact wrap section" id="kontak"><p class="eyebrow">MARI TERHUBUNG</p><h2>Ada Pertanyaan? Kami Siap Membantu</h2><p class="section-description">Diskusikan distribusi, publishing, dan langkah berikutnya untuk musik Anda.</p><div class="contact-grid">
    @if($email)<a class="contact-card" href="mailto:{{ $email }}"><span class="icon-frame"><x-landing-icon name="mail"/></span><span><b>Email</b><small>{{ $email }}</small></span><x-landing-icon class="contact-arrow"/></a>@endif
    @if($whatsapp)<a class="contact-card whatsapp" href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer"><span class="icon-frame"><x-landing-icon name="chat"/></span><span><b>WhatsApp</b><small>Hubungi Tim Kami</small></span><x-landing-icon class="contact-arrow"/></a>@endif
    @if($address)<a class="contact-card" href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($address) }}" target="_blank" rel="noopener noreferrer"><span class="icon-frame"><x-landing-icon name="pin"/></span><span><b>Lokasi</b><small>Bogor, Jawa Barat</small></span><x-landing-icon class="contact-arrow"/></a>@endif
</div></section>
</main>
<footer class="site-footer"><div class="wrap"><div class="footer-main"><a href="#beranda" class="brand"><img src="{{ app_logo() }}" alt="{{ app_name() }}" width="220" height="64"></a><p>Platform distribusi musik dan publishing<br>untuk musisi independen Indonesia.</p><nav aria-label="Navigasi footer"><a href="#beranda">Beranda</a><a href="#layanan">Layanan</a><a href="#rilis">Rilis</a><a href="#kontak">Kontak</a></nav><a class="footer-top" href="#beranda">Kembali ke atas ↑</a></div><div class="footer-bottom"><span>© {{ date('Y') }} {{ app_name() }}. Semua hak dilindungi.</span><a href="{{ route('register.tnc') }}">Syarat & Ketentuan</a></div></div></footer>
@foreach($releases as $release)<x-landing-release-dialog :release="$release" :index="$loop->index"/>@endforeach
</body></html>
