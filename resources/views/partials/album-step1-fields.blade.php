<div class="row g-3">
    <!-- Album Title -->
    <div class="form-group col-12 col-md-6 mb-3">
        <label for="release-title" class="form-label">Album Title</label>
        <input type="text" class="form-control" name="title" id="release-title" x-model="$store.release.title" required>
        <input type="hidden" name="type" value="album">
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
            <span><strong>Tambah Primary Artist</strong><small>Tambahkan artis utama lainnya pada album ini</small></span>
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

    <!-- IPL is only required when the album version is not Original -->
    <div class="form-group col-12 release-ipl-field" x-show="$store.release.version != 1" x-transition>
        <div class="release-ipl-card">
            <div class="release-ipl-icon"><i class="material-icons-outlined">description</i></div>
            <div class="release-ipl-copy">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <label for="ipl" class="form-label mb-0">Music Usage License (Izin Penggunaan Lagu - IPL)</label>
                    <span class="release-required-label">Required</span>
                </div>
                <p>Unggah dokumen izin penggunaan lagu dalam format PDF untuk versi album selain Original.</p>
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
</div>
