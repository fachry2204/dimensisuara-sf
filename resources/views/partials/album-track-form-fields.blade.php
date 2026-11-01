<div class="row g-3">
    <!-- Audio Files -->
    <div class="col-12">
        <p class="form-label">Audio Files</p>
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <div class="track-placeholder" id="input-file-full-modal"
                    style="border: 2px dashed #ccc; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer;"
                    onclick="document.getElementById('file-full-modal').click()">
                    <i class="material-icons-outlined" style="font-size: 48px;">music_note</i>
                    <div class="fw-bold mt-2">Full Song</div>
                    <div class="mt-2 d-flex flex-column align-items-center justify-content-center">
                        <p class="text-center mb-2 text-primary" x-text="$store.tray.trackTray.filefullname || ''"></p>
                        <audio controls :src="$store.tray.trackTray.filefullcontent || ''"
                            x-show="$store.tray.trackTray.filefullcontent" id="audio-full-modal"
                            style="max-width: 400px;" onclick="event.stopPropagation()"></audio>
                    </div>
                </div>
                <input type="file" class="form-control" id="file-full-modal" style="display: none;"
                    accept="audio/wav,audio/x-wav,audio/wave,audio/x-pn-wav,audio/flac,audio/x-flac,.wav,.flac"
                    @change="uploadAudioFile('$store.tray.trackTray', 'full', '-modal')">
            </div>

            <div class="col-12 col-md-6">
                <div class="track-placeholder" id="input-file-trimmed-modal"
                    style="border: 2px dashed #ccc; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer;"
                    onclick="document.getElementById('file-trimmed-modal').click()">
                    <i class="material-icons-outlined" style="font-size: 48px;">music_note</i>
                    <div class="fw-bold mt-2">TikTok Ref</div>
                    <div class="mt-2 d-flex flex-column align-items-center justify-content-center">
                        <p class="text-center mb-2 text-primary" x-text="$store.tray.trackTray.filetrimmedname || ''">
                        </p>
                        <audio controls :src="$store.tray.trackTray.filetrimmedcontent || ''"
                            x-show="$store.tray.trackTray.filetrimmedcontent" id="audio-trimmed-modal"
                            style="max-width: 400px;" onclick="event.stopPropagation()"></audio>
                    </div>
                </div>
                <input type="file" class="form-control" id="file-trimmed-modal" style="display: none;"
                    accept="audio/wav,audio/x-wav,audio/wave,audio/x-pn-wav,audio/flac,audio/x-flac,.wav,.flac"
                    @change="uploadAudioFile('$store.tray.trackTray', 'trimmed', '-modal')">
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

    <!-- Metadata Fields for Album Track -->
    <div class="col-12 row g-3 p-0 m-0">
        <div class="col-12 col-md-6">
            <label class="form-label">Title</label>
            <input type="text" class="form-control form-control-sm" x-model="$store.tray.trackTray.title">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Title Language</label>
            <select class="form-select form-select-sm" x-model="$store.tray.trackTray.titlelang">
                @foreach (Symfony\Component\Intl\Languages::getNames() as $code => $language)
                    <option value="{{ $code }}">{{ $language }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Version</label>
            <select class="form-select form-select-sm" x-model="$store.tray.trackTray.version">
                @foreach(\App\Models\System\Version::all() as $version)
                    @if($version->is_active)
                        <option value="{{ $version->id }}">{{ $version->name }}</option>
                    @else
                        <option value="{{ $version->id }}" x-show="$store.tray.trackTray.version == {{ $version->id }}">
                            {{ $version->name }} (Legacy)
                        </option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Genre</label>
            <select class="form-select form-select-sm" x-model="$store.tray.trackTray.genre"
                @change="fetchSubgenresForScope('$store.tray.trackTray')">
                @foreach(\App\Models\System\Genre::all() as $genre)
                    @if($genre->is_active)
                        <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                    @else
                        <option value="{{ $genre->id }}" x-show="$store.tray.trackTray.genre == {{ $genre->id }}">
                            {{ $genre->name }} (Legacy)
                        </option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="col-12 release-ipl-field" x-show="$store.tray.trackTray.version != 1" x-transition>
            <div class="release-ipl-card">
                <div class="release-ipl-icon"><i class="material-icons-outlined">description</i></div>
                <div class="release-ipl-copy">
                    <label for="track-ipl-input" class="form-label mb-0">Music Usage License (Izin Penggunaan Lagu - IPL) <span class="release-required-label">Required</span></label>
                    <p>Versi selain Original memerlukan dokumen IPL dalam format PDF sebelum track dapat ditambahkan.</p>
                    <div class="release-ipl-current" x-show="$store.release.iplUrl || $store.tray.trackTray.iplFileName">
                        <i class="material-icons-outlined">check_circle</i>
                        <span x-text="$store.tray.trackTray.iplFileName || $store.release.iplName"></span>
                    </div>
                </div>
                <div class="release-ipl-action">
                    <input type="file" id="track-ipl-input" accept="application/pdf"
                        @change="window.syncTrackIpl($event)">
                    <label for="track-ipl-input" class="btn release-ipl-upload"><i class="material-icons-outlined">upload_file</i> Pilih file IPL</label>
                    <small x-text="$store.tray.trackTray.iplFileName || 'PDF wajib untuk versi non-Original'"></small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Subgenre</label>
            <select class="form-select form-select-sm" x-model="$store.tray.trackTray.subgenre">
                <option value="">No Subgenre</option>
                <template x-for="sub in $store.tray.subgenres" :key="sub.id">
                    <option :value="sub.id" x-text="sub.name" :selected="sub.id == $store.tray.trackTray.subgenre">
                    </option>
                </template>
            </select>
        </div>

        <!-- Primary Artists for Album Track -->
        <div class="col-12">
            <p class="form-label">Primary Artists</p>
            <template x-if="$store.tray.trackTray.primaryartists && $store.tray.trackTray.primaryartists.length">
                <div>
                    <template x-for="(artist, index) in $store.tray.trackTray.primaryartists" :key="index">
                        <div class="d-flex flex-column gap-2 mb-3 border border-0 rounded p-3 shadow">
                            <div class="d-flex gap-1 flex-row justify-content-between">
                                <span class="form-label text-sm" x-text="'Primary Artist ' + (index + 1)"></span>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary px-1 py-0" type="button"
                                        @click="moveUpItem($store.tray.trackTray.primaryartists, index)"><i
                                            class="bx bx-chevron-up"></i></button>
                                    <button class="btn btn-sm btn-outline-secondary px-1 py-0" type="button"
                                        @click="moveDownItem($store.tray.trackTray.primaryartists, index)"><i
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
                                    @click="$store.tray.trackTray.primaryartists.splice(index, 1)"><i
                                        class="bx bx-trash"></i> Remove</button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
            <button type="button" class="btn release-add-artist-button"
                @click="addArtistToArray('$store.tray.trackTray', 'primaryartists')">
                <i class="material-icons-outlined">person_add</i>
                <span><strong>Tambah Primary Artist</strong><small>Tambahkan artis utama untuk track ini</small></span>
            </button>
        </div>
    </div>

    <!-- Featured Artists -->
    <div class="col-12">
        <p class="form-label">Featured Artists</p>
        <template x-if="$store.tray.trackTray.featuredartists && $store.tray.trackTray.featuredartists.length">
            <div>
                <template x-for="(artist, index) in $store.tray.trackTray.featuredartists" :key="index">
                    <div class="d-flex flex-column gap-2 mb-3 border border-0 rounded p-3 shadow">
                        <div class="d-flex gap-1 flex-row justify-content-between">
                            <span class="form-label text-sm" x-text="'Featured Artist ' + (index + 1)"></span>
                            <div>
                                <button class="btn btn-sm btn-outline-secondary px-1 py-0" type="button"
                                    @click="moveUpItem($store.tray.trackTray.featuredartists, index)"><i
                                        class="bx bx-chevron-up"></i></button>
                                <button class="btn btn-sm btn-outline-secondary px-1 py-0" type="button"
                                    @click="moveDownItem($store.tray.trackTray.featuredartists, index)"><i
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
                                @click="$store.tray.trackTray.featuredartists.splice(index, 1)"><i
                                    class="bx bx-trash"></i> Remove</button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
        <button type="button" class="btn release-add-artist-button"
            @click="addArtistToArray('$store.tray.trackTray', 'featuredartists')">
            <i class="material-icons-outlined">group_add</i>
            <span><strong>Tambah Featured Artist</strong><small>Tambahkan artis kolaborasi untuk track ini</small></span>
        </button>
    </div>

    <!-- Instrumental Details -->
    <div class="col-12 col-md-6 mt-3">
        <label class="form-label">Instrumental</label>
        <select class="form-select form-select-sm" x-model="$store.tray.trackTray.instrumental"
            @change="if ($store.tray.trackTray.instrumental == 1) { $store.tray.trackTray.contributors.forEach(contributor => contributor.role = '') }">
            <option value="0">No</option>
            <option value="1">Yes</option>
        </select>
    </div>

    <div class="col-12 col-md-6" x-show="$store.tray.trackTray.instrumental == 0">
        <label class="form-label">Lyrics Language</label>
        <select class="form-select form-select-sm" x-model="$store.tray.trackTray.lyriclang">
            @foreach (Symfony\Component\Intl\Languages::getNames() as $code => $language)
                <option value="{{ $code }}">{{ $language }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6" x-show="$store.tray.trackTray.instrumental == 0">
        <label class="form-label">Explicit</label>
        <select class="form-select form-select-sm" x-model="$store.tray.trackTray.explicit">
            <option value="non-explicit">Non-Explicit</option>
            <option value="explicit">Explicit</option>
            <option value="clean">Clean</option>
        </select>
    </div>

    <div class="col-12" x-show="$store.tray.trackTray.instrumental == 0">
        <label class="form-label">Lyrics</label>
        <textarea class="form-control form-control-sm" rows="3" x-model="$store.tray.trackTray.lyrics"></textarea>
    </div>

    <!-- Contributors -->
    <div class="col-12">
        <p class="form-label">Contributors</p>
        <template x-for="(contributor, index) in $store.tray.trackTray.contributors" :key="index">
            <div class="row mb-3 align-items-center">
                <div class="col-10 pe-0">
                    <input type="text" class="form-control form-control-sm mb-1" placeholder="Name" x-model="contributor.name">
                    <select class="form-select form-select-sm" x-model="contributor.role">
                        <option value="">Select contributor role</option>
                        {{-- @foreach (App\Models\System\Contributor::all() as $contributor)
                            @if($contributor->is_active)
                                <option value="{{ $contributor->id }}">{{ $contributor->name }}</option>
                            @else
                                <option value="{{ $contributor->id }}" x-show="contributor.role == {{ $contributor->id }}">
                                    {{ $contributor->name }} (Legacy)
                                </option>
                            @endif
                        @endforeach --}}

                        <template x-for="(contributorRole, index) in $store.release.contributorRoles" :key="index">
                            <option 
                                :value="contributorRole.id" 
                                :disabled="!contributorRole.is_active || ($store.tray.trackTray.instrumental == 1 && !contributorRole.is_instrumental)" 
                                x-text="contributorRole.name" 
                                x-show="contributorRole.is_active || contributor.role == contributorRole.id">
                            </option>
                        </template>

                    </select>
                </div>
                <div class="col-2">
                    <button class="btn btn-outline-danger btn-sm w-100 py-2" type="button"
                        @click="$store.tray.trackTray.contributors.splice(index, 1)"><i class="bx bx-x"></i></button>
                </div>
            </div>
        </template>
        <button type="button" class="btn release-inline-action" aria-label="Tambah kontributor"
            @click="addRoleItem('$store.tray.trackTray', 'contributors')">
            <i class="material-icons-outlined" aria-hidden="true">person_add</i>
            <span class="release-inline-action-copy"><strong>Tambah kontributor</strong><small>Tambahkan peran kreatif untuk track ini</small></span>
            <i class="material-icons-outlined release-inline-action-arrow" aria-hidden="true">add</i>
        </button>
    </div>

    <!-- Production Details -->
    <div class="col-12">
        <p class="form-label">Production / Engineering</p>
        <template x-for="(production, index) in $store.tray.trackTray.productions" :key="index">
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
                        @click="$store.tray.trackTray.productions.splice(index, 1)"><i class="bx bx-x"></i></button>
                </div>
            </div>
        </template>
        <button type="button" class="btn release-inline-action" aria-label="Tambah produksi / engineering"
            @click="addRoleItem('$store.tray.trackTray', 'productions')">
            <i class="material-icons-outlined" aria-hidden="true">tune</i>
            <span class="release-inline-action-copy"><strong>Tambah produksi / engineering</strong><small>Catat producer, mixer, atau engineer</small></span>
            <i class="material-icons-outlined release-inline-action-arrow" aria-hidden="true">add</i>
        </button>
    </div>

    <!-- Songwriters Array -->
    <div class="col-12">
        <label class="form-label">Songwriters</label>
        <template x-for="(writer, index) in $store.tray.trackTray.songwriters" :key="index">
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
                        @click="$store.tray.trackTray.songwriters.splice(index, 1)"><i class="bx bx-x"></i></button>
                </div>
            </div>
        </template>
        <button type="button" class="btn release-inline-action mt-2" aria-label="Tambah penulis lagu"
            @click="addSongwriterItem('$store.tray.trackTray')">
            <i class="material-icons-outlined" aria-hidden="true">edit_note</i>
            <span class="release-inline-action-copy"><strong>Tambah penulis lagu</strong><small>Masukkan songwriter dan perannya</small></span>
            <i class="material-icons-outlined release-inline-action-arrow" aria-hidden="true">add</i>
        </button>
    </div>

    <div class="col-12 col-md-6" @unless($allowIdentifierEntry ?? false) x-show="$store.release.released == 1" @endunless x-transition>
        <label class="form-label" for="track-isrc">ISRC <span class="release-required-label" x-show="$store.release.released == 1">Required</span></label>
        <input type="text" id="track-isrc" class="form-control form-control-sm" x-model="$store.tray.trackTray.isrc"
            :required="$store.release.released == 1" maxlength="15" placeholder="Contoh: IDABC2400001">
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">ISWC (Optional)</label>
        <input type="text" class="form-control form-control-sm" x-model="$store.tray.trackTray.iswc">
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">What percentage of this track was written by you?</label>
        <div class="input-group">
            <input type="number" class="form-control form-control-sm" x-model="$store.tray.trackTray.writer_percentage"
                min="0" max="100">
            <span class="input-group-text">%</span>
        </div>
    </div>
</div>
