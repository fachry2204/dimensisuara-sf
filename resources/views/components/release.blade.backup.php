<div class="row pb-5 {{ ($type ?? '') === 'admin' ? 'admin-release-detail' : 'customer-release-detail' }}">
    @if($release->reason && $release->status == 'rejected')
        <div class="col-12 release-rejection-notice">
            <div class="alert alert-danger mb-4">
                <div class="d-flex align-items-center">
                    <div class="ms-3">
                        <h6 class="mb-1 text-danger">Rejection Reason</h6>
                        <div>
                            {{ $release->reason }}
                            @if($release->previous_rejected_status)
                                <div class="mt-3">
                                    Previous Status: @include('components.badge-release', ['status' => $release->previous_rejected_status])
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="col-12 album-detail release-overview-panel">
        <div class="row justify-content-start g-4">
            <div class="col-md-4 d-flex flex-column align-items-center release-cover-column">
                @if($release->cover_url)
                    <img class="album-cover mb-3" src="{{ $release->cover_url }}" loading="lazy" alt="Album Cover"
                        onerror="this.onerror=null;this.src='/build/images/album-placeholder.webp';">
                @else
                    <img class="album-cover mb-3" src="{{ URL::asset('build/images/album-placeholder.webp') }}"
                        loading="lazy" alt="Album Cover">
                @endif
                @if($canDownload ?? true)
                    <a href="{{ $release->cover_url }}" download="release-{{$release->id}}-cover"
                        class="btn release-cover-download mb-3"><i class="bx bx-download"></i> Album Cover</a>
                @endif
            </div>
            <div class="col-md-8 release-metadata-column">
                <div class="album-title-status">
                    <h2 class="fw-bold mb-0">{{ $release->title }}</h2>
                </div>
                @php
                    $primaryartists = [];
                    if ($release->primaryartists) {
                        foreach ($release->primaryartists ?? [] as $artist) {
                            if (is_string($artist)) {
                                $primaryartists[] = $artist;
                            } else {
                                $primaryartists[] = is_array($artist) ? ($artist['name'] ?? '') : ($artist->name ?? '');
                            }
                        }
                    }
                @endphp
                <div class="album-artist mb-2">{{ implode(', ', $primaryartists) }}</div>
                <div class="album-status mb-3">
                    @include('components.badge-release', ['status' => $release->status])
                </div>
                <div class="row release-metadata-grid">
                    <div class="col-lg-6">
                        <table class="table table-borderless album-items release-items-table">
                            <tbody>
                                <tr>
                                    <td>Planned Release Date</td>
                                    <td>{{ \Carbon\Carbon::parse($release->releaseon)->format('F j, Y') }}</td>
                                </tr>
                                @if($release->released)
                                    <tr>
                                        <td>Original Released Date</td>
                                        <td>{{ \Carbon\Carbon::parse($release->releaseddate)->format('F j, Y') }}</td>
                                    </tr>
                                @endif
                                @if($release->tiktok_ytmusic_rerelease_date)
                                    <tr>
                                        <td>TikTok & YouTube Music Pre-Release Date</td>
                                        <td>{{ \Carbon\Carbon::parse($release->tiktok_ytmusic_rerelease_date)->format('F j, Y') }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td>Version</td>
                                    <td>{{ $release->versionname }} @if($release->ipl_url) <small><a
                                    href="{{ $release->ipl_url }}" target="_blank">IPL</a></small> @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Genre</td>
                                    <td>{{ $release->genrename }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-lg-6">
                        <table class="table table-borderless album-items release-items-table release-artists-table">
                            <tbody>
                                <tr>
                                    <td>Subgenre</td>
                                    <td>{{ $release->subgenrename }}</td>
                                </tr>
                                <tr>
                                    <td>Title Language</td>
                                    <td>{{ $release->titlelangname }}</td>
                                </tr>
                                <tr>
                                    <td>UPC</td>
                                    <td>{{ $release->upc ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td>Record Label</td>
                                    <td>{{ $release->recordlabel }}</td>
                                </tr>
                                <tr>
                                    <td>Primary Artists</td>
                                    <td>
                                        <ul>
                                            @php $artist = null; @endphp
                                            @foreach(($release->primaryartists ?? []) as $artist)
                                                <li>
                                                    @if(is_string($artist))
                                                        {{ $artist }}
                                                    @else
                                                        @php
                                                            $artistName = is_array($artist) ? ($artist['name'] ?? '') : ($artist->name ?? '');
                                                            $socialUrl = is_array($artist) ? ($artist['social'] ?? null) : ($artist->social ?? null);
                                                            $spotifyUrl = is_array($artist) ? ($artist['spotify'] ?? null) : ($artist->spotify ?? null);
                                                            if ($socialUrl && !str_starts_with($socialUrl, 'https://')) {
                                                                $socialUrl = 'https://' . ltrim($socialUrl, '/');
                                                            }
                                                            if ($spotifyUrl && !str_starts_with($spotifyUrl, 'https://')) {
                                                                $spotifyUrl = 'https://' . ltrim($spotifyUrl, '/');
                                                            }
                                                        @endphp
                                                        {{ $artistName }}
                                                        @if($socialUrl)
                                                            <a target="_blank" href="{{ $socialUrl }}">
                                                                <span class="material-symbols-outlined"
                                                                    style="vertical-align: middle"> language </span>
                                                            </a>
                                                        @endif
                                                        @if($spotifyUrl)
                                                            <a target="_blank" href="{{ $spotifyUrl }}">
                                                                <img src="{{ URL::asset('build/images/spotify.png') }}"
                                                                    alt="spotify" style="width: 24px; height:24px">
                                                            </a>
                                                        @endif
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>

                                    </td>
                                </tr>
                                @if($release->contract_expired_at)
                                    <tr>
                                        <td>Contract Expiry Date</td>
                                        <td>{{ \Carbon\Carbon::parse($release->contract_expired_at)->format('F j, Y') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 track-information release-track-panel">
        @if($release->type !== 'single')
            <h3 class="section-toggle-heading">
                <button type="button" class="section-toggle-btn" data-bs-toggle="collapse"
                    data-bs-target="#track-info-collapse" aria-expanded="{{ ($type ?? '') === 'admin' ? 'true' : 'false' }}" aria-controls="track-info-collapse">
                    Track Information
                    <span class="section-toggle-icon" aria-hidden="true"></span>
                </button>
            </h3>
        @endif
        <div id="track-info-collapse" class="{{ $release->type === 'single' ? '' : 'collapse '.(($type ?? '') === 'admin' ? 'show' : '') }}">
            @if($release->type === 'single')
                {{-- Single: tampilkan langsung tanpa accordion --}}
                @foreach($release->tracks as $index => $track)
                    @php
                        $primaryartists = [];
                        if ($track->primaryartists) {
                            foreach ($track->primaryartists as $artist) {
                                if (is_string($artist)) {
                                    $primaryartists[] = $artist;
                                } else {
                                    $primaryartists[] = $artist->name;
                                }
                            }
                        }
                    @endphp
                    <div class="single-track-flat">
                        <div class="single-track-header">
                            <div class="track-number">{{ $index + 1 }}</div>
                            <div class="d-flex flex-column flex-grow-1">
                                <span class="track-title">{{ $track->title }}</span>
                                <span class="track-artist">{{ implode(', ', ($primaryartists)) }}</span>
                            </div>
                        </div>
                        <div class="track-detail single-track-body">\n                            <div class="row">\n                                @if(($canDownload ?? true) && ($track->full_exists || $track->trimmed_exists))\n                                    <div class="col-12 mb-3 border-bottom">\n                                        <div class="row justify-content-around text-center">\n                                            <div class="col-lg-4 mb-2">\n                                                @if($track->full_exists)\n                                                    <span class="fw-bold">Full Track</span>\n                                                    @if($track->full_file->identifier)\n                                                        <p class="text-center my-0">{{ $track->full_name }}</p>\n                                                        <audio preload="none" controls src="{{ $track->full_url }}" class="w-100"></audio>\n                                                    @endif\n                                                    <a href="{{ $track->full_url }}"\n                                                        download="release-{{ $release->id }}-track-{{ $track->id }}"\n                                                        class="btn btn-sm btn-download btn-outline-primary m-1"\n                                                        onclick="event.stopImmediatePropagation();"><i\n                                                            class="bx bx-download"></i> Track</a>\n                                                @endif\n                                            </div>\n                                            <div class="col-lg-4 mb-2">\n                                                @if($track->trimmed_exists)\n                                                    <span class="fw-bold">Trimmed Track</span>\n                                                    @if($track->trimmed_file->identifier)\n                                                        <p class="text-center my-0">{{ $track->trimmed_name }}</p>\n                                                        <audio preload="none" controls src="{{ $track->trimmed_url }}" class="w-100"></audio>\n                                                    @endif\n                                                    <a href="{{ $track->trimmed_url }}"\n                                                        download="release-{{ $release->id }}-track-{{ $track->id }}-trimmed"\n                                                        class="btn btn-sm btn-download btn-outline-primary m-1"\n                                                        onclick="event.stopImmediatePropagation();"><i\n                                                            class="bx bx-download"></i> Trimmed</a>\n                                                @endif\n                                            </div>\n                                        </div>\n                                    </div>\n                                @endif
                                    <div class="col-lg-4">
                                        <table class="table table-borderless album-items">
                                            <tr>
                                                <td>Title</td>
                                                <td>{{ $track->title }}</td>
                                            </tr>
                                            <tr>
                                                <td>Title language</td>
                                                <td>{{ $track->titlelangname }}</td>
                                            </tr>
                                            <tr>
                                                <td>Version</td>
                                                <td>{{ $track->versionname }}</td>
                                            </tr>
                                            <tr>
                                                <td>Genre</td>
                                                <td>{{ $track->genrename }}</td>
                                            </tr>
                                            <tr>
                                                <td>Subgenre</td>
                                                <td>{{ $track->subgenrename }}</td>
                                            </tr>
                                            <tr>
                                                <td>ISRC</td>
                                                <td>{{ $track->isrc }}</td>
                                            </tr>
                                            <tr>
                                                <td>Instrumental</td>
                                                <td>{{ $track->instrumental ? 'Yes' : 'No' }}</td>
                                            </tr>
                                            @if(!$track->instrumental)
                                                <tr>
                                                    <td>Lyrics Language</td>
                                                    <td>{{ $track->lyriclangname }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Explicit content</td>
                                                    <td>{{ $track->explicitname }}</td>
                                                </tr>
                                            @endif
                                        </table>
                                    </div>
                                    <div class="col-lg-4">
                                        <table class="table table-borderless album-items">
                                            <tr>
                                                <td>Primary artists</td>
                                                <td>
                                                    @if(!empty($track->primaryartists))
                                                        <ul class="ps-0 mb-0" style="list-style: none;">
                                                            @foreach(($track->primaryartists) as $artist)
                                                                <li>
                                                                    @if(is_string($artist))
                                                                        {{ $artist }}
                                                                    @else
                                                                        {{ $artist->name }}
                                                                        @php
                                                                            $socialUrl = $artist->social ?? null;
                                                                            $spotifyUrl = $artist->spotify ?? null;
                                                                            if ($socialUrl && !str_starts_with($socialUrl, 'https://')) {
                                                                                $socialUrl = 'https://' . ltrim($socialUrl, '/');
                                                                            }
                                                                            if ($spotifyUrl && !str_starts_with($spotifyUrl, 'https://')) {
                                                                                $spotifyUrl = 'https://' . ltrim($spotifyUrl, '/');
                                                                            }
                                                                        @endphp
                                                                        @if($artist->social)
                                                                            <a target="_blank" href="{{ $socialUrl }}">
                                                                                <span class="material-symbols-outlined"
                                                                                    style="vertical-align: middle"> language </span>
                                                                            </a>
                                                                        @endif
                                                                        @if($artist->spotify)
                                                                            <a target="_blank" href="{{ $spotifyUrl }}">
                                                                                <img src="{{ URL::asset('build/images/spotify.png') }}"
                                                                                    alt="spotify" style="width: 24px; height:24px">
                                                                            </a>
                                                                        @endif
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Featured artists</td>
                                                <td>
                                                    @if(!empty($track->featuredartists))
                                                        <ul class="ps-0 mb-0" style="list-style: none;">
                                                            @foreach(($track->featuredartists) as $artist)
                                                                <li>
                                                                    @if(is_string($artist))
                                                                        {{ $artist }}
                                                                    @else
                                                                        {{ $artist->name }}
                                                                        @php
                                                                            $socialUrl = $artist->social ?? null;
                                                                            $spotifyUrl = $artist->spotify ?? null;
                                                                            if ($socialUrl && !str_starts_with($socialUrl, 'https://')) {
                                                                                $socialUrl = 'https://' . ltrim($socialUrl, '/');
                                                                            }
                                                                            if ($spotifyUrl && !str_starts_with($spotifyUrl, 'https://')) {
                                                                                $spotifyUrl = 'https://' . ltrim($spotifyUrl, '/');
                                                                            }
                                                                        @endphp
                                                                        @if($artist->social)
                                                                            <a target="_blank" href="{{ $socialUrl }}">
                                                                                <span class="material-symbols-outlined"
                                                                                    style="vertical-align: middle"> language </span>
                                                                            </a>
                                                                        @endif
                                                                        @if($artist->spotify)
                                                                            <a target="_blank" href="{{ $spotifyUrl }}">
                                                                                <img src="{{ URL::asset('build/images/spotify.png') }}"
                                                                                    alt="spotify" style="width: 24px; height:24px">
                                                                            </a>
                                                                        @endif
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Songwriter</td>
                                                <td>
                                                    @if($track->songwriters)
                                                        <ul class="ps-0 mb-0" style="list-style: none;">
                                                            @foreach($track->songwriters as $index => $songwriter)
                                                                @if($songwriter['role'] == 'author_composer')
                                                                    <li>{{ $songwriter['name'] }} ({{ \App\Enums\SongwriterRole::tryFrom('author')?->label() ?? '—' }})
                                                                    </li>
                                                                    <li>{{ $songwriter['name'] }} ({{ \App\Enums\SongwriterRole::tryFrom('composer')?->label() ?? '—' }})
                                                                    </li>
                                                                @else
                                                                    <li>{{ $songwriter['name'] }} ({{ \App\Enums\SongwriterRole::tryFrom($songwriter['role'] ?? '')?->label() ?? '—' }})
                                                                    </li>
                                                                @endif
                                                            @endforeach

                                                        </ul>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-lg-4">
                                        <table class="table table-borderless album-items">
                                            <tr>
                                                <td>Contributors</td>
                                                <td>
                                                    @if(!empty($track->contributors_pretty))
                                                        <ul class="ps-0 mb-0" style="list-style: none;">
                                                            @foreach($track->contributors_pretty as $contributor)
                                                                <li>{{ $contributor->name }} ({{ $contributor->role }})</li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Production / Engineering</td>
                                                <td>
                                                    @if(!empty($track->productions))
                                                        <ul class="ps-0 mb-0" style="list-style: none;">
                                                            @foreach($track->productions_pretty as $production)
                                                                <li>{{ $production->name }} ({{ $production->role }})</li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                @if(!empty($track->lyrics))
                                    <div class="d-flex justify-content-end mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse"
                                            data-bs-target="#lyrics-{{$track->id}}" aria-expanded="false"
                                            aria-controls="lyrics-{{$track->id}}">
                                            Show/Hide Lyrics
                                        </button>
                                    </div>
                                    <div class="collapse" id="lyrics-{{$track->id}}">
                                        <div class="card card-body">
                                            <pre>{{ $track->lyrics }}</pre>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @if($release->histories && $release->histories->isNotEmpty())
        <div class="col-12 mt-4 release-history-panel">
            <h3 class="section-toggle-heading">
                <button type="button" class="section-toggle-btn" data-bs-toggle="collapse"
                    data-bs-target="#release-history-collapse" aria-expanded="false"
                    aria-controls="release-history-collapse">
                    Release History
                    <span class="section-toggle-icon" aria-hidden="true"></span>
                </button>
            </h3>
            <div id="release-history-collapse" class="collapse">
                <div class="release-timeline">
                    @foreach($release->histories as $history)
                        @php
                            $timelineVariant = 'secondary';
                            if ($history->type === 'waiting') {
                                $timelineVariant = 'warning';
                            } elseif ($history->type === 'rejected') {
                                $timelineVariant = 'danger';
                            } elseif ($history->type === 'accepted') {
                                $timelineVariant = 'success';
                            }
                        @endphp
                        <div class="release-timeline-item release-timeline-item--{{ $timelineVariant }}">
                            <div class="release-timeline-marker release-timeline-marker--{{ $timelineVariant }}"
                                aria-hidden="true"></div>
                            <div class="release-timeline-content">
                                <div class="release-timeline-header">
                                    <span class="release-timeline-action"
                                        style="white-space:pre-wrap">{{ $history->message }}</span>
                                    <span
                                        class="release-timeline-date">{{ $history->created_at?->format('l, F j, Y H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

@push('css')
    <link href="{{ URL::asset('build/css/album.css') }}" rel="stylesheet" />
    <style>
        .accordion-button::after {
            margin-left: 1em;
        }

        /* Collapsible section headings (Track Information, Release History) */
        .section-toggle-heading {
            margin-bottom: 0.75rem;
        }

        .section-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0;
            border: 0;
            background: none;
            font-size: inherit;
            font-weight: 700;
            color: inherit;
            cursor: pointer;
            text-align: left;
        }

        .section-toggle-btn:hover {
            color: var(--bs-primary);
        }

        .section-toggle-icon {
            display: inline-block;
            width: 0.6rem;
            height: 0.6rem;
            border-right: 2px solid currentColor;
            border-bottom: 2px solid currentColor;
            transform: rotate(45deg);
            margin-left: 0.15rem;
            transition: transform 0.2s ease;
        }

        .section-toggle-btn[aria-expanded="false"] .section-toggle-icon {
            transform: rotate(-135deg);
        }

        table.album-items {
            width: fit-content;
        }

        .album-items td:first-child {
            color: var(--bs-secondary-color) !important;
            padding-left: 0px;
            white-space: normal;
            font-size: 1em;
            max-width: 160px;
        }

        [data-bs-theme=dark] .album-items td:last-child {
            color: white;
        }

        [data-bs-theme=semi-dark] .album-items td:last-child {
            color: black;
        }

        .album-items td:last-child {
            font-weight: 700;
            padding-right: 0px;
            font-size: 1.1em;
        }

        .album-items td {
            padding-top: 3px;
            padding-bottom: 3px
        }

        @media (max-width: 768px) {
            .album-items td:first-child {
                width: 50%;
                text-align: right;
            }

            .album-items td:last-child {
                width: 50%;
                text-align: left;
            }
        }

        /* Release history timeline */
        .release-timeline {
            position: relative;
            padding-left: 1.5rem;
        }

        .release-timeline::before {
            content: '';
            position: absolute;
            left: 0.375rem;
            top: 0.5rem;
            bottom: 0.5rem;
            width: 2px;
            background: var(--bs-border-color);
            border-radius: 1px;
        }

        .release-timeline-item {
            position: relative;
            padding-bottom: 1.25rem;
        }

        .release-timeline-item:last-child {
            padding-bottom: 0;
        }

        .release-timeline-marker {
            position: absolute;
            left: -1.5rem;
            top: 0.25rem;
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 50%;
            border: 2px solid var(--bs-body-bg);
            box-shadow: 0 0 0 1px var(--bs-border-color);
        }

        .release-timeline-marker--warning {
            background-color: var(--bs-warning);
        }

        .release-timeline-marker--danger {
            background-color: var(--bs-danger);
        }

        .release-timeline-marker--success {
            background-color: var(--bs-success);
        }

        .release-timeline-marker--secondary {
            background-color: var(--bs-secondary);
        }

        .release-timeline-header {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 0.35rem 0.75rem;
        }

        .release-timeline-by {
            font-size: 0.9em;
        }

        .release-timeline-date {
            font-size: 0.85em;
            color: var(--bs-secondary-color);
            margin-left: auto;
        }

        .release-timeline-message {
            margin-top: 0.25rem;
        }

        .admin-release-detail{max-width:1240px;margin:0 auto}.admin-release-detail .release-overview-panel,.admin-release-detail .release-track-panel{margin-bottom:26px;padding:28px;border:1px solid #285176;border-radius:15px;background:linear-gradient(135deg,rgba(15,36,61,.96),rgba(10,28,48,.96));box-shadow:0 18px 38px rgba(0,0,0,.14)}.admin-release-detail .release-cover-column{justify-content:flex-start}.admin-release-detail .album-cover{width:100%;max-width:330px;aspect-ratio:1;object-fit:cover;margin:0!important;border:1px solid #3b6a92;border-radius:10px;box-shadow:0 14px 28px rgba(0,0,0,.25)}.admin-release-detail .release-cover-download{display:inline-flex;align-items:center;justify-content:center;gap:7px;width:100%;max-width:330px;margin-top:16px;border:1px solid #4c94ca;border-radius:8px;background:transparent;color:#b9d9f8;font-size:.8rem;font-weight:800}.admin-release-detail .release-cover-download:hover{border-color:#75b9ef;background:#173959;color:#fff}.admin-release-detail .album-title-status h2{color:#f7fbff;font-size:clamp(1.7rem,3vw,2.55rem);letter-spacing:-.04em}.admin-release-detail .album-artist{color:#8dbdff;font-size:1.16rem;font-weight:600}.admin-release-detail .album-status .badge{padding:7px 12px;border-radius:999px;font-size:.75rem}.admin-release-detail .release-metadata-grid{margin-top:18px}.admin-release-detail .release-items-table{width:100%;margin:0}.admin-release-detail .release-items-table td{padding:9px 8px;border:0}.admin-release-detail .release-items-table td:first-child{width:52%;color:#91b1d2!important;font-size:.78rem;font-weight:600}.admin-release-detail .release-items-table td:last-child{color:#f3f8ff;font-size:.88rem;font-weight:700}.admin-release-detail .release-artists-table{border-left:1px solid rgba(68,113,153,.58);padding-left:22px}.admin-release-detail .release-artists-table ul{padding:0;margin:0;list-style:none}.admin-release-detail .release-artists-table li{display:flex;align-items:center;gap:6px;margin-bottom:5px}.admin-release-detail .release-track-panel{padding:0;overflow:hidden}.admin-release-detail .section-toggle-heading{margin:0;border-bottom:1px solid rgba(60,104,143,.65)}.admin-release-detail .section-toggle-btn{width:100%;padding:21px 25px;color:#f4f8ff;font-size:1.35rem}.admin-release-detail .section-toggle-btn:hover{color:#fff}.admin-release-detail #track-info-collapse{padding:18px 24px 24px}.admin-release-detail .accordion{border:1px solid #2f5b82;border-radius:10px;overflow:hidden}.admin-release-detail .accordion-item{border:0;border-bottom:1px solid #2b547a;background:#0b2036}.admin-release-detail .accordion-item:last-child{border-bottom:0}.admin-release-detail .accordion-button{min-height:72px;padding:14px 18px;background:#102a45;color:#edf6ff;box-shadow:none}.admin-release-detail .accordion-button:not(.collapsed){background:#173553;color:#fff}.admin-release-detail .track-number{display:grid;place-items:center;flex:none;width:30px;height:30px;margin-right:15px;border:1px solid #4a83b4;border-radius:50%;color:#9fc9ef;font-size:.76rem;font-weight:800}.admin-release-detail .track-title{font-size:.92rem;font-weight:800}.admin-release-detail .track-artist{margin-top:3px;color:#91b7da;font-size:.76rem}.admin-release-detail .accordion-body{background:#0d243c;color:#d5e6f6}.admin-release-detail .track-detail .album-items{width:100%}.admin-release-detail .track-detail .album-items td:first-child{color:#8eafcf!important;font-size:.76rem}.admin-release-detail .track-detail .album-items td:last-child{color:#f0f7ff;font-size:.84rem}.admin-release-detail audio{max-height:34px}@media(max-width:767px){.admin-release-detail .release-overview-panel{padding:20px}.admin-release-detail .release-artists-table{margin-top:8px;border-top:1px solid rgba(68,113,153,.58);border-left:0;padding:13px 0 0}.admin-release-detail #track-info-collapse{padding:12px}.admin-release-detail .section-toggle-btn{padding:17px;font-size:1.12rem}.admin-release-detail .release-items-table td:first-child{width:49%}}
        .admin-release-detail .release-history-panel{padding:0;overflow:hidden;border:1px solid #285176;border-radius:15px;background:linear-gradient(135deg,rgba(15,36,61,.96),rgba(10,28,48,.96));box-shadow:0 18px 38px rgba(0,0,0,.14)}.admin-release-detail .release-history-panel .section-toggle-heading{margin:0;border-bottom:1px solid rgba(60,104,143,.65)}.admin-release-detail .release-history-panel .section-toggle-btn{width:100%;padding:21px 25px;color:#f4f8ff;font-size:1.35rem}.admin-release-detail .release-history-panel .section-toggle-btn:hover{color:#fff}.admin-release-detail #release-history-collapse{padding:18px 24px 24px}.admin-release-detail .release-timeline{padding-left:28px}.admin-release-detail .release-timeline:before{left:8px;top:21px;bottom:21px;background:linear-gradient(#518ac0,rgba(81,138,192,.12))}.admin-release-detail .release-timeline-item{padding:0 0 12px}.admin-release-detail .release-timeline-item:last-child{padding-bottom:0}.admin-release-detail .release-timeline-marker{left:-28px;top:20px;width:12px;height:12px;border:2px solid #0d243c;box-shadow:0 0 0 2px rgba(108,162,214,.3)}.admin-release-detail .release-timeline-content{min-height:54px;padding:15px 17px;border:1px solid rgba(60,108,150,.62);border-radius:9px;background:rgba(19,49,79,.74)}.admin-release-detail .release-timeline-item--warning .release-timeline-content{border-left:3px solid #f1b84b}.admin-release-detail .release-timeline-item--danger .release-timeline-content{border-left:3px solid #f05b7e}.admin-release-detail .release-timeline-item--success .release-timeline-content{border-left:3px solid #39ca8a}.admin-release-detail .release-timeline-header{align-items:center;gap:10px}.admin-release-detail .release-timeline-action{color:#eef7ff;font-size:.88rem;font-weight:750}.admin-release-detail .release-timeline-date{padding:4px 8px;border-radius:5px;background:rgba(114,165,212,.1);color:#a9c9e7;font-size:.72rem;font-weight:650}@media(max-width:767px){.admin-release-detail #release-history-collapse{padding:12px}.admin-release-detail .release-history-panel .section-toggle-btn{padding:17px;font-size:1.12rem}.admin-release-detail .release-timeline-header{align-items:flex-start;flex-direction:column}.admin-release-detail .release-timeline-date{margin-left:0}}
        .admin-release-detail .release-overview-panel{position:relative;isolation:isolate;overflow:hidden}.admin-release-detail .release-overview-panel:before{position:absolute;right:38px;top:38px;width:280px;height:110px;background:repeating-linear-gradient(90deg,transparent 0 9px,rgba(93,162,223,.14) 9px 12px,transparent 12px 18px);clip-path:polygon(0 60%,7% 60%,7% 35%,14% 35%,14% 82%,21% 82%,21% 16%,28% 16%,28% 68%,35% 68%,35% 43%,42% 43%,42% 0,49% 0,49% 90%,56% 90%,56% 27%,63% 27%,63% 73%,70% 73%,70% 47%,77% 47%,77% 12%,84% 12%,84% 67%,91% 67%,91% 37%,100% 37%,100% 62%,0 62%);content:"";opacity:.9;pointer-events:none}.admin-release-detail .release-overview-panel:after{position:absolute;right:64px;bottom:25px;color:rgba(104,171,229,.12);content:"♫  ♪";font-size:76px;font-weight:700;letter-spacing:25px;line-height:1;pointer-events:none}.admin-release-detail .release-overview-panel>.row{position:relative;z-index:1}.admin-release-detail .release-cover-column{padding:6px 8px}.admin-release-detail .release-metadata-column{padding:8px 18px}.admin-release-detail .release-metadata-grid>.col-lg-6{min-width:0}.admin-release-detail .release-items-table td:first-child{width:48%;letter-spacing:.015em}.admin-release-detail .release-items-table td:last-child{overflow-wrap:anywhere}.admin-release-detail .release-artists-table{background:rgba(8,25,43,.38);border:1px solid rgba(61,111,153,.52);border-radius:10px;padding:8px 12px}.admin-release-detail .release-artists-table td{padding:10px 7px}.admin-release-detail .release-artists-table td:first-child{width:44%}.admin-release-detail .release-artists-table a{display:inline-grid;place-items:center;width:24px;height:24px;border-radius:50%;background:rgba(85,151,208,.14);color:#7dbaff}.admin-release-detail .release-artists-table img{width:18px!important;height:18px!important}@media(max-width:991px){.admin-release-detail .release-overview-panel:before{right:-30px;opacity:.45}.admin-release-detail .release-overview-panel:after{right:15px;bottom:15px;font-size:50px}.admin-release-detail .release-metadata-column{padding:8px}.admin-release-detail .release-artists-table{margin-top:12px}}@media(max-width:575px){.admin-release-detail .release-overview-panel:before,.admin-release-detail .release-overview-panel:after{display:none}.admin-release-detail .release-cover-column{padding:0}.admin-release-detail .release-metadata-column{padding:0}.admin-release-detail .release-items-table td{padding:8px 5px}.admin-release-detail .release-items-table td:first-child{width:51%;font-size:.72rem}.admin-release-detail .release-items-table td:last-child{font-size:.81rem}}
        /* Final shared layout: customer and admin release details */
        :is(.customer-release-detail, .admin-release-detail) {
            --release-accent: #4f9bd8;
            max-width: 1240px;
            margin-inline: auto;
            row-gap: 24px;
        }

        :is(.customer-release-detail, .admin-release-detail) > .release-overview-panel,
        :is(.customer-release-detail, .admin-release-detail) > .release-track-panel,
        :is(.customer-release-detail, .admin-release-detail) > .release-history-panel {
            margin: 0;
            min-width: 0;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-overview-panel {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            padding: 30px;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-overview-panel::after {
            z-index: 0;
            opacity: .72;
            pointer-events: none;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-overview-panel > .row {
            position: relative;
            z-index: 1;
            align-items: start;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-cover-column {
            align-items: stretch !important;
            max-width: 350px;
            margin-inline: auto;
            padding: 0 10px;
        }

        :is(.customer-release-detail, .admin-release-detail) .album-cover {
            display: block;
            width: 100%;
            max-width: 330px;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            margin-inline: auto !important;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-cover-download {
            align-self: center;
            min-height: 42px;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-metadata-column {
            min-width: 0;
            padding: 2px 18px 0;
        }

        :is(.customer-release-detail, .admin-release-detail) .album-title-status h2 {
            max-width: min(100%, 680px);
            margin-bottom: 6px !important;
            overflow-wrap: anywhere;
            line-height: 1.12;
        }

        :is(.customer-release-detail, .admin-release-detail) .album-artist {
            overflow-wrap: anywhere;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-metadata-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0 28px;
            margin-top: 22px;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-metadata-grid > [class*="col-"] {
            width: auto;
            min-width: 0;
            padding: 0;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-items-table,
        :is(.customer-release-detail, .admin-release-detail) .track-detail .album-items {
            width: 100%;
            table-layout: fixed;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-items-table td {
            vertical-align: top;
            padding: 10px 8px;
            line-height: 1.4;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-items-table td:first-child {
            width: 44%;
            max-width: none;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-items-table td:last-child {
            width: 56%;
            overflow-wrap: anywhere;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-artists-table {
            padding: 0;
            border: 0;
            border-left: 0;
            border-radius: 0;
            background: transparent;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-track-panel {
            padding: 0;
            overflow: hidden;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-track-panel > .section-toggle-heading,
        :is(.customer-release-detail, .admin-release-detail) .release-history-panel > .section-toggle-heading {
            margin: 0;
        }

        :is(.customer-release-detail, .admin-release-detail) .release-track-panel > .section-toggle-heading .section-toggle-btn,
        :is(.customer-release-detail, .admin-release-detail) .release-history-panel > .section-toggle-heading .section-toggle-btn {
            width: 100%;
            justify-content: space-between;
            min-height: 68px;
            padding: 18px 24px;
        }

        :is(.customer-release-detail, .admin-release-detail) #track-info-collapse,
        :is(.customer-release-detail, .admin-release-detail) #release-history-collapse {
            padding: 0 24px 24px;
        }

        :is(.customer-release-detail, .admin-release-detail) .accordion-item {
            overflow: hidden;
        }

        :is(.customer-release-detail, .admin-release-detail) .accordion-button {
            min-width: 0;
            padding: 16px 18px;
        }

        :is(.customer-release-detail, .admin-release-detail) .track-title,
        :is(.customer-release-detail, .admin-release-detail) .track-artist {
            overflow-wrap: anywhere;
        }

        :is(.customer-release-detail, .admin-release-detail) audio {
            max-width: 100%;
        }

        @media (max-width: 991.98px) {
            :is(.customer-release-detail, .admin-release-detail) .release-metadata-grid {
                grid-template-columns: 1fr;
            }

            :is(.customer-release-detail, .admin-release-detail) .release-artists-table {
                margin-top: 4px;
                padding-top: 4px;
                border-top: 1px solid rgba(100, 148, 190, .35);
            }
        }

        @media (max-width: 767.98px) {
            :is(.customer-release-detail, .admin-release-detail) {
                row-gap: 16px;
            }

            :is(.customer-release-detail, .admin-release-detail) .release-overview-panel {
                padding: 20px 16px;
            }

            :is(.customer-release-detail, .admin-release-detail) .release-metadata-column {
                padding: 4px 0 0;
            }

            :is(.customer-release-detail, .admin-release-detail) .release-cover-column {
                max-width: 300px;
                padding: 0;
            }

            :is(.customer-release-detail, .admin-release-detail) .release-overview-panel::before,
            :is(.customer-release-detail, .admin-release-detail) .release-overview-panel::after {
                display: none;
            }

            :is(.customer-release-detail, .admin-release-detail) .release-items-table td:first-child,
            :is(.customer-release-detail, .admin-release-detail) .release-items-table td:last-child {
                width: 50%;
                text-align: left;
            }

            :is(.customer-release-detail, .admin-release-detail) .release-track-panel > .section-toggle-heading .section-toggle-btn,
            :is(.customer-release-detail, .admin-release-detail) .release-history-panel > .section-toggle-heading .section-toggle-btn {
                min-height: 58px;
                padding: 15px 16px;
                font-size: 1.08rem;
            }

            :is(.customer-release-detail, .admin-release-detail) #track-info-collapse,
            :is(.customer-release-detail, .admin-release-detail) #release-history-collapse {
                padding: 0 14px 16px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            :is(.customer-release-detail, .admin-release-detail) .section-toggle-icon,
            :is(.customer-release-detail, .admin-release-detail) .accordion-button {
                transition: none;
            }
        }
    </style>
@endpush
