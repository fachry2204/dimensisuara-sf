@extends('layouts.app')
@section('title')
    @if ($publishing->id)
        Edit Publishing
    @else
        Create a Publishing
    @endif
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.publishing.index') }}">All Publishing</a></li>
                    @if ($publishing->id)<li class="breadcrumb-item"><a href="{{ route('admin.publishing.show', ['id' => $publishing->id]) }}">View Publishing</a></li>@endif
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>
            <h4 class="mb-0 font-weight-bold fs-2">@if ($publishing->id) Edit Publishing @else Create a Publishing @endif</h4>
        </div>
    </div>
@endsection
@section('content')
    <form action="{{ route('admin.publishing.store') }}" id="publishing-form" method="POST" x-data @submit.prevent="$store.publishing.methods.submit()">
        @csrf
        <input type="hidden" name="id" value="{{ $publishing->id }}">
        <div class="card">
            <div class="card-header p-3">
                <h6 class="card-title mb-0 fw-bold">User Assignment</h6>
            </div>
            <div class="card-body">
                @php
                    $profiles = \App\Models\User\Profile::all()->sortBy('full_name');
                @endphp
                <div class="form-group mb-3">
                    <label for="profileid" class="form-label">Assign User</label>
                    <p>Select the user that will be assigned to this publishing</p>
                    <p>Warning: Changing user will clear current songwriters and their shares in this request.</p>
                    <select name="profileid" id="profileid" class="form-select" required>
                        @foreach ($profiles as $profile)
                            <option value="{{ $profile->id }}" @if($profile->id == old('profileid', $publishing->profileid)) selected @endif>{{ $profile->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label for="status" class="form-label">Release Status</label>
                    <p>Giving the publishing a status of "Draft" or "Rejected" will let the system delete the publishing if the user does not complete the publishing in 5 days.</p>
                    <select name="status" id="status" class="form-select">
                        @foreach (['draft' => 'Draft', 'pending' => 'Pending', 'review' => 'Under Review', 'rejected' => 'Rejected', 'completed' => 'Completed'] as $code => $name)
                            <option value="{{ $code }}" @if(old('status', $publishing->status) == $code) selected @endif>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header p-3">
                <h6 class="card-title mb-0 fw-bold">Request Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $publishing->title) }}">
                    </div>
                    <div class="col-12">
                        <label for="other_title" class="form-label">Other Title</label>
                        <input type="text" class="form-control" id="other_title" name="other_title" value="{{ old('other_title', $publishing->other_title) }}">
                    </div>
                    <div class="col-12">
                        <label for="sample" class="form-label">Link to song sample <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sample" name="sample" value="{{ old('sample', $publishing->sample) }}">
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <p>Rights Granted <span class="text-danger">*</span></p>
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
                            Select synchronization licensing type:
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="sync_license_type" id="sync_license_type_exclusive" value="exclusive" @if( old('sync_license_type', $publishing->sync_license_type) == 'exclusive' || $publishing->sync_license_type == null ) checked @endif>
                                <label class="form-check-label" for="sync_license_type_exclusive">
                                    Exclusive License<br>
                                    <small>Only the Publisher shall have the exclusive right to negotiate synchronization licensing of the Composition with third parties, subject to written notice to the Author/Composer.</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="sync_license_type" id="sync_license_type_non-exclusive" value="non-exclusive" @if( old('sync_license_type', $publishing->sync_license_type) == 'non-exclusive' ) checked @endif>
                                <label class="form-check-label" for="sync_license_type_non-exclusive">
                                    Non-Exclusive License<br>
                                    <small>The Author/Composer may negotiate synchronization licensing of the Composition with third parties, subject to written notice to the Publisher.</small>
                                </label>
                            </div>
                        </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="" class="form-label">Performer <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="performer" name="performer" value="{{ old('performer', $publishing->performer) }}">
                    </div>
                    <div class="col-lg-6">
                        <label for="duration" class="form-label">Duration <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control text-center" id="duration_minute" name="duration_minute" placeholder="mm" maxlength="2" style="max-width: 80px; font-variant-numeric: tabular-nums;" value="{{ old('duration_minute', $publishing->duration_minute) }}" oninput="this.value=this.value.replace(/[^0-9]/g,'').substring(0,2)">
                            <span class="input-group-text">:</span>
                            <input type="text" class="form-control text-center" id="duration_second" name="duration_second" placeholder="ss" maxlength="2" style="max-width: 80px; font-variant-numeric: tabular-nums;" value="{{ old('duration_second', $publishing->duration_second) }}" oninput="this.value=this.value.replace(/[^0-9]/g,'').substring(0,2)" onblur="if(parseInt(this.value)>59)this.value='59'">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <label for="genre" class="form-label">Genre <span class="text-danger">*</span></label>
                        <select type="text" class="form-control" id="genre" name="genre">
                            @foreach( \App\Models\System\Genre::all() as $genre )
                                @if($genre->is_active || old('genre', $publishing->genre) == $genre->id)
                                    <option value="{{ $genre->id }}" @if( old('genre', $publishing->genre) == $genre->id ) selected @endif>{{ $genre->name }}@if(!$genre->is_active) (Legacy)@endif</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <label for="language" class="form-label">Language <span class="text-danger">*</span></label>
                        <select type="text" class="form-control" id="language" name="language">
                            @foreach (Symfony\Component\Intl\Languages::getNames() as $code => $language)
                                <option value="{{ $code }}" @if( (old('language', $publishing->language) ?? 'id') == $code ) selected @endif>{{ $language }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <label for="iswc" class="form-label">ISWC</label>
                        <input type="text" class="form-control" id="iswc" name="iswc" value="{{ old('iswc', $publishing->iswc) }}">
                    </div>
                    <div class="col-lg-6">
                        <label for="isrc" class="form-label">ISRC <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="isrc" name="isrc" value="{{ old('isrc', $publishing->isrc) }}">
                    </div>
                    <div class="col-12">
                        <label for="note" class="form-label">Note</label>
                        <textarea class="form-control" id="note" name="note">{{ old('note', $publishing->note) }}</textarea>
                    </div>
        
                    <div class="col-12">
                        <p class="form-label">Songwriters</p>
                        <table class="table table-borderless table-responsive">
                            <thead>
                                <th>Name / Email</th>
                                <th>Role</th>
                                <th style="width: 150px">Share</th>
                                <th style="width: 50px"></th>
                            </thead>
                            <tbody>
                                <template x-for="(songwriter, index) in $store.publishing.songwriters">
                                    <tr>
                                        <td>
                                            <select class="form-select" x-model="songwriter.id" x-effect="$nextTick(() => $el.dispatchEvent(new Event('change')))">
                                                <option value="">Select Songwriter</option>
                                                {{-- <template x-for="songwriter in availableSongwriters">
                                                    <option :value="songwriter.id" x-text="songwriter.full_name + ' - ' + songwriter.email"></option>
                                                </template> --}}
                                                @foreach ($songwriters as $songwriter)
                                                    <option value="{{ $songwriter['id'] }}">{{ $songwriter['full_name'] }} - {{ $songwriter['email'] }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select" x-model="songwriter.role">
                                                @foreach (\App\Enums\SongwriterRole::options() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" class="form-control" x-model="songwriter.share">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <template x-if="index != 0">
                                                <button type="button" class="btn btn-danger btn-sm" style="height: 100%" x-on:click="$store.publishing.methods.removeSongwriter(songwriter)">
                                                    <i class="material-icons-outlined">delete</i>
                                                </button>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2" style="text-align: right">Total Share</th>
                                    <th x-text="$store.publishing.methods.totalShare"></th>
                                    <th>
                                        <button type="button" class="btn btn-outline-primary btn-sm" style="width: fit-content" x-on:click="$store.publishing.methods.addSongwriter()">
                                            <i class="material-icons-outlined">add</i>
                                        </button>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="d-flex flex-column align-items-end">
                            <p><i>Note: The sum of all writer shares should be equal to 100%. </i></p>
                        </div>
                    </div>
                    <div class="col-12">
                        Please read and agree to the following term:<br><br>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="accept_terms" id="accept_terms" @if( old('accept_terms', $publishing->accept_terms) ) checked @endif>
                            <label class="form-check-label" for="accept_terms">
                                <i>In the event the Author/Composer terminates the collaboration agreement prior to the expiration of the agreement term, such termination shall become effective <strong>one (1) year</strong> after the submission of the termination request by the Author/Composer, provided that it has been approved by all parties (<strong>Post Term Period</strong> applies to this agreement)</i>.  <span class="text-danger">*</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div id="songwriters"></div>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Submit Publishing</button>
                </div>
            </div>
        </div>
    </form>
@endsection
@push('css')
@endpush
@push('script')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script type="application/json" id="json-sws">@json( $songwriters )</script>
    <script type="application/json" id="json-songwriters">@json( old('songwriters', json_decode($publishing->songwriters)) ?? [] )</script>
    <script>
        function savedraft() {
            Alpine.store('publishing').methods.draft();
        }
        document.addEventListener('alpine:init', () => {
            Alpine.store('publishing', {
                init() {
                    if (this.songwriters.length === 0) {
                        this.methods.addSongwriter();
                    }
                },
                title: '',
                artist: '',
                link: '',
                availableSongwriters: JSON.parse(document.getElementById('json-sws').textContent),
                songwriters: JSON.parse(document.getElementById('json-songwriters').textContent),

                methods: {
                    addSongwriter() {
                        Alpine.store('publishing').songwriters.push({
                            id: '',
                            role: 'author_composer',
                            share: 0,
                        });
                    },
    
                    removeSongwriter(songwriter) {
                        const index = Alpine.store('publishing').songwriters.indexOf(songwriter);
                        Alpine.store('publishing').songwriters.splice(index, 1);
                    },
    
                    get totalShare() {
                        let total = 0;
                        Alpine.store('publishing').songwriters.forEach(songwriter => {
                            total += parseFloat(songwriter.share);
                        });
                        return parseFloat(total.toFixed(3)) + '%';
                    },
                    draft() {
                        $('#publishing-form').append(`<input type="hidden" name="status" value="draft">`);
                        Alpine.store('publishing').methods.submit();
                    },
                    submit() {
                        $('#songwriters').html('');
                        for (let i = 0; i < Alpine.store('publishing').songwriters.length; i++) {
                            const songwriter = Alpine.store('publishing').songwriters[i];
                            $('#songwriters').append(`
                                <input type="hidden" name="songwriters[${i}][id]" value="${songwriter.id || ''}">
                                <input type="hidden" name="songwriters[${i}][role]" value="${songwriter.role || ''}">
                                <input type="hidden" name="songwriters[${i}][share]" value="${(songwriter.share || 0)}">
                            `);
                        }
                        $('#publishing-form').submit();
                    }
                }

            });
        });
    </script>
@endpush