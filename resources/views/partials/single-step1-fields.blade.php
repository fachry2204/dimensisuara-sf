<div class="row g-3">

    <!-- Audio Files -->
    <div class="form-group mb-3">
        <p class="form-label">Audio Files</p>
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <div class="track-placeholder" id="input-file-full-inline"
                    style="border: 2px dashed #ccc; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer;"
                    onclick="document.getElementById('file-full-inline').click()">
                    <i class="material-icons-outlined" style="font-size: 48px;">music_note</i>
                    <div class="fw-bold mt-2">Full Song</div>
                    <div class="mt-2 d-flex flex-column align-items-center justify-content-center">
                        <p class="text-center mb-2 text-primary"
                            x-text="$store.release.tracks[0] ? $store.release.tracks[0].filefullname : ''"></p>
                        <audio controls :src="$store.release.tracks[0] ? $store.release.tracks[0].filefullcontent : ''"
                            x-show="$store.release.tracks[0] && $store.release.tracks[0].filefullcontent"
                            id="audio-full-inline" style="max-width: 400px;" onclick="event.stopPropagation()"></audio>
                    </div>
                </div>
                <input type="file" class="form-control" id="file-full-inline" style="display: none;"
                    accept="audio/wav,audio/x-wav,audio/wave,audio/x-pn-wav,audio/flac,audio/x-flac,.wav,.flac"
                    @change="uploadAudioFile('$store.release.tracks[0]', 'full', '-inline')">
            </div>

            <div class="col-12 col-md-6">
                <div class="track-placeholder" id="input-file-trimmed-inline"
                    style="border: 2px dashed #ccc; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer;"
                    onclick="document.getElementById('file-trimmed-inline').click()">
                    <i class="material-icons-outlined" style="font-size: 48px;">music_note</i>
                    <div class="fw-bold mt-2">TikTok Ref</div>
                    <div class="mt-2 d-flex flex-column align-items-center justify-content-center">
                        <p class="text-center mb-2 text-primary"
                            x-text="$store.release.tracks[0] ? $store.release.tracks[0].filetrimmedname : ''"></p>
                        <audio controls
                            :src="$store.release.tracks[0] ? $store.release.tracks[0].filetrimmedcontent : ''"
                            x-show="$store.release.tracks[0] && $store.release.tracks[0].filetrimmedcontent"
                            id="audio-trimmed-inline" style="max-width: 400px;"
                            onclick="event.stopPropagation()"></audio>
                    </div>
                </div>
                <input type="file" class="form-control" id="file-trimmed-inline" style="display: none;"
                    accept="audio/wav,audio/x-wav,audio/wave,audio/x-pn-wav,audio/flac,audio/x-flac,.wav,.flac"
                    @change="uploadAudioFile('$store.release.tracks[0]', 'trimmed', '-inline')">
            </div>
        </div>
        <span>File yang tidak memenuhi ketentuan akan ditolak otomatis sebelum diunggah.</span>
        <ul class="mb-0">
            <li>Audio file must be in <strong>WAV or FLAC</strong> format</li>
            <li>Sample rate harus <strong>44.1 kHz sampai 96 kHz</strong> dengan bit depth <strong>16-bit atau 24-bit</strong></li>
            <li>Ukuran file maksimal <strong>200 MB</strong></li>
            <li>TikTok Ref memiliki durasi maksimal <strong>59 detik</strong></li>
        </ul>
    </div>

    <!-- Single Title -->
    <div class="form-group col-12 col-md-6 mb-3">
        <label for="release-title" class="form-label">Single Title</label>
        <input type="text" class="form-control" name="title" id="release-title" x-model="$store.release.title" required>
        <input type="hidden" name="type" value="single">
        <input type="hidden" name="id" x-model="$store.release.id">
    </div>

    <!-- Title Language -->
    <div class="form-group col-12 col-md-6 mb-3">
        <label for="titlelang" class="form-label">Title Language</label>
        <select name="titlelang" id="titlelang" class="form-select" x-model="$store.release.titlelang" required>
            @foreach (Symfony\Component\Intl\Languages::getNames() as $code => $language)
                <option value="{{ $code }}">{{ $language }}</option>
            @endforeach
        </select>
    </div>

    <!-- Primary Artists -->
    <div class="form-group mb-3">
        <p class="form-label">Primary Artist</p>
        <template x-if="$store.release.primaryartists.length">
            <div class="row flex-column flex-md-row px-md-3">
                <div class="col-md-7">
                    <template x-for="(primaryartist, index) in $store.release.primaryartists" :key="index">
                        <div class="d-flex flex-column gap-2 mb-3 border border-0 rounded p-3 shadow">
                            <div class="d-flex gap-1 flex-row justify-content-between">
                                <span class="form-label text-lg" x-text="'Primary Artist ' + (index + 1)"></span>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary px-1 py-0" type="button"
                                        @click="$store.release.methods.moveUp(index, 'primaryartists')"><i
                                            class="bx bx-chevron-up"></i></button>
                                    <button class="btn btn-sm btn-outline-secondary px-1 py-0" type="button"
                                        @click="$store.release.methods.moveDown(index, 'primaryartists')"><i
                                            class="bx bx-chevron-down"></i></button>
                                </div>
                            </div>
                            <div class="d-flex gap-1 flex-column flex-grow-1 justify-content-center">
                                <div class="mb-2">
                                    <label class="form-label"><small>Artist Name</small></label>
                                    <input type="text" class="form-control" placeholder="Artist name"
                                        :name="'primaryartists[' + index + '][name]'" x-model="primaryartist.name">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label"><small>Social Media</small></label>
                                    <input type="text" class="form-control" placeholder="Social media link"
                                        :name="'primaryartists[' + index + '][social]'" x-model="primaryartist.social">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label"><small>Spotify Link</small></label>
                                    <input type="text" class="form-control" placeholder="Spotify link"
                                        :name="'primaryartists[' + index + '][spotify]'"
                                        x-model="primaryartist.spotify">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-sm btn-outline-danger" type="button"
                                    @click="$store.release.methods.remove(index, 'primaryartists')"><i
                                        class="bx bx-trash"></i> Remove</button>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="col-md-5 order-first order-md-last">
                    <p>
                        <span>
                            <strong>
                                For social media and Spotify link:
                            </strong>
                            <ul>
                                <li>The social media and Spotify artist <strong>should have the same
                                        name</strong> as artist name mentioned in the release</li>
                                <li>Spotify link is optional, please fill if artist has a Spotify artist
                                    account</li>
                                <li>Use full URL, complete with https:// (e.g.
                                    https://open.spotify.com/artist/...).</li>
                            </ul>
                        </span>
                    </p>
                </div>
            </div>
        </template>
        <button type="button" class="btn release-add-artist-button"
            @click="$store.release.methods.add('primaryartists')">
            <i class="material-icons-outlined">person_add</i>
            <span><strong>Tambah Primary Artist</strong><small>Tambahkan artis utama lainnya pada rilis ini</small></span>
        </button>
    </div>

    <!-- Featured Artists (Single Track) -->
    <div class="form-group mb-3">
        <p class="form-label">Featured Artists</p>
        <template
            x-if="$store.release.tracks[0] && $store.release.tracks[0].featuredartists && $store.release.tracks[0].featuredartists.length">
            <div class="row flex-column flex-md-row px-md-3">
                <div class="col-md-7">
                    <template x-for="(artist, index) in $store.release.tracks[0].featuredartists" :key="index">
                        <div class="d-flex flex-column gap-2 mb-3 border border-0 rounded p-3 shadow">
                            <div class="d-flex gap-1 flex-row justify-content-between">
                                <span class="form-label text-sm" x-text="'Featured Artist ' + (index + 1)"></span>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary px-1 py-0" type="button"
                                        @click="moveUpItem($store.release.tracks[0].featuredartists, index)"><i
                                            class="bx bx-chevron-up"></i></button>
                                    <button class="btn btn-sm btn-outline-secondary px-1 py-0" type="button"
                                        @click="moveDownItem($store.release.tracks[0].featuredartists, index)"><i
                                            class="bx bx-chevron-down"></i></button>
                                </div>
                            </div>
                            <div class="d-flex gap-1 flex-column flex-grow-1">
                                <div>
                                    <label class="form-label"><small>Artist Name</small></label>
                                    <input type="text" class="form-control form-control-sm" x-model="artist.name">
                                </div>
                                <div>
                                    <label class="form-label"><small>Social Media</small></label>
                                    <input type="text" class="form-control form-control-sm" x-model="artist.social">
                                </div>
                                <div>
                                    <label class="form-label"><small>Spotify Link</small></label>
                                    <input type="text" class="form-control form-control-sm" x-model="artist.spotify">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-sm btn-outline-danger" type="button"
                                    @click="$store.release.tracks[0].featuredartists.splice(index, 1)"><i
                                        class="bx bx-trash"></i> Remove</button>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="col-md-5 order-first order-md-last">
                    <p>
                        <span>
                            <strong>
                                For social media and Spotify link:
                            </strong>
                            <ul>
                                <li>The social media and Spotify artist <strong>should have the same
                                        name</strong> as artist name mentioned in the release</li>
                                <li>Spotify link is optional, please fill if artist has a Spotify artist
                                    account</li>
                                <li>Use full URL, complete with https:// (e.g.
                                    https://open.spotify.com/artist/...).</li>
                            </ul>
                        </span>
                    </p>
                </div>
            </div>
        </template>
        <button type="button" class="btn release-add-artist-button"
            @click="addArtistToArray('$store.release.tracks[0]', 'featuredartists')">
            <i class="material-icons-outlined">group_add</i>
            <span><strong>Tambah Featured Artist</strong><small>Tambahkan artis kolaborasi pada track ini</small></span>
        </button>
    </div>

    <!-- Record Label -->
    <div class="form-group col-12 col-md-6 mb-3">
        <label for="recordlabel" class="form-label">Record Label</label>
        <input type="text" class="form-control" name="recordlabel" id="recordlabel" x-model="$store.release.recordlabel"
            required>
    </div>

    <!-- Version -->
    <div class="form-group col-12 col-md-6 mb-3">
        <label for="version" class="form-label">Version</label>
        <select name="version" class="form-select" id="version" x-model="$store.release.version" required>
            @foreach(\App\Models\System\Version::all() as $version)
                @if($version->is_active)
                    <option value="{{ $version->id }}">{{ $version->name }}</option>
                @else
                    <option value="{{ $version->id }}" x-show="$store.release.version == {{ $version->id }}">
                        {{ $version->name }} (Legacy)
                    </option>
                @endif
            @endforeach
        </select>
    </div>

    <!-- Music Usage License: only required for a non-original version -->
    <div class="form-group col-12 release-ipl-field" x-show="$store.release.version != 1" x-transition>
        <div class="release-ipl-card">
            <div class="release-ipl-icon"><i class="material-icons-outlined">description</i></div>
            <div class="release-ipl-copy">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <label for="ipl" class="form-label mb-0">Music Usage License (Izin Penggunaan Lagu - IPL)</label>
                    <span class="release-required-label">Required</span>
                </div>
                <p>Unggah dokumen izin penggunaan lagu dalam format PDF untuk versi rilis non-Original.</p>
                <div class="release-ipl-current" x-show="$store.release.iplUrl">
                    <i class="material-icons-outlined">task_alt</i>
                    <a :href="$store.release.iplUrl" target="_blank" x-text="$store.release.iplName"></a>
                </div>
            </div>
            <div class="release-ipl-action">
                <input type="file" name="ipl" id="ipl" accept="application/pdf" :required="$store.release.version != 1"
                    @change="document.getElementById('ipl-file-name').textContent = $event.target.files[0] ? $event.target.files[0].name : 'PDF, maksimal sesuai ketentuan sistem'">
                <label for="ipl" class="btn release-ipl-upload"><i class="material-icons-outlined">upload_file</i> Upload PDF</label>
                <small id="ipl-file-name">PDF, maksimal sesuai ketentuan sistem</small>
            </div>
        </div>
    </div>

    <!-- Genre -->
    <div class="form-group col-12 col-md-6 mb-3">
        <label for="genre" class="form-label">Genre</label>
        <select name="genre" class="form-select" id="genre" x-model="$store.release.genre"
            @change="$store.release.methods.fetchSubgenres()" required>
            @foreach(\App\Models\System\Genre::all() as $genre)
                @if($genre->is_active)
                    <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                @else
                    <option value="{{ $genre->id }}" x-show="$store.release.genre == {{ $genre->id }}">{{ $genre->name }}
                        (Legacy)</option>
                @endif
            @endforeach
        </select>
    </div>

    <!-- Subgenre -->
    <div class="form-group col-12 col-md-6 mb-3">
        <label for="subgenre" class="form-label">Subgenre</label>
        <select name="subgenre" class="form-select" id="subgenre" x-model="$store.release.subgenre">
            <option value="">No Subgenre</option>
            <template x-for="sub in $store.release.subgenres" :key="sub.id">
                <option :value="sub.id" x-text="sub.name" :selected="sub.id == $store.release.subgenre"></option>
            </template>
        </select>
    </div>

    <!-- Instrumental Details -->
    <div class="form-group col-12 col-md-6 mb-3">
        <label class="form-label">Instrumental</label>
        <select class="form-select form-select-sm" x-model="$store.release.tracks[0].instrumental"
            @change="if ($store.release.tracks[0].instrumental == 1) { $store.release.tracks[0].contributors.forEach(contributor => contributor.role = '') }">
            <option value="0">No</option>
            <option value="1">Yes</option>
        </select>
    </div>

    <!-- Lyrics Language -->
    <div class="form-group col-12 col-md-6 mb-3" x-show="$store.release.tracks[0] && $store.release.tracks[0].instrumental == 0">
        <label class="form-label">Lyrics Language</label>
        <select class="form-select form-select-sm" x-model="$store.release.tracks[0].lyriclang">
            @foreach (Symfony\Component\Intl\Languages::getNames() as $code => $language)
                <option value="{{ $code }}">{{ $language }}</option>
            @endforeach
        </select>
    </div>

    <!-- Explicit -->
    <div class="form-group col-12 col-md-6 mb-3" x-show="$store.release.tracks[0] && $store.release.tracks[0].instrumental == 0">
        <label class="form-label">Explicit</label>
        <select class="form-select form-select-sm" x-model="$store.release.tracks[0].explicit">
            <option value="non-explicit">Non-Explicit</option>
            <option value="explicit">Explicit</option>
            <option value="clean">Clean</option>
        </select>
    </div>

    <!-- Lyrics -->
    <div class="form-group mb-3" x-show="$store.release.tracks[0] && $store.release.tracks[0].instrumental == 0">
        <label class="form-label">Lyrics</label>
        <textarea class="form-control form-control-sm" rows="3" x-model="$store.release.tracks[0].lyrics"></textarea>
    </div>


    <!-- Contributors -->
    <div class="form-group mb-3">
        <p class="form-label">Contributors</p>
        <template
            x-for="(contributor, index) in ($store.release.tracks[0] ? $store.release.tracks[0].contributors : [])"
            :key="index">
            <div class="row mb-3 align-items-center">
                <div class="col-10 pe-0">
                    <input type="text" class="form-control form-control-sm mb-1" placeholder="Name"
                        x-model="contributor.name">
                    <select class="form-select form-select-sm" x-model="contributor.role">
                        <option value="">Select contributor role</option>
                        <template x-for="(contributorRole, index) in $store.release.contributorRoles" :key="index">
                            <option 
                                :value="contributorRole.id" 
                                :disabled="!contributorRole.is_active || ($store.release.tracks[0].instrumental == 1 && !contributorRole.is_instrumental)" 
                                x-text="contributorRole.name" 
                                x-show="contributorRole.is_active || contributor.role == contributorRole.id">
                            </option>
                        </template>
                    </select>
                </div>
                <div class="col-2">
                    <button class="btn btn-outline-danger btn-sm w-100 py-2" type="button"
                        @click="$store.release.tracks[0].contributors.splice(index, 1)"><i class="bx bx-x"></i></button>
                </div>
            </div>
        </template>
        <button type="button" class="btn release-inline-action" aria-label="Tambah kontributor"
            @click="addRoleItem('$store.release.tracks[0]', 'contributors')">
            <i class="material-icons-outlined" aria-hidden="true">person_add</i>
            <span class="release-inline-action-copy"><strong>Tambah kontributor</strong><small>Tambahkan peran kreatif untuk lagu ini</small></span>
            <i class="material-icons-outlined release-inline-action-arrow" aria-hidden="true">add</i>
        </button>
    </div>

    <!-- Production Details -->
    <div class="form-group mb-3">
        <p class="form-label">Production / Engineering</p>
        <template x-for="(production, index) in ($store.release.tracks[0] ? $store.release.tracks[0].productions : [])"
            :key="index">
            <div class="row mb-3 align-items-center">
                <div class="col-10 pe-0">
                    <input type="text" class="form-control form-control-sm mb-1" placeholder="Name"
                        x-model="production.name">
                    <select class="form-select form-select-sm" x-model="production.role">
                        @foreach (App\Models\System\Production::all() as $production)
                            @if($production->is_active)
                                <option value="{{ $production->id }}">{{ $production->name }}</option>
                            @else
                                <option value="{{ $production->id }}" x-show="production.role == {{ $production->id }}">
                                    {{ $production->name }} (Legacy)
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-2">
                    <button class="btn btn-outline-danger btn-sm w-100 py-2" type="button"
                        @click="$store.release.tracks[0].productions.splice(index, 1)"><i class="bx bx-x"></i></button>
                </div>
            </div>
        </template>
        <button type="button" class="btn release-inline-action" aria-label="Tambah produksi / engineering"
            @click="addRoleItem('$store.release.tracks[0]', 'productions')">
            <i class="material-icons-outlined" aria-hidden="true">tune</i>
            <span class="release-inline-action-copy"><strong>Tambah produksi / engineering</strong><small>Catat producer, mixer, atau engineer</small></span>
            <i class="material-icons-outlined release-inline-action-arrow" aria-hidden="true">add</i>
        </button>
    </div>

    <!-- Songwriters Array -->
    <div class="form-group mb-3">
        <label class="form-label">Songwriters</label>
        <template x-for="(writer, index) in ($store.release.tracks[0] ? $store.release.tracks[0].songwriters : [])"
            :key="index">
            <div class="row mb-2 align-items-center">
                <div class="col-10 pe-0">
                    <input type="text" class="form-control form-control-sm mb-1" placeholder="Name"
                        x-model="writer.name">
                    <select class="form-select form-select-sm" x-model="writer.role">
                        <option value="">-- Role --</option>
                        @foreach (\App\Enums\SongwriterRole::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-2">
                    <button class="btn btn-outline-danger btn-sm w-100 py-2" type="button"
                        @click="$store.release.tracks[0].songwriters.splice(index, 1)"><i class="bx bx-x"></i></button>
                </div>
            </div>
        </template>
        <button type="button" class="btn release-inline-action mt-2" aria-label="Tambah penulis lagu"
            @click="addSongwriterItem('$store.release.tracks[0]')">
            <i class="material-icons-outlined" aria-hidden="true">edit_note</i>
            <span class="release-inline-action-copy"><strong>Tambah penulis lagu</strong><small>Masukkan songwriter dan perannya</small></span>
            <i class="material-icons-outlined release-inline-action-arrow" aria-hidden="true">add</i>
        </button>
    </div>

    <!-- ISWC -->
    <div class="form-group col-12 col-md-6 mb-3">
        <label class="form-label">ISWC (Optional)</label>
        <input type="text" class="form-control form-control-sm" x-model="$store.release.tracks[0].iswc">
    </div>

    <!-- Writer Percentage -->
    <div class="form-group col-12 col-md-6 mb-3">
        <label class="form-label">What percentage of this track was written by you?</label>
        <div class="input-group">
            <input type="number" class="form-control form-control-sm"
                x-model="$store.release.tracks[0].writer_percentage" min="0" max="100">
            <span class="input-group-text">%</span>
        </div>
    </div>
</div>
