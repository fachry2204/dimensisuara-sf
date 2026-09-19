@extends('layouts.app')
@section('title')
    @if ($publishing->id) Edit @else New @endif Song Publishing Registration Form
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('publishing.index') }}">Publishing</a></li>
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>
            <h4 class="mb-0 font-weight-bold fs-2">@if ($publishing->id) Edit @else New @endif Song Publishing Registration
                Form</h4>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm mt-auto" onclick="savedraft()">Save Draft</button>
    </div>
@endsection
@section('content')

    <div id="stepper" class="bs-stepper">
        <div class="d-lg-flex flex-lg-row align-items-lg-center justify-content-lg-between mb-3" role="tablist">
            <div class="step" data-target="#step-1">
                <div class="step-trigger" role="tab" id="stepper1trigger1" aria-controls="step-1">
                    <div class="bs-stepper-circle">1</div>
                    <div class="">
                        <h5 class="mb-0 steper-title">Song Metadata</h5>
                        <p class="mb-0 steper-sub-title">Select or enter data manually</p>
                    </div>
                </div>
            </div>
            <div class="bs-stepper-line"></div>
            <div class="step" data-target="#step-2">
                <div class="step-trigger" role="tab" id="stepper1trigger2" aria-controls="step-2">
                    <div class="bs-stepper-circle">2</div>
                    <div class="">
                        <h5 class="mb-0 steper-title">Publishing Details</h5>
                        <p class="mb-0 steper-sub-title">Rights, Synchronization Type</p>
                    </div>
                </div>
            </div>
            <div class="bs-stepper-line"></div>
            <div class="step" data-target="#step-3">
                <div class="step-trigger" role="tab" id="stepper1trigger3" aria-controls="step-3">
                    <div class="bs-stepper-circle">3</div>
                    <div class="">
                        <h5 class="mb-0 steper-title">Songwriter List</h5>
                        <p class="mb-0 steper-sub-title">Name, Role, and Share</p>
                    </div>
                </div>
            </div>
            <div class="bs-stepper-line"></div>
            <div class="step" data-target="#step-4">
                <div class="step-trigger" role="tab" id="stepper1trigger4" aria-controls="step-4">
                    <div class="bs-stepper-circle">4</div>
                    <div class="">
                        <h5 class="mb-0 steper-title">Review</h5>
                        <p class="mb-0 steper-sub-title">Finalize Publishing Request</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bs-stepper-content">
            <form action="{{ route('publishing.store') }}" id="publishing-form" method="POST" x-data
                @submit.prevent="$store.publishing.methods.submit()">
                @csrf
                <input type="hidden" name="id" value="{{ $publishing->id }}">

                <div id="step-1" role="tabpanel" class="bs-stepper-pane content" aria-labelledby="stepper1trigger1">
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" class="btn btn-primary px-4" onclick="stepper.next()">
                            Next
                            <i class='bx bx-right-arrow-alt ms-2'></i>
                        </button>
                    </div>
                    <h3 class="mb-3">Song Metadata</h3>
                    <div class="form-group">
                        <span class="form-label">
                            Please provide song metadata:
                        </span>
                        <div class="mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="source" id="source_existing"
                                    value="existing" x-model="$store.publishing.source">
                                <label class="form-check-label mb-2" for="source_existing">
                                    From existing published release<br>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="source" id="source_new" value="new"
                                    x-model="$store.publishing.source">
                                <label class="form-check-label mb-2" for="source_new">
                                    Enter data manually<br>
                                </label>
                            </div>
                        </div>
                    </div>
                    <fieldset id="source_existing_field" x-show="$store.publishing.source === 'existing'">
                        <div class="form-group">
                            <label for="release_id" class="form-label">Release Title <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="release_id" name="release_id"
                                x-model="$store.publishing.release_id" @change="$store.publishing.methods.fetchTracks()">
                                <option value="">Select a release</option>
                                @if($releases->count() >= 1)
                                    @foreach ($releases as $release)
                                        <option value="{{ $release->id }}">[UPC: {{ $release->upc }}] {{ $release->title }}</option>
                                    @endforeach
                                @else
                                    <option value="" disabled>No release found</option>
                                @endif
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="track_id" class="form-label">Track Title <span class="text-danger">*</span></label>
                            <select class="form-select" id="track_id" name="track_id" x-model="$store.publishing.track_id"
                                @change="$store.publishing.methods.selectTrack()">
                                <option value="">Select a track</option>
                                <template x-for="track in $store.publishing.tracks" :key="track.id">
                                    <option :value="track.id" x-text="track.title"></option>
                                </template>
                            </select>
                            <div class="mt-1" x-show="$store.publishing.loading_tracks">
                                <small class="text-muted"><i class="bx bx-loader bx-spin"></i> Fetching tracks...</small>
                            </div>
                            <div class="mt-1" x-show="$store.publishing.fetch_error">
                                <small class="text-danger">Failed to fetch tracks. Please try again.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="iswc" class="form-label">ISWC</label>
                            <input type="text" class="form-control" id="iswc" name="iswc" x-model="$store.publishing.iswc">
                        </div>
                    </fieldset>
                    <fieldset id="source_new_field" x-show="$store.publishing.source === 'new'" style="display: none;">
                        <div class="form-group">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title"
                                x-model="$store.publishing.title" :readonly="$store.publishing.is_autofilled">
                        </div>
                        <div class="form-group">
                            <label for="other_title" class="form-label">Other Title</label>
                            <input type="text" class="form-control" id="other_title" name="other_title"
                                x-model="$store.publishing.other_title" :readonly="$store.publishing.is_autofilled">
                        </div>
                        <div class="form-group">
                            <label for="sample" class="form-label">Link to Song Sample <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sample" name="sample"
                                x-model="$store.publishing.sample" :readonly="$store.publishing.is_autofilled">
                        </div>
                        <div class="form-group">
                            <label for="performer" class="form-label">Performer <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="performer" name="performer"
                                x-model="$store.publishing.performer" :readonly="$store.publishing.is_autofilled">
                        </div>
                        <div class="form-group">
                            <label for="duration" class="form-label">Duration <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control text-center" id="duration_minute"
                                    name="duration_minute" placeholder="mm" maxlength="2"
                                    style="max-width: 80px; font-variant-numeric: tabular-nums;"
                                    x-model="$store.publishing.duration_minute" :readonly="$store.publishing.is_autofilled"
                                    oninput="this.value=this.value.replace(/[^0-9]/g,'').substring(0,2)">
                                <span class="input-group-text">:</span>
                                <input type="text" class="form-control text-center" id="duration_second"
                                    name="duration_second" placeholder="ss" maxlength="2"
                                    style="max-width: 80px; font-variant-numeric: tabular-nums;"
                                    x-model="$store.publishing.duration_second" :readonly="$store.publishing.is_autofilled"
                                    oninput="this.value=this.value.replace(/[^0-9]/g,'').substring(0,2)"
                                    onblur="if(parseInt(this.value)>59)this.value='59'">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="genre" class="form-label">Genre <span class="text-danger">*</span></label>
                            <select type="text" class="form-control" id="genre" name="genre"
                                x-model="$store.publishing.genre"
                                :style="$store.publishing.is_autofilled ? 'pointer-events: none; background-color: var(--bs-secondary-bg);' : ''">
                                @foreach(\App\Models\System\Genre::all() as $genre)
                                    @if($genre->is_active)
                                        <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                                    @else
                                        <option value="{{ $genre->id }}" x-show="$store.publishing.genre == {{ $genre->id }}">{{ $genre->name }} (Legacy)</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="language" class="form-label">Language <span class="text-danger">*</span></label>
                            <select type="text" class="form-control" id="language" name="language"
                                x-model="$store.publishing.language"
                                :style="$store.publishing.is_autofilled ? 'pointer-events: none; background-color: var(--bs-secondary-bg);' : ''">
                                @foreach (Symfony\Component\Intl\Languages::getNames() as $code => $language)
                                    <option value="{{ $code }}">{{ $language }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="iswc" class="form-label">ISWC</label>
                            <input type="text" class="form-control" id="iswc" name="iswc" x-model="$store.publishing.iswc"
                                :readonly="$store.publishing.is_autofilled">
                        </div>
                        <div class="form-group">
                            <label for="isrc" class="form-label">ISRC <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="isrc" name="isrc" x-model="$store.publishing.isrc"
                                :readonly="$store.publishing.is_autofilled">
                        </div>
                    </fieldset>
                </div>
                <div id="step-2" role="tabpanel" class="bs-stepper-pane content" aria-labelledby="stepper1trigger2">
                    <div class="d-flex justify-content-between mb-3">
                        <button type="button" class="btn btn-outline-primary px-4" onclick="stepper.previous()">
                            <i class='bx bx-left-arrow-alt me-2'></i>
                            Previous
                        </button>
                        <button type="button" class="btn btn-primary px-4" onclick="stepper.next()">
                            Next
                            <i class='bx bx-right-arrow-alt ms-2'></i>
                        </button>
                    </div>
                    <h3 class="mb-3">Publishing Details</h3>
                    <div class="form-group">
                        <span class="form-label">Rights Granted <span class="text-danger">*</span></span>
                        <div class="alert alert-info" role="alert">
                            <small>This request already includes the following rights:
                                <ul class="mb-0">
                                    <li>Synchronization</li>
                                    <li>Mechanical</li>
                                    <li>Performing</li>
                                    <li>Printing</li>
                                    <li>Other Rights</li>
                                </ul>
                            </small>
                        </div>
                        <div class="ms-3">
                            <span class="form-label">Select synchronization licensing type: <span
                                    class="text-danger">*</span></span>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="sync_license_type"
                                    id="sync_license_type_exclusive" value="exclusive"
                                    x-model="$store.publishing.sync_license_type">
                                <label class="form-check-label" for="sync_license_type_exclusive">
                                    Exclusive License<br>
                                    <small>Only the Publisher shall have the exclusive right to negotiate
                                        synchronization
                                        licensing of the Composition with third parties, subject to written notice to
                                        the
                                        Author/Composer.</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="sync_license_type"
                                    id="sync_license_type_non-exclusive" value="non-exclusive"
                                    x-model="$store.publishing.sync_license_type">
                                <label class="form-check-label" for="sync_license_type_non-exclusive">
                                    Non-Exclusive License<br>
                                    <small>The Author/Composer may negotiate synchronization licensing of the
                                        Composition
                                        with third parties, subject to written notice to the Publisher.</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="step-3" role="tabpanel" class="bs-stepper-pane content" aria-labelledby="stepper1trigger3">
                    <div class="d-flex justify-content-between mb-3">
                        <button type="button" class="btn btn-outline-primary px-4" onclick="stepper.previous()">
                            <i class='bx bx-left-arrow-alt me-2'></i>
                            Previous
                        </button>
                        <button type="button" class="btn btn-primary px-4" onclick="stepper.next()">
                            Next
                            <i class='bx bx-right-arrow-alt ms-2'></i>
                        </button>
                    </div>
                    <h3 class="mb-3">Songwriter Split Sheet</h3>
                    <div class="accordion mb-3">
                        <template x-for="(entry, idx) in $store.publishing.songwriters" :key="idx">
                            <div class="accordion-item" x-data="{ expanded: idx === 0 }">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" :class="{'collapsed': !expanded}"
                                        @click="expanded = !expanded">
                                        <div class="d-flex justify-content-between align-items-center w-100 pe-3">
                                            <div>
                                                <span class="fw-semibold"
                                                    x-text="entry.name || (idx === 0 ? 'Primary Songwriter' : 'Co-Writer ' + idx)"></span>
                                                <span class="badge bg-secondary ms-2 text-capitalize"
                                                    x-text="entry.role ? entry.role.replace('_', ' & ') : 'Role'"></span>
                                            </div>
                                            <div class="fw-bold text-primary" x-text="(entry.share || 0) + '%'"></div>
                                        </div>
                                    </button>
                                </h2>
                                <div class="accordion-collapse collapse" :class="{'show': expanded}">
                                    <div class="accordion-body position-relative bg-light">
                                        <template x-if="idx !== 0">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 mt-3 me-3 z-3"
                                                @click="$store.publishing.songwriters.splice(idx, 1)">
                                                <i class="bx bx-x"></i> Remove
                                            </button>
                                        </template>
                                        <input type="hidden" :name="`songwriters[${idx}][id]`" x-model="entry.id">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label form-label-sm fw-semibold">Name</label>
                                                <input type="text" class="form-control" placeholder="Full legal name"
                                                    :name="`songwriters[${idx}][name]`" x-model="entry.name"
                                                    :readonly="idx === 0">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label form-label-sm fw-semibold">Email Address</label>
                                                <input type="email" class="form-control" placeholder="Email"
                                                    :name="`songwriters[${idx}][email]`" x-model="entry.email"
                                                    :readonly="idx === 0">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label form-label-sm fw-semibold">Citizen ID (KTP)</label>
                                                <input type="text" class="form-control" placeholder="Identity Number"
                                                    :name="`songwriters[${idx}][ktp_num]`" x-model="entry.ktp_num"
                                                    :readonly="idx === 0">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label form-label-sm fw-semibold">Phone Number</label>
                                                <input type="text" class="form-control" placeholder="Phone Number"
                                                    :name="`songwriters[${idx}][phone]`" x-model="entry.phone"
                                                    :readonly="idx === 0">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label form-label-sm fw-semibold">Publisher
                                                    (Optional)</label>
                                                <input type="text" class="form-control" placeholder="e.g. Soundfresh"
                                                    :name="`songwriters[${idx}][publisher]`" x-model="entry.publisher"
                                                    :readonly="idx === 0">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label form-label-sm fw-semibold">Role</label>
                                                <select class="form-select" :name="`songwriters[${idx}][role]`"
                                                    x-model="entry.role">
                                                    @foreach (\App\Enums\SongwriterRole::options() as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label form-label-sm fw-semibold">LMK / PRO
                                                    (Optional)</label>
                                                <input type="text" class="form-control" placeholder="e.g. WAMI, ASCAP"
                                                    :name="`songwriters[${idx}][lmk]`" x-model="entry.lmk">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label form-label-sm fw-semibold">Split Share</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" placeholder="%"
                                                        :name="`songwriters[${idx}][share]`" x-model="entry.share" min="0"
                                                        max="100">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary"
                        @click="$store.publishing.songwriters.push({name:'', email:'', ktp_num:'', phone:'', role:'author', publisher:'', lmk:'', share:0})">+
                        Add Writer</button>
                </div>
                <div id="step-4" role="tabpanel" class="bs-stepper-pane content" aria-labelledby="stepper1trigger4">
                    <div class="d-flex justify-content-between mb-3">
                        <button type="button" class="btn btn-outline-primary px-4" onclick="stepper.previous()">
                            <i class='bx bx-left-arrow-alt me-2'></i>
                            Previous
                        </button>
                    </div>
                    <h4 class="mb-3">Review Summary</h4>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <h5>Song Metadata</h5>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th style="width: 150px">Title</th>
                                    <td><span x-text="$store.publishing.title || 'Not provided'"
                                            :class="{'text-muted': !$store.publishing.title}"></span></td>
                                </tr>
                                <tr>
                                    <th>Performer</th>
                                    <td><span x-text="$store.publishing.performer || 'Not provided'"
                                            :class="{'text-muted': !$store.publishing.performer}"></span></td>
                                </tr>
                                <tr>
                                    <th>Duration</th>
                                    <td><span
                                            x-text="`${$store.publishing.duration_minute || '00'}:${($store.publishing.duration_second || 0).toString().padStart(2, '0')}`"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Genre</th>
                                    <td><span x-text="$store.publishing.genre || 'Not provided'"
                                            :class="{'text-muted': !$store.publishing.genre}"></span></td>
                                </tr>
                                <tr>
                                    <th>Language</th>
                                    <td><span x-text="($store.publishing.languages && $store.publishing.language ? ($store.publishing.languages[$store.publishing.language.toUpperCase()] || $store.publishing.languages[$store.publishing.language.toLowerCase()] || $store.publishing.language) : 'Not provided')"
                                             :class="{'text-muted': !$store.publishing.language}"></span></td>
                                </tr>
                                <tr>
                                    <th>ISRC</th>
                                    <td><span x-text="$store.publishing.isrc || 'Not provided'"
                                            :class="{'text-muted': !$store.publishing.isrc}"></span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h5>Publishing Details</h5>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th>ISWC</th>
                                    <td><span x-text="$store.publishing.iswc || 'Not provided'"
                                            :class="{'text-muted': !$store.publishing.iswc}"></span></td>
                                </tr>
                                <tr>
                                    <th style="width: 150px">Sync License</th>
                                    <td><span x-text="$store.publishing.sync_license_type" class="text-capitalize"></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="form-group">
                        <span class="form-label">
                            Please read and agree to the following term:
                        </span>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="accept_terms" id="accept_terms"
                                @if(old('accept_terms', $publishing->accept_terms)) checked @endif>
                            <label class="form-check-label" for="accept_terms">
                                <i>In the event the Author/Composer terminates the collaboration agreement prior to the
                                    expiration of the agreement term, such termination shall become effective
                                    <strong>one
                                        (1) year</strong> after the submission of the termination request by the
                                    Author/Composer, provided that it has been approved by all parties (<strong>Post
                                        Term
                                        Period</strong> applies to this agreement)</i>. <span class="text-danger">*</span>
                            </label>
                        </div>
                    </div>
                    <div id="songwriters"></div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Submit Publishing</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/bs-stepper/css/bs-stepper.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/css/album.css') }}" rel="stylesheet" />

@endpush
@push('script')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src=" {{ URL::asset('build/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
    <script type="application/json" id="json-releases">@json($releases)</script>
    <script type="application/json"
        id="json-songwriters">@json(old('songwriters', json_decode($publishing->songwriters)) ?? [])</script>
    <script type="application/json" id="json-languages">@json(Symfony\Component\Intl\Languages::getNames())</script>
    <script>
        $(document).ready(function () {
            // Initialize Stepper
            stepper = new Stepper(document.querySelector('#stepper'), {
                linear: false
            });
        });

        function savedraft() {
            Alpine.store('publishing').methods.draft();
        }
        document.addEventListener('alpine:init', () => {
            Alpine.store('publishing', {
                init() {
                    if (Alpine.store('publishing').songwriters.length === 0) {
                        Alpine.store('publishing').songwriters.push({
                            name: @json(trim(current_user()->full_name ?? '')),
                            email: @json(current_user()->email ?? ''),
                            ktp_num: @json(current_user()->profile->ktp_num ?? ''),
                            phone: @json(current_user()->profile->phone ?? ''),
                            role: 'author',
                            share: 0,
                            lmk: '',
                            publisher: 'Soundfresh'
                        });
                    }

                    if (Alpine.store('publishing').release_id) {
                        Alpine.store('publishing').methods.fetchTracks(true);
                    }
                },
                source: @json(old('source', $publishing->id ? ($publishing->release_id ? 'existing' : 'new') : 'existing')),
                languages: JSON.parse(document.getElementById('json-languages').textContent),
                release_id: @json((string) old('release_id', $publishing->release_id ?? '')),
                track_id: @json((string) old('track_id', $publishing->track_id ?? '')),
                tracks: [],
                loading_tracks: false,
                fetch_error: false,
                title: @json(old('title', $publishing->title ?? '')),
                other_title: @json(old('other_title', $publishing->other_title ?? '')),
                sample: @json(old('sample', $publishing->sample ?? '')),
                performer: @json(old('performer', $publishing->performer ?? '')),
                duration_minute: @json(old('duration_minute', $publishing->duration_minute ?? '')),
                duration_second: @json(old('duration_second', $publishing->duration_second ?? '')),
                genre: @json(old('genre', $publishing->genre ?? '')),
                language: @json(old('language', $publishing->language ?? 'id')),
                iswc: @json(old('iswc', $publishing->iswc ?? '')),
                isrc: @json(old('isrc', $publishing->isrc ?? '')),
                sync_license_type: @json(old('sync_license_type', $publishing->sync_license_type ?? 'exclusive')),
                note: @json(old('note', $publishing->note ?? '')),
                is_autofilled: false,
                songwriters: JSON.parse(document.getElementById('json-songwriters').textContent),
                methods: {
                    async fetchTracks(isEditInit = false) {
                        const previousTrackId = Alpine.store('publishing').track_id;

                        if (!Alpine.store('publishing').release_id) {
                            Alpine.store('publishing').tracks = [];
                            Alpine.store('publishing').track_id = '';
                            return;
                        }

                        Alpine.store('publishing').loading_tracks = true;
                        Alpine.store('publishing').fetch_error = false;

                        try {
                            const response = await fetch(`/releases/_ajax/${Alpine.store('publishing').release_id}/tracks`);
                            if (!response.ok) throw new Error('Network response was not ok');
                            const data = await response.json();
                            if (data.success) {
                                Alpine.store('publishing').tracks = data.tracks;
                            } else {
                                throw new Error(data.errors);
                            }

                            if (!isEditInit) {
                                Alpine.store('publishing').track_id = '';
                            } else {
                                // Force reactivity by temporarily clearing the value
                                Alpine.store('publishing').track_id = '';
                                setTimeout(() => {
                                    Alpine.store('publishing').track_id = previousTrackId;
                                }, 100);
                            }
                        } catch (error) {
                            console.error("Failed to fetch tracks", error);
                            Alpine.store('publishing').fetch_error = true;
                            Alpine.store('publishing').tracks = [];
                        } finally {
                            Alpine.store('publishing').loading_tracks = false;
                        }
                    },
                    selectTrack() {
                        const trackId = parseInt(Alpine.store('publishing').track_id);
                        if (!trackId) {
                            Alpine.store('publishing').is_autofilled = false;
                            return;
                        }

                        const track = Alpine.store('publishing').tracks.find(t => t.id === trackId);
                        if (track) {
                            Alpine.store('publishing').title = track.title || '';
                            Alpine.store('publishing').performer = track.performer || '';
                            Alpine.store('publishing').genre = track.genre || '';
                            Alpine.store('publishing').language = track.language || 'id';
                            Alpine.store('publishing').isrc = track.isrc || '';
                            Alpine.store('publishing').iswc = track.iswc || '';
                            Alpine.store('publishing').duration_minute = track.duration_minute || 0;
                            Alpine.store('publishing').duration_second = track.duration_second || 0;
                            Alpine.store('publishing').sample = track.sample || '';
                            Alpine.store('publishing').is_autofilled = true;

                            if (track.songwriters && Array.isArray(track.songwriters)) {
                                let populatedWriters = [];

                                if (Alpine.store('publishing').songwriters.length > 0) {
                                    populatedWriters = Alpine.store('publishing').songwriters.slice(0, 1);
                                }
                                track.songwriters.forEach((w) => {
                                    populatedWriters.push({
                                        name: w.name || '',
                                        email: '',
                                        ktp_num: '',
                                        phone: '',
                                        role: w.role || 'author',
                                        lmk: '',
                                        publisher: 'Soundfresh',
                                        share: 0
                                    });
                                });

                                if (populatedWriters.length > 0) {
                                    Alpine.store('publishing').songwriters = populatedWriters;
                                }
                            }
                        }
                    },

                    draft() {
                        $('#publishing-form').append(`<input type="hidden" name="status" value="draft">`);
                        Alpine.store('publishing').methods.submit();
                    },
                    submit() {
                        // Validate songwriters share rate
                        let totalShare = Alpine.store('publishing').songwriters.reduce((sum, e) => sum + Number(e.share || 0), 0);
                        if (totalShare !== 100) {
                            alert('Total songwriter share must be exactly 100%.');
                            return;
                        }

                        $('#publishing-form').submit();
                    },

                }
            });
        });
    </script>
@endpush