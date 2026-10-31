@extends('layouts.app')

@section('title', 'Analytics & Penghasilan')

@section('header')
    <div class="analytics-page-header">
        <div>
            <span>ANALYTICS & PENGHASILAN</span>
            <h1>Analytics & Penghasilan</h1>
            <p>Pantau performa katalog dari laporan distribusi yang diterima.</p>
        </div>
        <form method="GET" action="{{ route('analytics.index') }}" class="analytics-period-form">
            <span class="material-icons-outlined" aria-hidden="true">calendar_month</span>
            <label for="analytics-date-range" class="visually-hidden">Pilih periode laporan</label>
            <select name="date_range" id="analytics-date-range" aria-label="Periode laporan" onchange="this.form.requestSubmit()">
                <option value="all_time" @selected($dateRange === 'all_time')>Semua waktu</option>
                <option value="last_completed_quarter" @selected($dateRange === 'last_completed_quarter')>Kuartal kalender terakhir</option>
                <option value="last_6_months" @selected($dateRange === 'last_6_months')>6 bulan terakhir</option>
                <option value="last_12_months" @selected($dateRange === 'last_12_months')>12 bulan terakhir</option>
                <option value="this_year" @selected($dateRange === 'this_year')>Tahun ini</option>
                <option value="last_year" @selected($dateRange === 'last_year')>Tahun lalu</option>
            </select>
            <span class="analytics-period-display">{{ \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('D MMM') }} – {{ \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('D MMM YYYY') }}</span>
            <button type="submit" class="visually-hidden">Terapkan</button>
        </form>
    </div>
@endsection

@section('content')
@php
    $formatStreams = fn ($value) => number_format((int) $value, 0, ',', '.');
    $formatMoney = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
    $trendUp = $trendDirection === 'up';
    $trendDown = $trendDirection === 'down';
    $countryName = function ($code) {
        $normalized = strtoupper(trim((string) $code));

        if (strlen($normalized) !== 2) {
            return $code;
        }

        try {
            return \Symfony\Component\Intl\Countries::getName($normalized, 'id') ?: $code;
        } catch (\Throwable $exception) {
            return $code;
        }
    };
@endphp
<div class="analytics-page">
    <div class="analytics-data-note"><span class="material-icons-outlined">info</span>
        @if($latestAvailableDate)
            Data terakhir tersedia: <strong>{{ \Carbon\Carbon::parse($latestAvailableDate)->locale('id')->isoFormat('MMMM YYYY') }}</strong>. Laporan dapat mengalami jeda dari mitra distribusi.
        @else
            Belum ada laporan distribusi tersedia untuk akun ini.
        @endif
    </div>
    @if($latestAvailableDate && $monthsFormatted->isEmpty() && $dateRange !== 'all_time')
        <div class="analytics-data-note mb-3"><span class="material-icons-outlined">date_range</span>Periode yang dipilih belum memiliki laporan. <a href="{{ route('analytics.index', ['date_range' => 'all_time']) }}">Lihat semua data yang tersedia</a>.</div>
    @endif

    <div class="analytics-summary-grid">
        <article class="analytics-summary-card">
            <span class="analytics-summary-icon stream material-icons-outlined">headphones</span>
            <div><small>Total stream</small><strong>{{ $formatStreams($currentPeriod) }}</strong><em class="{{ $trendUp ? 'up' : ($trendDown ? 'down' : '') }}"><span class="material-icons-outlined">{{ $trendUp ? 'trending_up' : ($trendDown ? 'trending_down' : 'remove') }}</span>@if($trendUp || $trendDown){{ number_format(abs($trendPercentage), 1, ',', '.') }}% vs periode sebelumnya @else Belum ada pembanding @endif</em></div>
        </article>
        <article class="analytics-summary-card">
            <span class="analytics-summary-icon income material-icons-outlined">account_balance_wallet</span>
            <div><small>Estimasi penghasilan</small><strong>{{ $formatMoney($periodRevenue) }}</strong><em>Nilai penghasilan klien pada laporan</em></div>
        </article>
        <article class="analytics-summary-card">
            <span class="analytics-summary-icon release material-icons-outlined">album</span>
            <div><small>Rilis berperforma</small><strong>{{ $formatStreams($releaseCount) }}</strong><em>Rilis dengan stream pada periode ini</em></div>
        </article>
        <article class="analytics-summary-card">
            <span class="analytics-summary-icon platform material-icons-outlined">equalizer</span>
            <div><small>Platform terlapor</small><strong>{{ $formatStreams($platformCount) }}</strong><em>Platform dengan aktivitas tercatat</em></div>
        </article>
    </div>

    <div class="analytics-primary-grid">
        <article class="analytics-card analytics-chart-card">
            <header><div><h2>Tren stream & penghasilan</h2><p>{{ \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('D MMM YYYY') }} – {{ \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('D MMM YYYY') }}</p></div><div class="analytics-legend"><span><i class="green"></i>Stream</span><span><i class="red"></i>Penghasilan</span></div></header>
            @if($monthsFormatted->isNotEmpty())<div id="analytics-bars" class="analytics-bars"></div>@else<div class="analytics-no-data"><span class="material-icons-outlined">bar_chart</span>Belum ada laporan pada periode ini.</div>@endif
        </article>
        <article class="analytics-card">
            <header><div><h2>Platform teratas</h2><p>Berdasarkan total stream</p></div></header>
            @forelse($platformRows as $index => $platform)
                @php($share = $currentPeriod ? ($platform->streams / $currentPeriod) * 100 : 0)
                <div class="analytics-rank"><b>{{ $index + 1 }}</b><div><strong>{{ $platform->platform }}</strong><span><i style="width:{{ min($share, 100) }}%"></i></span></div><em>{{ $formatStreams($platform->streams) }}</em><small>{{ number_format($share, 1, ',', '.') }}%</small></div>
            @empty
                <div class="analytics-no-data compact"><span class="material-icons-outlined">music_off</span>Belum ada data platform.</div>
            @endforelse
        </article>
    </div>

    <div class="analytics-secondary-grid">
        <article class="analytics-card">
            <header><div><h2>Wilayah pendengar</h2><p>Negara berdasarkan laporan distribusi</p></div></header>
            @forelse($countryRows as $index => $country)
                @php($share = $currentPeriod ? ($country->streams / $currentPeriod) * 100 : 0)
                <div class="analytics-country"><b>{{ $index + 1 }}</b><div><strong>{{ $countryName($country->country) }}</strong><span><i style="width:{{ min($share, 100) }}%"></i></span></div><em>{{ number_format($share, 1, ',', '.') }}%</em></div>
            @empty
                <div class="analytics-no-data compact"><span class="material-icons-outlined">public_off</span>Belum ada data wilayah.</div>
            @endforelse
        </article>
        <article class="analytics-card analytics-insights">
            <header><div><h2>Insight untuk kamu</h2><p>Ringkasan dari periode yang dipilih</p></div></header>
            @if($topTrack && $topTrack->streams > 0)<div class="analytics-insight featured"><span class="material-icons-outlined">lightbulb</span><div><small>LAGU TERKUAT</small><strong>{{ $topTrack->title }}</strong><p>Menyumbang {{ number_format($trackShare, 1, ',', '.') }}% dari seluruh stream pada periode ini.</p></div></div>@endif
            <div class="analytics-insight"><span class="material-icons-outlined">{{ $trendUp ? 'trending_up' : ($trendDown ? 'trending_down' : 'remove') }}</span><div><strong>{{ $trendUp ? 'Stream meningkat' : ($trendDown ? 'Stream menurun' : 'Perbandingan belum tersedia') }}</strong><p>@if($trendUp || $trendDown){{ number_format(abs($trendPercentage), 1, ',', '.') }}% dibandingkan periode sebelumnya.@else Pilih periode dengan data pembanding untuk melihat perubahan performa.@endif</p></div></div>
            @if($countryRows->first())<div class="analytics-insight"><span class="material-icons-outlined">public</span><div><strong>Wilayah terkuat: {{ $countryName($countryRows->first()->country) }}</strong><p>{{ number_format(($countryRows->first()->streams / max($currentPeriod, 1)) * 100, 1, ',', '.') }}% stream tercatat dari wilayah ini.</p></div></div>@endif
        </article>
    </div>

    <article class="analytics-card analytics-performance-card">
        <header><div><h2>Performa rilismu</h2><p>Semua rilis diurutkan berdasarkan stream pada periode ini · 20 rilis per halaman</p></div></header>
        @if($releases->isEmpty())<div class="analytics-no-data compact"><span class="material-icons-outlined">album</span>Belum ada rilis pada akun ini.</div>
        @else
            <div class="table-responsive"><table class="analytics-performance-table analytics-release-table"><thead><tr><th>#</th><th>Rilis</th><th>Artis</th><th>Format</th><th>Stream</th><th>Estimasi penghasilan</th><th class="analytics-action-heading">Aksi</th></tr></thead><tbody>@foreach($releases as $index => $release)@php($artistNames = collect($release->primaryartists ?? [])->map(fn ($artist) => data_get($artist, 'name'))->filter()->implode(', '))<tr><td>{{ $releases->firstItem() + $index }}</td><td><a class="analytics-release-link" href="{{ route('analytics.release.statistics', $release->id) }}" aria-label="Lihat statistik rilis untuk {{ $release->title }}"><span class="material-icons-outlined">album</span><strong>{{ $release->title }}</strong><i class="material-icons-outlined">arrow_forward</i></a></td><td>{{ $artistNames ?: '—' }}</td><td>{{ ucfirst($release->type ?: 'Rilis') }}</td><td>{{ $formatStreams($release->streams) }}</td><td>{{ $formatMoney($release->revenue) }}</td><td class="analytics-action-cell"><a class="analytics-view-button" href="{{ route('analytics.release.statistics', $release->id) }}" aria-label="Lihat detail analitik {{ $release->title }}"><span class="material-icons-outlined">visibility</span><span>View</span></a></td></tr>@endforeach</tbody></table></div>
            <nav class="analytics-pagination" aria-label="Navigasi halaman rilis">
                <span class="analytics-pagination-summary">Menampilkan {{ $releases->firstItem() }}–{{ $releases->lastItem() }} dari {{ $releases->total() }} rilis</span>
                @if($releases->hasPages())
                    @php($firstPage = max(1, $releases->currentPage() - 2))
                    @php($lastPage = min($releases->lastPage(), $releases->currentPage() + 2))
                    <div class="analytics-pagination-links">
                        @if($releases->onFirstPage())
                            <span class="is-disabled">‹ Sebelumnya</span>
                        @else
                            <a href="{{ $releases->previousPageUrl() }}">‹ Sebelumnya</a>
                        @endif
                        @if($firstPage > 1)
                            <a href="{{ $releases->url(1) }}">1</a>
                            @if($firstPage > 2)
                                <span class="is-ellipsis">…</span>
                            @endif
                        @endif
                        @for($page = $firstPage; $page <= $lastPage; $page++)
                            @if($page === $releases->currentPage())
                                <span class="is-current" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $releases->url($page) }}">{{ $page }}</a>
                            @endif
                        @endfor
                        @if($lastPage < $releases->lastPage())
                            @if($lastPage < $releases->lastPage() - 1)
                                <span class="is-ellipsis">…</span>
                            @endif
                            <a href="{{ $releases->url($releases->lastPage()) }}">{{ $releases->lastPage() }}</a>
                        @endif
                        @if($releases->hasMorePages())
                            <a href="{{ $releases->nextPageUrl() }}">Berikutnya ›</a>
                        @else
                            <span class="is-disabled">Berikutnya ›</span>
                        @endif
                    </div>
                @endif
            </nav>
        @endif
    </article>
</div>
@endsection

@push('css')
<style>
.sf-app .main-wrapper .main-content{max-width:1440px!important;padding:2rem!important;background:#0b1220!important;border:0!important}.analytics-page-header{display:flex;justify-content:space-between;align-items:flex-end;gap:24px;margin:0 0 22px}.analytics-page-header>div>span{display:block;color:#ff7131;font-size:11px;font-weight:800;letter-spacing:.16em;margin-bottom:8px}.analytics-page-header h1{margin:0;color:#f8fafc!important;font-size:34px!important;line-height:1.1;font-weight:800!important}.analytics-page-header p{color:#9aacc2!important;font-size:14px;margin:8px 0 0!important}.analytics-period-form{height:48px;display:flex;align-items:center;gap:9px;background:#151f2e;border:1px solid #2c3c54;border-radius:12px;padding:5px 6px 5px 13px}.analytics-period-form>span{color:#c5d2e0}.analytics-period-form select{min-width:148px;background:#1d293a;color:#f8fafc;border:0;border-radius:7px;padding:9px;font-size:13px;font-weight:700;outline:0}.analytics-period-form button{height:34px;background:#ff6230;border:0;border-radius:7px;color:#fff;padding:0 15px;font-weight:800;font-size:12px}.analytics-page{color:#d7e1ee}.analytics-data-note{display:flex;align-items:center;gap:8px;background:#11233d;border:1px solid #23466d;border-radius:10px;color:#aecbed;padding:11px 13px;font-size:12px;margin-bottom:18px}.analytics-data-note span{font-size:18px;color:#65a9f6}.analytics-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:18px}.analytics-summary-card,.analytics-card{background:#141e2d!important;border:1px solid #2a3a51!important;border-radius:16px;box-shadow:0 10px 26px rgba(0,0,0,.16)}.analytics-summary-card{min-height:126px;padding:20px;display:flex;gap:14px;align-items:center}.analytics-summary-icon{height:48px;width:48px;display:grid;place-items:center;border-radius:14px;font-size:24px}.analytics-summary-icon.stream{color:#38d996;background:#123f36}.analytics-summary-icon.income{color:#f87171;background:#482328}.analytics-summary-icon.release{color:#b395ff;background:#30255c}.analytics-summary-icon.platform{color:#64c9f6;background:#153c52}.analytics-summary-card small{display:block;color:#9eb0c6;font-size:12px;font-weight:700;margin-bottom:4px}.analytics-summary-card strong{display:block;color:#fff;font-size:23px;line-height:1.1}.analytics-summary-card em{display:flex;align-items:center;gap:2px;color:#8da0b7;font-size:10px;font-style:normal;margin-top:7px}.analytics-summary-card em span{font-size:14px}.analytics-summary-card em.up{color:#39d89d}.analytics-summary-card em.down{color:#fb7185}.analytics-primary-grid{display:grid;grid-template-columns:minmax(0,1.75fr) minmax(340px,.9fr);gap:18px;margin-bottom:18px}.analytics-secondary-grid{display:grid;grid-template-columns:minmax(0,.9fr) minmax(340px,1.1fr);gap:18px;margin-bottom:18px}.analytics-card{padding:20px}.analytics-card header{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px}.analytics-card h2{color:#f8fafc!important;font-size:17px!important;font-weight:800!important;margin:0 0 4px!important}.analytics-card header p{color:#8fa1b8!important;font-size:12px;margin:0!important}.analytics-legend{display:flex;gap:14px;white-space:nowrap;color:#a9b7c8;font-size:11px}.analytics-legend i{display:inline-block;height:8px;width:8px;border-radius:50%;margin-right:4px}.analytics-legend .green{background:#29c687}.analytics-legend .red{background:#f05252}.analytics-bars{height:290px}.analytics-rank{display:grid;grid-template-columns:24px minmax(0,1fr) auto 42px;gap:9px;align-items:center;padding:9px 0}.analytics-rank>b,.analytics-country>b{height:22px;width:22px;border-radius:50%;display:grid;place-items:center;background:#263851;color:#d6e0eb;font-size:11px}.analytics-rank strong,.analytics-country strong{display:block;color:#f3f6fa;font-size:12px}.analytics-rank>div>span,.analytics-country>div>span{display:block;height:5px;background:#293a51;border-radius:20px;margin-top:6px;overflow:hidden}.analytics-rank>div>span i,.analytics-country>div>span i{display:block;height:100%;background:linear-gradient(90deg,#ff563c,#ff8838);border-radius:inherit}.analytics-rank em,.analytics-country em{font-style:normal;color:#eef4fa;font-size:11px;font-weight:700}.analytics-rank>small{color:#93a6bd;text-align:right;font-size:10px}.analytics-country{display:grid;grid-template-columns:24px minmax(0,1fr) 44px;gap:10px;align-items:center;padding:10px 0}.analytics-insight{display:flex;gap:11px;padding:11px 0;border-top:1px solid #293a51}.analytics-insight.featured{border:1px solid #6c3e35;background:#261f22;border-radius:11px;padding:12px;margin-bottom:8px}.analytics-insight>span{display:grid;place-items:center;flex:0 0 32px;height:32px;border-radius:9px;background:#20334a;color:#7dbcf9;font-size:18px}.analytics-insight.featured>span{background:#553522;color:#ff9f43}.analytics-insight small{display:block;color:#ff873b;font-size:9px;font-weight:800;letter-spacing:.1em}.analytics-insight strong{display:block;color:#f8fafc;font-size:12px}.analytics-insight p{color:#9db0c7;font-size:11px;line-height:1.45;margin:3px 0 0}.analytics-performance-card{margin-bottom:18px}.analytics-performance-table{width:100%;min-width:680px;border-collapse:collapse}.analytics-performance-table th{color:#8fa1b8;font-size:10px;text-transform:uppercase;letter-spacing:.05em;text-align:left;padding:10px 8px;border-bottom:1px solid #32425a}.analytics-performance-table td{color:#b9c7d8;font-size:12px;padding:11px 8px;border-bottom:1px solid #25354c}.analytics-performance-table tbody tr:last-child td{border-bottom:0}.analytics-performance-table th:nth-child(n+4),.analytics-performance-table td:nth-child(n+4){text-align:right}.analytics-performance-table td:nth-child(2){color:#f8fafc;font-weight:700}.analytics-performance-table td:nth-child(2) span{vertical-align:middle;display:inline-grid;place-items:center;width:26px;height:26px;border-radius:7px;background:#2a2630;color:#fb8555;font-size:15px;margin-right:7px}.analytics-no-data{height:250px;display:grid;place-content:center;gap:6px;text-align:center;color:#8fa1b8;font-size:12px}.analytics-no-data span{font-size:30px}.analytics-no-data.compact{height:150px}@media(max-width:1100px){.analytics-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.analytics-primary-grid,.analytics-secondary-grid{grid-template-columns:1fr}}@media(max-width:700px){.sf-app .main-wrapper .main-content{padding:18px!important}.analytics-page-header{align-items:stretch;flex-direction:column}.analytics-period-form{width:max-content}.analytics-summary-grid{grid-template-columns:1fr}.analytics-primary-grid,.analytics-secondary-grid{gap:14px}}
</style>
<style>
.analytics-summary-grid{background:transparent!important}
.analytics-summary-card{--analytics-accent:#3b82f6;background:transparent!important;background-image:none!important;border:1px solid color-mix(in srgb,var(--analytics-accent) 48%,transparent)!important;border-top:3px solid var(--analytics-accent)!important;box-shadow:none!important;transition:transform .18s ease,border-color .18s ease,background-color .18s ease}
.analytics-summary-card:nth-child(1){--analytics-accent:#3b82f6}.analytics-summary-card:nth-child(2){--analytics-accent:#10b981}.analytics-summary-card:nth-child(3){--analytics-accent:#f59e0b}.analytics-summary-card:nth-child(4){--analytics-accent:#a855f7}
.analytics-summary-card:hover{transform:translateY(-3px);border-color:var(--analytics-accent)!important;background:color-mix(in srgb,var(--analytics-accent) 8%,transparent)!important;box-shadow:none!important}
.analytics-summary-card:focus-within{outline:2px solid var(--analytics-accent);outline-offset:3px}
.analytics-summary-card .analytics-summary-icon{color:var(--analytics-accent)!important;background:transparent!important;border:1px solid color-mix(in srgb,var(--analytics-accent) 35%,transparent);transition:transform .18s ease,color .18s ease}
.analytics-summary-card:hover .analytics-summary-icon{transform:scale(1.08) rotate(-4deg)}
@media(prefers-reduced-motion:reduce){.analytics-summary-card,.analytics-summary-card .analytics-summary-icon{transition:none}.analytics-summary-card:hover{transform:none}.analytics-summary-card:hover .analytics-summary-icon{transform:none}}
.analytics-release-link{display:inline-flex;align-items:center;gap:7px;color:#f8fafc;text-decoration:none}.analytics-release-link:hover{color:#ff925f}.analytics-release-link>span{display:inline-grid;place-items:center;width:26px;height:26px;border-radius:7px;background:#2a2630;color:#fb8555;font-size:15px}.analytics-release-link>i{margin-left:2px;color:#79b7f7;font-size:16px;opacity:0;transform:translateX(-4px);transition:opacity .16s,transform .16s}.analytics-performance-table tbody tr:hover .analytics-release-link>i{opacity:1;transform:translateX(0)}.analytics-release-table{min-width:920px}.analytics-release-table th:nth-child(5),.analytics-release-table td:nth-child(5),.analytics-release-table th:nth-child(6),.analytics-release-table td:nth-child(6){text-align:right}.analytics-action-heading,.analytics-action-cell{text-align:center!important}.analytics-view-button{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:34px;padding:0 12px;border:1px solid #f97316;border-radius:8px;background:transparent;color:#fb923c;text-decoration:none;font-size:12px;font-weight:800;transition:transform .16s ease,background-color .16s ease,color .16s ease}.analytics-view-button .material-icons-outlined{font-size:17px}.analytics-view-button:hover,.analytics-view-button:focus-visible{background:#f97316;color:#fff;transform:translateY(-2px)}.analytics-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 8px 2px;color:#8fa1b8;font-size:12px}.analytics-pagination-links{display:flex;align-items:center;gap:5px}.analytics-pagination-links a,.analytics-pagination-links span{min-width:31px;height:31px;padding:0 9px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #31425a;border-radius:7px;color:#b8c8da;text-decoration:none;font-weight:700}.analytics-pagination-links a:hover{border-color:#ff7131;color:#ff925f}.analytics-pagination-links .is-current{background:#ff6230;border-color:#ff6230;color:#fff}.analytics-pagination-links .is-disabled{color:#64748b;cursor:not-allowed}.analytics-pagination-links .is-ellipsis{border-color:transparent;min-width:12px;padding:0}@media(prefers-reduced-motion:reduce){.analytics-view-button{transition:none}.analytics-view-button:hover,.analytics-view-button:focus-visible{transform:none}}@media(max-width:700px){.analytics-release-link>i{display:none}.analytics-view-button>span:last-child{display:none}.analytics-view-button{width:34px;padding:0}.analytics-pagination{align-items:flex-start;flex-direction:column}.analytics-pagination-links{flex-wrap:wrap}}
</style>
@endpush

@push('script')
<script src="https://cdn.jsdelivr.net/npm/echarts@6.0.0/dist/echarts.min.js"></script>
<script type="application/json" id="analytics-months">@json($monthsFormatted)</script>
<script type="application/json" id="analytics-streams">@json($streamSeries)</script>
<script type="application/json" id="analytics-revenue">@json($revenueSeries)</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const node = document.getElementById('analytics-bars');
    if (!node || typeof echarts === 'undefined') return;
    const chart = echarts.init(node);
    const months = JSON.parse(document.getElementById('analytics-months').textContent);
    const streams = JSON.parse(document.getElementById('analytics-streams').textContent);
    const revenue = JSON.parse(document.getElementById('analytics-revenue').textContent);
    const rupiah = value => 'Rp' + Number(value).toLocaleString('id-ID', {maximumFractionDigits: 0});
    chart.setOption({grid:{left:44,right:66,top:18,bottom:40},tooltip:{trigger:'axis',backgroundColor:'#182536',borderColor:'#3b4f6a',textStyle:{color:'#eef4fa'},formatter(items){return '<b>'+items[0].axisValue+'</b><br>'+items.map(item=>item.marker+' '+item.seriesName+': <b>'+(item.seriesName === 'Penghasilan' ? rupiah(item.value) : Number(item.value).toLocaleString('id-ID'))+'</b>').join('<br>')}},xAxis:{type:'category',data:months,axisLine:{lineStyle:{color:'#3a4b63'}},axisLabel:{color:'#9fb1c7',fontSize:10}},yAxis:[{type:'value',splitLine:{lineStyle:{color:'#263850'}},axisLabel:{color:'#9fb1c7',fontSize:10,formatter:value=>Number(value).toLocaleString('id-ID',{notation:'compact'})}},{type:'value',splitLine:{show:false},axisLabel:{color:'#9fb1c7',fontSize:10,formatter:value=>rupiah(value)}}],series:[{name:'Stream',type:'bar',data:streams,barMaxWidth:32,itemStyle:{color:'#29c687',borderRadius:[5,5,0,0]}},{name:'Penghasilan',type:'bar',yAxisIndex:1,data:revenue,barMaxWidth:32,itemStyle:{color:'#f05252',borderRadius:[5,5,0,0]}}]});
    window.addEventListener('resize', () => chart.resize());
});
</script>
@endpush
