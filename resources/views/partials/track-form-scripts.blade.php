<script>
    if (typeof window.moveUpItem !== 'function') {
        window.moveUpItem = function (arr, index) {
            if (index > 0) {
                arr.splice(index, 1, ...arr.splice(index - 1, 1, arr[index]));
            }
        };

        window.moveDownItem = function (arr, index) {
            if (index < arr.length - 1) {
                arr.splice(index, 1, ...arr.splice(index + 1, 1, arr[index]));
            }
        };

        window.addArtistToArray = function (scopeStr, artistKey) {
            const trackObj = (scopeStr === '$store.release.tracks[0]')
                ? Alpine.store('release').tracks[0]
                : Alpine.store('tray').trackTray;

            if (!trackObj[artistKey]) {
                trackObj[artistKey] = [];
            }
            trackObj[artistKey].push({ name: '', social: '', spotify: '' });
        };

        window.addRoleItem = function (scopeStr, key) {
            const trackObj = (scopeStr === '$store.release.tracks[0]')
                ? Alpine.store('release').tracks[0]
                : Alpine.store('tray').trackTray;

            if (!trackObj[key]) {
                trackObj[key] = [];
            }
            trackObj[key].push({ name: '', role: String(trackObj.instrumental) === '1' ? '' : '1' });
        };

        window.addSongwriterItem = function (scopeStr) {
            const trackObj = (scopeStr === '$store.release.tracks[0]')
                ? Alpine.store('release').tracks[0]
                : Alpine.store('tray').trackTray;

            if (!trackObj.songwriters) {
                trackObj.songwriters = [];
            }
            trackObj.songwriters.push({ name: '', role: '' });
        };

        window.fetchSubgenresForScope = async function (scopeStr) {
            const trackObj = (scopeStr === '$store.release.tracks[0]')
                ? Alpine.store('release').tracks[0]
                : Alpine.store('tray').trackTray;

            if (trackObj && trackObj.genre) {
                const isCustomer = !window.location.pathname.includes('/admin/');
                let url = isCustomer
                    ? "{{ route('releases.get_subgenre', ':id') }}"
                    : "{{ route('admin.releases.get_subgenre', ':id') }}";
                url = url.replace(':id', trackObj.genre);
                if (trackObj.subgenre) {
                    url += '?current=' + trackObj.subgenre;
                }

                try {
                    await $.ajax({
                        url: url,
                        type: 'GET',
                        success: function (data) {
                            if (scopeStr === '$store.release.tracks[0]') {
                                Alpine.store('release').trackSubGenres = data.subgenres;
                            } else {
                                Alpine.store('tray').subgenres = data.subgenres;
                            }
                        }
                    });
                } catch (e) {
                    console.error("Failed to load subgenres", e);
                }
            }
        };

        window.parseAudioMetadata = async function (file) {
            const allowedFormats = ['wav', 'flac'];
            const ext = file.name.split('.').pop().toLowerCase();
            const maxSize = 200 * 1024 * 1024;
            if (!allowedFormats.includes(ext)) {
                throw 'File ditolak: format harus WAV atau FLAC.';
            }
            if (file.size > maxSize) {
                throw 'File ditolak: ukuran file maksimal 200 MB.';
            }

            // Read the first 2048 bytes to cover the WAV and FLAC STREAMINFO headers
            const buffer = await file.slice(0, 2048).arrayBuffer();
            const view = new DataView(buffer);

            let bitDepth = 0;
            let duration = 0;
            let sampleRate = 0;

            if (ext === 'wav') {
                const riff = String.fromCharCode(view.getUint8(0), view.getUint8(1), view.getUint8(2), view.getUint8(3));
                const wave = String.fromCharCode(view.getUint8(8), view.getUint8(9), view.getUint8(10), view.getUint8(11));
                if (riff !== 'RIFF' || wave !== 'WAVE') {
                    throw 'Invalid WAV file header.';
                }

                let offset = 12;
                let fmtFound = false;
                let channels = 0;
                let dataSize = 0;

                while (offset < buffer.byteLength - 8) {
                    const chunkId = String.fromCharCode(view.getUint8(offset), view.getUint8(offset + 1), view.getUint8(offset + 2), view.getUint8(offset + 3));
                    const chunkSize = view.getUint32(offset + 4, true);
                    if (chunkId === 'fmt ') {
                        fmtFound = true;
                        channels = view.getUint16(offset + 10, true);
                        sampleRate = view.getUint32(offset + 12, true);
                        bitDepth = view.getUint16(offset + 22, true);
                    } else if (chunkId === 'data') {
                        dataSize = chunkSize;
                    }
                    offset += 8 + chunkSize;
                }

                if (!fmtFound) {
                    throw 'No fmt chunk found in WAV file.';
                }

                if (bitDepth !== 16 && bitDepth !== 24) {
                    throw `Unsupported bit depth: ${bitDepth}-bit. Only 16-bit or 24-bit files are allowed.`;
                }

                if (dataSize === 0) {
                    dataSize = file.size - offset; // fallback to remaining file size
                }

                if (sampleRate && channels && bitDepth && dataSize) {
                    const bytesPerSec = sampleRate * channels * (bitDepth / 8);
                    duration = dataSize / bytesPerSec;
                }
            } else if (ext === 'flac') {
                const magic = String.fromCharCode(view.getUint8(0), view.getUint8(1), view.getUint8(2), view.getUint8(3));
                if (magic !== 'fLaC') {
                    throw 'Invalid FLAC file signature.';
                }

                const hi = view.getUint32(18, false); // Big Endian
                const lo = view.getUint32(22, false); // Big Endian

                sampleRate = hi >>> 12;
                const channels = ((hi >>> 9) & 0x07) + 1;
                bitDepth = ((hi >>> 4) & 0x1F) + 1;
                const totalSamples = (BigInt(hi & 0x0F) << 32n) | BigInt(lo);

                if (bitDepth !== 16 && bitDepth !== 24) {
                    throw `Unsupported bit depth: ${bitDepth}-bit. Only 16-bit or 24-bit files are allowed.`;
                }

                if (sampleRate && totalSamples) {
                    duration = Number(totalSamples) / sampleRate;
                }
            }

            if (!sampleRate || sampleRate < 44100 || sampleRate > 96000) {
                throw `File ditolak: sample rate ${sampleRate || 'tidak terbaca'} Hz. Gunakan 44.1 kHz sampai 96 kHz.`;
            }

            return { bitDepth, sampleRate, duration };
        };

        window.verifyAudioMetadata = async function (file) {
            await window.parseAudioMetadata(file);
        };

        window.uploadAudioFile = async function (scopeStr, type, suffix) {
            const inputId = 'file-' + type + suffix;
            const audioId = 'audio-' + type + suffix;
            const nameKey = (type === 'full') ? 'filefullname' : 'filetrimmedname';
            const contentKey = (type === 'full') ? 'filefullcontent' : 'filetrimmedcontent';
            const tempKey = (type === 'full') ? 'filefulltemp' : 'filetrimmedtemp';

            const trackObj = (scopeStr === '$store.release.tracks[0]')
                ? Alpine.store('release').tracks[0]
                : Alpine.store('tray').trackTray;

            try {
                const file = document.getElementById(inputId).files[0];
                if (!file) return;

                // Validate and retrieve header duration
                const metadata = await window.parseAudioMetadata(file);
                if (type === 'trimmed' && (!metadata.duration || metadata.duration > 59)) {
                    throw 'File TikTok Ref ditolak: durasi maksimal adalah 59 detik.';
                }

                const audioURL = URL.createObjectURL(file);
                const audioPlayer = document.getElementById(audioId);

                trackObj[nameKey] = file.name;
                trackObj[contentKey] = audioURL;

                Alpine.store('tray').overlayProgress = 0;
                Alpine.store('tray').overlayText = "Uploading track...";
                Alpine.store('tray').overlayActive = true;

                // Wait for player to load metadata or use binary duration if player reports Infinity/errors
                await new Promise((resolve, reject) => {
                    audioPlayer.src = audioURL;
                    audioPlayer.onloadedmetadata = function () {
                        const playerDuration = audioPlayer.duration;
                        if (isFinite(playerDuration) && playerDuration > 0) {
                            trackObj.duration = Math.round(playerDuration);
                        } else if (metadata.duration > 0) {
                            trackObj.duration = Math.round(metadata.duration);
                        } else {
                            trackObj.duration = 0;
                        }
                        resolve();
                    };
                    audioPlayer.onerror = function () {
                        if (metadata.duration > 0) {
                            trackObj.duration = Math.round(metadata.duration);
                            resolve();
                        } else {
                            reject('Failed to load audio player metadata.');
                        }
                    };
                });

                // Get file hash
                const arrayBuffer = await file.arrayBuffer();
                let hashHex = '';
                if (window.crypto && window.crypto.subtle) {
                    const hashBuffer = await crypto.subtle.digest('SHA-256', arrayBuffer);
                    hashHex = Array.from(new Uint8Array(hashBuffer))
                        .map(byte => byte.toString(16).padStart(2, '0'))
                        .join('');
                } else {
                    const wordArray = CryptoJS.lib.WordArray.create(new Uint8Array(arrayBuffer));
                    hashHex = CryptoJS.SHA256(wordArray).toString(CryptoJS.enc.Hex);
                }

                // Chunk upload
                const filename = (Math.random() + 1).toString(36).substring(7);
                const chunkSize = 5 * 1024 * 1024; // 5MB chunks
                const totalchunks = Math.ceil(file.size / chunkSize);
                let uploadPromises = [];

                const isCustomer = !window.location.pathname.includes('/admin/');
                const chunkStoreUrl = isCustomer
                    ? "{{ route('releases.track_chunk_store') }}"
                    : "{{ route('admin.releases.track_chunk_store') }}";
                const chunkMergeUrl = isCustomer
                    ? "{{ route('releases.track_chunk_merge') }}"
                    : "{{ route('admin.releases.track_chunk_merge') }}";

                for (let i = 0; i < totalchunks; i++) {
                    const chunk = file.slice(i * chunkSize, (i + 1) * chunkSize);

                    const uploadPromise = new Promise((resolveUpload, rejectUpload) => {
                        let formData = new FormData();
                        formData.append('chunk', chunk);
                        formData.append('index', i);
                        formData.append('filename', filename);

                        $.ajax({
                            url: chunkStoreUrl,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function () {
                                resolveUpload();
                            },
                            error: function (err) {
                                rejectUpload('Chunk upload failed.');
                            }
                        });
                    });

                    uploadPromises.push(uploadPromise);
                }

                await Promise.all(uploadPromises);

                // Merge request
                let mergeData = {
                    filename: filename,
                    totalchunks: totalchunks,
                    hash: hashHex,
                    type: type
                };

                await new Promise((resolveMerge, rejectMerge) => {
                    $.ajax({
                        url: chunkMergeUrl,
                        type: 'POST',
                        data: mergeData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (data) {
                            if (data.success) {
                                trackObj[tempKey] = filename;
                                resolveMerge();
                            } else {
                                rejectMerge(data.errors || 'Merge failed.');
                            }
                        },
                        error: function (err) {
                            const errMsg = err.responseJSON && err.responseJSON.errors ? err.responseJSON.errors : 'Merge failed.';
                            rejectMerge(errMsg);
                        }
                    });
                });

                Lobibox.notify('success', {
                    pauseDelayOnHover: true,
                    size: 'mini',
                    rounded: true,
                    position: 'top right',
                    icon: 'bi bi-check-circle',
                    msg: 'Track upload finished.'
                });

            } catch (error) {
                document.getElementById(audioId).src = '';
                document.getElementById(inputId).value = '';
                trackObj[nameKey] = '';
                trackObj[contentKey] = '';
                trackObj[tempKey] = '';

                window.showUploadError(
                    error,
                    type === 'trimmed' ? 'TikTok Ref ditolak' : 'File master ditolak'
                );
            } finally {
                const fileInput = document.getElementById(inputId);
                if (fileInput) {
                    fileInput.value = '';
                }
                Alpine.store('tray').overlayActive = false;
            }
        };
    }
</script>
