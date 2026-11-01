@extends('layouts.app')
@php($allowIdentifierEntry = session()->has('impersonate') && auth()->user()?->can('impersonate-users'))
@php
    $restrictRecordLabel = !current_user()->profile->whitelabel_enabled;
@endphp
@section('title')
    @php
        $print_type = $release->type == 'single' ? 'Single' : 'Album';
    @endphp
    @if ($release->id)
        Edit {{$print_type}}
    @else
        Create a {{$print_type}}
    @endif
@endsection
@section('header')
    <div class="release-create-header">
        <div class="release-create-title-block">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('releases.index') }}">My Releases</a></li>
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>

            <h1>@if ($release->id) Edit {{$print_type}} @else Create a {{$print_type}} @endif</h1>
            <p>{{ $release->id ? 'Lengkapi informasi rilis Anda sebelum dikirim.' : 'Siapkan karya Anda dalam beberapa langkah sederhana.' }}</p>
        </div>
        <button type="button" class="btn release-save-draft" onclick="saveDraft()">
            <i class="material-icons-outlined">save</i> Save Draft
        </button>
    </div>
@endsection
@section('content')

    <div x-data="$store.release" class="release-creation-page">
        <div id="stepper" class="bs-stepper">
            {{-- Stepper Navigation --}}
            <div class="release-step-nav" role="tablist">
                <div class="step" data-target="#step-1">
                    <div class="step-trigger" role="tab" id="stepper1trigger1" aria-controls="step-1">
                        <div class="bs-stepper-circle">1</div>
                        <div class="">
                            <h5 class="mb-0 steper-title">{{$print_type}} Information</h5>
                            <p class="mb-0 steper-sub-title">Title, Cover Art, Label, UPC</p>
                        </div>
                    </div>
                </div>
                @if($release->type == 'album')
                    <div class="bs-stepper-line"></div>
                    <div class="step" data-target="#step-2">
                        <div class="step-trigger" role="tab" id="stepper1trigger2" aria-controls="step-2">
                            <div class="bs-stepper-circle">2</div>
                            <div class="">
                                <h5 class="mb-0 steper-title">Release Detail</h5>
                                <p class="mb-0 steper-sub-title">Release Date, Publisher</p>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="bs-stepper-line"></div>
                <div class="step" data-target="#step-3">
                    <div class="step-trigger" role="tab" id="stepper1trigger3" aria-controls="step-3">
                        <div class="bs-stepper-circle">{{ $release->type == 'single' ? 2 : 3 }}</div>
                        <div class="">
                            <h5 class="mb-0 steper-title">{{ $release->type == 'single' ? 'Release Detail' : 'Track Information' }}</h5>
                            <p class="mb-0 steper-sub-title">{{ $release->type == 'single' ? 'Release Date, Publisher' : 'Track List and Details' }}</p>
                        </div>
                    </div>
                </div>
                <div class="bs-stepper-line"></div>
                <div class="step" data-target="#step-4">
                    <div class="step-trigger" role="tab" id="stepper1trigger4" aria-controls="step-4">
                        <div class="bs-stepper-circle">{{ $release->type == 'single' ? 3 : 4 }}</div>
                        <div class="">
                            <h5 class="mb-0 steper-title">Review</h5>
                            <p class="mb-0 steper-sub-title">Finalize Release</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stepper Content --}}
            <div class="bs-stepper-content">

                {{-- Release Form --}}
                <form
                    action="@if($release->id){{ route('releases.update', $release->id) }}@else{{ route('releases.store') }}@endif"
                    id="release-form" method="POST" enctype="multipart/form-data">
                    @csrf
                    @if($release->id)
                        @method('PUT')
                    @endif

                    <div id="step-1" role="tabpanel" class="bs-stepper-pane content" aria-labelledby="stepper1trigger1">
                        <div class="release-step-heading">
                            <span class="release-step-kicker">Step 1 of {{ $release->type == 'single' ? 3 : 4 }}</span>
                            <h2>{{ $print_type }} Information</h2>
                            <p>Upload artwork dan lengkapi detail utama untuk memulai rilis.</p>
                        </div>

                        <!-- Cover Art -->
                        <div class="form-group release-cover-section">
                            <label for="cover-input" class="form-label">Cover Art <span>Required</span></label>
                            <input type="file" name="cover" id="cover-input" style="display: none" accept="image/jpeg"
                                @change="$store.release.methods.uploadCover()">
                            <div class="release-cover-layout">
                                <div class="text-center release-cover-upload" id="cover-input-element">
                                    <label for="cover-input">
                                        <img class="album-cover" :src="$store.release.coverImage" id="cover-placeholder"
                                            alt="Album Cover Input">
                                    </label>
                                    <div class="release-cover-actions">
                                        <label for="cover-input" class="btn release-upload-button"><i class="material-icons-outlined">upload</i> Upload artwork</label>
                                        <button type="button" class="btn release-remove-button"
                                            @click="$store.release.methods.removeCover()">Remove</button>
                                    </div>
                                </div>
                                <div class="release-cover-guidelines">
                                    <div class="release-guideline-icon"><i class="material-icons-outlined">album</i></div>
                                    <div>
                                        <h3>Artwork requirements</h3>
                                        <p>Gunakan artwork persegi <strong>JPG atau JPEG</strong>, minimal <strong>3000 × 3000 px</strong>, tajam, dan siap edar.</p>
                                        <ul>
                                            <li>Nama artis dan judul harus sesuai dengan data rilis.</li>
                                            <li>Jangan gunakan logo, handle media sosial, atau informasi kontak.</li>
                                            <li>Hindari iklan, alamat toko, atau elemen yang tidak terkait musik.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <section class="release-metadata-section">
                            <header>
                                <div>
                                    <span class="release-step-kicker">Release metadata</span>
                                    <h3>Informasi rilis</h3>
                                    <p>Lengkapi informasi karya, artis, dan audio Anda.</p>
                                </div>
                                <i class="material-icons-outlined">music_note</i>
                            </header>
                            @if($release->type == 'single')
                                @include('partials.single-step1-fields')
                            @else
                                @include('partials.album-step1-fields')
                            @endif
                        </section>
                        <div class="release-step-footer">
                            <span><i class="material-icons-outlined">info</i> Anda dapat menyimpan draf kapan saja.</span>
                            <button type="button" class="btn release-next-button" onclick="stepper.next()">
                                Next: Release Detail <i class='bx bx-right-arrow-alt ms-2'></i>
                            </button>
                        </div>
                    </div>
                    @if($release->type == 'album')
                        <div id="step-3" role="tabpanel" class="bs-stepper-pane content" aria-labelledby="stepper1trigger3">
                            <section class="release-tracks-card">
                                <header class="release-tracks-heading">
                                    <div>
                                        <span class="release-step-kicker">Step 3 of 4</span>
                                        <h2>Track Information</h2>
                                        <p>Tambahkan lagu untuk EP atau Album Anda, lalu isi detail setiap track.</p>
                                    </div>
                                    <button type="button" class="btn release-add-track" id="add-track"
                                        @click="$store.release.methods.addTrack()"><i class="material-icons-outlined">add</i> Add Track</button>
                                </header>
                                <div class="release-tracks-summary">
                                    <div><i class="material-icons-outlined">queue_music</i><span><small>Jumlah track</small><strong x-text="$store.release.tracks.length + ' track'"></strong></span></div>
                                    <div><i class="material-icons-outlined">schedule</i><span><small>Total durasi</small><strong x-text="$store.release.methods.totalDuration" id="totalDuration"></strong></span></div>
                                </div>
                                <div class="release-track-list">
                                    <template x-if="$store.release.tracks.length === 0">
                                        <div class="release-track-empty"><i class="material-icons-outlined">library_music</i><strong>Belum ada track</strong><span>Tambahkan track pertama untuk melanjutkan.</span></div>
                                    </template>
                                    <template x-for="(track, index) in $store.release.tracks" :key="index">
                                        <article class="release-track-item">
                                            <button type="button" class="release-track-main" @click="$store.release.methods.editTrack(index)">
                                                <span class="release-track-number" x-text="index + 1"></span>
                                                <span class="release-track-name"><strong x-text="track.title || 'Untitled Track'"></strong><small>Click untuk mengubah informasi track</small></span>
                                                <span class="release-track-duration"><i class="material-icons-outlined">schedule</i><span x-text="$store.release.methods.humanizeDuration(track.duration)"></span></span>
                                            </button>
                                            <div class="release-track-actions">
                                                <button type="button" title="Pindah ke atas" @click="$store.release.methods.moveUp(index, 'tracks')"><i class="bx bx-chevron-up"></i></button>
                                                <button type="button" title="Pindah ke bawah" @click="$store.release.methods.moveDown(index, 'tracks')"><i class="bx bx-chevron-down"></i></button>
                                                <button type="button" class="release-track-remove" title="Hapus track" @click="$store.release.methods.remove(index, 'tracks')"><i class="bx bx-x"></i></button>
                                            </div>
                                        </article>
                                    </template>
                                </div>
                                <footer class="release-detail-footer">
                                    <button type="button" class="btn release-previous-button" onclick="stepper.previous()"><i class='bx bx-left-arrow-alt me-2'></i> Previous</button>
                                    <button type="button" class="btn release-next-button" onclick="stepper.next()">Next: Review <i class='bx bx-right-arrow-alt ms-2'></i></button>
                                </footer>
                            </section>
                        </div>
                    @endif
                    <div id="{{ $release->type == 'album' ? 'step-2' : 'step-3' }}" role="tabpanel" class="bs-stepper-pane content" aria-labelledby="{{ $release->type == 'album' ? 'stepper1trigger2' : 'stepper1trigger3' }}">
                        <section class="release-detail-card">
                            <header class="release-detail-heading">
                                <div>
                                    <span class="release-step-kicker">Step 2 of {{ $release->type == 'single' ? 3 : 4 }}</span>
                                    <h2>Release Detail</h2>
                                    <p>Tentukan status dan jadwal perilisan karya Anda.</p>
                                </div>
                                <span class="release-heading-icon material-icons-outlined">calendar_month</span>
                            </header>
                            <div class="release-detail-body">
                                <div class="release-status-choice">
                                    <p class="form-label">Apakah rilis ini sudah pernah didistribusikan di luar {{ app_name() }}?</p>
                                    <div class="release-radio-options">
                                        <label class="release-radio-option" for="released-no">
                                            <input class="form-check-input" type="radio" name="released" value="0" id="released-no" @checked((string) old('released', $release->released ?? 0) === '0')
                                                x-model="$store.release.released" @if(old('released', $release->released) == 0) checked @endif required>
                                            <span><strong>Belum, ini rilis baru</strong><small>Belum pernah dipublikasikan pada layanan lain.</small></span>
                                        </label>
                                        <label class="release-radio-option" for="released-yes">
                                            <input class="form-check-input" type="radio" name="released" value="1" id="released-yes" @checked((string) old('released', $release->released ?? 0) === '1')
                                                x-model="$store.release.released" @if(old('released', $release->released) == 1) checked @endif>
                                            <span><strong>Sudah, pernah dirilis</strong><small>Masukkan tanggal rilis awal dan UPC jika tersedia.</small></span>
                                        </label>
                                    </div>
                                    <fieldset class="release-previous-fields" :disabled="$store.release.released == 0" x-show="$store.release.released == 1" x-transition>
                                        <div class="form-group">
                                            <label for="releaseddate" class="form-label">Original Release Date</label>
                                            <input type="date" class="form-control" name="releaseddate" id="releaseddate"
                                                value="{{ old('releaseddate', $release->releaseddate) }}" required>
                                        </div>
                                        @unless($allowIdentifierEntry)
                                        <div class="form-group">
                                            <label class="form-label" for="upc">UPC <span class="release-required-label">Required</span></label>
                                            <input type="text" class="form-control" name="upc" id="upc"
                                                x-model="$store.release.upc" :required="$store.release.released == 1">
                                        </div>
                                        @endunless
                                        @if($release->type == 'single' && !$allowIdentifierEntry)
                                            <div class="form-group">
                                                <label class="form-label" for="isrc">ISRC <span class="release-required-label">Required</span></label>
                                                <input type="text" class="form-control" id="isrc"
                                                    x-model="$store.release.tracks[0].isrc" :required="$store.release.released == 1"
                                                    maxlength="15" placeholder="Contoh: IDABC2400001">
                                            </div>
                                        @endif
                                    </fieldset>
                                    @if($allowIdentifierEntry)
                                        <div class="form-group mt-3">
                                            <label class="form-label" for="upc">UPC</label>
                                            <input type="text" class="form-control" name="upc" id="upc"
                                                x-model="$store.release.upc" :required="$store.release.released == 1" maxlength="255"
                                                placeholder="Masukkan UPC rilis jika sudah tersedia">
                                            <small class="text-muted">Admin dapat mengisi UPC untuk rilis baru maupun rilis yang sudah terbit.</small>
                                        </div>
                                    @endif
                                </div>
                                <div class="release-schedule-grid">
                                    <div class="form-group release-date-field">
                                        <label for="releaseon" class="form-label">Planned Release Date</label>
                                        <p>Pilih tanggal setidaknya 14 hari dari hari ini.</p>
                                        <input type="date" class="form-control" name="releaseon" id="releaseon"
                                            @change="$store.release.methods.determinePreRelease()"
                                            x-model="$store.release.releaseon" required
                                            min="{{ date('Y-m-d', strtotime('+14 days')) }}">
                                    </div>
                                    <div class="form-group release-date-field">
                                        <label for="tiktok_ytmusic_rerelease_date" class="form-label">TikTok & YouTube Music Pre-Release Date <em>Optional</em></label>
                                        <p>Atur antara 1–7 hari sebelum tanggal rilis utama.</p>
                                        <input type="date" class="form-control" name="tiktok_ytmusic_rerelease_date"
                                            id="tiktok_ytmusic_rerelease_date" x-model="$store.release.tiktok_ytmusic_rerelease_date">
                                    </div>
                                </div>
                            </div>
                            <footer class="release-detail-footer">
                                <button type="button" class="btn release-previous-button" onclick="stepper.previous()">
                                    <i class='bx bx-left-arrow-alt me-2'></i> Previous
                                </button>
                                <button type="button" class="btn release-next-button" onclick="stepper.next()">
                                    Next: Review <i class='bx bx-right-arrow-alt ms-2'></i>
                                </button>
                            </footer>
                        </section>
                    </div>
                    <div id="step-4" role="tabpanel" class="bs-stepper-pane content" aria-labelledby="stepper1trigger4">
                        <div class="release-review-topbar">
                            <div>
                                <span class="release-step-kicker">Final step</span>
                                <h2>Review & Submit</h2>
                                <p>Periksa kembali detail rilis sebelum mengirimkannya untuk verifikasi.</p>
                            </div>
                            <button type="button" class="btn release-previous-button" onclick="stepper.previous()">
                                <i class='bx bx-left-arrow-alt me-2'></i>
                                Previous
                            </button>
                        </div>
                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center">
                                <div class="ms-3">
                                    <div class="">Please review the following information. After you submit the release, you
                                        will not be able to edit it. Our admin will verify your release and contact you if
                                        there are any issues.</div>
                                </div>
                            </div>
                        </div>
                        <div class="row justify-content-evenly">
                            <div class="col-md-4 p-2 text-center" id="cover-input-element">
                                <img class="album-cover" :src="$store.release.coverImage" alt="Album Cover Final">
                            </div>
                            <div class="col-md-6 p-2">
                                <table class="table table-borderless album-items">
                                    <thead class="d-none">
                                        <tr>
                                            <th scope="col">Field</th>
                                            <th scope="col">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Title</td>
                                            <td x-text="$store.release.title"></td>
                                        </tr>
                                        <tr>
                                            <td>Artists</td>
                                            <td>
                                                <ul style="list-style:none; padding-left: 0px; margin-bottom: 0px;">
                                                    <template x-for="artist in $store.release.primaryartists">
                                                        <li x-text="artist.name"></li>
                                                    </template>
                                                </ul>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Record Label</td>
                                            <td x-text="$store.release.recordlabel"></td>
                                        </tr>
                                        <tr>
                                            <td>UPC</td>
                                            <td x-text="$store.release.upc"></td>
                                        </tr>
                                        <tr>
                                            <td>Number of tracks</td>
                                            <td x-text="$store.release.tracks.length"></td>
                                        </tr>
                                        <tr>
                                            <td>Total Duration</td>
                                            <td x-text="$store.release.methods.totalDuration"></td>
                                        </tr>
                                        <tr>
                                            <td>Release Date</td>
                                            <td
                                                x-text="new Intl.DateTimeFormat('default', {year: 'numeric', month: 'long', day: '2-digit'}).format(new Date($store.release.releaseon))">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <section class="release-review-tracks">
                            <header>
                                <div>
                                    <span class="release-step-kicker">Uploaded tracks</span>
                                    <h3>Preview track</h3>
                                    <p>Dengarkan File Master yang telah diunggah sebelum mengirim rilis.</p>
                                </div>
                                <span class="material-icons-outlined">headphones</span>
                            </header>
                            <div class="release-review-track-list">
                                <template x-for="(track, index) in $store.release.tracks" :key="index">
                                    <article class="release-review-track-item">
                                        <div class="release-review-track-info">
                                            <span class="release-track-number" x-text="index + 1"></span>
                                            <div>
                                                <strong x-text="track.title || 'Untitled Track'"></strong>
                                                <small><i class="material-icons-outlined">schedule</i><span x-text="$store.release.methods.humanizeDuration(track.duration)"></span></small>
                                            </div>
                                        </div>
                                        <div class="release-review-audio">
                                            <audio controls preload="metadata" :src="track.filefullcontent || track.full_url || ''"
                                                x-show="track.filefullcontent || track.full_url"></audio>
                                            <span x-show="!(track.filefullcontent || track.full_url)"><i class="material-icons-outlined">audio_file</i> File Master belum tersedia untuk preview</span>
                                        </div>
                                    </article>
                                </template>
                            </div>
                        </section>
                        <div class="release-review-footer">
                            <span><i class="material-icons-outlined">verified</i> Pastikan semua informasi sudah benar.</span>
                            <button type="submit" class="btn release-submit-button"
                                @click.prevent="$store.release.methods.submit()">Submit Release <i class="bx bx-right-arrow-alt ms-2"></i></button>
                        </div>
                    </div>
                    <input type="hidden" name="tracks_json" id="tracks-json-input">
                </form>
            </div>
        </div>
    </div>
@endsection
@push('modals')
    {{-- Validation Modal --}}
    <div x-data="$store.validation">
        <div class="modal fade" id="validationModal" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Validation Error</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p x-text="$store.validation.methods.validationHeader"></p>
                        <ul>
                            <template x-for="error in $store.validation.errors">
                                <li class="mb-0" x-text="error"></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Used for cover and audio files rejected before or during upload. --}}
    <div class="modal fade release-upload-error-modal" id="uploadErrorModal" tabindex="-1" aria-labelledby="uploadErrorModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <span class="release-upload-error-icon material-icons-outlined">error_outline</span>
                    <h5 id="uploadErrorModalTitle">Upload ditolak</h5>
                    <p id="uploadErrorModalMessage" class="mb-0"></p>
                    <button type="button" class="btn release-upload-error-close" data-bs-dismiss="modal">Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Track Modal --}}
    <div x-data="$store.tray">
        @if($release->type == 'album')
            <div class="modal fade" id="addTrack" aria-modal="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header border-bottom-0 py-2">
                            <h5 class="modal-title" x-show="$store.tray.trackTrayMode == 'add'">Add Track</h5>
                            <h5 class="modal-title" x-show="$store.tray.trackTrayMode == 'edit'">Edit Track</h5>

                            <a href="javascript:;" data-bs-dismiss="modal">
                                <i class="material-icons-outlined">close</i>
                            </a>
                        </div>
                        <div class="modal-body" style="max-height: 80vh" data-simplebar="true">
                            <div class="form-body">
                                <form action="#" id="track-form"
                                    @submit.prevent="$store.tray.trackTrayMode == 'add' ? $store.tray.methods.addTrack() : $store.tray.methods.updateTrack($store.tray.trackTray.index)">
                                    @include('partials.album-track-form-fields')
                                </form>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="d-md-flex d-grid align-items-center gap-3">
                                <button type="submit" form="track-form" class="btn btn-primary px-4 ms-auto"
                                    x-show="$store.tray.trackTrayMode == 'add'"><i class="material-icons-outlined">library_add</i> Tambahkan track</button>
                                <button type="submit" form="track-form" class="btn btn-primary px-4 ms-auto"
                                    x-show="$store.tray.trackTrayMode == 'edit'"><i class="material-icons-outlined">save</i> Simpan perubahan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

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
    <link href="{{ URL::asset('build/plugins/bs-stepper/css/bs-stepper.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/css/album.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/plugins/notifications/css/lobibox.css') }}" rel="stylesheet">
    <style>
        .release-create-header{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:30px}.release-create-title-block .breadcrumb{font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.release-create-title-block .breadcrumb a{color:#ff7043;text-decoration:none}.release-create-title-block h1{margin:8px 0 5px;color:#f7f9ff;font-size:clamp(1.9rem,3vw,2.7rem);font-weight:800;letter-spacing:-.04em}.release-create-title-block p{margin:0;color:#8faed4;font-size:.98rem}.release-save-draft{display:inline-flex;align-items:center;gap:8px;border:1px solid #ff7043!important;border-radius:10px!important;background:rgba(255,112,67,.12)!important;color:#ff8a65!important;font-weight:700;padding:10px 15px!important;white-space:nowrap}.release-save-draft:hover{background:#ff7043!important;color:#fff!important}
        .release-creation-page{max-width:1180px;margin:0 auto;padding-bottom:40px}.release-creation-page #stepper{background:transparent;border:0;box-shadow:none}.release-step-nav{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(0,1fr);align-items:stretch;gap:8px;margin:0 0 28px;padding:7px;border:1px solid #28476a;border-radius:16px;background:rgba(18,35,57,.92)}.release-step-nav .step{min-width:0}.release-step-nav .step-trigger{width:100%;min-height:66px;display:flex;align-items:center;gap:11px;padding:12px 14px;border:1px solid transparent!important;border-radius:11px!important;background:transparent!important;color:#90a8c6!important;text-align:left}.release-step-nav .step.active .step-trigger{border-color:rgba(255,128,84,.55)!important;background:linear-gradient(135deg,rgba(255,112,67,.24),rgba(255,142,81,.10))!important;color:#fff!important;box-shadow:inset 0 1px 0 rgba(255,255,255,.06)}.release-step-nav .bs-stepper-circle{display:grid;place-items:center;flex:0 0 31px;width:31px;height:31px;margin:0;border:1px solid #45698f;border-radius:50%;background:#0b1a2e;color:#b8cce6;font-weight:800}.release-step-nav .active .bs-stepper-circle{border-color:#ff7043;background:#ff7043;color:#fff}.release-step-nav .steper-title{font-size:.92rem!important;font-weight:800;line-height:1.2}.release-step-nav .steper-sub-title{margin-top:3px!important;color:#7794b6;font-size:.73rem;line-height:1.25}.release-step-nav .bs-stepper-line{display:none!important}
        .release-creation-page .bs-stepper-content{padding:0}.release-creation-page .bs-stepper-pane{padding:0}.release-step-heading{margin:0 0 23px}.release-step-kicker{display:block;margin-bottom:5px;color:#ff8a65;font-size:.69rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.release-step-heading h2{margin:0;color:#f8fbff;font-size:1.65rem;font-weight:800}.release-step-heading p{margin:7px 0 0;color:#91acd0}.release-cover-section{padding:24px;border:1px solid #2d4d71;border-radius:18px;background:linear-gradient(135deg,rgba(24,48,76,.9),rgba(14,28,47,.96));box-shadow:0 16px 34px rgba(0,0,0,.16)}.release-cover-section>.form-label{display:flex;align-items:center;gap:8px;margin:0 0 16px;color:#ecf4ff;font-size:1rem;font-weight:800}.release-cover-section>.form-label span{padding:3px 8px;border-radius:20px;background:rgba(255,112,67,.15);color:#ff936f;font-size:.65rem;letter-spacing:.04em;text-transform:uppercase}.release-cover-layout{display:grid;grid-template-columns:minmax(210px,260px) minmax(0,1fr);gap:30px;align-items:center}.release-cover-upload{padding:11px;border:1px dashed #51749a;border-radius:15px;background:#0b1a2e}.release-cover-upload label:first-child{display:block;overflow:hidden;border-radius:10px;cursor:pointer}.release-cover-upload .album-cover{display:block;width:100%;aspect-ratio:1;object-fit:cover}.release-cover-actions{display:flex;gap:8px;margin-top:11px}.release-cover-actions .btn{flex:1;border-radius:8px;font-size:.76rem;font-weight:700}.release-upload-button{display:inline-flex!important;align-items:center;justify-content:center;gap:5px;background:#ff7043!important;color:#fff!important}.release-upload-button:hover{background:#ff8a65!important}.release-remove-button{border:1px solid #47688d!important;color:#9eb8d8!important}.release-cover-guidelines{display:flex;gap:16px;padding:18px;border:1px solid rgba(93,137,180,.36);border-radius:14px;background:rgba(6,18,34,.38)}.release-guideline-icon{display:grid;place-items:center;flex:0 0 42px;height:42px;border-radius:12px;background:rgba(255,112,67,.15);color:#ff8a65}.release-cover-guidelines h3{margin:0 0 6px;color:#fff;font-size:1rem;font-weight:800}.release-cover-guidelines p,.release-cover-guidelines li{color:#a8bfd9;font-size:.84rem;line-height:1.55}.release-cover-guidelines p{margin-bottom:7px}.release-cover-guidelines ul{margin:0;padding-left:18px}.release-cover-guidelines li::marker{color:#ff8a65}
        .release-metadata-section{margin-top:22px;padding:24px;border:1px solid #2d4d71;border-radius:18px;background:#101f34}.release-metadata-section>header{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:21px;padding-bottom:16px;border-bottom:1px solid #294766}.release-metadata-section h3{margin:0;color:#f7faff;font-size:1.2rem;font-weight:800}.release-metadata-section p{margin:5px 0 0;color:#8da9cb;font-size:.87rem}.release-metadata-section>header>i{display:grid;place-items:center;width:38px;height:38px;border-radius:11px;background:rgba(108,142,255,.14);color:#8aabff}.release-metadata-section .form-label,.release-detail-card .form-label{color:#c4d6ed;font-size:.82rem;font-weight:700}.release-metadata-section .form-control,.release-metadata-section .form-select,.release-metadata-section .input-group-text,.release-detail-card .form-control{min-height:42px;border-color:#345879!important;background:#0b182a!important;color:#eef5ff!important;border-radius:9px!important}.release-metadata-section .form-control:focus,.release-metadata-section .form-select:focus,.release-detail-card .form-control:focus{border-color:#ff7043!important;box-shadow:0 0 0 .2rem rgba(255,112,67,.16)!important}.release-metadata-section .track-placeholder{border-color:#4c7198!important;background:#0b182a!important;color:#dce9f9}.release-metadata-section .track-placeholder .material-icons-outlined{color:#ff8a65}.release-metadata-section .btn-outline-primary{border-color:#5f88b6!important;color:#a9c9f1!important}.release-metadata-section .btn-outline-primary:hover{background:#31587d!important;color:#fff!important}.release-step-footer,.release-detail-footer{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:24px;padding:16px 2px;color:#8da9cb;font-size:.83rem}.release-step-footer span{display:inline-flex;align-items:center;gap:6px}.release-step-footer span i{font-size:18px;color:#ff8a65}.release-next-button{display:inline-flex;align-items:center;gap:6px;border:0!important;border-radius:10px!important;background:#ff7043!important;color:#fff!important;font-weight:800;padding:12px 17px!important}.release-next-button:hover{background:#ff8a65!important;transform:translateY(-1px)}.release-previous-button{display:inline-flex;align-items:center;border:1px solid #55779c!important;border-radius:10px!important;background:transparent!important;color:#b9d0e9!important;font-weight:700;padding:11px 16px!important}.release-previous-button:hover{border-color:#ff8a65!important;color:#ff9b7d!important}
        .release-detail-card,.release-tracks-card{overflow:hidden;border:1px solid #2d4d71;border-radius:18px;background:#101f34;box-shadow:0 16px 34px rgba(0,0,0,.16)}.release-detail-heading,.release-review-topbar,.release-tracks-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:24px 25px 20px;border-bottom:1px solid #294766;background:linear-gradient(120deg,rgba(255,112,67,.12),rgba(19,42,68,.1))}.release-detail-heading h2,.release-review-topbar h2,.release-tracks-heading h2{margin:0;color:#f7faff;font-size:1.42rem;font-weight:800}.release-detail-heading p,.release-review-topbar p,.release-tracks-heading p{margin:6px 0 0;color:#91acd0;font-size:.9rem}.release-heading-icon{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;border-radius:12px;background:rgba(255,112,67,.15);color:#ff8a65}.release-detail-body{padding:24px}.release-status-choice{padding:18px;border:1px solid #2c4b6d;border-radius:13px;background:#0d1b2e}.release-status-choice>.form-label{margin-bottom:13px;color:#eff6ff;font-size:.92rem}.release-radio-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:11px}.release-radio-option{display:flex;gap:9px;align-items:flex-start;min-height:76px;padding:13px;border:1px solid #345879;border-radius:10px;background:#10223a;cursor:pointer}.release-radio-option:has(input:checked){border-color:#ff7043;background:rgba(255,112,67,.11)}.release-radio-option input{margin:4px 0 0;accent-color:#ff7043}.release-radio-option span{display:grid;gap:3px;color:#eaf3ff;font-size:.84rem}.release-radio-option small{color:#89a8cc;font-size:.74rem;line-height:1.35}.release-previous-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:15px;padding-top:15px;border:0;border-top:1px solid #294766}.release-required-label{display:inline-block;margin-left:5px;padding:2px 6px;border-radius:999px;background:rgba(255,112,67,.16);color:#ff966f;font-size:.61rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.release-schedule-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-top:18px}.release-date-field{padding:17px;border:1px solid #2c4b6d;border-radius:13px;background:#0d1b2e}.release-date-field p{min-height:34px;margin:5px 0 12px;color:#8faed1;font-size:.78rem;line-height:1.4}.release-date-field em{color:#8ea8c6;font-size:.7rem;font-style:normal;font-weight:400}.release-detail-footer{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:0;padding:18px 24px;border-top:1px solid #294766;background:rgba(7,18,31,.38)}.release-add-track{display:inline-flex;align-items:center;gap:6px;border:0!important;border-radius:10px!important;background:#ff7043!important;color:#fff!important;font-weight:800;padding:11px 15px!important;white-space:nowrap}.release-add-track:hover{background:#ff8a65!important}.release-tracks-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:20px 24px 0}.release-tracks-summary>div{display:flex;align-items:center;gap:10px;padding:13px 15px;border:1px solid #315779;border-radius:11px;background:#0b1a2e}.release-tracks-summary i{display:grid;place-items:center;width:32px;height:32px;border-radius:9px;background:rgba(108,142,255,.15);color:#91b3ff}.release-tracks-summary span{display:grid;gap:2px}.release-tracks-summary small{color:#8ca8c9;font-size:.72rem}.release-tracks-summary strong{color:#f2f7ff;font-size:.95rem}.release-track-list{display:grid;gap:10px;padding:20px 24px}.release-track-empty{display:grid;justify-items:center;gap:5px;padding:36px 15px;border:1px dashed #46698f;border-radius:13px;background:rgba(11,26,46,.56);color:#8faed2;text-align:center}.release-track-empty i{font-size:33px;color:#ff8a65}.release-track-empty strong{color:#e5effb;font-size:.95rem}.release-track-empty span{font-size:.78rem}.release-track-item{display:flex;align-items:stretch;overflow:hidden;border:1px solid #335779;border-radius:12px;background:#0c1b2f}.release-track-main{display:flex;align-items:center;flex:1;min-width:0;gap:12px;padding:12px;border:0;background:transparent;color:#eaf3ff;text-align:left}.release-track-main:hover{background:rgba(69,104,142,.16)}.release-track-number{display:grid;place-items:center;flex:0 0 31px;width:31px;height:31px;border-radius:9px;background:rgba(255,112,67,.15);color:#ff906e;font-weight:800}.release-track-name{display:grid;min-width:0;gap:3px}.release-track-name strong{overflow:hidden;color:#eff6ff;font-size:.91rem;text-overflow:ellipsis;white-space:nowrap}.release-track-name small{color:#8faed1;font-size:.71rem}.release-track-duration{display:flex;align-items:center;gap:4px;margin-left:auto;color:#adcae8;font-size:.78rem;white-space:nowrap}.release-track-duration i{font-size:16px;color:#6fa7ff}.release-track-actions{display:flex;align-items:center;padding:7px;border-left:1px solid #294766}.release-track-actions button{display:grid;place-items:center;width:29px;height:29px;border:0;border-radius:7px;background:transparent;color:#a7c1df}.release-track-actions button:hover{background:#294969;color:#fff}.release-track-actions .release-track-remove{color:#ff8b8b}.release-track-actions .release-track-remove:hover{background:rgba(255,93,93,.16);color:#ff9d9d}.release-creation-page #step-4{padding:0;overflow:hidden;border:1px solid #2d4d71;border-radius:18px;background:#101f34}.release-creation-page #step-4 .alert{margin:20px 24px;border:1px solid rgba(255,158,83,.35);border-radius:12px;background:rgba(255,145,62,.1);color:#ffd5ad}.release-creation-page #step-4 .row{padding:0 18px 5px}.release-creation-page #step-4 .album-items{overflow:hidden;border:1px solid #2c4b6d;border-radius:12px;background:#0b182a;color:#e9f3ff}.release-creation-page #step-4 .album-items td{padding:12px 15px;border-color:#294766;color:#d7e6f8}.release-creation-page #step-4 .album-items td:first-child{width:45%;color:#8eacce;font-size:.78rem;font-weight:800;text-transform:uppercase}.release-creation-page #step-4 .album-cover{max-width:230px;border-radius:13px;box-shadow:0 14px 28px rgba(0,0,0,.28)}.release-review-footer{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-top:17px;padding:18px 24px;border-top:1px solid #294766;background:rgba(7,18,31,.38);color:#8da9cb;font-size:.82rem}.release-review-footer span{display:flex;align-items:center;gap:6px}.release-review-footer span i{color:#54d891}.release-submit-button{display:inline-flex;align-items:center;border:0!important;border-radius:10px!important;background:#26b66e!important;color:#fff!important;font-weight:800;padding:12px 17px!important}.release-submit-button:hover{background:#31ca7e!important}
        .release-review-tracks{margin:20px 24px 24px;padding:19px;border:1px solid #2d4d71;border-radius:14px;background:#0c1b2f}.release-review-tracks>header{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;padding-bottom:14px;border-bottom:1px solid #294766}.release-review-tracks h3{margin:0;color:#f5f9ff;font-size:1rem;font-weight:800}.release-review-tracks p{margin:4px 0 0;color:#8eacce;font-size:.78rem}.release-review-tracks>header>span{display:grid;place-items:center;width:35px;height:35px;border-radius:10px;background:rgba(108,142,255,.14);color:#90b1ff}.release-review-track-list{display:grid;gap:9px;margin-top:14px}.release-review-track-item{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:11px 12px;border:1px solid #294a6c;border-radius:11px;background:#101f34}.release-review-track-info{display:flex;align-items:center;gap:10px;min-width:0}.release-review-track-info>div{display:grid;min-width:0;gap:4px}.release-review-track-info strong{overflow:hidden;color:#edf5ff;font-size:.87rem;text-overflow:ellipsis;white-space:nowrap}.release-review-track-info small{display:flex;align-items:center;gap:4px;color:#8eacce;font-size:.72rem}.release-review-track-info small i{font-size:15px;color:#75aaff}.release-review-audio{min-width:260px}.release-review-audio audio{display:block;width:100%;height:32px}.release-review-audio>span{display:flex;align-items:center;justify-content:flex-end;gap:5px;color:#8ca8c7;font-size:.72rem}.release-review-audio>span i{font-size:17px}.release-ipl-field{margin-top:-4px;margin-bottom:12px}.release-ipl-card{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:16px;align-items:center;padding:17px 18px;border:1px solid #45688f;border-radius:13px;background:linear-gradient(120deg,rgba(46,83,122,.24),rgba(10,27,46,.42))}.release-ipl-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:rgba(112,157,255,.15);color:#91b3ff}.release-ipl-copy .form-label{color:#eaf3ff;font-size:.87rem}.release-ipl-copy p{margin:6px 0 0;color:#8eacce;font-size:.78rem;line-height:1.45}.release-ipl-current{display:flex;align-items:center;gap:5px;margin-top:7px;color:#59d994;font-size:.78rem}.release-ipl-current a{color:#8ac5ff;text-decoration:none}.release-ipl-action{display:grid;justify-items:end;gap:5px}.release-ipl-action input{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}.release-ipl-upload{display:inline-flex!important;align-items:center;gap:7px;border:0!important;border-radius:9px!important;background:#ff7043!important;color:#fff!important;font-weight:800;white-space:nowrap;padding:10px 13px!important}.release-ipl-upload:hover{background:#ff8a65!important}.release-ipl-action small{max-width:190px;color:#86a3c5;font-size:.68rem;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.release-upload-error-modal{z-index:1085!important}.modal-backdrop.release-upload-error-backdrop{z-index:1080!important}.release-upload-error-modal .modal-content{overflow:hidden;border:1px solid #5f4a51;border-radius:17px;background:#122037;color:#edf5ff;box-shadow:0 24px 60px rgba(0,0,0,.45)}.release-upload-error-modal .modal-body{padding:30px}.release-upload-error-icon{display:grid;place-items:center;width:52px;height:52px;margin:0 auto 13px;border-radius:50%;background:rgba(255,91,91,.15);color:#ff7676;font-size:29px}.release-upload-error-modal h5{margin:0 0 8px;color:#fff;font-size:1.18rem;font-weight:800}.release-upload-error-modal p{color:#b9cbe3;white-space:pre-line;line-height:1.55}.release-upload-error-close{margin-top:20px;border:0!important;border-radius:9px!important;background:#ff7043!important;color:#fff!important;font-weight:800;padding:10px 23px!important}.release-upload-error-close:hover{background:#ff8a65!important}
        #addTrack .modal-dialog{max-width:min(1040px,calc(100vw - 28px))}#addTrack .modal-content{border:1px solid #315779;background:#101f34;color:#eaf3ff}#addTrack .modal-header,#addTrack .modal-footer{border-color:#294766!important}#addTrack .modal-body{padding:24px!important;background:#0d1b2e}#addTrack .form-label{color:#c7daf1;font-weight:700;font-size:.8rem}#addTrack .form-control,#addTrack .form-select,#addTrack .input-group-text{border-color:#345879!important;background:#091728!important;color:#edf5ff!important;border-radius:8px!important}#addTrack .form-control:focus,#addTrack .form-select:focus{border-color:#ff7043!important;box-shadow:0 0 0 .2rem rgba(255,112,67,.14)!important}#addTrack .track-placeholder{min-height:155px!important;border-color:#527ba4!important;background:#0b1a2e!important;color:#eaf3ff}#addTrack .track-placeholder .material-icons-outlined{color:#ff8a65}#addTrack .btn-primary{border:0!important;background:#ff7043!important}#addTrack .btn-outline-primary{border-color:#527ba4!important;color:#b9d4f2!important}
        @media (max-width:767.98px){.release-create-header{align-items:flex-start;flex-direction:column;margin-bottom:22px}.release-save-draft{width:100%;justify-content:center}.release-step-nav{display:grid;grid-auto-flow:row;grid-template-columns:1fr;gap:4px}.release-step-nav .step-trigger{min-height:52px;padding:10px}.release-cover-layout{grid-template-columns:1fr;gap:18px}.release-cover-upload{max-width:260px;margin:auto}.release-cover-guidelines{padding:14px}.release-metadata-section,.release-cover-section,.release-detail-body,.release-detail-heading,.release-tracks-heading{padding:17px}.release-tracks-heading{align-items:stretch;flex-direction:column}.release-add-track{justify-content:center}.release-tracks-summary{grid-template-columns:1fr;padding:17px 17px 0}.release-track-list{padding:17px}.release-track-name small{display:none}.release-track-item{align-items:center}.release-track-actions{border-left:0}.release-track-duration{display:none}.release-step-footer,.release-detail-footer{align-items:stretch;flex-direction:column}.release-next-button,.release-previous-button{justify-content:center}.release-step-nav .steper-sub-title{display:none}.release-radio-options,.release-previous-fields,.release-schedule-grid{grid-template-columns:1fr}.release-date-field p{min-height:0}.release-creation-page #step-4{padding:17px}.release-review-tracks{margin:17px}.release-review-track-item{align-items:stretch;flex-direction:column}.release-review-audio{min-width:0;width:100%}.release-review-audio>span{justify-content:flex-start}.release-ipl-card{grid-template-columns:auto minmax(0,1fr);gap:12px}.release-ipl-action{grid-column:1/-1;justify-items:stretch}.release-ipl-upload{justify-content:center}.release-ipl-action small{max-width:none;text-align:center}#addTrack .modal-body{padding:17px!important}}
        .release-add-artist-button{display:flex!important;align-items:center;gap:11px;width:100%;min-height:58px;padding:10px 13px!important;border:1px dashed #527ba4!important;border-radius:10px!important;background:rgba(13,31,52,.56)!important;color:#b9d5f4!important;text-align:left}.release-add-artist-button>i{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;border-radius:9px;background:rgba(108,142,255,.15);color:#91b3ff;font-size:20px}.release-add-artist-button span{display:grid;gap:2px}.release-add-artist-button strong{color:#dcecff;font-size:.83rem;font-weight:800}.release-add-artist-button small{color:#88a8c9;font-size:.7rem;line-height:1.3}.release-add-artist-button:hover{border-color:#ff7043!important;background:rgba(255,112,67,.09)!important;color:#fff!important}.release-add-artist-button:hover>i{background:rgba(255,112,67,.15);color:#ff916f}.release-inline-action{display:flex!important;align-items:center;gap:11px;width:100%;min-height:55px;padding:9px 13px!important;border:1px dashed #527ba4!important;border-radius:10px!important;background:linear-gradient(110deg,rgba(13,31,52,.78),rgba(19,43,70,.7))!important;color:#c4daf3!important;text-align:left}.release-inline-action>i{display:grid;place-items:center;flex:0 0 33px;width:33px;height:33px;border-radius:9px;background:rgba(255,112,67,.14);color:#ff906e;font-size:19px}.release-inline-action span{display:grid;gap:2px}.release-inline-action strong{color:#e5f0ff;font-size:.82rem;font-weight:800}.release-inline-action small{color:#88a8c9;font-size:.69rem;line-height:1.3}.release-inline-action:hover{border-color:#ff7043!important;background:linear-gradient(110deg,rgba(255,112,67,.14),rgba(29,57,87,.78))!important;transform:translateY(-1px)}.release-inline-action:hover>i{background:#ff7043;color:#fff}.release-creation-page .btn-outline-danger{display:inline-flex!important;align-items:center;justify-content:center;gap:5px;border-radius:8px!important;border-color:rgba(255,111,111,.58)!important;background:rgba(255,93,93,.08)!important;color:#ff9d9d!important;font-weight:700}.release-creation-page .btn-outline-danger:hover{background:rgba(255,93,93,.2)!important;color:#fff!important}.release-creation-page .btn-outline-secondary{border-radius:7px!important;border-color:#426789!important;background:#0d2036!important;color:#a9c8e8!important}.release-creation-page .btn-outline-secondary:hover{border-color:#709bc3!important;background:#284766!important;color:#fff!important}#addTrack .btn-primary{display:inline-flex!important;align-items:center;gap:7px;border-radius:9px!important;box-shadow:0 8px 18px rgba(255,112,67,.22)}
        #addTrack .modal-dialog{max-width:min(860px,calc(100vw - 28px))}#addTrack .modal-header{padding:15px 20px!important}#addTrack .modal-footer{padding:12px 20px!important}#addTrack .modal-body{max-height:68vh!important;padding:18px!important}#addTrack .track-placeholder{min-height:116px!important;padding:14px!important}#addTrack .track-placeholder .material-icons-outlined{font-size:37px!important}#addTrack .track-placeholder .fw-bold{margin-top:5px!important;font-size:.9rem}#addTrack .track-placeholder .mt-2{margin-top:5px!important}#addTrack .track-placeholder p{margin-bottom:4px!important;font-size:.75rem}#addTrack .row.g-3{--bs-gutter-y:.8rem}#addTrack ul{margin-top:4px;padding-left:20px;font-size:.78rem;line-height:1.4}#addTrack .form-label{margin-bottom:5px}#addTrack .form-control,#addTrack .form-select{min-height:38px}
    </style>
@endpush
@push('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="{{ URL::asset('build/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/notifications/js/lobibox.js') }}"></script>
    @include('partials.track-form-scripts')
    <script type="application/json" id="json-primaryartists">@json(old('primaryartists', $release->primaryartists) ?? [])</script>
    <script type="application/json" id="json-subgenres">@json(\App\Models\System\Subgenre::where('genreid', old('genre', $release->genre) ?? 1)->orderBy('name')->get())</script>
    <script type="application/json" id="json-tracks">@json(old('tracks', $release->tracks) ?? [])</script>
    <script type="application/json" id="json-contributors">@json(\App\Models\System\Contributor::all() ?? [])</script>
    <script>
        window.showUploadError = function (message, title = 'Upload ditolak') {
            const normalizeMessage = (value) => {
                if (typeof value === 'string') return value;
                if (Array.isArray(value)) return value.join('\n');
                if (value && typeof value === 'object') return Object.values(value).flat().join('\n');
                return 'File tidak dapat diunggah. Silakan periksa kembali ketentuannya.';
            };

            document.getElementById('uploadErrorModalTitle').textContent = title;
            document.getElementById('uploadErrorModalMessage').textContent = normalizeMessage(message);
            const $modal = $('#uploadErrorModal');
            $modal.off('shown.bs.modal.releaseUploadError').one('shown.bs.modal.releaseUploadError', function () {
                // Bootstrap creates a separate backdrop for this nested modal.
                // Raise that backdrop together with the error dialog above Add Track.
                $('.modal-backdrop').last().addClass('release-upload-error-backdrop');
            });
            $modal.modal('show');
        };

        window.syncTrackIpl = function (event) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;

            if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
                event.target.value = '';
                window.showUploadError('IPL harus berupa dokumen PDF.', 'IPL ditolak');
                return;
            }

            const releaseIplInput = document.getElementById('ipl');
            if (!releaseIplInput) return;

            const transfer = new DataTransfer();
            transfer.items.add(file);
            releaseIplInput.files = transfer.files;

            if (window.Alpine) {
                Alpine.store('tray').trackTray.iplFileName = file.name;
                Alpine.store('release').iplName = file.name;
            }
        };

        // Draft function
        function saveDraft() {
            Alpine.store('release').methods.store();
        }

        // Normalize artist arrays (backward compatible)
        function normalizeArtistArray(artists) {
            if (!Array.isArray(artists)) {
                return [];
            }

            return artists.map(function (artist) {
                // Old format: string value
                if (typeof artist === 'string' || artist === null || artist === undefined) {
                    return {
                        name: artist || '',
                        social: '',
                        spotify: '',
                    };
                }

                // Object format (may or may not have all properties)
                return {
                    name: artist.name || '',
                    social: artist.social || '',
                    spotify: artist.spotify || '',
                };
            });
        }

        $(document).ready(function () {

            // Initialize Stepper
            stepper = new Stepper(document.querySelector('#stepper'), {
                linear: false
            });

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

            // Listen modal
            $('#validationModal').on('hidden.bs.modal', function () {
                // Check if the current URL contains 'create'
                if (window.location.pathname.includes('create') && Alpine.store('release').id) {
                    // Redirect to the edit page
                    window.location.href = "{{ route('releases.edit', ':id') }}".replace(':id', Alpine.store('release').id);
                }
            });
        
            Alpine.store('release').methods.determinePreRelease();
        
        });

        document.addEventListener('alpine:init', () => {
            Alpine.store('validation', {
                part: null,
                errors: [],

                methods: {
                    get validationHeader() {
                        var msg = 'Validation error(s) found.';
                        if (Alpine.store('validation').part == 'release') {
                            msg = 'There are validation error(s) found for the single/album information or details.';
                        }
                        else if (Alpine.store('validation').part == 'track') {
                            if (Alpine.store('validation').position) {
                                msg = 'There are validation error(s) found in track number ' + Alpine.store('validation').position + '.';
                            }
                            else {
                                msg = 'There are validation error(s) found in one of the tracks.';
                            }
                        }

                        return msg;
                    }
                }
            });

            var releasePrimaryArtist = JSON.parse(document.getElementById('json-primaryartists').textContent || '[]');
            releasePrimaryArtist = normalizeArtistArray(releasePrimaryArtist);

            Alpine.store('release', {
                init() {
                    const self = this;
                    if ('{{ $release->type }}' === 'single') {
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

                        Alpine.effect(() => {
                            if (self.tracks[0]) {
                                self.tracks[0].title = self.title;
                                self.tracks[0].titlelang = self.titlelang;
                                self.tracks[0].version = self.version;
                                self.tracks[0].genre = self.genre;
                                self.tracks[0].subgenre = self.subgenre;
                                self.tracks[0].primaryartists = JSON.parse(JSON.stringify(self.primaryartists || []));
                            }
                        });

                        if (self.tracks[0].genre) {
                            window.fetchSubgenresForScope('$store.release.tracks[0]');
                        }
                    }
                },
                primaryartists: releasePrimaryArtist,
                subgenres: JSON.parse(document.getElementById('json-subgenres').textContent),
                contributorRoles: JSON.parse(document.getElementById('json-contributors').textContent),
                tracks: JSON.parse(document.getElementById('json-tracks').textContent),
                coverImage: @if($release->cover_url) "{{ $release->cover_url }}" @else"{{URL::asset('build/images/album-placeholder.webp')}}" @endif,
                id:             {{ old('id', $release->id) ?? 0 }},
                version:        "{{ old('version', $release->version) ?? 1 }}",
                genre:          "{{ old('genre', $release->genre) ?? 1 }}",
                subgenre:       "{{ old('subgenre', $release->subgenre) != 0 ? old('subgenre', $release->subgenre) : '' }}",
                released:       @json((string) old('released', $release->released ?? 0)),
                title: "{{ old('title', $release->title) ?? '' }}",
                titlelang: "{{ old('titlelang', $release->titlelang) ?? 'id' }}",
                recordlabel: @json(current_user()->profile->recordLabelFor(old('recordlabel', $release->recordlabel) ?? '')),
                upc: "{{ old('upc', $release->upc) ?? '' }}",
                releaseon: "{{ old('releaseon', $release->releaseon) ?? '' }}",
                tiktok_ytmusic_rerelease_date: "{{ old('tiktok_ytmusic_rerelease_date', $release->tiktok_ytmusic_rerelease_date) ?? '' }}",
                iplUrl: "{{ $release->ipl_url ?? '' }}",
                iplName: "{{ $release->ipl ? $release->ipl->original_name : '' }}",

                trackSubGenres: [],
                methods: {
                    async fetchSubgenres() {
                        Alpine.store('release').subgenres = [];

                        if (Alpine.store('release').genre) {
                            var url = "{{ route('releases.get_subgenre', ':id') }}"
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
                            document.getElementById('cover-input').value = '';
                            window.showUploadError(error, 'Cover art ditolak');
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
                        @if($release->type == 'single')
                            if (Alpine.store('release').tracks.length > 0) {
                                Lobibox.notify('error', {
                                    pauseDelayOnHover: true,
                                    size: 'mini',
                                    rounded: true,
                                    position: 'top right',
                                    icon: 'bi bi-exclamation-triangle',
                                    msg: 'Single release can only have one track.'
                                });
                                return;
                            }
                        @endif
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
                        console.log(Alpine.store('tray').trackTray);

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
                        if (index == Alpine.store('release')[arr].length - 1) return;
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
                        let seconds = Math.floor(duration % 60).toString().padStart(2, '0');

                        return `${hours}:${minutes}:${seconds}`;
                    },
                    determinePreRelease() {
                        const planned = Alpine.store('release').releaseon;
                        const date = new Date(`${planned}T00:00:00`);
                        const input = $('#tiktok_ytmusic_rerelease_date');
                        if(!isNaN(date.getTime())){
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
                    async store() {
                        try {
                            // Set overlay
                            Alpine.store('tray').overlayActive = true;
                            Alpine.store('tray').overlayText = "Saving album data";

                            // Get form data
                            const form = document.querySelector('#release-form');
                            // Serialize tracks as JSON to avoid PHP max_input_vars truncation
                            document.getElementById('tracks-json-input').value = JSON.stringify(
                                Alpine.store('release').tracks.map((track, index) => ({ ...track, order: index }))
                            );
                            const formData = new FormData(form);

                            // Set method and url
                            const method = 'POST';
                            let url = "@if($release->id) {{ route('releases.update', ':id') }} @else {{ route('releases.store') }} @endif";
                            url = url.replace(':id', Alpine.store('release').id);

                            // Save draft
                            await ajaxCall(
                                url, method,
                                formData, false, false,
                                (data) => {
                                    // Fill release ID
                                    Alpine.store('release').id = data.id;

                                    // Fill tracks ID
                                    data.tracks.forEach((id, i) => {
                                        Alpine.store('release').tracks[i].id = id
                                    });

                                    // Unset cover input
                                    Alpine.store('release').cover = "";
                                    document.getElementById('cover-input').value = '';
                                    document.getElementById('ipl').value = '';
                                    if (data.ipl_url) {
                                        Alpine.store('release').iplUrl = data.ipl_url;
                                        Alpine.store('release').iplName = data.ipl_name;
                                    }
                                },
                                (data) => {
                                    Alpine.store('validation').part = data.responseJSON.part;
                                    Alpine.store('validation').errors = data.responseJSON.errors;
                                    Alpine.store('validation').position = data.responseJSON.position;
                                    $('#validationModal').modal('show');
                                },
                            )

                            // Success
                            Lobibox.notify('success', {
                                pauseDelayOnHover: true,
                                size: 'mini',
                                rounded: true,
                                position: 'top right',
                                icon: 'bi bi-check-circle',
                                msg: 'Album saved.'
                            });

                            // Check if the current URL contains 'create'
                            if (window.location.pathname.includes('create')) {
                                // Redirect to the edit page
                                window.location.href = "{{ route('releases.edit', ':id') }}".replace(':id', Alpine.store('release').id);
                            }

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
                            Alpine.store('tray').overlayActive = false;
                        }
                    },
                    async submit() {
                        try {
                            // Set overlay
                            Alpine.store('tray').overlayActive = true;
                            Alpine.store('tray').overlayText = "Saving album data";

                            // Get form data
                            const form = document.querySelector('#release-form');
                            // Serialize tracks as JSON to avoid PHP max_input_vars truncation
                            document.getElementById('tracks-json-input').value = JSON.stringify(
                                Alpine.store('release').tracks.map((track, index) => ({ ...track, order: index }))
                            );
                            const formData = new FormData(form);

                            // Set method and url
                            const method = 'POST';
                            let url = "@if($release->id) {{ route('releases.update', ':id') }} @else {{ route('releases.store') }} @endif";
                            url = url.replace(':id', Alpine.store('release').id);

                            // Save draft
                            await ajaxCall(
                                url, method,
                                formData, false, false,
                                (data) => {
                                    // Fill release ID
                                    Alpine.store('release').id = data.id;

                                    // Fill tracks ID
                                    data.tracks.forEach((id, i) => {
                                        Alpine.store('release').tracks[i].id = id
                                    });

                                    // Unset cover input
                                    Alpine.store('release').cover = "";
                                    document.getElementById('cover-input').value = '';
                                    document.getElementById('ipl').value = '';
                                    if (data.ipl_url) {
                                        Alpine.store('release').iplUrl = data.ipl_url;
                                        Alpine.store('release').iplName = data.ipl_name;
                                    }
                                },
                                (data) => {
                                    Alpine.store('validation').part = data.responseJSON.part;
                                    Alpine.store('validation').errors = data.responseJSON.errors;
                                    Alpine.store('validation').position = data.responseJSON.position;
                                    $('#validationModal').modal('show');
                                },
                            )

                            // Success
                            Lobibox.notify('success', {
                                pauseDelayOnHover: true,
                                size: 'mini',
                                rounded: true,
                                position: 'top right',
                                icon: 'bi bi-check-circle',
                                msg: 'Album saved.'
                            });

                            // Set overlay
                            Alpine.store('tray').overlayText = "Validating submission";

                            // Validating submission
                            await ajaxCall(
                                "{{ route('releases.submit', ':id') }}".replace(':id', Alpine.store('release').id),
                                'POST', false, false, false,
                                (data) => { },
                                (data) => {
                                    Alpine.store('validation').part = data.responseJSON.part;
                                    Alpine.store('validation').errors = data.responseJSON.errors;
                                    Alpine.store('validation').position = data.responseJSON.position;
                                    $('#validationModal').modal('show');
                                },
                            )

                            // Redirect to show page
                            window.location.href = "{{ route('releases.show', ':id') }}".replace(':id', Alpine.store('release').id);
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
                            Alpine.store('tray').overlayActive = false;
                        }
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
                },
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

                                filefullname: '',
                                filefullcontent: '',
                                filefullinput: '',
                                filefulltemp: '',

                                filetrimmedname: '',
                                filetrimmedcontent: '',
                                filetrimmedinput: '',
                                filetrimmedtemp: '',
                                iplFileName: '',
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
                            if (Number(Alpine.store('tray').trackTray.version) !== 1 && !Alpine.store('release').iplUrl && !Alpine.store('tray').trackTray.iplFileName) {
                                throw 'Upload Music Usage License (IPL) dalam format PDF untuk versi selain Original.';
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
                            Alpine.store('tray').overlayActive = false;
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
                            if (Alpine.store('tray').trackTray.writer_percentage === '' || Alpine.store('tray').trackTray.writer_percentage < 0 || Alpine.store('tray').trackTray.writer_percentage > 100) {
                                throw 'Please enter a valid writer percentage (0-100).';
                            }
                            if (Number(Alpine.store('tray').trackTray.version) !== 1 && !Alpine.store('release').iplUrl && !Alpine.store('tray').trackTray.iplFileName) {
                                throw 'Upload Music Usage License (IPL) dalam format PDF untuk versi selain Original.';
                            }

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
                            Alpine.store('tray').overlayActive = false;
                        }
                    },
                    async fetchSubgenres(subgenre = null) {
                        Alpine.store('tray').subgenres = [];

                        if (Alpine.store('tray').trackTray.genre) {
                            var url = "{{ route('releases.get_subgenre', ':id') }}"
                            url = url.replace(':id', Alpine.store('tray').trackTray.genre);

                            try {
                                // Custom ajax call
                                await ajaxCall(url, 'GET', null, false, false,
                                    (data) => {
                                        Alpine.store('tray').subgenres = data.subgenres;

                                        if (subgenre) {
                                            Alpine.store('tray').trackTray.subgenre = subgenre;
                                        }
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

                            // Validate file signature, size, bit depth and sample rate before upload.
                            const metadata = await window.parseAudioMetadata(file);
                            if (type === 'trimmed' && (!metadata.duration || metadata.duration > 59)) {
                                throw 'File TikTok Ref ditolak: durasi maksimal adalah 59 detik.';
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
                            if (!response.ok) {
                                let errorMsg = 'Failed to upload/merge track chunks.';
                                try {
                                    const data = await response.json();
                                    if (data && data.errors) {
                                        errorMsg = data.errors;
                                    }
                                } catch (e) {
                                    errorMsg = 'Server error during merge. Status: ' + response.status;
                                }
                                throw errorMsg;
                            }
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

                            window.showUploadError(error, type === 'trimmed' ? 'TikTok Ref ditolak' : 'File master ditolak');
                        }
                        finally {
                            document.getElementById(inputId).value = "";
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

                        return fetch("{{ route('releases.track_chunk_store') }}", {
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
                        return fetch("{{ route('releases.track_chunk_merge') }}", {
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
