@extends('layouts.app')
@section('title')
    @if ($release->id)
        Edit Release
    @else
        Create a Release
    @endif
@endsection
@section('header')
    <div class="admin-release-editor-header d-flex flex-row align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.releases.index') }}">All Releases</a></li>
                    @if ($release->id)
                        <li class="breadcrumb-item"><a href="{{ route('admin.releases.show', ['id' => $release->id]) }}">View
                    Release</a></li>@endif
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>
            <h4 class="mb-0 font-weight-bold fs-2">@if ($release->id) Edit Release @else Create a Release @endif</h4>
        </div>
    </div>
@endsection
@section('content')

    {{-- Release Form --}}
    <form
        action="@if($release->id){{ route('admin.releases.update', $release->id) }}@else{{ route('admin.releases.store') }}@endif"
        id="release-form" method="POST" x-data="$store.release" enctype="multipart/form-data" class="admin-release-editor">
        @csrf
        @if($release->id)
            @method('PUT')
        @endif
        <div class="admin-release-stepper" role="tablist" aria-label="Release form steps">
            <button type="button" class="admin-release-step is-active" data-admin-step="0"><span>1</span><b>Cover</b><small>Artwork</small></button>
            <button type="button" class="admin-release-step" data-admin-step="1"><span>2</span><b>Release Info</b><small>Metadata</small></button>
            <button type="button" class="admin-release-step" data-admin-step="2"><span>3</span><b>Release Date</b><small>Schedule</small></button>
            <button type="button" class="admin-release-step" data-admin-step="3"><span>4</span><b>Tracks</b><small>Audio & tracks</small></button>
        </div>
        <div class="admin-release-step-actions">
            <button type="button" class="btn admin-step-previous" disabled><i class="material-icons-outlined">arrow_back</i> Previous</button>
            <button type="button" class="btn admin-step-next">Next <i class="material-icons-outlined">arrow_forward</i></button>
        </div>
        <div class="card">
            <div class="card-header p-3">
                <h6 class="card-title mb-0 fw-bold">User Assignment</h6>
            </div>
            <div class="card-body admin-assignment-grid">
                @php
                    $profiles = \App\Models\User\Profile::all()->sortBy('full_name');
                @endphp
                <div class="form-group mb-3 assignment-field">
                    <label for="profileid" class="form-label">Assign User</label>
                    <p>Select the user that will be assigned to this release</p>
                    <select name="profileid" id="profileid" class="form-select profile-search-select" required>
                        @foreach ($profiles as $profile)
                            <option value="{{ $profile->id }}" @if($profile->id == old('profileid', $release->profileid)) selected
                            @endif>{{ $profile->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-3 assignment-field">
                    <label for="status" class="form-label">Release Status</label>
                    <p>Giving the release a status of "Draft" or "Rejected" will let the system delete the release if the
                        user does not complete the release in 5 days.</p>
                    <select name="status" id="status" class="form-select">
                        @foreach (['draft' => 'Draft', 'pending' => 'Pending', 'revision' => 'Revision', 'review' => 'Under Review', 'uploading' => 'Uploading', 'rejected' => 'Rejected', 'released' => 'Released'] as $code => $name)
                            <option value="{{ $code }}" @if(old('status', $release->status) == $code) selected @endif>{{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-3 assignment-field">
                    <label for="aggregator" class="form-label">Aggregator</label>
                    <p>Please select an aggregator to review this release:</p>
                    <select class="form-select" name="aggregator" id="aggregator">
                        @include('admin.aggregator.partials.aggregator-options', [
                            'selectedAggregator' => old('aggregator', $release->aggregator),
                            'legacyAggregator' => $release->exists ? $release->aggregator : null,
                        ])
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header p-3">
                <h6 class="card-title mb-0 fw-bold">Album/Single Cover</h6>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <input type="file" name="cover" id="cover-input" style="display: none" accept="image/jpeg"
                        @change="$store.release.methods.uploadCover()">
                    <div class="d-flex flex-column flex-md-row">
                        <div class="text-center p-2" id="cover-input-element">
                            <label for="cover-input">
                                <img class="album-cover" :src="$store.release.coverImage" id="cover-placeholder"
                                    alt="Album Cover Input" style="height: 250px; width: 250px">
                            </label>
                            <div class="mt-2"
                                x-show="$store.release.coverImage != '{{URL::asset('build/images/album-placeholder.webp')}}'">
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    @click="$store.release.methods.removeCover()">Remove Cover</button>
                            </div>
                        </div>
                        <div class="p-2 px-md-3">
                            <p>
                                Cover art must be a square .jpg or .jpeg file with a resolution of 3000x3000 px, not blurry
                                or pixelated.
                            </p>
                            <span>
                                <strong>
                                    It CANNOT contain:
                                </strong>
                                <ul>
                                    <li>Any text that is not included in and identical to the release data (artist names,
                                        track/release title, label name, etc).</li>
                                    <li>Brand Logos, social media logos or handles</li>
                                    <li>Contact information, store name or address, advertisements of any kind, etc.</li>
                                </ul>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header p-3">
                <h6 class="card-title mb-0 fw-bold">Album/Single Details</h6>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label for="type" class="form-label">Release Type</label>
                    <select name="type" id="type" class="form-select" x-model="$store.release.type" required>
                        <option value="album">Album</option>
                        <option value="single">Single</option>
                    </select>
                </div>
                <template x-if="$store.release.type === 'single'">
                    <div>
                        @include('partials.single-step1-fields', ['allowIdentifierEntry' => true])
                    </div>
                </template>
                <template x-if="$store.release.type !== 'single'">
                    <div>
                        @include('partials.album-step1-fields')
                    </div>
                </template>
            </div>
        </div>

        <div class="card" x-show="$store.release.type !== 'single'">
            <div class="card-header p-3">
                <h6 class="card-title mb-0 fw-bold">Tracks Details</h6>
            </div>
            <div class="card-body">
                <div>
                    <p class="text-center">You can list your tracks here. For release type single, you can only add one
                        track.
                        On submission, the type will be automatically changed to album if you have more than one track.</p>
                    <template x-for="(track, index) in $store.release.tracks">
                        <div class="input-group mb-2">
                            <button type="button"
                                class="input-group-text d-flex flex-row flex-grow-1 btn btn-sm btn-outline-primary"
                                @click="$store.release.methods.editTrack(index)">
                                <span class="p-2 text-lg fw-bold" x-text="index + 1"></span>
                                <div class="d-flex flex-column flex-lg-row p-2 text-start flex-grow-1">
                                    <span class="flex-grow-1" x-text="track.title ?? 'Untitled Track'"></span>
                                    <span class="" x-text="$store.release.methods.humanizeDuration(track.duration)"></span>
                                </div>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                @click="$store.release.methods.moveUp(index, 'tracks')"><i
                                    class="bx bx-chevron-up"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                @click="$store.release.methods.moveDown(index, 'tracks')"><i
                                    class="bx bx-chevron-down"></i></button>
                            <button type="button" class="btn btn-outline-danger"
                                @click="$store.release.methods.remove(index, 'tracks')"><i class="bx bx-x"></i></button>
                        </div>
                    </template>
                    <div class="text-center mb-3">
                        <span>Total duration: </span>
                        <span x-text="$store.release.methods.totalDuration" id="totalDuration"></span>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-outline-primary" id="add-track"
                            @click="$store.release.methods.addTrack()">Add Track</button>
                    </div>
                </div>
                <input type="hidden" name="tracks_json" id="tracks-json-input">
            </div>
        </div>

        <div class="card">
            <div class="card-header p-3">
                <h6 class="card-title mb-0 fw-bold">Release Date Details</h6>
            </div>
            <div class="card-body">
                <div class="form-group mb-4 release-history-choice">
                    <p class="form-label mb-2">Has this album already been released outside this system?</p>
                    <div class="release-choice-grid">
                    <div class="form-check release-choice-card">
                        <input class="form-check-input" type="radio" name="released" value="0" id="released-no" @checked((string) old('released', $release->released ?? 0) === '0')
                            x-model="$store.release.released" required>
                        <label class="form-check-label" for="released-no">No, this is a new release</label>
                    </div>
                    <div class="form-check release-choice-card">
                        <input class="form-check-input" type="radio" name="released" value="1" id="released-yes" @checked((string) old('released', $release->released ?? 0) === '1')
                            x-model="$store.release.released">
                        <label class="form-check-label" for="released-yes">Yes, this album has already been released</label>
                    </div>
                    </div>
                </div>
                <fieldset class="release-original-date mb-4" x-show="$store.release.released == 1" :disabled="$store.release.released == 0">
                    <div class="form-group mb-0">
                        <label for="releaseddate" class="form-label">Original Release Date</label>
                        <input type="date" class="form-control" name="releaseddate" id="releaseddate"
                            x-model="$store.release.releaseddate" required>
                    </div>
                </fieldset>
                <div class="form-group mb-3">
                    <label class="form-label" for="upc">UPC</label>
                    <input type="text" class="form-control" name="upc" id="upc" x-model="$store.release.upc"
                        :required="$store.release.released == 1" maxlength="255" placeholder="Masukkan UPC rilis jika sudah tersedia">
                    <small class="text-muted">Admin dapat mengisi UPC untuk rilis baru maupun rilis yang sudah terbit.</small>
                </div>
                <div class="form-group mb-3">
                    <label for="releaseon" class="form-label">Planned Release Date</label>
                    <input type="date" class="form-control" name="releaseon" id="releaseon"
                        @change="$store.release.methods.determinePreRelease()" x-model="$store.release.releaseon" required>
                </div>
                <div class="form-group mb-3">
                    <label for="tiktok_ytmusic_rerelease_date" class="form-label">TikTok & YouTube Music Pre-Release Date
                        (Optional)</label>
                    <p>Pre-release date should between 1 to 7 days prior to planned release date.</p>
                    <input type="date" class="form-control" name="tiktok_ytmusic_rerelease_date"
                        id="tiktok_ytmusic_rerelease_date" x-model="$store.release.tiktok_ytmusic_rerelease_date">
                </div>
            </div>
        </div>

        <div id="release-validation-feedback" class="alert alert-danger d-none" role="alert" aria-live="polite"></div>
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Save Release</button>
        </div>
    </form>
@endsection
@push('modals')
    {{-- Track Modal --}}
    <div x-data="$store.tray">
        <div class="modal fade" id="addTrack" aria-modal="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable track-editor-dialog">
                <div class="modal-content track-editor-modal">
                    <div class="modal-header py-3">
                        <h5 class="modal-title" x-show="$store.tray.trackTrayMode == 'add'">Add Track</h5>
                        <h5 class="modal-title" x-show="$store.tray.trackTrayMode == 'edit'">Edit Track</h5>

                        <a href="javascript:;" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body track-editor-body" data-simplebar="true">
                        <div class="form-body">
                            <form action="#" id="track-form"
                                @submit.prevent="$store.tray.trackTrayMode == 'add' ? $store.tray.methods.addTrack() : $store.tray.methods.updateTrack($store.tray.trackTray.index)">
                                @include('partials.album-track-form-fields', ['allowIdentifierEntry' => true])
                            </form>
                        </div>
                    </div>
                    <div class="modal-footer track-editor-footer">
                        <div class="d-md-flex d-grid align-items-center gap-3">
                            <button type="submit" form="track-form" class="btn btn-primary px-4 ms-auto"
                                x-show="$store.tray.trackTrayMode == 'add'">Add Track</button>
                            <button type="submit" form="track-form" class="btn btn-primary px-4 ms-auto"
                                x-show="$store.tray.trackTrayMode == 'edit'">Update Track</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div :class="'overlay-custom fade' + ($store.tray.overlayActive ? ' show' : '')">
            <div class="d-flex align-items-center justify-content-center h-100 flex-column">
                <div class="spinner-border text-primary" style="width: 6rem; height: 6rem">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="ms-2">
                    <p class="mb-0 text-light text-center" x-text="$store.tray.overlayText"></p>
                    <p class="mb-0 text-light text-center" x-show="$store.tray.overlayProgress > 0"
                        x-text="$store.tray.overlayProgress + '%'"></p>
                </div>
            </div>
        </div>
    </div>
@endpush
@push('css')
    <link href="{{ URL::asset('build/css/album.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/plugins/notifications/css/lobibox.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <style>
        .album-cover {
            width: 100%;
            height: 100%;
            max-height: 250px;
            max-width: 250px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
        }

        .album-cover:hover {
            border: solid 1px black;
        }

        .track-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            height: 100%;
            border-style: dashed;
            border-radius: 16px;
            margin: 5px;
            padding: 10px;
        }

        .track-placeholder:hover {
            cursor: pointer;
            background-color: rgba(0, 0, 0, 0.1);
        }

        html[data-bs-theme='dark'] .track-placeholder:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }

        .track-table tbody tr td::first-child {
            text-align: center;
        }

        html[data-bs-theme='semi-dark'] .form-label {
            color: black;
            font-weight: bold;
        }

        html[data-bs-theme='dark'] .form-label {
            color: white;
        }

        .bs-stepper-pane.content {
            min-height: 45vh;
        }

        .bs-stepper-pane.content.dstepper-block {
            display: flex !important;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        table.album-items {
            width: fit-content;
        }

        .album-items td:first-child {
            color: var(--bs-secondary-color) !important;
            padding-left: 0px;
            white-space: nowrap;
            font-size: 1em;
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

        .overlay-custom {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            transition: opacity 0.3s ease-in-out;
            display: none;
        }

        .overlay-custom.show {
            display: block;
        }

        .admin-release-editor-header .breadcrumb-item a{color:#79b9ff}.admin-release-editor-header h4{color:#f7fbff;letter-spacing:-.035em}.admin-release-editor{max-width:1240px;margin:0 auto}.admin-release-editor>.card{overflow:hidden;margin-bottom:22px;border:1px solid #285176;border-radius:14px;background:linear-gradient(135deg,#10243d,#0b2036);box-shadow:0 16px 32px rgba(0,0,0,.14)}.admin-release-editor>.card>.card-header{padding:18px 22px!important;border-bottom:1px solid rgba(60,104,143,.65)!important;background:rgba(11,31,52,.62)}.admin-release-editor>.card>.card-header .card-title{color:#f4f9ff;font-size:1.06rem}.admin-release-editor>.card>.card-body{padding:22px}.admin-release-editor .form-label{color:#c4dcf3!important;font-size:.78rem;font-weight:800}.admin-release-editor .form-group>p{margin:-3px 0 8px;color:#8facca;font-size:.78rem}.admin-release-editor .form-control,.admin-release-editor .form-select{min-height:42px;border-color:#3b668c;background:#091a2d;color:#edf6ff;border-radius:8px}.admin-release-editor .form-control:focus,.admin-release-editor .form-select:focus{border-color:#68aefa;box-shadow:0 0 0 .2rem rgba(83,164,255,.14)}.admin-release-editor .album-cover{border:1px solid #4c83ad;border-radius:11px;box-shadow:0 13px 25px rgba(0,0,0,.25)}.admin-release-editor .track-placeholder{min-height:94px;border:1px dashed #4b8bc2;background:rgba(17,49,80,.48);color:#b9d8f6}.admin-release-editor .input-group{overflow:hidden;border-radius:8px}.admin-release-editor .input-group .btn{border-color:#416b91;background:#102b47;color:#d5e8fa}.admin-release-editor .input-group .btn:hover{background:#1b4166;color:#fff}@media(min-width:992px){.admin-release-editor>.card>.card-body>.form-group:has(#profileid),.admin-release-editor>.card>.card-body>.form-group:has(#status),.admin-release-editor>.card>.card-body>.form-group:has(#aggregator){display:inline-block;width:calc(33.333% - 12px);vertical-align:top;margin-right:8px}}@media(max-width:767px){.admin-release-editor>.card>.card-body{padding:17px}.admin-release-editor-header{align-items:flex-start!important;flex-direction:column}.admin-release-editor .album-cover{max-width:220px;max-height:220px}}
        .track-editor-dialog{max-width:1080px}.track-editor-modal{border:1px solid #315d85;border-radius:13px;background:#0d2036;color:#eaf4ff;box-shadow:0 22px 54px rgba(0,0,0,.48)}.track-editor-modal .modal-header{border-bottom:1px solid rgba(62,107,147,.62)!important;background:#102943}.track-editor-modal .modal-title{color:#f5f9ff;font-size:1.3rem;font-weight:800}.track-editor-modal .modal-header a{color:#ff9a45}.track-editor-body{max-height:calc(100vh - 210px);padding:22px 26px}.track-editor-modal .form-label{color:#c4dcf3!important;font-weight:800}.track-editor-modal .form-control,.track-editor-modal .form-select{min-height:40px;border-color:#3b668c;background:#091a2d;color:#edf6ff;border-radius:8px}.track-editor-modal .track-placeholder{min-height:190px!important;margin:0!important;border:2px dashed #5b9bd0!important;background:#0b1c30!important}.track-editor-modal .track-placeholder:hover{background:#102b47!important}.track-editor-modal .track-placeholder .material-icons-outlined{color:#ff8a65}.track-editor-modal .track-placeholder p{max-width:100%;overflow-wrap:anywhere;color:#ffad72!important;font-size:.76rem}.track-editor-modal .track-placeholder audio{width:100%;max-width:360px!important}.track-editor-modal ul{color:#cddded;font-size:.82rem}.track-editor-footer{position:sticky;bottom:0;z-index:2;padding:15px 26px;border-top:1px solid rgba(62,107,147,.62);background:#102943}.track-editor-footer .btn-primary{min-width:150px;border:0;background:#ff7043;font-weight:800}.track-editor-footer .btn-primary:hover{background:#ff8a65}@media(max-width:767px){.track-editor-dialog{margin:8px}.track-editor-body{max-height:calc(100vh - 170px);padding:16px}.track-editor-modal .track-placeholder{min-height:150px!important}.track-editor-footer{padding:12px 16px}.track-editor-footer .btn-primary{width:100%}}
        @media(min-width:992px){.admin-release-editor .assignment-field>p{min-height:48px}.admin-release-editor .assignment-field{margin-bottom:0!important}}
        .admin-release-editor .release-add-artist-button,.track-editor-modal .release-add-artist-button{display:inline-flex;align-items:center;gap:9px;max-width:100%;padding:10px 14px;border:1px solid #4a88bd;border-radius:8px;background:rgba(25,66,103,.5);color:#beddff;text-align:left}.admin-release-editor .release-add-artist-button i,.track-editor-modal .release-add-artist-button i{color:#73b4ff}.admin-release-editor .release-add-artist-button span,.track-editor-modal .release-add-artist-button span{display:grid;gap:2px}.admin-release-editor .release-add-artist-button strong,.track-editor-modal .release-add-artist-button strong{font-size:.78rem}.admin-release-editor .release-add-artist-button small,.track-editor-modal .release-add-artist-button small{color:#91b4d5;font-size:.67rem}.admin-release-editor .shadow,.track-editor-modal .shadow{border:1px solid rgba(61,107,148,.55)!important;background:#0d2742!important;box-shadow:none!important}.admin-release-editor #add-track{display:inline-flex;align-items:center;gap:7px;padding:10px 17px;border-color:#ff7043;background:rgba(255,112,67,.1);color:#ff9b78;font-weight:800}.admin-release-editor #totalDuration{color:#85c6ff;font-weight:800}.admin-release-editor .form-check{padding:10px 12px;margin-bottom:6px;border:1px solid rgba(61,107,148,.45);border-radius:8px;background:rgba(9,28,47,.45)}.admin-release-editor .form-check-input:checked{background-color:#ff7043;border-color:#ff7043}.admin-release-editor>.d-flex>.btn-primary{min-width:142px;border:0;background:#ff7043;font-weight:800;box-shadow:0 8px 18px rgba(255,112,67,.18)}
        .admin-release-stepper{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin:0 0 16px;padding:8px;border:1px solid #294d74;border-radius:13px;background:#0d1d31}.admin-release-step{display:grid;grid-template-columns:32px 1fr;column-gap:9px;align-items:center;min-height:58px;padding:8px 10px;border:1px solid transparent;border-radius:9px;background:transparent;color:#94b2cf;text-align:left}.admin-release-step span{display:grid;grid-row:span 2;place-items:center;width:30px;height:30px;border:1px solid #4f7da6;border-radius:50%;font-size:.77rem;font-weight:800}.admin-release-step b{color:#d8e8f7;font-size:.76rem}.admin-release-step small{color:#82a4c5;font-size:.66rem}.admin-release-step.is-active{border-color:#ff7043;background:rgba(255,112,67,.12)}.admin-release-step.is-active span{border-color:#ff7043;background:#ff7043;color:#fff}.admin-release-step.is-active b{color:#fff}.admin-release-step-actions{display:flex;justify-content:space-between;margin:0 0 16px}.admin-release-step-actions .btn{display:inline-flex;align-items:center;gap:7px;min-height:39px;border-radius:8px;font-size:.78rem;font-weight:800}.admin-step-previous{border:1px solid #4a759c;background:transparent;color:#b7d7f4}.admin-step-next{border:0;background:#ff7043;color:#fff;box-shadow:0 8px 16px rgba(255,112,67,.2)}.admin-release-editor .admin-step-pane-hidden{display:none!important}@media(max-width:900px){.admin-release-stepper{grid-template-columns:repeat(4,180px);overflow-x:auto}.admin-release-step{white-space:nowrap}}@media(max-width:575px){.admin-release-stepper{margin-left:-2px;margin-right:-2px}.admin-release-step-actions{position:sticky;bottom:8px;z-index:4;padding:8px;border:1px solid #315b81;border-radius:10px;background:#0d2036}}
        .admin-release-editor .admin-assignment-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;padding:24px}.admin-release-editor .admin-assignment-grid .assignment-field{display:flex!important;flex-direction:column;width:auto!important;margin:0!important}.admin-release-editor .admin-assignment-grid .assignment-field>p{min-height:58px;margin:5px 0 10px!important;line-height:1.45}.admin-release-editor .admin-assignment-grid .assignment-field .form-select{margin-top:auto}@media(max-width:900px){.admin-release-editor .admin-assignment-grid{grid-template-columns:1fr}.admin-release-editor .admin-assignment-grid .assignment-field>p{min-height:0}}
        #addTrack .track-editor-dialog{max-width:880px!important}.track-editor-body{padding:18px 20px}.track-editor-modal .track-placeholder{min-height:155px!important}.track-editor-footer{padding:12px 20px}@media(max-width:900px){#addTrack .track-editor-dialog{max-width:calc(100% - 24px)!important}.track-editor-modal .track-placeholder{min-height:140px!important}}
        .admin-release-editor .release-choice-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.admin-release-editor .release-choice-card{position:relative;min-height:62px;margin:0!important;padding:17px 16px 15px 40px;border:1px solid #3c668c;background:#0b2036;transition:border-color .2s ease,background .2s ease}.admin-release-editor .release-choice-card:has(.form-check-input:checked){border-color:#ff7043;background:rgba(255,112,67,.08)}.admin-release-editor .release-choice-card .form-check-input{margin-left:-24px;margin-top:3px}.admin-release-editor .release-choice-card .form-check-label{color:#e5f1fc;font-size:.84rem;font-weight:700}.admin-release-editor .release-original-date{margin:14px -2px -2px;padding:0;border:0}.admin-release-editor .release-original-date .form-group{margin:0!important}@media(max-width:767px){.admin-release-editor .release-choice-grid{grid-template-columns:1fr}}
        .admin-release-editor>.release-original-date{margin:0 0 18px!important;padding:0;border:0}
        .track-editor-modal .release-inline-action{display:flex;align-items:center;gap:10px;width:100%;min-height:56px;padding:10px 14px;border:1px solid #4a88bd;border-radius:8px;background:rgba(25,66,103,.5);color:#c7e1fa;text-align:left}.track-editor-modal .release-inline-action:hover{border-color:#72b4ef;background:#173959;color:#fff}.track-editor-modal .release-inline-action>i{flex:none;color:#7dbdff;font-size:23px}.track-editor-modal .release-inline-action>span{display:grid;gap:3px;min-width:0}.track-editor-modal .release-inline-action strong{font-size:.82rem;line-height:1.2}.track-editor-modal .release-inline-action small{color:#91b4d5;font-size:.7rem;line-height:1.3;white-space:normal}.track-editor-modal .release-inline-action:hover small{color:#cce4fb}
        .admin-release-editor .select2-container{width:100%!important}.admin-release-editor .select2-container--default .select2-selection--single{height:42px;border:1px solid #3b668c;border-radius:8px;background:#091a2d}.admin-release-editor .select2-container--default .select2-selection--single .select2-selection__rendered{padding-left:13px;color:#edf6ff;line-height:40px}.admin-release-editor .select2-container--default .select2-selection--single .select2-selection__arrow{height:40px}.select2-dropdown{border-color:#3b668c;background:#0d2036}.select2-search--dropdown .select2-search__field{border-color:#4d7da7;border-radius:6px;background:#081a2c;color:#eff7ff}.select2-container--default .select2-results__option{color:#d7e8f8}.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{background:#1b4770;color:#fff}.select2-container--default .select2-results__option--selected{background:#17324e;color:#fff}
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/select2/js/select2.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#profileid').select2({
                width: '100%',
                placeholder: 'Search user by name…',
                allowClear: false
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('release-form');
            if (!form) return;
            const cards = Array.from(form.querySelectorAll(':scope > .card'));
            const assignmentCard = cards.shift();
            const stepper = form.querySelector('.admin-release-stepper');
            form.insertBefore(assignmentCard, stepper);
            const steps = cards;
            const controls = Array.from(form.querySelectorAll('.admin-release-step'));
            const previous = form.querySelector('.admin-step-previous');
            const next = form.querySelector('.admin-step-next');
            const stepOrder = [0, 1, 3, 2];
            let active = 0;

            function selectStep(index, scroll = true) {
                active = Math.max(0, Math.min(index, stepOrder.length - 1));
                const activePane = stepOrder[active];
                steps.forEach((step, stepIndex) => step.classList.toggle('admin-step-pane-hidden', stepIndex !== activePane));
                controls.forEach((control, stepIndex) => control.classList.toggle('is-active', stepIndex === active));
                previous.disabled = active === 0;
                next.classList.toggle('d-none', active === steps.length - 1);
                if (scroll) window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            // Native validation happens before submit and cannot focus inputs in hidden steps.
            const feedback = form.querySelector('#release-validation-feedback');
            let validationFrame = null;
            let reportingValidity = false;
            form.addEventListener('invalid', function (event) {
                if (reportingValidity) return;
                event.preventDefault();
                if (validationFrame !== null) return;
                const input = event.target;
                validationFrame = requestAnimationFrame(function () {
                    validationFrame = null;
                    const pane = steps.findIndex(step => step.contains(input));
                    const stepIndex = stepOrder.indexOf(pane);
                    if (stepIndex >= 0) selectStep(stepIndex, false);
                    const label = input.labels && input.labels.length ? input.labels[0].textContent.trim() : (input.name || 'kolom wajib');
                    const stepName = stepIndex >= 0 ? controls[stepIndex].querySelector('b').textContent.trim() : 'User Assignment';
                    feedback.textContent = `Rilis belum disimpan. Periksa ${label} pada bagian ${stepName}. ${input.validationMessage}`;
                    feedback.classList.remove('d-none');
                    input.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    input.focus({ preventScroll: true });
                    reportingValidity = true;
                    try { input.reportValidity(); } finally { reportingValidity = false; }
                });
            }, true);
            form.addEventListener('input', function () {
                if (form.querySelector(':invalid') === null) feedback.classList.add('d-none');
            });
            form.addEventListener('submit', function () { feedback.classList.add('d-none'); });

            controls.forEach(control => control.addEventListener('click', () => selectStep(Number(control.dataset.adminStep))));
            previous.addEventListener('click', () => selectStep(active - 1));
            next.addEventListener('click', () => selectStep(active + 1));
            selectStep(0);
        });
    </script>
    <script src="{{ URL::asset('build/plugins/notifications/js/lobibox.js') }}"></script>
    @include('partials.track-form-scripts')
    <script type="application/json" id="json-primaryartists">@json(old('primaryartists', $release->primaryartists) ?? [])</script>
    <script type="application/json" id="json-subgenres">@json(\App\Models\System\Subgenre::where('genreid', old('genre', $release->genre) ?? 1)->orderBy('name')->get())</script>
    <script type="application/json" id="json-tracks">@json(old('tracks', $release->tracks) ?? [])</script>
    <script type="application/json" id="json-contributors">@json(\App\Models\System\Contributor::all() ?? [])</script>
    <script>
        function normalizeArtistArray(artists) {
            if (!Array.isArray(artists)) {
                return [];
            }

            return artists.map(function (artist) {
                if (typeof artist === 'string' || artist === null || artist === undefined) {
                    return {
                        name: artist || '',
                        social: '',
                        spotify: '',
                    };
                }
                return {
                    name: artist.name || '',
                    social: artist.social || '',
                    spotify: artist.spotify || '',
                };
            });
        }

        $(document).ready(function () {
            // Handle custom input for tracks
            $('#input-file-full').click(function () {
                $('#file-full').click();
            });
            $('#input-file-trimmed').click(function () {
                $('#file-trimmed').click();
            });

            // Handle track modal on hide & handle modal scroll
            $('#addTrack').on('hide.bs.modal', function () {
                $('#addTrack').find('.simplebar-content-wrapper').scrollTop(0);
            });

            // Serialize tracks as JSON before form submit to avoid PHP max_input_vars truncation
            $('#release-form').on('submit', function () {
                document.getElementById('tracks-json-input').value = JSON.stringify(
                    Alpine.store('release').tracks.map((track, index) => ({ ...track, order: index }))
                );
            });
        });

        document.addEventListener('alpine:init', () => {

            var releasePrimaryArtist = JSON.parse(document.getElementById('json-primaryartists').textContent || '[]');
            releasePrimaryArtist = normalizeArtistArray(releasePrimaryArtist);

            Alpine.store('release', {
                init() {
                    const self = this;

                    // If type is single at bootstrap, make sure we have exactly 1 track
                    if (self.type === 'single') {
                        if (self.tracks.length === 0) {
                            self.tracks.push({
                                id: null,
                                title: self.title || '',
                                titlelang: self.titlelang || 'id',
                                version: String(self.version || '1'),
                                genre: String(self.genre || '13'),
                                subgenre: self.subgenre || '',
                                primaryartists: JSON.parse(JSON.stringify(self.primaryartists || [])),
                                featuredartists: [],
                                contributors: [],
                                productions: [],
                                songwriters: [],
                                instrumental: 0,
                                lyriclang: 'id',
                                explicit: 'non-explicit',
                                lyrics: '',
                                isrc: '',
                                duration: 0,
                                iswc: '',
                                writer_percentage: 100,
                                filefullname: '',
                                filefullcontent: '',
                                filefullinput: '',
                                filefulltemp: '',
                                filetrimmedname: '',
                                filetrimmedcontent: '',
                                filetrimmedinput: '',
                                filetrimmedtemp: '',
                            });
                        } else {
                            const track = self.tracks[0];
                            if (!track.filefullcontent && track.full_url) {
                                track.filefullcontent = track.full_url;
                            }
                            if (!track.filefullname && track.full_name) {
                                track.filefullname = track.full_name;
                            }
                            if (!track.filetrimmedcontent && track.trimmed_url) {
                                track.filetrimmedcontent = track.trimmed_url;
                            }
                            if (!track.filetrimmedname && track.trimmed_name) {
                                track.filetrimmedname = track.trimmed_name;
                            }
                            if ((!self.primaryartists || self.primaryartists.length === 0) && track.primaryartists && track.primaryartists.length > 0) {
                                self.primaryartists = JSON.parse(JSON.stringify(track.primaryartists));
                            }
                        }
                    }

                    Alpine.effect(() => {
                        if (self.type === 'single') {
                            if (self.tracks.length === 0) {
                                self.tracks.push({
                                    id: null,
                                    title: self.title || '',
                                    titlelang: self.titlelang || 'id',
                                    version: String(self.version || '1'),
                                    genre: String(self.genre || '13'),
                                    subgenre: self.subgenre || '',
                                    primaryartists: JSON.parse(JSON.stringify(self.primaryartists || [])),
                                    featuredartists: [],
                                    contributors: [],
                                    productions: [],
                                    songwriters: [],
                                    instrumental: 0,
                                    lyriclang: 'id',
                                    explicit: 'non-explicit',
                                    lyrics: '',
                                    isrc: '',
                                    duration: 0,
                                    iswc: '',
                                    writer_percentage: 100,
                                    filefullname: '',
                                    filefullcontent: '',
                                    filefullinput: '',
                                    filefulltemp: '',
                                    filetrimmedname: '',
                                    filetrimmedcontent: '',
                                    filetrimmedinput: '',
                                    filetrimmedtemp: '',
                                });
                            } else if (self.tracks.length > 1) {
                                // If they toggle to single but had multiple tracks, keep the first one
                                self.tracks = [self.tracks[0]];
                            }

                            if (self.tracks[0]) {
                                self.tracks[0].title = self.title;
                                self.tracks[0].titlelang = self.titlelang;
                                self.tracks[0].version = self.version;
                                self.tracks[0].genre = self.genre;
                                self.tracks[0].subgenre = self.subgenre;
                                self.tracks[0].primaryartists = JSON.parse(JSON.stringify(self.primaryartists || []));
                            }
                        }
                    });

                    // Reactively fetch subgenres for the track if we are single and genre changes
                    Alpine.effect(() => {
                        if (self.type === 'single' && self.tracks[0]) {
                            const currentGenre = self.genre;
                            if (currentGenre) {
                                window.fetchSubgenresForScope('$store.release.tracks[0]');
                            }
                        }
                    });

                    // Bound the pre-release date to the planned release date
                    self.methods.determinePreRelease();
                },
                primaryartists: releasePrimaryArtist,
                subgenres: JSON.parse(document.getElementById('json-subgenres').textContent),
                contributorRoles: JSON.parse(document.getElementById('json-contributors').textContent),
                tracks: JSON.parse(document.getElementById('json-tracks').textContent),
                coverImage: @if($release->cover_url) "{{ $release->cover_url }}" @else"{{URL::asset('build/images/album-placeholder.webp')}}" @endif,
                id:             {{ old('id', $release->id) ?? 0 }},
                type: "{{ old('type', $release->type) ?? 'album' }}",
                title: "{{ old('title', $release->title) ?? '' }}",
                titlelang: "{{ old('titlelang', $release->titlelang) ?? 'id' }}",
                recordlabel: "{{ old('recordlabel', $release->recordlabel) ?? '' }}",
                upc: "{{ old('upc', $release->upc) ?? '' }}",
                releaseddate: @json(old('releaseddate', $release->releaseddate?->format('Y-m-d')) ?? ''),
                releaseon: "{{ old('releaseon', $release->releaseon) ?? '' }}",
                tiktok_ytmusic_rerelease_date: "{{ old('tiktok_ytmusic_rerelease_date', $release->tiktok_ytmusic_rerelease_date) ?? '' }}",
                version:        {{ old('version', $release->version) ?? 1 }},
                genre:          {{ old('genre', $release->genre) ?? 1 }},
                subgenre:       {{ old('subgenre', $release->subgenre) ?? 0 }},
                released:       @json((string) old('released', $release->released ?? 0)),
                trackSubGenres: [],
                methods: {
                    determinePreRelease() {
                        const planned = Alpine.store('release').releaseon;
                        const date = new Date(`${planned}T00:00:00`);
                        const input = $('#tiktok_ytmusic_rerelease_date');
                        if (!isNaN(date.getTime())) {
                            // Allowed window: 1 to 7 days before the planned release date
                            const min = new Date(date);
                            const max = new Date(date);
                            min.setDate(min.getDate() - 7);
                            max.setDate(max.getDate() - 1);

                            const formatDate = (date) => {
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const day = String(date.getDate()).padStart(2, '0');
                                return `${date.getFullYear()}-${month}-${day}`;
                            };

                            const minString = formatDate(min);
                            const maxString = formatDate(max);

                            // Clear the input if its current value is outside the allowed range
                            const currentValue = Alpine.store('release').tiktok_ytmusic_rerelease_date;
                            if (currentValue && (currentValue < minString || currentValue > maxString)) {
                                Alpine.store('release').tiktok_ytmusic_rerelease_date = '';
                            }

                            input.attr('min', minString);
                            input.attr('max', maxString);
                            input.prop("disabled", false);
                        }
                        else {
                            input.prop("disabled", true);
                        }
                    },
                    async fetchSubgenres() {
                        Alpine.store('release').subgenres = [];

                        if (Alpine.store('release').genre) {
                            var url = "{{ route('admin.releases.get_subgenre', ':id') }}"
                            url = url.replace(':id', Alpine.store('release').genre);
                            if (Alpine.store('release').subgenre) {
                                url += '?current=' + Alpine.store('release').subgenre;
                            }

                            try {
                                // Custom ajax call
                                await ajaxCall(url, 'GET', null, false, false,
                                    (data) => {
                                        Alpine.store('release').subgenres = data.subgenres;
                                    },
                                    (data) => {
                                        Lobibox.notify('error', {
                                            pauseDelayOnHover: true,
                                            size: 'mini',
                                            rounded: true,
                                            position: 'top right',
                                            icon: 'bi bi-exclamation-triangle',
                                            msg: 'Album genre not found.'
                                        });
                                    },
                                )
                            }
                            catch (error) {
                                Lobibox.notify('error', {
                                    pauseDelayOnHover: true,
                                    size: 'mini',
                                    rounded: true,
                                    position: 'top right',
                                    icon: 'bi bi-exclamation-triangle',
                                    msg: error
                                });
                            }

                        }
                    },
                    async uploadCover() {
                        var file = document.getElementById('cover-input').files[0];
                        if (!file) {
                            return;
                        }

                        try {
                            // Validate file
                            await Promise.all([
                                // Check MIME type
                                new Promise((resolve, reject) => {
                                    var allowedMimeTypes = ['image/jpeg'];
                                    if (!allowedMimeTypes.includes(file.type)) {
                                        reject('Please upload a file with JPG or JPEG format for album cover.');
                                    } else {
                                        resolve();
                                    }
                                }),

                                // Check image resolution
                                new Promise((resolve, reject) => {
                                    var img = new Image();
                                    img.onload = function () {
                                        var width = img.width;
                                        var height = img.height;
                                        if (width != 3000 || height != 3000 || width !== height) {
                                            $('#cover-input').val('');
                                            reject('Please upload a file with minimum resolution of 3000px x 3000px and 1:1 aspect ratio for album cover.');
                                        } else {
                                            resolve();  // Resolve if the image meets the requirements
                                        }
                                    };
                                    img.onerror = function () {
                                        reject('There was an error loading the image.');
                                    };
                                    img.src = URL.createObjectURL(file);
                                })
                            ]);

                            // Display image directly
                            const reader = new FileReader();
                            reader.onload = function (e) {
                                Alpine.store('release').coverImage = e.target.result;
                            };
                            reader.readAsDataURL(file); // Read the file as a data URL

                        } catch (error) {
                            Lobibox.notify('error', {
                                pauseDelayOnHover: true,
                                rounded: true,
                                position: 'top right',
                                icon: 'bi bi-exclamation-triangle',
                                msg: error
                            });
                        }
                    },
                    removeCover() {
                        Alpine.store('release').coverImage = "{{URL::asset('build/images/album-placeholder.webp')}}";
                        document.getElementById('cover-input').value = '';
                    },
                    add(arr) {
                        const key = Alpine.store('release')[arr];
                        key.push({
                            name: '',
                            social: '',
                            spotify: '',
                        });
                    },
                    addTrack() {
                        Alpine.store('tray').methods.newTrack();
                    },
                    editTrack(trackIndex) {
                        const track = JSON.parse(JSON.stringify(Alpine.store('release').tracks[trackIndex]));
                        Alpine.store('tray').trackTray = track;
                        Alpine.store('tray').trackTray.index = trackIndex;
                        Alpine.store('tray').trackTrayMode = 'edit',
                            Alpine.store('tray').methods.fetchSubgenres(Alpine.store('tray').trackTray.subgenre);

                        Alpine.store('tray').trackTray.primaryartists = normalizeArtistArray(track.primaryartists || []);
                        Alpine.store('tray').trackTray.featuredartists = normalizeArtistArray(track.featuredartists || []);

                        if (track.filefullcontent) {
                            Alpine.store('tray').trackTray.filefullcontent = track.filefullcontent;
                            Alpine.store('tray').trackTray.filefullname = track.filefullname;
                        }
                        else if (track.full_url) {
                            Alpine.store('tray').trackTray.filefullcontent = track.full_url;
                            Alpine.store('tray').trackTray.filefullname = track.full_name;
                        }

                        if (track.filetrimmedcontent) {
                            Alpine.store('tray').trackTray.filetrimmedcontent = track.filetrimmedcontent;
                            Alpine.store('tray').trackTray.filetrimmedname = track.filetrimmedname;
                        }
                        else if (track.trimmed_url) {
                            Alpine.store('tray').trackTray.filetrimmedcontent = track.trimmed_url;
                            Alpine.store('tray').trackTray.filetrimmedname = track.trimmed_name;
                        }

                        $('#addTrack').modal('show');
                    },
                    moveUp(index, arr) {
                        if (index == 0) return;
                        const key = Alpine.store('release')[arr];
                        key.splice(index, 1, ...key.splice(index - 1, 1, key[index]));
                    },
                    moveDown(index, arr) {
                        const key = Alpine.store('release')[arr];
                        key.splice(index, 1, ...key.splice(index + 1, 1, key[index]));
                    },
                    remove(index, arr) {
                        const key = Alpine.store('release')[arr];
                        key.splice(index, 1);
                    },
                    humanizeDuration(duration) {
                        let hours = Math.floor(duration / 3600).toString().padStart(2, '0');
                        let minutes = Math.floor((duration % 3600) / 60).toString().padStart(2, '0');
                        let seconds = (duration % 60).toString().padStart(2, '0');

                        return `${hours}:${minutes}:${seconds}`;
                    },

                    get totalDuration() {
                        let total = 0;
                        Alpine.store('release').tracks.forEach(track => {
                            if (track.duration) {
                                total += parseFloat(track.duration);
                            }
                        });
                        return this.humanizeDuration(total);
                    },
                }
            });

            Alpine.store('tray', {
                overlayActive: false,
                overlayText: 'Loading...',
                subgenres: [],
                trackTrayMode: 'add',
                trackTray: {
                    primaryartists: [],
                    featuredartists: [],
                    contributors: [],
                    productions: [],
                    songwriters: [],
                },
                methods: {
                    newTrack() {
                        // Reset tray
                        Alpine.store('tray').trackTrayMode = 'add',
                            Alpine.store('tray').trackTray = {
                                id: null,
                                title: '',
                                titlelang: 'id',
                                version: '1',
                                genre: '13',
                                subgenre: '',
                                primaryartists: [],
                                featuredartists: [],
                                contributors: [],
                                productions: [],
                                songwriters: [],
                                instrumental: 0,
                                lyriclang: 'id',
                                explicit: 'non-explicit',
                                lyrics: '',
                                isrc: '',
                                duration: 0,
                                iswc: '',
                                writer_percentage: 100,

                                filefullname: '',
                                filefullcontent: '',
                                filefullinput: '',
                                filefulltemp: '',

                                filetrimmedname: '',
                                filetrimmedcontent: '',
                                filetrimmedinput: '',
                                filetrimmedtemp: '',
                            };

                        // Get subgenre
                        Alpine.store('tray').methods.fetchSubgenres();

                        $('#addTrack').modal('show');
                    },
                    addTrack() {
                        try {
                            if (Alpine.store('tray').trackTray.writer_percentage === '' || Alpine.store('tray').trackTray.writer_percentage < 0 || Alpine.store('tray').trackTray.writer_percentage > 100) {
                                throw 'Please enter a valid writer percentage (0-100).';
                            }

                            // Push track to array
                            const newTrack = JSON.parse(JSON.stringify(Alpine.store('tray').trackTray));
                            Alpine.store('release').tracks.push(newTrack);

                            $('#addTrack').modal('hide');
                            Lobibox.notify('success', {
                                pauseDelayOnHover: true,
                                size: 'mini',
                                rounded: true,
                                position: 'top right',
                                icon: 'bi bi-check-circle',
                                msg: 'Track added'
                            });
                        }
                        catch (error) {
                            Lobibox.notify('error', {
                                pauseDelayOnHover: true,
                                size: 'mini',
                                rounded: true,
                                position: 'top right',
                                icon: 'bi bi-exclamation-triangle',
                                msg: error
                            });
                        }
                        finally {
                            Alpine.store('release').overlayActive = false;
                        }
                    },

                    add(arr) {
                        if (!Alpine.store('tray').trackTray[arr]) Alpine.store('tray').trackTray[arr] = [];
                        const key = Alpine.store('tray').trackTray[arr]
                        key.push({
                            name: '',
                            social: '',
                            spotify: '',
                        });
                    },
                    addArr(arr) {
                        if (!Alpine.store('tray').trackTray[arr]) Alpine.store('tray').trackTray[arr] = [];
                        const key = Alpine.store('tray').trackTray[arr]
                        key.push({ 'name': '', 'role': '1' });
                    },
                    moveUp(index, arr) {
                        if (index == 0) return;
                        const key = Alpine.store('tray').trackTray[arr]
                        key.splice(index, 1, ...key.splice(index - 1, 1, key[index]));
                    },
                    moveDown(index, arr) {
                        const key = Alpine.store('tray').trackTray[arr]
                        key.splice(index, 1, ...key.splice(index + 1, 1, key[index]));
                    },
                    remove(index, arr) {
                        const key = Alpine.store('tray').trackTray[arr]
                        key.splice(index, 1);
                    },

                    async updateTrack(trackIndex) {
                        try {
                            // Push track to array
                            const newTrack = JSON.parse(JSON.stringify(Alpine.store('tray').trackTray));
                            Alpine.store('release').tracks[trackIndex] = newTrack;

                            $('#addTrack').modal('hide');
                            Lobibox.notify('success', {
                                pauseDelayOnHover: true,
                                size: 'mini',
                                rounded: true,
                                position: 'top right',
                                icon: 'bi bi-check-circle',
                                msg: 'Track updated'
                            });
                        }
                        catch (error) {
                            Lobibox.notify('error', {
                                pauseDelayOnHover: true,
                                size: 'mini',
                                rounded: true,
                                position: 'top right',
                                icon: 'bi bi-exclamation-triangle',
                                msg: error
                            });
                        }
                        finally {
                            Alpine.store('release').overlayActive = false;
                        }
                    },
                    async fetchSubgenres(subgenre = null) {
                        Alpine.store('tray').subgenres = [];

                        if (Alpine.store('tray').trackTray.genre) {
                            var url = "{{ route('admin.releases.get_subgenre', ':id') }}"
                            url = url.replace(':id', Alpine.store('tray').trackTray.genre);

                            try {
                                // Custom ajax call
                                await ajaxCall(url, 'GET', null, false, false,
                                    (data) => {
                                        Alpine.store('tray').subgenres = data.subgenres;

                                        if (subgenre) {
                                            Alpine.store('tray').trackTray.subgenre = subgenre;
                                        }


                                        // if(subgenre) {
                                        //     Alpine.store('tray').trackTray.subgenre = subgenre;
                                        // }
                                        // else if(data.subgenres[0]){
                                        //     Alpine.store('tray').trackTray.subgenre = data.subgenres[0].id;
                                        // }
                                    },
                                    (data) => {
                                        Lobibox.notify('error', {
                                            pauseDelayOnHover: true,
                                            size: 'mini',
                                            rounded: true,
                                            position: 'top right',
                                            icon: 'bi bi-exclamation-triangle',
                                            msg: 'Album genre not found.'
                                        });
                                    },
                                )
                            }
                            catch (error) {
                                Lobibox.notify('error', {
                                    pauseDelayOnHover: true,
                                    size: 'mini',
                                    rounded: true,
                                    position: 'top right',
                                    icon: 'bi bi-exclamation-triangle',
                                    msg: error
                                });
                            }

                        }
                    },
                    fetchSubgenreApply() {
                        let value = Alpine.store('tray').trackTray.subgenre;
                        document.getElementById('subgenre-tray').value = value;
                    },
                    async getFileHash(file) {
                        const arrayBuffer = await file.arrayBuffer();

                        // Use Web Crypto API if available
                        if (window.crypto && window.crypto.subtle) {
                            try {
                                const hashBuffer = await crypto.subtle.digest('SHA-256', arrayBuffer);
                                return Array.from(new Uint8Array(hashBuffer))
                                    .map(byte => byte.toString(16).padStart(2, '0'))
                                    .join('');
                            } catch (error) {
                                console.error("Web Crypto API failed:", error);
                            }
                        }

                        // Fallback to CryptoJS if Web Crypto API is unavailable
                        const wordArray = CryptoJS.lib.WordArray.create(new Uint8Array(arrayBuffer));
                        const hashHex = CryptoJS.SHA256(wordArray).toString(CryptoJS.enc.Hex);
                        return hashHex;
                    },
                    async uploadTrack(type) {
                        const inputId = (type == 'full') ? 'file-full' : 'file-trimmed';
                        const audioId = (type == 'full') ? 'audio-full' : 'audio-trimmed';
                        const name = (type == 'full') ? 'filefullname' : 'filetrimmedname';
                        const content = (type == 'full') ? 'filefullcontent' : 'filetrimmedcontent';
                        const model = (type == 'full') ? 'filefullinput' : 'filetrimmedinput';
                        const temp = (type == 'full') ? 'filefulltemp' : 'filetrimmedtemp';

                        try {
                            // Get file
                            const file = document.getElementById(inputId).files[0];
                            if (!file) {
                                return;
                            }

                            // Check file size
                            const maxSize = 300 * 1024 * 1024; // 300MB
                            if (file.size > maxSize) {
                                throw "File too large. Maximum size is 300MB.";
                            }

                            // Load audio
                            const audioURL = URL.createObjectURL(file);
                            const audioPlayer = document.getElementById(audioId);
                            Alpine.store('tray').trackTray[name] = file.name;
                            Alpine.store('tray').trackTray[content] = audioURL;
                            await Alpine.store('tray').methods.loadTrack(audioPlayer, type);

                            // Set overlay
                            Alpine.store('tray').overlayProgress = 0;
                            Alpine.store('tray').overlayText = "Uploading track...";
                            Alpine.store('tray').overlayActive = true;

                            // Get file hash
                            const hashHex = await Alpine.store('tray').methods.getFileHash(file);

                            // Setting chunks
                            const filename = (Math.random() + 1).toString(36).substring(7);
                            const chunkSize = 5 * 1024 * 1024; // 5MB chunks
                            const totalchunks = Math.ceil(file.size / chunkSize);
                            let uploadPromises = [];

                            // Upload chunks
                            for (let i = 0; i < totalchunks; i++) {
                                const chunk = file.slice(i * chunkSize, (i + 1) * chunkSize);
                                uploadPromises.push(Alpine.store('tray').methods.trackChunkUpload(chunk, i, totalchunks, filename));
                            }

                            // Wait for all chunks to finish uploading
                            await Promise.all(uploadPromises);

                            // Notify backend to merge chunks
                            const response = await Alpine.store('tray').methods.trackChunkMerge(filename, hashHex, totalchunks, type);
                            const data = await response.json();
                            if (!data.success) {
                                throw data.errors;
                            }

                            // Set track temp name
                            Alpine.store('tray').trackTray[temp] = filename;
                        }
                        catch (error) {
                            // Clear input and audio
                            document.getElementById(audioId).src = '';
                            document.getElementById(inputId).src = '';
                            Alpine.store('tray').trackTray[name] = '';
                            Alpine.store('tray').trackTray[content] = '';
                            Alpine.store('tray').trackTray[model] = '';
                            Alpine.store('tray').trackTray[temp] = '';

                            Lobibox.notify('error', {
                                pauseDelayOnHover: true,
                                size: 'mini',
                                rounded: true,
                                position: 'top right',
                                icon: 'bi bi-exclamation-triangle',
                                msg: error,
                            });
                        }
                        finally {
                            Alpine.store('tray').overlayProgress = 0;
                            Alpine.store('tray').overlayActive = false;
                        }
                    },
                    trackChunkUpload(chunk, index, totalchunks, filename) {
                        let formData = new FormData();
                        formData.append("chunk", chunk);
                        formData.append("index", index);
                        formData.append("total_chunks", totalchunks);
                        formData.append("filename", filename);

                        return fetch("{{ route('admin.releases.track_chunk_store') }}", {
                            method: "POST",
                            body: formData,
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                        }).then(response => {
                            if (response.ok) {
                                const increment = Math.round(100 / totalchunks);
                                if (Alpine.store('tray').overlayProgress + increment < 100) {
                                    Alpine.store('tray').overlayProgress += Math.round(100 / totalchunks);
                                }
                            }
                        });
                    },
                    trackChunkMerge(filename, hash = null, totalchunks = null, type = null) {
                        return fetch("{{ route('admin.releases.track_chunk_merge') }}", {
                            method: "POST",
                            body: JSON.stringify({ filename, hash, totalchunks, type }),
                            headers: {
                                "Content-Type": "application/json",
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                    },
                    clearTrack(type) {
                        var inputId = (type == 'full') ? 'file-full' : 'file-trimmed';
                        var audioId = (type == 'full') ? 'audio-full' : 'audio-trimmed';
                        var name = (type == 'full') ? 'filefullname' : 'filetrimmedname';
                        var content = (type == 'full') ? 'filefullcontent' : 'filetrimmedcontent';
                        var model = (type == 'full') ? 'filefullinput' : 'filetrimmedinput';

                        document.getElementById(audioId).src = '';
                        document.getElementById(inputId).src = '';
                        Alpine.store('tray').trackTray[name] = '';
                        Alpine.store('tray').trackTray[content] = '';
                        Alpine.store('tray').trackTray[model] = '';
                    },
                    loadTrack(audioPlayer, type) {
                        return new Promise((resolve, reject) => {
                            if (type == 'full') {
                                audioPlayer.onloadedmetadata = function () {
                                    const duration = audioPlayer.duration; // Duration in seconds
                                    Alpine.store('tray').trackTray.duration = duration.toFixed();
                                    resolve(true);
                                };
                            }
                            else {
                                audioPlayer.onloadedmetadata = function () {
                                    const duration = audioPlayer.duration; // Duration in seconds
                                    if (duration > 59) {
                                        reject('TikTok ref maximum duration should be equal or less than 59 seconds.');
                                    }
                                    resolve(true);
                                };
                            }
                        })
                    },
                    removeTrack(trackIndex) {
                        const index = this.tracks.indexOf(trackIndex);
                        this.tracks.splice(index, 1);
                    },
                    humanizeDuration(duration) {
                        let hours = Math.floor(duration / 3600).toString().padStart(2, '0');
                        let minutes = Math.floor((duration % 3600) / 60).toString().padStart(2, '0');
                        let seconds = (duration % 60).toString().padStart(2, '0');

                        return `${hours}:${minutes}:${seconds}`;
                    },
                    addContributor() {
                        this.publishing.contributors.push({
                            name: '',
                            type: 'composer',
                            percentage: 0,
                        });
                    },
                    removeContributor(contributor) {
                        const index = this.publishing.contributors.indexOf(contributor);
                        this.publishing.contributors.splice(index, 1);
                    },
                }
            });
        });
    </script>
@endpush
