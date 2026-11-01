@extends('layouts.app')
@section('title','Analitik Semua Rilis')
@section('content-class','main-content--wide')
@section('content')
@php
    $number = fn ($value) => number_format((float)$value,0,',','.');
    $money = fn ($value) => 'Rp '.$number($value);
    $name = fn ($row) => trim(($row->firstname??'').' '.($row->lastname??'')) ?: 'Akun #'.($row->profileid??$row->id);
    $delta = function ($current,$prior) use ($filters) {
        if ($filters['compare']==='none') return 'Tanpa perbandingan';
        if ((float)$prior <= 0) return 'Belum ada pembanding positif';
        $change = (((float)$current-(float)$prior)/(float)$prior)*100;
        return ($change>0?'+':'').number_format($change,1,',','.').'%';
    };
    $link = fn ($tab) => route('admin.analytics.index',array_merge(request()->except(['release_page','track_page','user_page','artist_page','q']),['tab'=>$tab]));
    $tabs=['overview'=>'Ringkasan','releases'=>'Rilis & Lagu','artists'=>'Artis & User','geography'=>'Platform & Negara','quality'=>'Kualitas Data'];
    $countryName = fn ($value) => \App\Support\CountryName::display($value);
    $maxCountry = max(1,(float)$countries->max('streams'));
@endphp
<div class="admin-analytics">
    <header class="aa-heading"><div><h1>Analitik Semua Rilis</h1><p>Pantau katalog, stream, dan pendapatan dari laporan royalti yang telah dihitung.</p></div><a class="btn btn-primary" href="{{ route('admin.analytics.export',request()->query()) }}"><span class="material-icons-outlined">download</span> Ekspor Rilis (CSV)</a></header>
    <nav class="aa-tabs" aria-label="Bagian analitik">@foreach($tabs as $key=>$label)<a href="{{ $link($key) }}" class="{{ $filters['tab']===$key?'active':'' }}" @if($filters['tab']===$key) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>
    @include('admin.analytics.filters')
    <div class="aa-kpis">
        @foreach([['Total Stream / Unit',$number($totals->streams),'play_arrow',$delta($totals->streams,$previous->streams??0)],['Pendapatan Total',$money($totals->revenue),'payments',$delta($totals->revenue,$previous->revenue??0)],['Bagian User',$money($totals->user_revenue),'groups','Sesuai rate pada baris laporan'],['Bagian Sistem',$money($totals->system_revenue),'pie_chart','Pendapatan setelah bagian user']] as $kpi)
        <article class="aa-panel aa-kpi"><div><span class="aa-icon material-icons-outlined">{{ $kpi[2] }}</span><span>{{ $kpi[0] }}</span></div><strong>{{ $kpi[1] }}</strong><small>{{ $kpi[3] }}</small></article>
        @endforeach
    </div>
    @if($filters['tab']==='overview')
        <div class="aa-chart-grid">
            <article class="aa-panel"><div class="aa-section-title"><h2>Tren Performa</h2><select id="aa-metric" class="form-select" aria-label="Metrik grafik"><option value="streams">Stream / Unit</option><option value="revenue">Pendapatan</option></select></div><div id="aa-trend" class="aa-chart" aria-label="Grafik tren performa"></div><p class="aa-note">{{ $filters['start'] }} s.d. {{ $filters['end'] }}@if($filters['compare']!=='none') dibandingkan {{ substr($filters['previous_from'],0,7) }} s.d. {{ substr($filters['previous_to'],0,7) }}@endif. Bulan tanpa laporan ditampilkan 0.</p></article>
            <article class="aa-panel"><h2>Sumber Pendapatan</h2><div id="aa-platform-chart" class="aa-chart" aria-label="Kontribusi pendapatan platform"></div><p class="aa-note">Kontribusi positif per platform. Koreksi negatif tetap dihitung dalam total dan tabel.</p><a class="aa-more" href="{{ $link('geography') }}">Lihat semua platform →</a></article>
        </div>
        <div class="aa-halves">
            <article class="aa-panel"><div class="aa-section-title"><h2>Rilis Teratas</h2><span class="aa-note">Berdasarkan stream / unit</span></div>
                @forelse($topReleases as $release)<div class="aa-ranking"><img src="{{ $covers[$release->id] }}" alt="" loading="lazy"><div><strong>{{ $release->title }}</strong><small>{{ $name($release) }}</small></div><div class="aa-ranking-value"><strong>{{ $number($release->streams) }}</strong><small>{{ $money($release->revenue) }}</small></div></div>@empty<p class="aa-empty">Belum ada aktivitas rilis pada periode ini.</p>@endforelse
                <a class="aa-more" href="{{ $link('releases') }}">Lihat semua rilis →</a>
            </article>
            <article class="aa-panel"><h2>Negara Pendengar</h2><p class="aa-note">Distribusi quantity sesuai negara pada laporan.</p>
                @forelse($countries->take(7) as $country)<div class="aa-country"><span>{{ $countryName($country->country) }}</span><div><i style="width:{{ max(0,min(100,100*(float)$country->streams/$maxCountry)) }}%"></i></div><strong>{{ $number($country->streams) }}</strong></div>@empty<p class="aa-empty">Belum ada data negara.</p>@endforelse
                <a class="aa-more" href="{{ $link('geography') }}">Lihat semua negara →</a>
            </article>
        </div>
    @endif
    @if(in_array($filters['tab'],['overview','quality']))
        <section class="aa-quality aa-panel"><div><h2>Kualitas Data</h2><p class="aa-note">Sesuai periode dan filter aktif.</p></div><div><strong>{{ $number($quality['completed']) }}</strong><small>Laporan selesai</small></div><div><strong>{{ $number($quality['waiting']) }}</strong><small>Menunggu calculation</small></div><div><strong>{{ $number($quality['incomplete']) }}</strong><small>ISRC belum punya akun</small></div><a href="{{ $link('quality') }}">Tinjau data →</a></section>
    @endif
    @if(in_array($filters['tab'],['overview','releases'])) @include('admin.analytics.releases') @endif
    @if($filters['tab']==='artists' || $filters['tab']==='geography') @include('admin.analytics.breakdown') @endif
    @if($filters['tab']==='quality') @include('admin.analytics.quality') @endif
    <footer class="aa-note">Koreksi negatif dipertahankan. Nilai IDR dibulatkan hanya untuk tampilan; ekspor mempertahankan nilai sumber.</footer>
</div>
@endsection
@push('css')
<link rel="stylesheet" href="{{ asset('build/css/admin-analytics.css') }}?v={{ file_exists(public_path('build/css/admin-analytics.css'))?filemtime(public_path('build/css/admin-analytics.css')):1 }}">
@endpush
@push('script')
@if($filters['tab']==='overview')
<script id="aa-chart-data" type="application/json">@json($chartData)</script>
<script src="{{ asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('build/js/admin-analytics.js') }}?v={{ file_exists(public_path('build/js/admin-analytics.js'))?filemtime(public_path('build/js/admin-analytics.js')):1 }}"></script>
@endif
@endpush
