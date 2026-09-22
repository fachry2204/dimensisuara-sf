@extends('layouts.app')
@section('title')
    All Releases
@endsection
@section('header')
    <div class="release-index-hero">
        <div><p>Music Distribution</p><h1>All Releases</h1></div>
        <div class="release-index-toolbar">
            <form action="{{ route('admin.releases.export.releases') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn release-toolbar-button">
                    <i class="material-icons-outlined material-symbols-outlined me-1"
                        style="font-size: 16px; vertical-align: middle;">download</i>
                    Export Releases
                </button>
            </form>
            <form action="{{ route('admin.releases.export.tracks') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn release-toolbar-button">
                    <i class="material-icons-outlined material-symbols-outlined me-1"
                        style="font-size: 16px; vertical-align: middle;">download</i>
                    Export Tracks
                </button>
            </form>
            @can('create-releases')
                <a href="{{ route('admin.releases.create') }}" class="btn release-create-button"><i class="material-icons-outlined">add_circle</i> Create Release for User</a>
            @endcan
        </div>
    </div>
@endsection
@section('content')
    @php
        $sections = [
            'pending' => [
                'name' => 'Pending',
                'icon' => 'hourglass_empty',
                'count' => isset($by_status['pending']) ? $by_status['pending'] : 0
            ],
            'revision' => [
                'name' => 'Revision',
                'icon' => 'redo',
                'count' => isset($by_status['revision']) ? $by_status['revision'] : 0
            ],
            'review' => [
                'name' => 'Under Review',
                'icon' => 'overview',
                'count' => isset($by_status['review']) ? $by_status['review'] : 0
            ],
            'uploading' => [
                'name' => 'Uploading',
                'icon' => 'arrow_upload_progress',
                'count' => isset($by_status['uploading']) ? $by_status['uploading'] : 0
            ],
            'rejected' => [
                'name' => 'Rejected',
                'icon' => 'cancel_presentation',
                'count' => isset($by_status['rejected']) ? $by_status['rejected'] : 0
            ],
            'released' => [
                'name' => 'Published',
                'icon' => 'published_with_changes',
                'count' => isset($by_status['released']) ? $by_status['released'] : 0
            ],
            'all' => [
                'name' => 'All Releases',
                'icon' => 'all_inclusive',
                'count' => isset($by_status['all']) ? $by_status['all'] : 0
            ],
            'draft' => [
                'name' => 'Draft',
                'icon' => 'description',
                'count' => isset($by_status['draft']) ? $by_status['draft'] : 0
            ]
        ];

        // Limited viewers (e.g. Partner) only see published releases.
        if (!auth()->user()->can('view-releases-all')) {
            $sections = ['released' => $sections['released']];
        }
    @endphp

    <div class="row g-3 mb-4 release-summary-grid">
        <div class="col-12 col-md-4">
            <div class="card release-summary-card album-summary">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="release-summary-icon">
                            <span class="material-icons-outlined">album</span>
                        </div>
                        <div class="">
                            <h4 class="mb-0">{{ $by_type['album'] ?? 0 }}</h4>
                            <p class="mb-0">Albums Released</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card release-summary-card single-summary">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="release-summary-icon">
                            <span class="material-icons-outlined">library_music</span>
                        </div>
                        <div class="">
                            <h4 class="mb-0">{{ $by_type['single'] ?? 0 }}</h4>
                            <p class="mb-0">Singles Released</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card release-summary-card track-summary">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="release-summary-icon">
                            <span class="material-icons-outlined">music_note</span>
                        </div>
                        <div class="">
                            <h4 class="mb-0">{{ $by_type['tracks'] ?? 0 }}</h4>
                            <p class="mb-0">Total Tracks</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-pills release-status-tabs mb-4">
        @foreach ($sections as $key => $section)
            <li class="nav-item">
                @if ($loop->first)
                    <a class="nav-link active" data-bs-toggle="pill" href="#tabs-{{ $key }}" role="tab" aria-selected="true">
                @else
                        <a class="nav-link" data-bs-toggle="pill" href="#tabs-{{ $key }}" role="tab" aria-selected="false"
                            tabindex="-1">
                    @endif
                        <div class="d-flex align-items-center">
                            <div class="tab-icon"><i
                                    class="material-icons-outlined material-symbols-outlined me-1">{{ $section['icon'] }}</i>
                            </div>
                            <div class="tab-title">{{ $section['name'] }} (<span class="count">{{ $section['count'] }}</span>)
                            </div>
                        </div>
                    </a>
            </li>
        @endforeach
    </ul>
    <div class="tab-content" id="tab-content">
        @foreach ($sections as $key => $section)
            @if ($loop->first)
                <div class="tab-pane fade show active" id="tabs-{{ $key }}" role="tabpanel">
            @else
                    <div class="tab-pane fade" id="tabs-{{ $key }}" role="tabpanel">
                @endif
                    <table class="table albums table-striped" id="{{ $key }}" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Album</th>
                                <th>Release Type</th>
                                <th>Release Status</th>
                                <th>Release Date</th>
                                <th>Uploaded By</th>
                                <th>Aggregator</th>
                                <th>Uploaded At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
        @endforeach
        </div>
@endsection
    @push('css')
        <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
        <style>
            .sf-app .main-wrapper .main-content{max-width:1440px!important;padding:28px 32px 44px!important;background:#081225!important}.release-index-hero{position:relative;display:flex;align-items:center;justify-content:space-between;gap:22px;min-height:88px;margin-bottom:20px;padding:0 0 18px;border-bottom:1px solid rgba(61,102,144,.42)}.release-index-hero:after{position:absolute;top:-42px;right:21%;width:440px;height:132px;background:repeating-linear-gradient(90deg,transparent 0 9px,rgba(99,157,221,.11) 9px 12px,transparent 12px 18px);clip-path:polygon(0 47%,7% 47%,7% 30%,13% 30%,13% 58%,20% 58%,20% 11%,27% 11%,27% 72%,34% 72%,34% 26%,41% 26%,41% 54%,48% 54%,48% 0,55% 0,55% 80%,62% 80%,62% 35%,69% 35%,69% 62%,76% 62%,76% 19%,83% 19%,83% 57%,90% 57%,90% 39%,100% 39%,100% 51%,0 51%);content:"";opacity:.9;pointer-events:none}.release-index-hero>div{position:relative;z-index:1}.release-index-hero p{margin:0 0 4px;color:#8eadcd;font-size:.74rem;font-weight:700;letter-spacing:.05em}.release-index-hero h1{margin:0;color:#f5f9ff;font-size:2.1rem;font-weight:800;letter-spacing:-.04em}.release-index-toolbar{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:9px}.release-toolbar-button,.release-create-button{display:inline-flex!important;align-items:center;gap:7px;min-height:42px;padding:9px 13px!important;border-radius:9px!important;font-size:.78rem;font-weight:700}.release-toolbar-button{border:1px solid #45678a!important;background:rgba(12,30,50,.72)!important;color:#aec8e3!important}.release-toolbar-button:hover{border-color:#77aaf0!important;background:#173658!important;color:#fff!important}.release-create-button{border:0!important;background:#ff7043!important;color:#fff!important;box-shadow:0 8px 18px rgba(255,112,67,.22)}.release-create-button:hover{background:#ff8a65!important;color:#fff!important;transform:translateY(-1px)}.release-create-button i{font-size:17px}.release-summary-grid{margin-bottom:18px!important;background:transparent!important}.release-summary-card{--summary-accent:#72b0ff;position:relative;min-height:94px;border:1px solid color-mix(in srgb,var(--summary-accent) 58%,transparent)!important;border-radius:13px!important;background:transparent!important;background-image:none!important;color:var(--summary-accent);box-shadow:none!important;transition:transform .18s ease,border-color .18s ease}.release-summary-card:after{display:none!important;content:none!important}.release-summary-card:hover{transform:translateY(-4px);border-color:var(--summary-accent)!important}.release-summary-card .card-body{position:relative;z-index:1;padding:20px;background:transparent!important}.release-summary-card h4{color:var(--summary-accent);font-size:1.55rem;font-weight:800}.release-summary-card p{color:#b9c6d5;font-size:.79rem;font-weight:700}.release-summary-icon{display:grid;place-items:center;width:48px;height:48px;border:1px solid currentColor;border-radius:13px;background:transparent!important;color:var(--summary-accent);transition:transform .18s ease}.release-summary-card:hover .release-summary-icon{transform:scale(1.08) rotate(-4deg)}.release-summary-icon .material-icons-outlined{font-size:25px}.album-summary{--summary-accent:#c084fc}.single-summary{--summary-accent:#60a5fa}.track-summary{--summary-accent:#34d399}@media(prefers-reduced-motion:reduce){.release-summary-card,.release-summary-icon{transition:none!important}.release-summary-card:hover,.release-summary-card:hover .release-summary-icon{transform:none!important}}.release-status-tabs{display:flex!important;flex-wrap:wrap;gap:4px;margin:0 0 16px!important;padding:7px;border:1px solid #294d74;border-radius:12px;background:#0d1d31}.release-status-tabs .nav-item{flex:0 0 auto}.release-status-tabs .nav-link{display:flex;align-items:center;min-height:43px;padding:0 13px;border:1px solid transparent;border-radius:8px;color:#a8c3e0;font-size:.78rem;font-weight:700}.release-status-tabs .nav-link .tab-icon{display:inline-flex;margin-right:6px;color:#7dafff}.release-status-tabs .nav-link .tab-icon i{font-size:19px}.release-status-tabs .nav-link.active{border-color:#ff7043;background:linear-gradient(120deg,#ff7043,#f05b38);color:#fff;box-shadow:0 6px 13px rgba(255,112,67,.2)}.release-status-tabs .nav-link.active .tab-icon{color:#fff}.tab-content{padding:18px;border:1px solid #294d74;border-radius:13px;background:#0d1d31}.tab-content .dataTables_filter label,.tab-content .dataTables_length label{color:#c9dbed;font-size:.8rem}.tab-content .dataTables_filter input,.tab-content .dataTables_length select{margin-left:7px;border:1px solid #45688d;background:#08182a;color:#eef6ff;border-radius:8px}.tab-content .dataTables_filter input{min-height:37px;min-width:210px}.tab-content table.albums{margin-top:16px!important;color:#d9e8f7}.tab-content table.albums thead th{border-bottom:0;background:#172f4c;color:#b5d0ec;font-size:.69rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;white-space:nowrap}.tab-content table.albums tbody td{border-color:rgba(59,94,129,.48);background:transparent;color:#d8e6f5;font-size:.78rem}.tab-content table.albums tbody tr:hover td{background:rgba(57,96,139,.15)}.albums img.cover{border-radius:8px;object-fit:cover}.albums .fw-bold{color:#eff6ff;font-size:.82rem}.albums .text-muted{color:#89a7c6!important;font-size:.72rem}.albums td .actions{display:flex;justify-content:flex-end}.albums td .actions a{padding:7px 11px;border-color:#5b88b5;color:#a9cdf2;border-radius:7px;font-size:.73rem;font-weight:700}.albums td .actions a:hover{border-color:#ff7043;background:#ff7043;color:#fff}.tab-content .dataTables_info,.tab-content .dataTables_paginate{margin-top:15px;color:#94b0cc!important;font-size:.76rem}.tab-content .pagination .page-link{border-color:#345674;background:#142a44;color:#a9c9ea}.tab-content .pagination .active>.page-link{border-color:#ff7043;background:#ff7043;color:#fff}@media(max-width:900px){.sf-app .main-wrapper .main-content{padding:20px!important}.release-index-hero{align-items:flex-start;flex-direction:column}.release-index-toolbar{justify-content:flex-start}.release-status-tabs{flex-wrap:nowrap;overflow-x:auto}.release-status-tabs .nav-link{white-space:nowrap}.tab-content{overflow-x:auto}}@media(max-width:540px){.release-index-hero h1{font-size:1.75rem}.release-index-toolbar{width:100%}.release-index-toolbar form,.release-index-toolbar a{flex:1}.release-toolbar-button,.release-create-button{width:100%;justify-content:center}.release-summary-card .card-body{padding:16px}.tab-content{padding:12px}.tab-content .dataTables_filter input{min-width:150px;width:150px}.release-status-tabs{margin-left:-12px!important;margin-right:-12px!important;border-radius:0}}
            .release-date-cell{display:grid;gap:4px;white-space:normal}.release-date-cell>span{color:#dceafa;font-weight:600}.release-countdown{display:inline-flex;width:max-content;padding:3px 6px;border-radius:999px;font-size:.62rem;font-weight:800;line-height:1.2}.release-countdown.is-upcoming{background:rgba(83,164,255,.14);color:#81bbff}.release-countdown.is-overdue{background:rgba(255,112,67,.15);color:#ff9a76}.release-countdown.is-today{background:rgba(76,217,153,.15);color:#77e7b1}
            .release-type-cell{display:grid;justify-items:start;gap:6px}.release-type-cell small{padding-left:3px;color:#88a8c7;font-size:.68rem;font-weight:700}.release-type-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border:1px solid;border-radius:999px;font-size:.69rem;font-weight:800;line-height:1}.release-type-badge i{font-size:15px}.release-type-badge.is-single{border-color:rgba(255,140,97,.48);background:rgba(255,112,67,.13);color:#ffad8a}.release-type-badge.is-album{border-color:rgba(111,173,255,.45);background:rgba(83,164,255,.13);color:#94c3ff}
            .release-status-review{background:#f47c3c!important;color:#fff!important}.release-status-revision{background:#8b5cf6!important;color:#fff!important}
            .uploaded-date-cell{display:grid;gap:4px;white-space:normal}.uploaded-date-cell>span{color:#dceafa;font-weight:600}.uploaded-date-cell small{color:#86a8c9;font-size:.61rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.uploaded-date-cell strong{color:#ffbf8a;font-size:.72rem;font-weight:800}
        </style>
    @endpush
    @push('script')
        <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
        <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
        <script>
            function badge_release(status) {
                if (status == 'pending') {
                    return `@include('components.badge-release', ['status' => 'pending'])`;
                } else if (status == 'revision') {
                    return `@include('components.badge-release', ['status' => 'revision'])`;
                } else if (status == 'review') {
                    return `@include('components.badge-release', ['status' => 'review'])`;
                } else if (status == 'uploading') {
                    return `@include('components.badge-release', ['status' => 'uploading'])`;
                } else if (status == 'released') {
                    return `@include('components.badge-release', ['status' => 'released'])`;
                } else if (status == 'rejected') {
                    return `@include('components.badge-release', ['status' => 'rejected'])`;
                } else if (status == 'draft') {
                    return `@include('components.badge-release', ['status' => 'draft'])`;
                } else {
                    return `<span class="badge bg-secondary">${status}</span>`;
                }
            }

            function releaseDateWithCountdown(value) {
                if (!value) return '-';

                const releaseDate = new Date(`${value}T00:00:00`);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const diffDays = Math.round((releaseDate - today) / 86400000);
                const dateText = releaseDate.toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' });
                const countdown = diffDays > 0
                    ? `<small class="release-countdown is-upcoming">${diffDays} ${diffDays === 1 ? 'day' : 'days'} left</small>`
                    : diffDays < 0
                        ? `<small class="release-countdown is-overdue">${Math.abs(diffDays)} ${Math.abs(diffDays) === 1 ? 'day' : 'days'} overdue</small>`
                        : '<small class="release-countdown is-today">Releases today</small>';

                return `<div class="release-date-cell"><span>${dateText}</span>${countdown}</div>`;
            }

            function uploadedDateWithContract(value, row) {
                const uploadedDate = value
                    ? new Date(value).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' })
                    : '-';

                if (row.status !== 'released' || !row.contract_expired_at) {
                    return uploadedDate;
                }

                const expiryDate = new Date(row.contract_expired_at).toLocaleString('default', {
                    month: 'long', day: 'numeric', year: 'numeric'
                });

                return `<div class="uploaded-date-cell"><span>${uploadedDate}</span><small>Contract Expiry Date</small><strong>${expiryDate}</strong></div>`;
            }

            function releaseTypeBadge(value, trackCount) {
                const type = String(value || '').toLowerCase();
                const label = type === 'single' ? 'Single' : 'Album';
                const icon = type === 'single' ? 'music_note' : 'album';
                const count = Number.isFinite(Number(trackCount)) ? Number(trackCount) : 0;
                const trackLabel = `${count} ${count === 1 ? 'track' : 'tracks'}`;

                return `<div class="release-type-cell"><span class="release-type-badge is-${type === 'single' ? 'single' : 'album'}"><i class="material-icons-outlined">${icon}</i>${label}</span><small>${trackLabel}</small></div>`;
            }

            $(document).ready(function () {
                $('.release-type').click(function () {
                    window.location.href = $(this).data('link');
                });

                // Initialize DataTable (only for the tabs rendered for this user)
                @json(array_keys($sections)).forEach(element => {
                    let url = "{{ route('admin.releases.get_data', ':status') }}".replace(':status', element);
                    let columns = [
                        {
                            render: function (data, type, row) {
                                var cover = "/build/images/album-placeholder.webp";
                                if (row.thumbnail_url) {
                                    cover = row.thumbnail_url;
                                }

                                var artist = '-';
                                if (row.primaryartists?.[0]) {
                                    artist = row.primaryartists?.[0]?.name ?? (row.primaryartists?.[0]?.name ?? row.primaryartists?.[0]);
                                }

                                var title = row.title ?? 'Untitled Album'

                                return `
                                        <div class="d-flex flex-row align-items-center">
                                            <img class="cover" src="${cover}" width="50" height="50" loading="lazy" onerror="this.onerror=null;this.src='/build/images/album-placeholder.webp';">
                                            <div class="ms-3">
                                                <p class="fw-bold mb-1">${title}</p>
                                                <p class="text-muted mb-0">${artist}</p>
                                            </div>
                                        </div>
                                    `;
                            }
                        },
                        {
                            data: "type",
                            render: function (data, type, row) {
                                return releaseTypeBadge(data, row.tracknum);
                            }
                        },
                        {
                            render: function (data, type, row) {
                                return badge_release(row.status);
                            }
                        },
                        {
                            data: "releaseon",
                            render: {
                                display: function (data, type, row) {
                                    return releaseDateWithCountdown(data);

                                },
                                sort: function (data, type, row) {
                                    return data;
                                }
                            }
                        },
                        {
                            data: "fullname",
                            defaultContent: "-",
                            render: function (data, type, row) {
                                return data;
                            }
                        },
                        {
                            data: "aggregator",
                            defaultContent: "-",
                            render: function (data, type, row) {
                                return data;
                            }
                        },
                        {
                            data: "created_at",
                            render: {
                                display: function (data, type, row) {
                                    return uploadedDateWithContract(data, row);

                                },
                                sort: function (data, type, row) {
                                    return data;
                                }
                            }
                        },
                        {
                            data: "id",
                            render: function (data, type, row) {
                                var link = "{{ route('admin.releases.show', ':id') }}".replace(':id', data);
                                return `<div class="actions"><a href="${link}" class="btn btn-sm btn-outline-primary">View</a>`;
                            }
                        }
                    ];
                    if (['released', 'all'].includes(element)) {
                        columns = [
                            {
                                render: function (data, type, row) {
                                    var cover = "/build/images/album-placeholder.webp";
                                    if (row.thumbnail_url) {
                                        cover = row.thumbnail_url;
                                    }

                                    var artist = '-';
                                    if (row.primaryartists?.[0]) {
                                        artist = row.primaryartists?.[0]?.name ?? (row.primaryartists?.[0]?.name ?? row.primaryartists?.[0]);
                                    }

                                    var title = row.title ?? 'Untitled Album'

                                    return `
                                            <div class="d-flex flex-row align-items-center">
                                                <img class="cover" src="${cover}" width="50" height="50" loading="lazy" onerror="this.onerror=null;this.src='/build/images/album-placeholder.webp';">
                                                <div class="ms-3">
                                                    <p class="fw-bold mb-1">${title}</p>
                                                    <p class="text-muted mb-0">${artist}</p>
                                                </div>
                                            </div>
                                        `;
                                }
                            },
                            {
                                data: "type",
                                render: function (data, type, row) {
                                    return releaseTypeBadge(data, row.tracknum);
                                }
                            },
                            {
                                render: function (data, type, row) {
                                    return badge_release(row.status);
                                }
                            },
                            {
                                data: "releaseon",
                                render: {
                                    display: function (data, type, row) {
                                        return releaseDateWithCountdown(data);

                                    },
                                    sort: function (data, type, row) {
                                        return data;
                                    }
                                }
                            },
                            {
                                data: "fullname",
                                defaultContent: "-",
                                render: function (data, type, row) {
                                    return data;
                                }
                            },
                            {
                                data: "aggregator",
                                defaultContent: "-",
                                render: function (data, type, row) {
                                    return data;
                                }
                            },
                            {
                                data: "created_at",
                                render: {
                                    display: function (data, type, row) {
                                        return uploadedDateWithContract(data, row);

                                    },
                                    sort: function (data, type, row) {
                                        return data;
                                    }
                                }
                            },
                            {
                                data: "id",
                                render: function (data, type, row) {
                                    var link = "{{ route('admin.releases.show', ':id') }}".replace(':id', data);
                                    return `<div class="actions"><a href="${link}" class="btn btn-sm btn-outline-primary">View</a>`;
                                }
                            }
                        ];

                    }
                    console.log(columns.length)

                    $('#' + element).DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: url,
                        responsive: true,
                        pageLength: 10,
                        lengthChange: true,
                        columnDefs: [
                            { responsivePriority: 1, targets: 0 },
                            {
                                responsivePriority: 2, targets: -1,
                                orderable: false,
                                searchable: false
                            },
                            {
                                responsivePriority: 3,
                                targets: ['released', 'all'].includes(element) ? -4 : -2,
                            },
                        ],
                        order: [
                            6,
                            'desc'
                        ],
                        columns,
                    });
                });
            });
        </script>
    @endpush
