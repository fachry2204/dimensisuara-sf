@extends('layouts.app')
@section('title')
Releases
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">My Releases</h4>
        <div class="btn-group">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#release-selector">Upload New Release</button>
            <button type="button" class="btn btn-sm btn-outline-primary split-bg-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"> 
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg-end"> 
                <a class="dropdown-item fs-6" href="{{ route('releases.create', 'single') }}">Upload a single</a>
                <a class="dropdown-item fs-6" href="{{ route('releases.create', 'album') }}">Upload an album</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
<script>document.addEventListener('DOMContentLoaded',function(){const tabs=document.querySelector('.nav-pills');const single=tabs?.querySelector('[href="#tabs-single"]')?.closest('li');if(single)tabs.prepend(single);const singleLink=tabs?.querySelector('[href="#tabs-single"]');if(singleLink){tabs.querySelectorAll('.nav-link').forEach(x=>x.classList.remove('active'));singleLink.classList.add('active');document.querySelectorAll('.tab-content>.tab-pane').forEach(x=>x.classList.remove('show','active'));document.querySelector('#tabs-single')?.classList.add('show','active');}});</script>
<section class="release-summary" aria-label="Ringkasan rilis"><article><span class="material-icons-outlined">layers</span><div><small>Total releases</small><strong>{{ count($releases) }}</strong></div></article><article><span class="material-icons-outlined release-live">radio_button_checked</span><div><small>Live</small><strong>{{ collect($releases)->where('status','released')->count() }}</strong></div></article><article><span class="material-icons-outlined release-attention">priority_high</span><div><small>Needs attention</small><strong>{{ count($rejected) + count($draft) }}</strong></div></article></section>

    <ul class="nav nav-pills mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" data-bs-toggle="pill" href="#tabs-album" role="tab" aria-selected="true">
                <div class="d-flex align-items-center">
                    <div class="tab-icon"><i class="material-icons-outlined me-1">album</i>
                    </div>
                    <div class="tab-title">Albums</div>
                </div>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="pill" href="#tabs-single" role="tab" aria-selected="false" tabindex="-1">
                <div class="d-flex align-items-center">
                    <div class="tab-icon"><i class="material-icons-outlined me-1">music_note</i>
                    </div>
                    <div class="tab-title">Singles</div>
                </div>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="pill" href="#tabs-rejected" role="tab" aria-selected="false" tabindex="-1">
                <div class="d-flex align-items-center">
                    <div class="tab-icon"><i class="material-icons-outlined me-1">cancel_presentation</i>
                    </div>
                    <div class="tab-title">Rejected</div>
                </div>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="pill" href="#tabs-all" role="tab" aria-selected="false" tabindex="-1">
                <div class="d-flex align-items-center">
                    <div class="tab-icon"><i class="material-icons-outlined me-1">all_inclusive</i>
                    </div>
                    <div class="tab-title">All Releases</div>
                </div>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="pill" href="#tabs-draft" role="tab" aria-selected="false" tabindex="-1">
                <div class="d-flex align-items-center">
                    <div class="tab-icon"><i class="material-icons-outlined me-1">description</i>
                    </div>
                    <div class="tab-title">Draft</div>
                </div>
            </a>
        </li>
    </ul>
    <div class="tab-content" id="tab-content">
        <div class="tab-pane fade show active" id="tabs-album" role="tabpanel">
            <table class="table albums" id="albums">
                <thead>
                    <tr>
                        <th>Album</th>
                        <th>Number of Tracks</th>
                        <th>Release Status</th>
                        <th>Release Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="tab-pane fade" id="tabs-single" role="tabpanel">
            <table class="table albums" id="singles">
                <thead>
                    <tr>
                        <th>Single</th>
                        <th>Release Status</th>
                        <th>Release Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="tab-pane fade" id="tabs-rejected" role="tabpanel">
            <div class="alert alert-danger">
                <div class="d-flex align-items-center">
                    <div class="ms-3">
                        All rejected releases will be deleted after 5 days from rejection date. Please make sure to submit your revision before deletion date.
                    </div>
                </div>
            </div>
            <table class="table albums" id="rejected">
                <thead>
                    <tr>
                        <th>Album/Single</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Will Be Deleted By</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="tab-pane fade" id="tabs-all" role="tabpanel">
            <table class="table albums" id="all">
                <thead>
                    <tr>
                        <th>Album</th>
                        <th>Number of Tracks</th>
                        <th>Release Status</th>
                        <th>Release Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="tab-pane fade" id="tabs-draft" role="tabpanel">
            <div class="alert alert-danger">
                <div class="d-flex align-items-center">
                    <div class="ms-3">
                        All draft releases will be deleted after 5 days from last update. Please make sure to submit your draft release before deletion date.
                    </div>
                </div>
            </div>
            <table class="table albums" id="albums-draft">
                <thead>
                    <tr>
                        <th>Album</th>
                        <th>Number of Tracks</th>
                        <th>Will Be Deleted By</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <div class="modal fade" id="release-delete-modal" aria-modal="true" role="dialog">
        <form id="release-delete-form" method="POST">
            @method('DELETE')
            @csrf
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Release</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this release?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
@push('modals')
    <div class="modal fade" id="release-selector" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-fullscreen release-picker-dialog">
            
            <div class="modal-content">
                <div class="release-picker-studio" aria-hidden="true">
                    <span class="studio-speaker studio-speaker-left"></span><span class="studio-speaker studio-speaker-right"></span>
                    <span class="studio-console"></span><span class="studio-wave studio-wave-one"></span><span class="studio-wave studio-wave-two"></span>
                    <i class="material-icons-outlined studio-note studio-note-one">music_note</i><i class="material-icons-outlined studio-note studio-note-two">queue_music</i>
                </div>
                <div class="modal-header release-picker-header" style="border: none">
                    <a href="javascript:;" class="primaery-menu-close ms-auto" data-bs-dismiss="modal">
                        <i class="material-icons-outlined">close</i>
                    </a>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-column text-center release-picker">
                        <span class="release-picker-kicker"><i class="material-icons-outlined">graphic_eq</i> RELEASE STUDIO</span>
                        <h4>Pilih tipe rilis</h4>
                        <p class="release-picker-subtitle">Mulai dengan format yang sesuai untuk musikmu.</p>
                        <div class="row justify-content-center release-picker-options">
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card border-primary release-type release-type-single" data-link="{{ route('releases.create', 'single') }}">
                                    <div class="card-body d-flex flex-column justify-content-center">
                                        <i class="material-icons-outlined release-type-icon">music_note</i>
                                        <p class="fw-bold fs-4">Single</p>
                                        <p>Untuk satu lagu</p><div class="release-type-detail"><span><i class="material-icons-outlined">music_note</i></span> 1 track</div><div class="release-type-detail"><span><i class="material-icons-outlined">bolt</i></span> Rilis lebih cepat</div><button type="button" class="btn release-type-cta">Pilih Single <i class="material-icons-outlined">arrow_forward</i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="card border-primary release-type release-type-album" data-link="{{ route('releases.create', 'album') }}">
                                    <div class="card-body d-flex flex-column justify-content-center">
                                        <i class="material-icons-outlined release-type-icon">album</i>
                                        <p class="fw-bold fs-4">EP / Album</p>
                                        <p>Untuk dua lagu atau lebih</p><div class="release-type-detail"><span><i class="material-icons-outlined">queue_music</i></span> 2+ track</div><div class="release-type-detail"><span><i class="material-icons-outlined">library_music</i></span> Kelola satu proyek rilis</div><button type="button" class="btn release-type-cta">Pilih EP / Album <i class="material-icons-outlined">arrow_forward</i></button>
                                    </div>
                                </div>
                            </div>
                        </div><p class="release-picker-step">● ─ ● ─ ● ─ ●<br><span>Langkah 1 dari 4 — Tipe rilis</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
@endpush
@push('css')
    <style>.release-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:-4px 0 22px}.release-summary article{display:flex;align-items:center;gap:14px;min-height:82px;padding:18px 22px;border-radius:12px}.release-summary article:nth-child(1){background:#132641;border:1px solid #315a8e}.release-summary article:nth-child(2){background:#12382f;border:1px solid #287a60}.release-summary article:nth-child(3){background:#423215;border:1px solid #80611d}.release-summary span{display:grid;place-items:center;width:42px;height:42px;border-radius:10px;background:#20344d;color:#82b8ff}.release-summary .release-live{background:#0d5946;color:#51dfa3}.release-summary .release-attention{background:#65450c;color:#ffc34d}.release-summary small{display:block;color:#c3d2e4;font-size:12px}.release-summary strong{display:block;color:#f8fafc;font-size:24px;line-height:1.1}@media(max-width:700px){.release-summary{grid-template-columns:1fr}}</style>
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .release-type {
            cursor: pointer;
        }
        img.cover {
            object-fit: cover
        }
        .release-type {
            transition: background-color 0.3s, color 0.3s;
        }
        .release-type:hover {
            background-color: #f5f5f5;
            color: black;
        }
        .release-picker-dialog .modal-content { background:#0b1220; color:#f8fafc; }
        .release-picker-header { padding:28px 36px; }
        .release-picker-header .primaery-menu-close { display:grid; place-items:center; width:48px; height:48px; border-radius:50%; background:#17263a; color:#aac0db; }
        .release-picker { max-width:1080px; margin:0 auto; padding:10px 24px 48px; }
        .release-picker-brand { align-self:flex-start; color:#ff8050; font-size:13px; font-weight:800; letter-spacing:.16em; }
        .release-picker h4 { margin:24px 0 8px; font-size:44px; font-weight:800; color:#f8fafc; }
        .release-picker-subtitle { color:#9eb7d6; font-size:19px; margin-bottom:34px; }
        .release-picker-options { width:100%; margin:0; gap:22px; }
        .release-picker-options > div { flex:1 1 0; max-width:440px; padding:0; }
        .release-picker .release-type { min-height:510px; padding:28px; border-radius:18px; border:1px solid #345274 !important; background:#111f33; color:#f4f8fc; transition:transform .2s,border-color .2s; }
        .release-picker .release-type:hover { transform:translateY(-4px); background:#152842; color:#fff; }
        .release-picker .release-type-single { border-color:#ff7043 !important; background:#ff7043; }
        .release-picker .release-type-icon { display:grid; place-items:center; width:112px; height:112px; margin:4px auto 24px; border-radius:50%; background:rgba(5,16,32,.22); color:#fff; font-size:62px; }
        .release-picker .release-type-album .release-type-icon { color:#8eb8ff; background:#0b1627; }
        .release-picker .release-type .fs-4 { margin:0 0 4px; font-size:32px !important; }
        .release-picker .release-type .fs-4 + p { margin-bottom:28px; color:inherit; opacity:.88; font-size:16px; }
        .release-type-detail { display:flex; align-items:center; gap:12px; margin:14px 0; text-align:left; font-size:15px; }
        .release-type-detail span { display:grid; place-items:center; width:30px; height:30px; border-radius:50%; background:rgba(5,16,32,.18); font-weight:800; }
        .release-type-cta { width:100%; margin-top:28px; padding:13px; border:1px solid #91b8ef; border-radius:10px; color:#a7c7ff; font-weight:800; }
        .release-type-single .release-type-cta { background:#fff; border-color:#fff; color:#ff7043; }
        .release-picker-step { margin:32px 0 0; color:#466184; letter-spacing:5px; font-size:15px; }.release-picker-step:first-letter{color:#ff7043}.release-picker-step span{letter-spacing:0;color:#8fa7c5;font-size:13px;}
        .release-picker-dialog .modal-content { min-height:100vh; overflow:hidden; }
        .release-picker-header { padding:16px 28px; }
        .release-picker { padding:0 24px 20px; }
        .release-picker h4 { margin:8px 0 4px; font-size:34px; }
        .release-picker-subtitle { margin-bottom:18px; font-size:16px; }
        .release-picker .release-type { min-height:420px; padding:20px 26px; }
        .release-picker .release-type-icon { width:82px; height:82px; margin:0 auto 14px; font-size:46px; }
        .release-picker .release-type .fs-4 { font-size:26px !important; }
        .release-picker .release-type .fs-4 + p { margin-bottom:18px; font-size:14px; }
        .release-type-detail { margin:10px 0; font-size:14px; }
        .release-type-detail span { width:26px; height:26px; }
        .release-type-cta { margin-top:16px; padding:10px; }
        .release-picker-step { margin:16px 0 0; font-size:12px; }
        .release-picker-options { max-width:860px; margin:0 auto !important; gap:18px; }
        .release-picker-options > div { max-width:400px; }
        .release-picker .release-type { min-height:365px; padding:18px 24px; border-width:1px !important; box-shadow:0 14px 0 rgba(2,8,20,.72),0 24px 34px rgba(0,0,0,.3); transform:translateY(0); }
        .release-picker .release-type:hover { transform:translateY(-6px); box-shadow:0 20px 0 rgba(2,8,20,.72),0 32px 40px rgba(0,0,0,.38); }
        .release-picker .release-type-single { background:#e85a38; border-color:#ff956f !important; box-shadow:inset 0 1px 0 rgba(255,255,255,.3),0 14px 0 #74301f,0 24px 34px rgba(0,0,0,.3); }
        .release-picker .release-type-album { background:#172c55; border-color:#5d91e7 !important; box-shadow:inset 0 1px 0 rgba(167,201,255,.24),0 14px 0 #091a36,0 24px 34px rgba(0,0,0,.3); }
        .release-picker .release-type-icon { width:70px; height:70px; margin-bottom:12px; font-size:38px; box-shadow:inset 0 2px 4px rgba(255,255,255,.12),0 7px 0 rgba(4,12,26,.3); }
        .release-picker .release-type .fs-4 { font-size:24px !important; }.release-picker .release-type .fs-4 + p{margin-bottom:14px}.release-type-detail{margin:8px 0}.release-type-cta{margin-top:12px;padding:9px;box-shadow:0 4px 0 rgba(4,12,26,.25)}
        .release-picker-dialog .modal-content{position:relative;isolation:isolate;background:linear-gradient(145deg,#081225 0%,#0a1629 46%,#0b1220 100%)}.release-picker-header,.release-picker-dialog .modal-body{position:relative;z-index:1}.release-picker-studio{position:absolute;inset:0;z-index:-1;overflow:hidden;pointer-events:none;opacity:.48;background:radial-gradient(circle at 50% 42%,rgba(64,125,187,.2),transparent 26%),radial-gradient(circle at 10% 82%,rgba(255,112,67,.09),transparent 27%),linear-gradient(115deg,transparent 0 48%,rgba(112,166,230,.035) 48% 49%,transparent 49% 100%)}.studio-speaker{position:absolute;width:170px;height:170px;border:20px solid rgba(95,150,216,.14);border-radius:50%;box-shadow:inset 0 0 0 34px rgba(4,13,27,.3),0 0 0 14px rgba(105,164,233,.035)}.studio-speaker:after{position:absolute;inset:43px;border-radius:50%;background:rgba(134,185,242,.16);content:""}.studio-speaker-left{top:17%;left:-55px}.studio-speaker-right{right:-55px;bottom:16%}.studio-console{position:absolute;bottom:-11vh;left:22%;width:56%;height:29vh;border:1px solid rgba(105,161,225,.18);border-radius:18px 18px 0 0;background:repeating-linear-gradient(90deg,rgba(111,169,231,.11) 0 2px,transparent 2px 9%),repeating-linear-gradient(0deg,rgba(111,169,231,.09) 0 1px,transparent 1px 25px);box-shadow:0 -12px 42px rgba(65,129,200,.09),inset 0 1px 0 rgba(162,207,250,.12);transform:perspective(460px) rotateX(58deg)}.studio-wave{position:absolute;width:42vw;height:16vw;border:2px solid rgba(105,164,233,.12);border-radius:50%;transform:rotate(-18deg)}.studio-wave-one{top:10%;left:14%}.studio-wave-two{right:7%;top:35%;transform:rotate(23deg) scale(.8)}.studio-note{position:absolute;color:rgba(124,175,238,.2);font-size:58px}.studio-note-one{top:24%;right:20%;transform:rotate(14deg)}.studio-note-two{bottom:22%;left:18%;transform:rotate(-18deg)}.release-picker-kicker{display:inline-flex;align-items:center;justify-content:center;gap:7px;margin-top:3px;color:#89b8f4;font-size:.69rem;font-weight:800;letter-spacing:.17em}.release-picker-kicker i{color:#ff8a65;font-size:18px}.release-picker .release-type{position:relative;overflow:hidden}.release-picker .release-type:before{position:absolute;top:-70%;right:-30%;width:75%;height:130%;border-radius:50%;background:linear-gradient(130deg,rgba(255,255,255,.14),transparent 60%);content:"";pointer-events:none}.release-picker .card-body{position:relative;z-index:1}.release-type-detail span i{font-size:15px}.release-type-cta{display:inline-flex!important;align-items:center;justify-content:center;gap:8px}.release-type-cta i{font-size:17px}.release-picker-header .primaery-menu-close{box-shadow:0 7px 18px rgba(0,0,0,.22);transition:transform .2s,background .2s}.release-picker-header .primaery-menu-close:hover{background:#ff7043;color:#fff;transform:rotate(90deg)}
        .release-picker-studio{z-index:0}
        .albums td {
            vertical-align: middle;
            width: fit-content;
        }
        .albums td .actions {
            display: flex;
            flex-wrap: nowrap;
            justify-content: end;
        }
        .albums td .actions a {
            flex: 1 1 45%;
            box-sizing: border-box;
            padding: 10px;
            margin: 0px 5px;
        }
        /* Releases workspace — preserves the existing tabs, DataTables, filters and actions. */
        .sf-app .main-wrapper .main-content { max-width: 1440px !important; padding: 2rem !important; background: #0b1220 !important; }
        .main-content h4 { color: #f8fafc; font-size: 34px !important; font-weight: 750; letter-spacing: -.5px; }
        .btn-group .btn-primary { background: #ff7043; border-color: #ff7043; border-radius: 9px 0 0 9px; font-weight: 700; padding: 10px 15px; }
        .btn-group .split-bg-primary { background: #ff7043; border-color: #ff7043; color: #fff; border-radius: 0 9px 9px 0; }
        .nav-pills { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin: 0 0 22px !important; }
        .nav-pills .nav-link { display: flex; align-items: center; min-height: 64px; padding: 0 18px; color: #aec1d5; background: #111d2f; border: 1px solid #29415e; border-radius: 11px; font-weight: 700; }
        .nav-pills .nav-link.active { color: #fff; background: #2b2224; border-color: #ff7043; box-shadow: inset 0 0 0 1px #ff7043; }
        .nav-pills .tab-icon { display: inline-flex; margin-right: 9px; font-size: 22px; color: #ff7043; }
        .nav-pills .nav-link:not(.active) .tab-icon { color: #77a8ff; }
        .nav-pills .tab-title { font-size: 14px; }
        .tab-content { background: #111d2f; border: 1px solid #29415e; border-radius: 13px; padding: 18px; }
        .tab-content .dataTables_filter input, .tab-content .dataTables_length select { background: #0b1220; border: 1px solid #36506e; color: #edf4fb; border-radius: 8px; }
        .tab-content table.dataTable { margin-top: 14px !important; color: #eaf2fb; border-collapse: separate; border-spacing: 0; }
        .tab-content table.dataTable thead th { background: #1c3049; color: #aec1d5; border-bottom: 0; font-size: 11px; letter-spacing: .04em; padding: 13px 12px; }
        .tab-content table.dataTable tbody td { background: transparent; border-top: 1px solid #29415e; color: #eaf2fb; padding: 13px 12px; vertical-align: middle; }
        .tab-content table.dataTable tbody tr:hover td { background: #17263a; }
        .tab-content .text-muted { color: #91a7c0 !important; }
        .tab-content .btn-outline-primary { color: #ff8050; border-color: #ff8050; border-radius: 7px; }
        .tab-content .btn-outline-primary:hover { background: #ff7043; border-color: #ff7043; color: #fff; }
        .tab-content .alert-danger { background: #3b2027; border: 1px solid #7b3342; color: #fecdd3; border-radius: 9px; }
        @media (max-width: 900px) { .nav-pills { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 520px) { .sf-app .main-wrapper .main-content { padding: 18px !important; } .nav-pills { grid-template-columns: 1fr; } .tab-content { overflow-x: auto; } }
        @media (max-width: 768px) {
            #albums td .actions a {
                flex: 1 1 100%;
            }
        }
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        function deleteRelease(id) {
            $('#release-delete-form').attr('action', "{{ route('releases.destroy', ':id') }}".replace(':id', id));
            $('#release-delete-modal').modal('show');
        }

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

        $(document).ready(function() {
            $('.release-type').click(function() {
                window.location.href = $(this).data('link');
            });

            $('#albums').DataTable({
                responsive: true,
                autoWidth: false,
                columnDefs: [
                    { responsivePriority: 1, targets: 0 },
                    { responsivePriority: 2, targets: -1 },
                    { responsivePriority: 3, targets: -2 },
                    { responsivePriority: 3, targets: -3 },
                ],
                data: @json($albums),
                columns: [
                    { render: function(data, type, row) {
                        var cover = "/build/images/album-placeholder.webp";

                        if(row.thumbnail_url) {
                            cover = row.thumbnail_url;
                        }
                        
                        var artist = '-';
                        if(row.primaryartists?.[0]) {
                            artist = row.primaryartists?.[0]?.name ?? (row.primaryartists?.[0]?.name ?? row.primaryartists?.[0]);
                        }
                        var title = row.title ?? 'Untitled Album'
                        return `
                            <div class="d-flex flex-row align-items-center">
                                <img class="cover" src="${cover}" width="50" height="50">
                                <div class="ms-3">
                                    <p class="fw-bold mb-1">${title}</p>
                                    <p class="text-muted mb-0">${artist}</p>
                                </div>
                            </div>
                        `;
                    }},
                    { data: "tracknum" },
                    { render: function(data, type, row) {
                        return badge_release(row.status);
                    }},
                    { render: function(data, type, row) {
                        return row.releaseon ? new Date(row.releaseon).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' }) : '-';
                    }},
                    { render: function(data, type, row) {
                        var link = "{{ route('releases.show', ':id') }}".replace(':id', row.id);
                        return `<div class="actions"><a href="${link}" class="btn btn-sm btn-outline-primary">View</a>`;
                    }}
                ],
            });

            $('#singles').DataTable({
                responsive: true,
                autoWidth: false,
                columnDefs: [
                    { responsivePriority: 1, targets: 0 },
                    { responsivePriority: 2, targets: -1 },
                    { responsivePriority: 3, targets: -2 },
                    { responsivePriority: 3, targets: -3 },
                ],
                data: @json($singles),
                columns: [
                    { render: function(data, type, row) {
                        var cover = "/build/images/album-placeholder.webp";
                        if(row.thumbnail_url) {
                            cover = row.thumbnail_url;
                        }

                        var artist = '-';
                        if(row.primaryartists?.[0]) {
                            artist = row.primaryartists?.[0]?.name ?? (row.primaryartists?.[0]?.name ?? row.primaryartists?.[0]);
                        }
                        var title = row.title ?? 'Untitled Album'
                        return `
                            <div class="d-flex flex-row align-items-center">
                                <img class="cover" src="${cover}" width="50" height="50">
                                <div class="ms-3">
                                    <p class="fw-bold mb-1">${title}</p>
                                    <p class="text-muted mb-0">${artist}</p>
                                </div>
                            </div>
                        `;
                    }},
                    { render: function(data, type, row) {
                        return badge_release(row.status);
                    }},
                    { render: function(data, type, row) {
                        return row.releaseon ? new Date(row.releaseon).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' }) : '-';
                    }},
                    { render: function(data, type, row) {
                        var link = "{{ route('releases.show', ':id') }}".replace(':id', row.id);
                        return `<div class="actions"><a href="${link}" class="btn btn-sm btn-outline-primary">View</a>`;
                    }}
                ],
            });

            $('#rejected').DataTable({
                responsive: true,
                autoWidth: false,
                columnDefs: [
                    { responsivePriority: 1, targets: 0 },
                    { responsivePriority: 2, targets: -1 },
                    { responsivePriority: 3, targets: -2 },
                ],
                data: @json($rejected),
                columns: [
                    { render: function(data, type, row) {
                        var cover = "/build/images/album-placeholder.webp";
                        if(row.thumbnail_url) {
                            cover = row.thumbnail_url;
                        }

                        var artist = '-';
                        if(row.primaryartists?.[0]) {
                            artist = row.primaryartists?.[0]?.name ?? (row.primaryartists?.[0]?.name ?? row.primaryartists?.[0]);
                        }
                        var title = row.title ?? 'Untitled Album'
                        return `
                            <div class="d-flex flex-row align-items-center">
                                <img class="cover" src="${cover}" width="50" height="50">
                                <div class="ms-3">
                                    <p class="fw-bold mb-1">${title}</p>
                                    <p class="text-muted mb-0">${artist}</p>
                                </div>
                            </div>
                        `;
                    }},
                    { render: function(data, type, row) {
                        return row.type == 'album' ? 'Album' : 'Single';
                    }},
                    { data: "reason" },
                    { render: function(data, type, row) {
                        return row.delete_by ? new Date(row.delete_by).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' }) : '-';
                    }},
                    { render: function(data, type, row) {
                        var link = "{{ route('releases.edit', ':id') }}".replace(':id', row.id);
                        return `<div class="actions"><a href="${link}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="javascript:void(0)" class="btn btn-sm btn-outline-danger" onclick="deleteRelease(${row.id})">Delete</a></div>`;
                    }}
                ],
            });

            $('#all').DataTable({
                responsive: true,
                autoWidth: false,
                columnDefs: [
                    { responsivePriority: 1, targets: 0 },
                    { responsivePriority: 2, targets: -1 },
                    { responsivePriority: 3, targets: -2 },
                    { responsivePriority: 3, targets: -3 },
                ],
                data: @json($releases),
                columns: [
                    { render: function(data, type, row) {
                        var cover = "/build/images/album-placeholder.webp";
                        if(row.thumbnail_url) {
                            cover = row.thumbnail_url;
                        }

                        var artist = '-';
                        if(row.primaryartists?.[0]) {
                            artist = row.primaryartists?.[0]?.name ?? (row.primaryartists?.[0]?.name ?? row.primaryartists?.[0]);
                        }

                        var title = row.title ?? 'Untitled Album'
                        return `
                            <div class="d-flex flex-row align-items-center">
                                <img class="cover" src="${cover}" width="50" height="50">
                                <div class="ms-3">
                                    <p class="fw-bold mb-1">${title}</p>
                                    <p class="text-muted mb-0">${artist}</p>
                                </div>
                            </div>
                        `;
                    }},
                    { data: "tracknum" },
                    { render: function(data, type, row) {
                        return badge_release(row.status);
                    }},
                    { render: function(data, type, row) {
                        return row.releaseon ? new Date(row.releaseon).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' }) : '-';
                    }},
                    { render: function(data, type, row) {
                        var link = "{{ route('releases.show', ':id') }}".replace(':id', row.id);
                        return `<div class="actions"><a href="${link}" class="btn btn-sm btn-outline-primary">View</a>`;
                    }}
                ],
            });

            $('#albums-draft').DataTable({
                responsive: true,
                autoWidth: false,
                columnDefs: [
                    { responsivePriority: 1, targets: 0 },
                    { responsivePriority: 2, targets: -1 },
                    { responsivePriority: 3, targets: -2 },
                ],
                data: @json($draft),
                columns: [
                    { render: function(data, type, row) {
                        var cover = "/build/images/album-placeholder.webp";
                        if(row.thumbnail_url) {
                            cover = row.thumbnail_url;
                        }

                        var artist = '-';
                        if(row.primaryartists?.[0]) {
                            artist = row.primaryartists?.[0]?.name ?? (row.primaryartists?.[0]?.name ?? row.primaryartists?.[0]);
                        }
                        
                        var title = row.title ?? 'Untitled Album'
                        return `
                            <div class="d-flex flex-row align-items-center">
                                <img class="cover" src="${cover}" width="50" height="50">
                                <div class="ms-3">
                                    <p class="fw-bold mb-1">${title}</p>
                                    <p class="text-muted mb-0">${artist}</p>
                                </div>
                            </div>
                        `;
                    }},
                    { data: "tracknum" },
                    { render: function(data, type, row) {
                        return row.delete_by ? new Date(row.delete_by).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' }) : '-';
                    }},
                    { render: function(data, type, row) {
                        var link = "{{ route('releases.edit', ':id') }}".replace(':id', row.id);
                        return `<div class="actions"><a href="${link}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="#" class="btn btn-sm btn-outline-danger" onclick="deleteRelease(${row.id})">Delete</a></div>`;
                    }}
                ],
            });
        });
    </script>
@endpush
