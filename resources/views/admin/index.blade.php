@extends('layouts.app')

@section('title', 'Admin Home')

@section('header')
    <section class="admin-studio-hero">
        <div class="admin-studio-ornament" aria-hidden="true"><i class="material-icons-outlined">graphic_eq</i><i class="material-icons-outlined">album</i></div>
        <div>
            <span class="admin-eyebrow"><i class="material-icons-outlined">equalizer</i> RELEASE OPERATIONS</span>
            <h1>Admin Home</h1>
            <p>Kelola rilis, pantau performa, dan pastikan musik terbaik sampai ke seluruh dunia.</p>
        </div>
        <span class="admin-studio-status"><i class="material-icons-outlined">sensors</i> Studio control</span>
    </section>
@endsection

@section('content')
    <div class="admin-dashboard">
        <section class="admin-metric-grid" aria-label="Ringkasan rilis">
            <a href="{{ route('admin.releases.index') }}" class="admin-metric-card metric-pending"><span class="admin-metric-icon"><i class="material-icons-outlined">pending_actions</i></span><span><small>Pending Release</small><strong>{{ $pendingCount }}</strong></span><i class="material-icons-outlined admin-card-arrow">chevron_right</i></a>
            <a href="{{ route('admin.releases.index') }}" class="admin-metric-card metric-review"><span class="admin-metric-icon"><i class="material-icons-outlined">manage_search</i></span><span><small>Under Review</small><strong>{{ $reviewCount }}</strong></span><i class="material-icons-outlined admin-card-arrow">chevron_right</i></a>
            <a href="{{ route('admin.releases.index') }}" class="admin-metric-card metric-uploading"><span class="admin-metric-icon"><i class="material-icons-outlined">cloud_upload</i></span><span><small>Uploading</small><strong>{{ $uploadingCount }}</strong></span><i class="material-icons-outlined admin-card-arrow">chevron_right</i></a>
            <a href="{{ route('admin.releases.index') }}" class="admin-metric-card metric-tracks"><span class="admin-metric-icon"><i class="material-icons-outlined">queue_music</i></span><span><small>Total Track</small><strong>{{ number_format($totalTracks, 0, ',', '.') }}</strong></span><i class="material-icons-outlined admin-card-arrow">chevron_right</i></a>
        </section>

        <section class="admin-main-grid">
            <article class="admin-panel admin-queue-panel">
                <header class="admin-panel-header"><div><h2>Release Queue</h2><p>Releases that require team action.</p></div><a href="{{ route('admin.releases.index') }}">View all <i class="material-icons-outlined">arrow_forward</i></a></header>
                @if($canViewAllReleases && $pendingReleases->isNotEmpty())
                    <div class="admin-queue-head"><span>Cover</span><span>Album</span><span>Artist</span><span>Release Status</span><span>Release Date</span></div>
                    <div class="admin-queue-list">
                        @foreach($pendingReleases as $release)
                            @php
                                $artists = is_string($release->primaryartists) ? json_decode($release->primaryartists, true) : $release->primaryartists;
                                $artist = data_get($artists, '0.name') ?? (is_array($artists) ? ($artists[0] ?? '-') : '-');
                            @endphp
                            <a class="admin-queue-item" href="{{ route('admin.releases.show', $release->id) }}">
                                <img src="{{ $release->thumbnail_url ?: URL::asset('build/images/album-placeholder.webp') }}" alt="Cover {{ $release->title }}">
                                <span class="admin-release-name"><strong>{{ $release->title }}</strong><small>{{ ucfirst($release->type) }}</small></span>
                                <span class="admin-artist-name">{{ $artist }}</span>
                                @include('components.badge-release', ['status' => $release->status])
                                <span class="admin-due-date">{{ $release->releaseon ? \Carbon\Carbon::parse($release->releaseon)->format('d M Y') : 'Not scheduled' }}</span>
                                <i class="material-icons-outlined admin-row-arrow">chevron_right</i>
                            </a>
                        @endforeach
                    </div>
                @elseif(!$canViewAllReleases)
                    <div class="admin-empty-state"><i class="material-icons-outlined">lock</i><strong>Akses antrean rilis terbatas</strong><span>Anda dapat melihat rilis yang sudah dipublikasikan melalui daftar rilis.</span><a href="{{ route('admin.releases.index') }}" class="btn">Buka daftar rilis <i class="material-icons-outlined">arrow_forward</i></a></div>
                @else
                    <div class="admin-empty-state"><i class="material-icons-outlined">task_alt</i><strong>Tidak ada antrean rilis</strong><span>Semua rilis sudah ditangani. Kerja bagus!</span></div>
                @endif
            </article>

            <article class="admin-panel admin-chart-panel">
                <header class="admin-panel-header"><div><h2>Release Trend</h2><p>Activity over the last seven days.</p></div><span class="admin-period"><i class="material-icons-outlined">calendar_today</i> 7 days</span></header>
                <div class="admin-chart-legend"><span><i></i> Releases submitted</span><span><i></i> Published</span></div><div id="release-graph"></div>
            </article>
        </section>

        <section class="admin-panel admin-catalog-chart-panel" aria-labelledby="published-catalog-heading">
            <header class="admin-panel-header">
                <div>
                    <h2 id="published-catalog-heading">Published catalogue</h2>
                    <p>Perbandingan track, rilis, dan album yang dipublikasikan {{ strtolower($catalogPeriodMeta['label']) }}.</p>
                </div>
                <form method="GET" class="admin-catalog-period-form" aria-label="Pilih periode published catalogue">
                    <label for="catalog-period" class="form-label">Periode:</label>
                    <span class="material-icons-outlined" aria-hidden="true">calendar_month</span>
                    <select id="catalog-period" name="catalog_period" onchange="this.form.submit()">
                        <option value="monthly" @selected($catalogPeriod === 'monthly')>Per bulan</option>
                        <option value="quarterly" @selected($catalogPeriod === 'quarterly')>Per 3 bulan</option>
                        <option value="semiannual" @selected($catalogPeriod === 'semiannual')>Per 6 bulan</option>
                        <option value="yearly" @selected($catalogPeriod === 'yearly')>Per tahun</option>
                    </select>
                    <span class="catalog-period-display">{{ $catalogPeriodMeta['label'] ?? 'Periode tidak valid' }}</span>
                </form>
            </header>
            <div class="admin-catalog-chart-summary" aria-label="Published catalogue values for selected period">
                <span><i class="catalog-track-dot"></i>Published tracks <strong>{{ number_format($catalogTotals['tracks'], 0, ',', '.') }}</strong></span>
                <span><i class="catalog-release-dot"></i>Published releases <strong>{{ number_format($catalogTotals['releases'], 0, ',', '.') }}</strong></span>
                <span><i class="catalog-album-dot"></i>Published albums <strong>{{ number_format($catalogTotals['albums'], 0, ',', '.') }}</strong></span>
            </div>
            <div id="published-catalog-graph" role="img" aria-label="Bar chart of published tracks, releases, and albums by selected period"></div>
        </section>

        <section class="admin-panel admin-release-statistics-panel" aria-labelledby="release-statistics-heading">
            <header class="admin-panel-header">
                <div>
                    <h2 id="release-statistics-heading">Release performance</h2>
                    <p>10 rilis terbaru. Pilih rilis untuk melihat statistik track dan platformnya.</p>
                </div>
                <a href="{{ route('admin.releases.index') }}">All releases <i class="material-icons-outlined">arrow_forward</i></a>
            </header>
            @if($statisticReleases->isNotEmpty())
                <div class="admin-release-stat-head"><span>Release</span><span>Artist</span><span>Format</span><span>Published</span><span>Statistics</span></div>
                <div class="admin-release-stat-list">
                    @foreach($statisticReleases as $release)
                        @php
                            $artists = is_string($release->primaryartists) ? json_decode($release->primaryartists, true) : $release->primaryartists;
                            $artist = data_get($artists, '0.name') ?? (is_array($artists) ? ($artists[0] ?? '-') : '-');
                        @endphp
                        <a class="admin-release-stat-row" href="{{ route('admin.releases.statistics', $release->id) }}">
                            <span class="admin-release-stat-title"><img src="{{ $release->thumbnail_url ?: URL::asset('build/images/album-placeholder.webp') }}" alt="Cover {{ $release->title }}"><strong>{{ $release->title }}</strong></span>
                            <span>{{ $artist }}</span>
                            <span class="admin-release-format">{{ ucfirst($release->type) }}</span>
                            <span>{{ $release->releaseon ? \Carbon\Carbon::parse($release->releaseon)->translatedFormat('d M Y') : '—' }}</span>
                            <span class="admin-stat-link">View statistics <i class="material-icons-outlined">insights</i></span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="admin-empty-state compact"><i class="material-icons-outlined">equalizer</i><strong>Belum ada rilis yang dipublikasikan</strong></div>
            @endif
        </section>

        <section class="admin-panel admin-activity-panel">
            <header class="admin-panel-header"><div><h2>Recent Activity</h2><p>Latest updates in the release workspace.</p></div><a class="admin-review-action" href="{{ route('admin.releases.index') }}">Review releases <i class="material-icons-outlined">arrow_forward</i></a></header>
            @if($canViewAllReleases && $recentActivity->isNotEmpty())
                <div class="admin-activity-list">
                    <div class="admin-activity-head"><span>Updated At</span><span>Album</span><span>Artist</span><span>Release Status</span><span>Activity</span><span>Action</span></div>
                    @foreach($recentActivity as $release)
                        @php
                            $artists = is_string($release->primaryartists) ? json_decode($release->primaryartists, true) : $release->primaryartists;
                            $artist = data_get($artists, '0.name') ?? (is_array($artists) ? ($artists[0] ?? '-') : '-');
                            $activityText = match($release->status) {
                                'released' => 'Release has been published.', 'revision' => 'Metadata revision is required.', 'rejected' => 'Release requires follow-up.', 'review' => 'Release is currently under review.', default => 'Release is waiting for team action.',
                            };
                        @endphp
                        <a class="admin-activity-row" href="{{ route('admin.releases.show', $release->id) }}">
                            <span class="admin-activity-time"><i class="admin-activity-dot admin-status-{{ $release->status }}"></i>{{ $release->updated_at->translatedFormat('d M Y · H:i') }}</span><strong>{{ $release->title }}</strong><span>{{ $artist }}</span>@include('components.badge-release', ['status' => $release->status])<span class="admin-activity-detail">{{ $activityText }}</span><i class="material-icons-outlined admin-row-arrow">chevron_right</i>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="admin-empty-state compact"><i class="material-icons-outlined">history</i><strong>Belum ada aktivitas untuk ditampilkan</strong></div>
            @endif
        </section>
    </div>
@endsection

@push('css')
    <style>
        .sf-app .main-wrapper .main-content{max-width:1440px!important;padding:28px 32px 44px!important;background:#081225!important}.admin-studio-hero{position:relative;display:flex;align-items:center;justify-content:space-between;min-height:132px;margin:0 0 22px;padding:26px 30px;overflow:hidden;border:1px solid #274b72;border-radius:18px;background:linear-gradient(118deg,#102541 0%,#142742 46%,#53291e 155%);box-shadow:0 18px 36px rgba(0,0,0,.18)}.admin-studio-hero:before{position:absolute;inset:0;background:repeating-linear-gradient(90deg,transparent 0 52px,rgba(127,176,230,.05) 52px 54px);content:""}.admin-studio-hero>div,.admin-studio-status{position:relative;z-index:1}.admin-eyebrow{display:flex;align-items:center;gap:6px;color:#ff916c;font-size:.68rem;font-weight:800;letter-spacing:.16em}.admin-eyebrow i{font-size:17px}.admin-studio-hero h1{margin:6px 0;color:#f8fbff;font-size:2rem;font-weight:800;letter-spacing:-.03em}.admin-studio-hero p{margin:0;color:#a9c7e6;font-size:.92rem}.admin-studio-status{display:inline-flex;align-items:center;gap:7px;padding:9px 13px;border:1px solid rgba(82,214,152,.32);border-radius:999px;background:rgba(23,153,101,.14);color:#84edbd;font-size:.78rem;font-weight:700}.admin-studio-status i{font-size:17px}.admin-studio-ornament{position:absolute;right:34px;top:-29px;display:flex;align-items:center;gap:21px;color:rgba(128,181,238,.12)}.admin-studio-ornament i:first-child{font-size:124px}.admin-studio-ornament i:last-child{font-size:88px}.admin-dashboard{display:grid;gap:18px}.admin-metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.admin-metric-card{position:relative;display:flex;align-items:center;gap:13px;min-height:94px;padding:15px 16px;overflow:hidden;border:1px solid #2d527b;border-radius:14px;background:transparent;color:#eaf4ff;text-decoration:none;transition:transform .2s,box-shadow .2s,border-color .2s}.admin-metric-card:before{display:none}.admin-metric-card:hover{transform:translateY(-3px);border-color:#6d9bc7;box-shadow:none;color:#fff}.admin-metric-icon{display:grid;place-items:center;flex:0 0 45px;width:45px;height:45px;border-radius:12px;background:#1b3556;color:#7eafff}.admin-metric-icon i{font-size:25px}.admin-metric-card span:not(.admin-metric-icon){display:grid;gap:3px}.admin-metric-card small{color:#a6c0de;font-size:.73rem}.admin-metric-card strong{color:#f8fbff;font-size:1.62rem;line-height:1;font-weight:800}.admin-card-arrow{margin-left:auto;color:#7ea4cc;font-size:21px}.metric-pending{border-color:#684a3c;background:transparent}.metric-pending .admin-metric-icon{background:rgba(255,131,72,.17);color:#ff9864}.metric-review .admin-metric-icon{background:rgba(74,147,255,.17);color:#72b1ff}.metric-live{border-color:#275e53;background:transparent}.metric-live .admin-metric-icon{background:rgba(59,213,151,.15);color:#51dea1}.metric-attention{border-color:#6d572f;background:transparent}.metric-attention .admin-metric-icon{background:rgba(255,189,64,.16);color:#ffca55}.admin-main-grid{display:grid;grid-template-columns:minmax(0,1.28fr) minmax(360px,.92fr);gap:18px}.admin-panel{overflow:hidden;border:1px solid #294b70;border-radius:15px;background:linear-gradient(145deg,#0e2036,#0b1a2d);box-shadow:0 15px 30px rgba(0,0,0,.13)}.admin-panel-header{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:19px 20px 15px;border-bottom:1px solid rgba(75,116,157,.34)}.admin-panel-header h2{margin:0;color:#f3f8ff;font-size:1.08rem;font-weight:800}.admin-panel-header p{margin:4px 0 0;color:#8eadcd;font-size:.76rem}.admin-panel-header>a{display:inline-flex;align-items:center;gap:4px;color:#78b3ff;font-size:.78rem;font-weight:700;text-decoration:none}.admin-panel-header>a i{font-size:17px}.admin-queue-head,.admin-queue-item{display:grid;grid-template-columns:58px minmax(120px,1.35fr) minmax(90px,.9fr) 92px 94px 18px;align-items:center;gap:10px}.admin-queue-head{padding:12px 20px 7px;color:#7f9fbe;font-size:.64rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase}.admin-queue-list{padding:0 12px 12px}.admin-queue-item{min-height:64px;padding:7px 8px;border-top:1px solid rgba(57,90,124,.43);color:#d9e7f6;text-decoration:none;transition:background .18s}.admin-queue-item:hover{border-radius:9px;background:rgba(73,121,170,.12);color:#fff}.admin-queue-item img{width:45px;height:45px;border-radius:7px;object-fit:cover;background:#1c304a}.admin-release-name{display:grid;gap:3px;min-width:0}.admin-release-name strong{overflow:hidden;color:#eff6ff;font-size:.79rem;text-overflow:ellipsis;white-space:nowrap}.admin-release-name small{color:#84a5c8;font-size:.68rem}.admin-artist-name,.admin-due-date{overflow:hidden;color:#b3c8df;font-size:.73rem;text-overflow:ellipsis;white-space:nowrap}.admin-status{display:inline-flex;justify-content:center;width:max-content;max-width:100%;padding:5px 8px;border-radius:999px;font-size:.65rem;font-weight:800;white-space:nowrap}.admin-status-pending{background:rgba(255,145,62,.16);color:#ffaf75}.admin-status-review{background:rgba(72,150,255,.17);color:#83bdff}.admin-status-released,.admin-status-completed{background:rgba(60,211,148,.16);color:#74edb4}.admin-status-revision{background:rgba(255,192,63,.16);color:#ffd067}.admin-status-rejected{background:rgba(255,94,105,.16);color:#ff9ba3}.admin-row-arrow{color:#6b91ba;font-size:18px}.admin-chart-panel{min-height:350px}.admin-period{display:inline-flex;align-items:center;gap:6px;padding:7px 9px;border:1px solid #365b84;border-radius:8px;color:#a8c8ed;font-size:.7rem}.admin-period i{font-size:15px}.admin-chart-legend{display:flex;gap:15px;padding:13px 20px 0;color:#a7c1dc;font-size:.72rem}.admin-chart-legend span{display:flex;align-items:center;gap:5px}.admin-chart-legend i{width:8px;height:8px;border-radius:50%;background:#67a8ff}.admin-chart-legend span+span i{background:#ff8c55}.admin-activity-panel .admin-panel-header{align-items:center}.admin-review-action{padding:9px 12px;border-radius:8px;background:#ff7043;color:#fff!important;box-shadow:0 6px 13px rgba(255,112,67,.2)}.admin-activity-list{padding:0 16px 10px}.admin-activity-row{display:grid;grid-template-columns:150px minmax(120px,.8fr) minmax(105px,.8fr) 100px minmax(190px,1.55fr) 18px;align-items:center;gap:12px;min-height:51px;border-top:1px solid rgba(57,90,124,.43);color:#b6cadf;font-size:.73rem;text-decoration:none;transition:background .18s}.admin-activity-row:hover{padding:0 8px;border-radius:8px;background:rgba(73,121,170,.12);color:#fff}.admin-activity-row strong{overflow:hidden;color:#eef6ff;font-size:.77rem;text-overflow:ellipsis;white-space:nowrap}.admin-activity-time{display:flex;align-items:center;gap:7px;color:#8caac8;font-size:.68rem}.admin-activity-dot{width:8px;height:8px;border-radius:50%;background:#7195bb}.admin-activity-dot.admin-status-released,.admin-activity-dot.admin-status-completed{background:#4bdb9a}.admin-activity-dot.admin-status-review{background:#6ba8ff}.admin-activity-dot.admin-status-revision{background:#ffd067}.admin-activity-dot.admin-status-rejected{background:#ff8690}.admin-activity-detail{overflow:hidden;color:#8eacca;text-overflow:ellipsis;white-space:nowrap}.admin-empty-state{display:grid;place-items:center;gap:7px;min-height:260px;padding:28px;text-align:center;color:#8faed0}.admin-empty-state i{display:grid;place-items:center;width:47px;height:47px;border-radius:13px;background:#183352;color:#7eb3ff;font-size:25px}.admin-empty-state strong{color:#dceafd;font-size:.9rem}.admin-empty-state span{max-width:280px;font-size:.76rem;line-height:1.45}.admin-empty-state .btn{display:inline-flex;align-items:center;gap:5px;margin-top:7px;border:1px solid #4e7eae;border-radius:8px;color:#afcff2;font-size:.75rem}.admin-empty-state .btn i{display:inline;width:auto;height:auto;background:transparent;font-size:16px}.admin-empty-state.compact{min-height:108px}@media(max-width:1200px){.admin-metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.admin-main-grid{grid-template-columns:1fr}.admin-chart-panel{min-height:300px}}@media(max-width:820px){.sf-app .main-wrapper .main-content{padding:18px!important}.admin-studio-hero{align-items:flex-start;flex-direction:column;padding:22px}.admin-studio-status{align-self:flex-start}.admin-studio-ornament{opacity:.5}.admin-queue-head{display:none}.admin-queue-item{grid-template-columns:54px minmax(0,1fr) 90px 18px}.admin-queue-item .admin-artist-name,.admin-queue-item .admin-due-date{display:none}.admin-activity-row{grid-template-columns:minmax(0,1fr) 90px 18px;gap:8px;padding:8px 0}.admin-activity-row .admin-activity-time,.admin-activity-row>span:nth-of-type(1):not(.admin-status),.admin-activity-detail{display:none}}@media(max-width:520px){.admin-metric-grid{grid-template-columns:1fr}.admin-studio-hero h1{font-size:1.7rem}.admin-studio-hero p{font-size:.8rem}.admin-panel-header{padding:16px}.admin-queue-item{grid-template-columns:48px minmax(0,1fr) 18px}.admin-queue-item .admin-status{display:none}.admin-activity-panel .admin-panel-header{align-items:flex-start;flex-direction:column}.admin-review-action{align-self:stretch;justify-content:center}}
        .admin-activity-head{display:grid;grid-template-columns:150px minmax(120px,.8fr) minmax(105px,.8fr) 100px minmax(190px,1.55fr) 18px;gap:12px;align-items:center;padding:12px 0 8px;color:#7f9fbe;font-size:.64rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase}.admin-activity-head span:last-child{text-align:center}.admin-catalog-chart-panel{min-height:390px}.admin-catalog-period-form{display:inline-flex;align-items:center;gap:6px;padding:3px 9px;border:1px solid #365b84;border-radius:8px;color:#a8c8ed;background:#0c1d31}.admin-catalog-period-form span{font-size:16px}.admin-catalog-period-form select{min-width:108px;border:0;outline:0;background:transparent;color:#d8eaff;font-size:.72rem;font-weight:700;cursor:pointer}.admin-catalog-period-form option{background:#10233a;color:#eaf4ff}.admin-catalog-chart-summary{display:flex;flex-wrap:wrap;gap:12px 22px;padding:15px 20px 0;color:#a7c1dc;font-size:.74rem}.admin-catalog-chart-summary span{display:flex;align-items:center;gap:6px}.admin-catalog-chart-summary i{width:9px;height:9px;border-radius:3px}.catalog-track-dot{background:#67a8ff}.catalog-release-dot{background:#ff8c55}.catalog-album-dot{background:#56d6a4}.admin-catalog-chart-summary strong{color:#eef6ff;font-size:.82rem}.admin-release-stat-head,.admin-release-stat-row{display:grid;grid-template-columns:minmax(220px,1.45fr) minmax(130px,.9fr) 90px 110px 130px;align-items:center;gap:16px}.admin-release-stat-head{padding:13px 20px 8px;color:#7f9fbe;font-size:.64rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase}.admin-release-stat-list{padding:0 12px 12px}.admin-release-stat-row{min-height:62px;padding:8px;border-top:1px solid rgba(57,90,124,.43);color:#afc4db;font-size:.75rem;text-decoration:none;transition:background .18s}.admin-release-stat-row:hover{border-radius:9px;background:rgba(73,121,170,.12);color:#fff}.admin-release-stat-title{display:flex;align-items:center;gap:10px;min-width:0}.admin-release-stat-title img{width:42px;height:42px;border-radius:7px;object-fit:cover;background:#1c304a}.admin-release-stat-title strong{overflow:hidden;color:#eff6ff;font-size:.8rem;text-overflow:ellipsis;white-space:nowrap}.admin-release-format{color:#8fb7df}.admin-stat-link{display:inline-flex;align-items:center;justify-content:flex-end;gap:4px;color:#76b3ff;font-weight:800}.admin-stat-link i{font-size:17px}@media(max-width:820px){.admin-activity-head,.admin-release-stat-head{display:none}.admin-release-stat-row{grid-template-columns:minmax(0,1fr) 125px;gap:10px}.admin-release-stat-row>span:nth-child(2),.admin-release-stat-row>span:nth-child(3),.admin-release-stat-row>span:nth-child(4){display:none}}@media(max-width:520px){.admin-catalog-chart-summary{display:grid;grid-template-columns:1fr;padding:13px 16px 0}.admin-catalog-period-form{width:100%;justify-content:center}.admin-release-stat-row{padding:8px 4px}.admin-stat-link{font-size:.68rem}}
    </style>
@endpush

@push('script')
    <script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
    <script>
        new ApexCharts(document.querySelector('#release-graph'), { series: [{ name: 'Releases submitted', data: @json($trendSubmitted) }, { name: 'Published', data: @json($trendPublished) }], chart: { height: 252, type: 'line', toolbar: { show: false }, background: 'transparent', foreColor: '#8eaac8' }, stroke: { curve: 'smooth', width: [3, 3] }, colors: ['#67a8ff', '#ff8c55'], grid: { borderColor: 'rgba(89,128,166,.22)', strokeDashArray: 4 }, markers: { size: 3, strokeWidth: 0, hover: { size: 5 } }, xaxis: { categories: @json($trendLabels), axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '10px' } } }, yaxis: { labels: { style: { fontSize: '10px' } } }, legend: { show: false }, tooltip: { theme: 'dark' }, dataLabels: { enabled: false } }).render();
        new ApexCharts(document.querySelector('#published-catalog-graph'), { series: [{ name: 'Published tracks', data: @json($catalogTracks) }, { name: 'Published releases', data: @json($catalogReleases) }, { name: 'Published albums', data: @json($catalogAlbums) }], chart: { height: 275, type: 'bar', toolbar: { show: false }, background: 'transparent', foreColor: '#8eaac8' }, colors: ['#67a8ff', '#ff8c55', '#56d6a4'], plotOptions: { bar: { horizontal: false, columnWidth: '56%', borderRadius: 4 } }, grid: { borderColor: 'rgba(89,128,166,.18)', strokeDashArray: 4, padding: { left: 8, right: 14 } }, xaxis: { categories: @json($catalogLabels), labels: { rotate: -35, trim: true, style: { fontSize: '10px' } }, axisBorder: { show: false }, axisTicks: { show: false } }, yaxis: { labels: { formatter: value => Number(value).toLocaleString('id-ID'), style: { fontSize: '10px' } } }, dataLabels: { enabled: false }, legend: { show: false }, tooltip: { theme: 'dark', shared: true, intersect: false, y: { formatter: value => Number(value).toLocaleString('id-ID') } } }).render();
    </script>
@endpush
