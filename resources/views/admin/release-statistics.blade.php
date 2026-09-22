@extends('layouts.app')

@php
    $backRoute = $backRoute ?? route('admin.home');
    $backLabel = $backLabel ?? 'Admin Home';
    $releaseDetailsRoute = $releaseDetailsRoute ?? route('admin.releases.show', $release->id);
@endphp

@section('title', 'Release Statistics')

@section('header')
    @php
        $artists = is_string($release->primaryartists) ? json_decode($release->primaryartists, true) : $release->primaryartists;
        $artist = data_get($artists, '0.name') ?? (is_array($artists) ? ($artists[0] ?? '-') : '-');
        $releaseDate = $release->releaseon ? \Carbon\Carbon::parse($release->releaseon)->translatedFormat('d M Y') : 'Belum dijadwalkan';
    @endphp
    <section class="release-stat-hero">
        <a class="release-stat-back" href="{{ $backRoute }}"><i class="material-icons-outlined">arrow_back</i> {{ $backLabel }}</a>
        <div class="release-stat-hero-content">
            <img src="{{ $release->thumbnail_url ?: URL::asset('build/images/album-placeholder.webp') }}" alt="Cover {{ $release->title }}">
            <div>
                <span class="release-stat-eyebrow"><i class="material-icons-outlined">insights</i> RELEASE STATISTICS</span>
                <h1>{{ $release->title }}</h1>
                <p>{{ $artist }} <span>•</span> {{ ucfirst($release->type) }} <span>•</span> {{ $release->tracks->count() }} track <span>•</span> {{ $releaseDate }}</p>
            </div>
            <a class="release-stat-view-release" href="{{ $releaseDetailsRoute }}">View release <i class="material-icons-outlined">open_in_new</i></a>
        </div>
    </section>
@endsection

@section('content')
    <main class="release-statistics-page">
        <section class="release-stat-scope" aria-label="Statistics scope">
            <i class="material-icons-outlined">filter_alt</i>
            <span>Menampilkan data laporan hanya untuk <strong>{{ $release->title }}</strong>, bukan total semua rilis.</span>
            @if($latestReportDate)<small>Data terakhir: {{ \Carbon\Carbon::parse($latestReportDate)->translatedFormat('F Y') }}</small>@endif
        </section>

        <form class="release-filter-panel" method="GET" action="{{ url()->current() }}" aria-label="Filter statistik rilis">
            <input type="hidden" name="period_group" value="{{ $selectedPeriodGroup }}">
            <div class="release-filter-heading"><i class="material-icons-outlined">tune</i><span>Filter statistik</span></div>
            <label for="release-platform-filter">Platform
                <select id="release-platform-filter" name="platform">
                    <option value="">Semua platform</option>
                    @foreach($availablePlatforms as $platform)
                        <option value="{{ $platform }}" @selected($selectedPlatform === $platform)>{{ $platform }}</option>
                    @endforeach
                </select>
            </label>
            <label for="release-month-filter">Bulan laporan
                <input id="release-month-filter" type="month" name="month" value="{{ $selectedMonth }}" aria-label="Pilih bulan laporan">
            </label>
            <label for="release-quarter-filter">Kuartal
                <select id="release-quarter-filter" name="quarter">
                    <option value="">Semua kuartal</option>
                    @foreach([1, 2, 3, 4] as $quarter)
                        <option value="{{ $quarter }}" @selected($selectedQuarter === $quarter)>Q{{ $quarter }}</option>
                    @endforeach
                </select>
            </label>
            <div class="release-filter-actions"><a href="{{ url()->current() }}" class="reset-filter">Reset</a><button type="submit">Terapkan <i class="material-icons-outlined">arrow_forward</i></button></div>
        </form>

        @php
            $dominantPlatform = $platformPerformance->first();
            $peakPlatformMonth = $topPlatformByMonth->sortByDesc('streams')->first();
            $latestPlatformMonth = $topPlatformByMonth->last();
            $previousPlatformMonth = $topPlatformByMonth->count() > 1 ? $topPlatformByMonth->slice(-2, 1)->first() : null;
            $monthlyChange = $previousPlatformMonth && $previousPlatformMonth->streams > 0
                ? (($latestPlatformMonth->streams - $previousPlatformMonth->streams) / $previousPlatformMonth->streams) * 100
                : null;
        @endphp
        <section class="release-insight-panel" aria-labelledby="release-insight-title">
            <header><div><span class="release-stat-eyebrow"><i class="material-icons-outlined">auto_awesome</i> RELEASE INSIGHT</span><h2 id="release-insight-title">Apa yang perlu diperhatikan</h2></div><span class="release-insight-period">Berdasarkan laporan final rilis ini</span></header>
            @if($dominantPlatform)
                <div class="release-insight-grid">
                    <article><i class="material-icons-outlined insight-icon platform">public</i><div><small>Platform dominan</small><strong>{{ $dominantPlatform->platform ?: 'Tidak diketahui' }}</strong><p>{{ number_format(($dominantPlatform->streams / max($totalStreams, 1)) * 100, 1, ',', '.') }}% dari seluruh stream rilis ini.</p></div></article>
                    <article><i class="material-icons-outlined insight-icon peak">trending_up</i><div><small>Bulan performa tertinggi</small><strong>{{ \Carbon\Carbon::parse($peakPlatformMonth->sales_month)->locale('id')->isoFormat('MMMM YYYY') }}</strong><p>{{ number_format($peakPlatformMonth->streams, 0, ',', '.') }} stream melalui {{ $peakPlatformMonth->platform ?: 'platform utama' }}.</p></div></article>
                    <article><i class="material-icons-outlined insight-icon latest">calendar_month</i><div><small>Perubahan bulan terakhir</small><strong>@if($monthlyChange !== null){{ $monthlyChange >= 0 ? '+' : '' }}{{ number_format($monthlyChange, 1, ',', '.') }}%@else Belum ada pembanding @endif</strong><p>{{ $latestPlatformMonth ? \Carbon\Carbon::parse($latestPlatformMonth->sales_month)->locale('id')->isoFormat('MMMM YYYY') : '—' }} · {{ $latestPlatformMonth->platform ?: 'Tidak diketahui' }}.</p></div></article>
                </div>
            @else
                <div class="release-insight-empty"><i class="material-icons-outlined">lightbulb</i> Insight akan muncul setelah laporan final untuk rilis ini tersedia.</div>
            @endif
        </section>

        <section class="release-stat-kpis" aria-label="Release performance summary">
            <article><span class="release-stat-icon streams"><i class="material-icons-outlined">headphones</i></span><div><small>Total streams</small><strong>{{ number_format($totalStreams, 0, ',', '.') }}</strong><em>Semua track pada rilis ini</em></div></article>
            <article><span class="release-stat-icon revenue"><i class="material-icons-outlined">account_balance_wallet</i></span><div><small>Estimasi penghasilan</small><strong>Rp{{ number_format($totalEarnings, 0, ',', '.') }}</strong><em>Bagian klien dari laporan final</em></div></article>
            <article><span class="release-stat-icon tracks"><i class="material-icons-outlined">queue_music</i></span><div><small>Track published</small><strong>{{ $release->tracks->count() }}</strong><em>Track dalam rilis terpilih</em></div></article>
        </section>

        <section class="release-stat-main-grid">
            <article class="release-stat-panel release-platform-panel">
                <header>
                    <div><h2>3 platform teratas per periode</h2><p>Tiga platform dengan stream tertinggi dari 12 bulan laporan terakhir.</p></div>
                    <form class="release-chart-period-form" method="GET" action="{{ url()->current() }}" aria-label="Kelompokkan periode grafik">
                        @if($selectedPlatform)<input type="hidden" name="platform" value="{{ $selectedPlatform }}">@endif
                        @if($selectedMonth)<input type="hidden" name="month" value="{{ $selectedMonth }}">@endif
                        @if($selectedQuarter)<input type="hidden" name="quarter" value="{{ $selectedQuarter }}">@endif
                        <i class="material-icons-outlined">calendar_month</i>
                        <select name="period_group" onchange="this.form.submit()" aria-label="Rentang periode">
                            <option value="monthly" @selected($selectedPeriodGroup === 'monthly')>Per bulan</option>
                            <option value="quarterly" @selected($selectedPeriodGroup === 'quarterly')>Per 3 bulan</option>
                            <option value="semiannual" @selected($selectedPeriodGroup === 'semiannual')>Per 6 bulan</option>
                        </select>
                    </form>
                </header>
                @php
                    $monthlyPlatformGroups = $topPlatformsByMonth->values();
                    $platformNames = collect([0, 1, 2])->map(function ($rank) use ($monthlyPlatformGroups) {
                        return $monthlyPlatformGroups->map(function ($platforms) use ($rank) {
                            return data_get($platforms->get($rank), 'platform') ?: 'Platform tidak tersedia';
                        })->values();
                    })->values();
                @endphp
                @if($monthlyPlatformGroups->isNotEmpty())
                    <div id="release-platform-month-chart" role="img" aria-label="Bar chart of the top platform each month for {{ $release->title }}"></div>
                @else
                    <div class="release-stat-empty compact"><i class="material-icons-outlined">public_off</i><strong>Belum ada data platform</strong><span>Rincian akan tersedia setelah laporan final diimpor.</span></div>
                @endif
            </article>
        </section>

        <section class="release-stat-panel release-platform-table-panel">
            <header><div><h2>Detail platform</h2><p>Rincian performa platform khusus untuk {{ $release->title }}.</p></div><span><i class="material-icons-outlined">fact_check</i> Finalized reports</span></header>
            @if($platformPerformance->isNotEmpty())
                <div class="release-platform-table-wrap">
                    <table>
                        <thead><tr><th>Platform</th><th>Streams</th><th>Estimasi penghasilan</th><th>Kontribusi</th><th>Laporan terakhir</th></tr></thead>
                        <tbody>
                            @foreach($platformPerformance as $platform)
                                @php
                                    $share = $totalStreams > 0 ? ($platform->streams / $totalStreams) * 100 : 0;
                                @endphp
                                <tr>
                                    <td><span class="platform-name"><i class="material-icons-outlined">music_note</i>{{ $platform->platform ?: 'Unknown platform' }}</span></td>
                                    <td>{{ number_format($platform->streams, 0, ',', '.') }}</td>
                                    <td>Rp{{ number_format($platform->earnings, 0, ',', '.') }}</td>
                                    <td><span class="platform-share"><i style="width: {{ min($share, 100) }}%"></i></span><b>{{ number_format($share, 1, ',', '.') }}%</b></td>
                                    <td>{{ $platform->latest_report_date ? \Carbon\Carbon::parse($platform->latest_report_date)->translatedFormat('M Y') : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="release-stat-empty"><i class="material-icons-outlined">table_rows</i><strong>Tidak ada laporan platform untuk rilis ini</strong><span>Tambahkan dan finalisasi laporan untuk menampilkan detail platform.</span></div>
            @endif
        </section>
    </main>
@endsection

@push('css')
<style>
.sf-app .main-wrapper .main-content{max-width:1440px!important;padding:25px 32px 46px!important;background:#081225!important}.release-stat-hero{margin:0 0 19px;padding:0 4px}.release-stat-back{display:inline-flex;align-items:center;gap:5px;margin-bottom:13px;color:#89b9ee;font-size:.76rem;font-weight:700;text-decoration:none}.release-stat-back i{font-size:17px}.release-stat-hero-content{display:flex;align-items:center;gap:17px;padding:22px 24px;border:1px solid #294f77;border-radius:16px;background:linear-gradient(112deg,#102843,#12233b 58%,#3e2830);box-shadow:0 17px 34px rgba(0,0,0,.16)}.release-stat-hero-content>img{width:78px;height:78px;border-radius:12px;object-fit:cover;background:#203b58;box-shadow:0 8px 16px rgba(0,0,0,.22)}.release-stat-eyebrow{display:flex;align-items:center;gap:5px;color:#ff9971;font-size:.67rem;font-weight:800;letter-spacing:.14em}.release-stat-eyebrow i{font-size:16px}.release-stat-hero h1{margin:5px 0;color:#f7fbff;font-size:1.85rem;font-weight:800;letter-spacing:-.03em}.release-stat-hero p{margin:0;color:#a7c4e2;font-size:.8rem}.release-stat-hero p span{padding:0 4px;color:#647f9e}.release-stat-view-release{display:inline-flex;align-items:center;gap:5px;margin-left:auto;padding:9px 12px;border:1px solid #507aa5;border-radius:8px;color:#b9d8f6;font-size:.74rem;font-weight:800;text-decoration:none}.release-stat-view-release i{font-size:16px}.release-statistics-page{display:grid;gap:16px}.release-stat-scope{display:flex;align-items:center;gap:8px;padding:11px 14px;border:1px solid rgba(64,171,207,.45);border-radius:10px;background:rgba(15,94,121,.22);color:#a9d8ea;font-size:.76rem}.release-stat-scope>i{color:#57cbef;font-size:19px}.release-stat-scope strong{color:#e3f6ff}.release-stat-scope small{margin-left:auto;color:#80b7cf;white-space:nowrap}.release-stat-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.release-stat-kpis article{display:flex;align-items:center;gap:13px;min-height:103px;padding:17px;border:1px solid #294c71;border-radius:13px;background:linear-gradient(140deg,#0e2138,#0b1a2d)}.release-stat-icon{display:grid;place-items:center;width:46px;height:46px;border-radius:12px}.release-stat-icon i{font-size:25px}.release-stat-icon.streams{background:rgba(72,151,255,.17);color:#77b4ff}.release-stat-icon.revenue{background:rgba(59,214,151,.15);color:#5be3aa}.release-stat-icon.tracks{background:rgba(255,139,94,.16);color:#ff9c73}.release-stat-kpis small{display:block;color:#91b0d0;font-size:.72rem}.release-stat-kpis strong{display:block;margin:2px 0;color:#f4f9ff;font-size:1.48rem;line-height:1.1}.release-stat-kpis em{color:#779abb;font-size:.68rem;font-style:normal}.release-stat-main-grid{display:grid;grid-template-columns:1fr;gap:16px}.release-stat-panel{overflow:hidden;border:1px solid #294c71;border-radius:14px;background:linear-gradient(145deg,#0e2036,#0a192b);box-shadow:0 14px 28px rgba(0,0,0,.12)}.release-stat-panel header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:18px 19px 14px;border-bottom:1px solid rgba(75,116,157,.35)}.release-stat-panel h2{margin:0;color:#f2f8ff;font-size:1.03rem;font-weight:800}.release-stat-panel p{margin:4px 0 0;color:#8faecc;font-size:.74rem}.release-stat-panel header>span{display:inline-flex;align-items:center;gap:5px;padding:6px 8px;border:1px solid #385c84;border-radius:8px;color:#9fc5ea;font-size:.66rem;font-weight:700;white-space:nowrap}.release-stat-panel header>span i{font-size:15px}.release-platform-panel{min-height:358px}.release-platform-table-wrap{overflow-x:auto}.release-platform-table-wrap table{width:100%;border-collapse:collapse;white-space:nowrap}.release-platform-table-wrap th{padding:12px 19px 9px;color:#81a2c3;font-size:.64rem;font-weight:800;letter-spacing:.05em;text-align:left;text-transform:uppercase}.release-platform-table-wrap td{padding:14px 19px;border-top:1px solid rgba(57,90,124,.43);color:#b8cde1;font-size:.75rem}.platform-name{display:flex;align-items:center;gap:8px;color:#eef6ff;font-weight:700}.platform-name i{display:grid;place-items:center;width:25px;height:25px;border-radius:7px;background:rgba(82,153,244,.15);color:#78b4ff;font-size:15px}.platform-share{width:75px;vertical-align:middle}.release-platform-table-wrap td b{margin-left:7px;color:#9fc8ef;font-size:.68rem}.release-stat-empty{display:grid;place-items:center;gap:7px;min-height:244px;padding:24px;text-align:center;color:#8faed0}.release-stat-empty.compact{min-height:240px}.release-stat-empty i{display:grid;place-items:center;width:46px;height:46px;border-radius:12px;background:#183352;color:#7eb3ff;font-size:24px}.release-stat-empty strong{color:#dceafd;font-size:.86rem}.release-stat-empty span{max-width:285px;font-size:.73rem;line-height:1.45}@media(max-width:900px){.release-stat-kpis{grid-template-columns:1fr}.release-stat-panel{min-height:auto}.release-stat-scope{align-items:flex-start}.release-stat-scope small{margin:0}.release-stat-scope{flex-wrap:wrap}}@media(max-width:620px){.sf-app .main-wrapper .main-content{padding:18px!important}.release-stat-hero-content{align-items:flex-start;flex-wrap:wrap;padding:18px}.release-stat-hero-content>img{width:58px;height:58px}.release-stat-hero h1{font-size:1.45rem}.release-stat-hero p{line-height:1.55}.release-stat-view-release{margin:0}.release-stat-kpis article{min-height:88px}.release-platform-table-wrap th,.release-platform-table-wrap td{padding-left:13px;padding-right:13px}.release-stat-scope small{width:100%}}
</style>
@endpush

@push('css')
<style>
#release-platform-month-chart{height:300px;padding:18px 12px 14px}.release-chart-period-form{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border:1px solid #385c84;border-radius:8px}.release-chart-period-form i{color:#9fc5ea;font-size:15px}.release-chart-period-form select{border:0;outline:0;background:transparent;color:#9fc5ea;font-size:.66rem;font-weight:700;cursor:pointer}.release-chart-period-form select option{background:#10243b;color:#eaf5ff}
.release-insight-panel{overflow:hidden;border:1px solid #365d86;border-radius:14px;background:linear-gradient(110deg,rgba(15,40,66,.96),rgba(25,34,57,.94))}.release-insight-panel header{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:15px 18px;border-bottom:1px solid rgba(86,128,170,.35)}.release-insight-panel h2{margin:3px 0 0;color:#f3f8ff;font-size:1rem;font-weight:800}.release-insight-period{color:#88add2;font-size:.68rem}.release-insight-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0}.release-insight-grid article{display:flex;gap:10px;min-height:98px;padding:16px 18px;border-right:1px solid rgba(86,128,170,.3)}.release-insight-grid article:last-child{border-right:0}.insight-icon{display:grid;place-items:center;flex:0 0 36px;width:36px;height:36px;border-radius:10px;font-size:20px}.insight-icon.platform{background:rgba(91,162,255,.15);color:#7bb6ff}.insight-icon.peak{background:rgba(73,219,155,.14);color:#66e4ac}.insight-icon.latest{background:rgba(255,152,91,.15);color:#ffa377}.release-insight-grid small{display:block;color:#91b4d6;font-size:.66rem}.release-insight-grid strong{display:block;margin-top:2px;color:#f4f9ff;font-size:.86rem}.release-insight-grid p{margin:4px 0 0;color:#91afd0;font-size:.68rem;line-height:1.42}.release-insight-empty{display:flex;align-items:center;gap:8px;padding:17px 18px;color:#94b4d3;font-size:.74rem}.release-insight-empty i{color:#7cb9f5;font-size:19px}
.release-filter-panel{display:grid;grid-template-columns:auto minmax(170px,1fr) minmax(145px,.7fr) minmax(135px,.6fr) auto;align-items:end;gap:11px;padding:14px 16px;border:1px solid #31577e;border-radius:13px;background:linear-gradient(110deg,#0e243d,#0c1e33)}.release-filter-heading{display:flex;align-items:center;gap:6px;min-height:37px;color:#c5def7;font-size:.76rem;font-weight:800;white-space:nowrap}.release-filter-heading i{color:#70b5ff;font-size:18px}.release-filter-panel label{display:grid;gap:5px;color:#91b5d7;font-size:.65rem;font-weight:700}.release-filter-panel select,.release-filter-panel input{width:100%;height:36px;padding:0 10px;border:1px solid #395f87;border-radius:8px;background:#091a2d;color:#eaf5ff;font-size:.74rem;outline:none}.release-filter-panel select:focus,.release-filter-panel input:focus{border-color:#6baff6;box-shadow:0 0 0 3px rgba(92,167,255,.13)}.release-filter-actions{display:flex;align-items:center;gap:9px;min-height:36px}.release-filter-actions a{color:#94b9dc;font-size:.7rem;font-weight:700;text-decoration:none}.release-filter-actions button{display:inline-flex;align-items:center;gap:4px;height:36px;padding:0 11px;border:0;border-radius:8px;background:#ff7146;color:#fff;font-size:.7rem;font-weight:800}.release-filter-actions button i{font-size:15px}
@media(max-width:900px){.release-filter-panel{grid-template-columns:repeat(2,minmax(0,1fr))}.release-filter-heading,.release-filter-actions{grid-column:1/-1}}@media(max-width:800px){.release-insight-grid{grid-template-columns:1fr}.release-insight-grid article{min-height:auto;border-right:0;border-bottom:1px solid rgba(86,128,170,.3)}.release-insight-grid article:last-child{border-bottom:0}}@media(max-width:620px){.release-filter-panel{grid-template-columns:1fr}.release-filter-heading,.release-filter-actions{grid-column:auto}.release-insight-panel header{align-items:flex-start;flex-direction:column;gap:5px}}
</style>
@endpush

@push('script')
<script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
<script>
    const platformMonthChart = document.querySelector('#release-platform-month-chart');
    if (platformMonthChart) {
        const platformMonths = @json($platformPeriodLabels);
        const platformNames = @json($platformNames);
        const tooltipColors = ['#6faeff', '#5be3aa', '#ff9c73'];
        const escapeTooltipText = value => String(value).replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]);
        new ApexCharts(platformMonthChart, {
            series: [
                { name: 'Peringkat 1', data: @json($monthlyPlatformGroups->map(fn ($platforms) => (int) data_get($platforms->get(0), 'streams', 0))->values()) },
                { name: 'Peringkat 2', data: @json($monthlyPlatformGroups->map(fn ($platforms) => (int) data_get($platforms->get(1), 'streams', 0))->values()) },
                { name: 'Peringkat 3', data: @json($monthlyPlatformGroups->map(fn ($platforms) => (int) data_get($platforms->get(2), 'streams', 0))->values()) }
            ],
            chart: { type: 'bar', height: 224, toolbar: { show: false }, background: 'transparent', foreColor: '#8faecc' },
            colors: ['#6faeff', '#5be3aa', '#ff9c73'],
            plotOptions: { bar: { columnWidth: '66%', borderRadius: 3 } },
            grid: { borderColor: 'rgba(89,128,166,.2)', strokeDashArray: 4, padding: { left: 2, right: 2 } },
            dataLabels: { enabled: false },
            xaxis: { categories: platformMonths, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '9px' } } },
            yaxis: { labels: { formatter: value => Number(value).toLocaleString('id-ID'), style: { fontSize: '9px' } } },
            tooltip: {
                custom: ({ series, seriesIndex, dataPointIndex }) => {
                    const platform = platformNames[seriesIndex]?.[dataPointIndex] || 'Platform tidak tersedia';
                    const streams = Number(series[seriesIndex]?.[dataPointIndex] || 0).toLocaleString('id-ID');
                    const month = platformMonths[dataPointIndex] || '';
                    return `<div style="min-width:160px;padding:10px 12px;background:#101d2f;color:#eaf4ff;border:1px solid #3a6089;border-radius:8px;font:600 12px system-ui"><span style="display:block;margin-bottom:5px;color:#8fafcf;font-size:10px">${escapeTooltipText(month)}</span><strong style="display:flex;align-items:center;gap:6px"><i style="width:8px;height:8px;border-radius:50%;background:${tooltipColors[seriesIndex]};display:inline-block"></i>${escapeTooltipText(platform)}</strong><span style="display:block;margin-top:5px;color:#9fc5ea">${streams} stream</span></div>`;
                }
            },
            legend: { show: false }
        }).render();
    }
</script>
@endpush
