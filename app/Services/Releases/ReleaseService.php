<?php

namespace App\Services\Releases;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

use App\Exceptions\Aggregator\ErrorCheckingMediaDurationException;
use App\Exceptions\Aggregator\ErrorMediaUploadException;
use App\Exceptions\Aggregator\InvalidCoverResolutionException;
use App\Exceptions\Aggregator\InvalidFileFormatException;
use App\Exceptions\Aggregator\InvalidMediaDurationException;
use App\Exceptions\Aggregator\InvalidAudioBitDepthException;
use App\Exceptions\Aggregator\ValidationErrorException;
use App\Exceptions\Aggregator\InvalidReleaseStatusTransitionException;

use App\Http\Requests\Release\AlbumRequest;
use App\Http\Requests\Release\TrackRequest;

use App\Models\User\User;
use App\Models\Release;
use App\Models\ReleaseHistory;
use App\Models\Track;

use App\Models\System\Contributor;

use App\Services\FileService;

use App\Notifications\Admin\Release\Submission;
use App\Notifications\Customer\Release\Rejected;
use App\Notifications\Customer\Release\Submitted;
use App\Notifications\Customer\Release\Verified;

class ReleaseService
{
    public function trackChunkStore($chunk, $index, $filename)
    {
        // Store chunks using service
        FileService::chunkStore($chunk, $index, $filename);

        return true;
    }

    public function trackChunkMerge($filename, $total_chunks, $hash, $type)
    {
        // Merge chunks
        $filepath = FileService::chunkMerge($filename, $total_chunks, $hash);

        try {
            // The browser validation improves feedback, but this server-side check is
            // authoritative and protects uploads that bypass the client.
            $maxFileSize = 200 * 1024 * 1024;
            $fileSize = filesize($filepath);
            if ($fileSize === false || $fileSize > $maxFileSize) {
                throw new \RuntimeException('File size must not exceed 200 MB.');
            }

            // Validation using getID3
            $getID3 = new \getID3();
            $getID3->setOption(array('encoding' => 'UTF-8'));
            $fileInfo = $getID3->analyze($filepath);

            // Check file format (wav or flac)
            $allowedFormats = ['wav', 'flac'];
            $format = strtolower($fileInfo['fileformat'] ?? '');
            if (!in_array($format, $allowedFormats, true)) {
                throw new InvalidFileFormatException($fileInfo['fileformat'] ?? 'unknown', $allowedFormats);
            }

            // Check bits per sample (16 or 24)
            $allowedBitDepths = [16, 24];
            $bitDepth = $fileInfo['audio']['bits_per_sample'] ?? null;
            if (!in_array((int) $bitDepth, $allowedBitDepths, true)) {
                throw new InvalidAudioBitDepthException($bitDepth, $allowedBitDepths);
            }

            // Sample rate must be from 44.1 kHz through 96 kHz.
            $sampleRate = (int) ($fileInfo['audio']['sample_rate'] ?? 0);
            if ($sampleRate < 44100 || $sampleRate > 96000) {
                throw new \RuntimeException(
                    'Invalid audio sample rate: ' . ($sampleRate ?: 'unknown') . ' Hz. Supported range is 44.1 kHz to 96 kHz.'
                );
            }

            // Check duration
            if (!isset($fileInfo['playtime_seconds'])) {
                throw new ErrorCheckingMediaDurationException;
            }

            // Check duration if type is trimmed
            if ($type == 'trimmed' && $fileInfo['playtime_seconds'] > 59) {
                throw new InvalidMediaDurationException($fileInfo['playtime_seconds'], 59);
            }

            return true;
        } catch (\Throwable $exception) {
            // A rejected upload must not remain available as a temporary asset.
            FileFacade::delete($filepath);
            throw $exception;
        }
    }

    public function store(Release $release, $profileid, $status, $data, $aggregator = null)
    {
        return DB::transaction(function () use ($release, $profileid, $status, $data, $aggregator) {

            // Set aggregator
            $release->aggregator = $aggregator;

            // Bulk set attributes
            foreach (['title', 'titlelang', 'recordlabel', 'version', 'genre', 'subgenre', 'releaseddate', 'releaseon', 'upc', 'tiktok_ytmusic_rerelease_date'] as $key) {
                $val = isset($data[$key]) ? $data[$key] : null;
                if (in_array($key, ['genre', 'subgenre', 'version']) && ($val === 0 || $val === '0' || $val === '')) {
                    $val = null;
                }
                $release->{$key} = $val;
            }

            // Get primary artists: Manual loop to avoid 'null' value
            $primaryartists = [];
            if (isset($data['primaryartists']) && is_array($data['primaryartists'])) {
                foreach ($data['primaryartists'] as $artist) {

                    if (!empty($artist) && is_array($artist)) {
                        $primaryartists[] = [
                            'name' => isset($artist['name']) ? $artist['name'] : null,
                            'social' => isset($artist['social']) ? $artist['social'] : null,
                            'spotify' => isset($artist['spotify']) ? $artist['spotify'] : null,
                        ];
                    }
                }
            }

            // Get tracks: prefer JSON blob (avoids max_input_vars), fallback to form array
            $tracks = [];
            if (isset($data['tracks_json']) && is_string($data['tracks_json'])) {
                // Guard: reject oversized payloads (10 MB)
                if (strlen($data['tracks_json']) > 10 * 1024 * 1024) {
                    throw new \InvalidArgumentException('Tracks data too large.');
                }
                $decoded = json_decode($data['tracks_json'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException('Invalid tracks data: ' . json_last_error_msg());
                }
                if (is_array($decoded)) {
                    $tracks = $decoded;
                }
            } elseif (isset($data['tracks']) && is_array($data['tracks'])) {
                $tracks = $data['tracks'];
            }

            // Set attributes manually
            $release->profileid = $profileid;
            $release->status = $status;
            $release->released = ($data['released'] ?? '0') == '1' ? 1 : 0;
            $release->type = ($data['type'] ?? 'album') == 'single' ? 'single' : 'album';
            $release->primaryartists = json_encode($primaryartists);
            $release->tracknum = is_array($tracks) ? count($tracks) : 0;
            $release->delete_by = in_array($release->status, ['draft', 'rejected']) ? now()->addDays(5) : null;
            $release->save();

            // Detect removed tracks
            $currentTrackIds = $release->tracks->pluck('id')->all();
            $newTrackIds = collect($tracks)->pluck('id')->toArray();
            $removedTrackIds = array_diff($currentTrackIds, $newTrackIds);

            // Upload cover draft
            if (isset($data['cover'])) {

                // Get cover
                $cover = $data['cover'];
                if ($cover) {
                    $imageInfo = getimagesize($cover->getPathname());
                    if ($imageInfo === false) {
                        throw new InvalidFileFormatException('unknown', ['jpg', 'jpeg']);
                    }

                    list($width, $height) = $imageInfo;
                    $mime = $imageInfo['mime'] ?? '';
                    if ($mime !== 'image/jpeg') {
                        $format = str_replace('image/', '', $mime) ?: 'unknown';
                        throw new InvalidFileFormatException($format, ['jpg', 'jpeg']);
                    }
                    if ($width !== 3000 || $height !== 3000) {
                        throw new InvalidCoverResolutionException($width, $height, 3000, 3000);
                    }
                }

                // Store cover
                FileService::store($cover, $release, $release->profile, 'cover', 'covers');

                // Store thumbnail
                FileService::store($cover, $release, $release->profile, 'thumbnail', 'thumbnails');
            }

            // A release-level IPL is required whenever the release or one of its tracks is non-original.
            $hasNonOriginalTrack = collect($tracks)->contains(
                fn ($track) => (int) ($track['version'] ?? 1) !== 1
            );

            // Delete IPL only when the release and every submitted track are Original.
            if ($release->version == 1 && !$hasNonOriginalTrack) {
                if ($release->ipl) {
                    $release->ipl->delete();
                }
            } else {
                // Store ipl draft
                if (isset($data['ipl'])) {
                    $ipl = $data['ipl'];
                    if ($ipl) {
                        FileService::store($ipl, $release, $release->profile, 'ipl', 'ipls');
                    }
                }
            }

            // Delete removed tracks
            foreach ($removedTrackIds as $trackId) {

                // Detach track
                $release->tracks()->detach($trackId);

                // Immediately delete track
                $track = Track::findOrFail($trackId);
                if (count($track->releases) == 0) {
                    $track->delete();
                }
            }

            // Update tracks
            $sync = [];
            if (is_array($tracks) && count($tracks) > 0) {
                foreach ($tracks as $index => $input_track) {

                    // Get or create track
                    $track = new Track;
                    if (isset($input_track['id'])) {
                        $temp_track = Track::whereHas('releases', function ($query) use ($release) {
                            $query->where('releases.id', $release->id);
                        })->findOrFail($input_track['id']);

                        if ($temp_track) {
                            $track = $temp_track;
                        }
                    }

                    // Set/update ownership
                    $track->profileid = $release->profile->id;

                    // Bulk set attributes
                    foreach (['title', 'titlelang', 'version', 'genre', 'subgenre', 'instrumental', 'lyrics', 'lyriclang', 'explicit', 'isrc', 'duration'] as $key) {
                        $val = $input_track[$key] ?? null;
                        if (in_array($key, ['genre', 'subgenre', 'version']) && ($val === 0 || $val === '0' || $val === '')) {
                            $val = null;
                        }
                        $track->{$key} = $val;
                    }

                    // Set ISWC and Writer Percentage
                    $track->iswc = $input_track['iswc'] ?? null;
                    $track->writer_percentage = $input_track['writer_percentage'] ?? 0;

                    // Populate JSON columns
                    foreach (['primaryartists', 'featuredartists'] as $key) {
                        $arr = [];
                        if (isset($input_track[$key]) && is_array($input_track[$key]) && count($input_track[$key]) > 0) {
                            foreach ($input_track[$key] as $value) {
                                if ($value) {
                                    $arr[] = $value;
                                }
                            }
                        }
                        $track->{$key} = json_encode($arr);
                    }
                    foreach (['contributors', 'productions', 'songwriters'] as $key) {
                        $arr = [];
                        if (isset($input_track[$key]) && is_array($input_track[$key]) && count($input_track[$key]) > 0) {
                            foreach ($input_track[$key] as $i => $value) {
                                if ($value['name'] ?? null) {
                                    $arr[$i]['name'] = $value['name'] ?? null;
                                    $arr[$i]['role'] = $value['role'] ?? null;
                                }
                            }
                        }
                        // Songwriter is casted as array
                        if ($key == 'songwriters') {
                            $track->{$key} = $arr;
                        } else {
                            $track->{$key} = json_encode($arr);
                        }
                    }
                    $track->save();


                    // Upload full draft
                    if ($input_track['filefulltemp'] ?? null) {
                        $path = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $input_track['filefulltemp']);

                        if (!FileFacade::exists($path)) {
                            throw new ErrorMediaUploadException("Unable to upload full track #" . ($index + 1) . ". Please reupload the track again.");
                        }
                        // Create uploaded file
                        $uploadedFile = new UploadedFile(
                            $path,                          // Absolute path to the file
                            $input_track['filefullname'],   // Name of the file
                            null,                           // Mime type
                            null,                           // Error code?
                            true                            // Is this a real file?
                        );

                        // Store file
                        FileService::store($uploadedFile, $track, $track->profile, 'full', 'tracks');
                    }

                    // Upload trimmed draft
                    if ($input_track['filetrimmedtemp'] ?? null) {
                        $path = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $input_track['filetrimmedtemp']);

                        if (!FileFacade::exists($path)) {
                            throw new ErrorMediaUploadException("Unable to upload TikTok Ref track #" . ($index + 1) . ". Please reupload the track again.");
                        }

                        // Create uploaded file
                        $uploadedFile = new UploadedFile(
                            $path,                              // Absolute path to the file
                            $input_track['filetrimmedname'],    // Name of the file
                            null,                               // Mime type
                            null,                               // Error code?
                            true                                // Is this a real file?
                        );

                        // Store file
                        FileService::store($uploadedFile, $track, $track->profile, 'trimmed', 'tracks');
                    }

                    // Attach track
                    $sync[$track->id] = ['track_order' => $input_track['order'] ?? $index];
                }
            }

            // Sync tracks
            $release->tracks()->sync($sync);

            // Check if release type is correct
            if ($release->type == 'single' && count($release->tracks) > 1) {
                $release->type = 'album';
                $release->save();
            }

            // Reload release track relation
            $release->load('tracks');

            // Return release
            return $release;
        });
    }

    public function destroy(Release $release)
    {
        // Use transaction
        DB::transaction(function () use ($release) {

            // Detach associated tracks
            $tracks = $release->tracks;
            foreach ($tracks as $track) {
                $release->tracks()->detach($track->id);
            }

            // Delete the release
            $release->delete();
        });
    }

    public function submit(Release $release)
    {
        $targetStatus = $release->status == 'draft' ? 'pending' : 'revision';

        // Validate Status Transition
        $this->validateStatusTransition($release, $targetStatus);

        // Validate Album
        $albumRequest = new AlbumRequest();
        $releaseData = $release->toArray();

        // Convert artist objects to arrays for validation
        if (isset($releaseData['primaryartists']) && is_array($releaseData['primaryartists'])) {
            $releaseData['primaryartists'] = array_map(function ($artist) {
                return (array) $artist;
            }, $releaseData['primaryartists']);
        }

        $validator = Validator::make(
            $releaseData,
            $albumRequest->rules($release),
            $albumRequest->messages(),
            $albumRequest->attributes()
        );

        // Manually validate cover and ipl
        $validator->after(function ($validator) use ($release) {
            if (!$release->cover) {
                $validator->errors()->add('cover', 'Cover art is required');
            }
            $hasNonOriginalTrack = $release->tracks()->where('version', '!=', 1)->exists();
            if (($release->version != 1 || $hasNonOriginalTrack) && !$release->ipl) {
                $validator->errors()->add('ipl', 'Music Usage License (IPL) is required for non-original versions');
            }
        });

        // Return validation errors if any exist
        if ($validator->fails()) {
            throw new ValidationErrorException('release', $validator->errors());
        }

        // Validate each Tracks
        $tracks = $release->tracks->sortBy('pivot.track_order')->values();

        foreach ($tracks as $index => $track) {
            $trackRequest = new TrackRequest();
            $trackData = $track->toArray();

            // Convert all nested object arrays to associative arrays for validation
            foreach (['primaryartists', 'featuredartists', 'contributors', 'productions', 'songwriters'] as $key) {
                if (isset($trackData[$key]) && is_array($trackData[$key])) {
                    $trackData[$key] = array_map(function ($item) {
                        return (array) $item;
                    }, $trackData[$key]);
                }
            }

            $validator = Validator::make(
                $trackData,
                $trackRequest->rules($track),
                $trackRequest->messages(),
                $trackRequest->attributes()
            );

            // Manually validate full and trimmed track
            $validator->after(function ($validator) use ($track, $release) {

                if (!$track->full_exists) {
                    $validator->errors()->add('full_song', 'Full song is required');
                }

                if (!$track->trimmed_exists) {
                    $validator->errors()->add('trimmed_song', 'TikTok ref is required');
                }

                if ($release->released && blank($track->isrc)) {
                    $validator->errors()->add('isrc', 'ISRC is required when the release has already been released.');
                }

                if ($track->instrumental == 1) {
                    foreach ($track->contributors as $contributor) {
                        $role = Contributor::find($contributor->role);
                        if ($role->is_instrumental == 0) {
                            $validator->errors()->add('contributors', 'Contributors cannot use non-instrumental role for instrumental track');
                        }
                    }
                }

            });

            // Return validation errors if any exist
            if ($validator->fails()) {
                throw new ValidationErrorException('track', $validator->errors(), $index + 1);
            }
        }

        // Update release
        DB::transaction(function () use ($release, $targetStatus) {

            // Create release history
            ReleaseHistory::create([
                'release_id' => $release->id,
                'action' => 'submit',
                'message' => 'Release submitted by user',
                'type' => 'waiting',
                'user_id' => current_user()->id,
            ]);

            $release->status = $targetStatus;
            $release->delete_by = null;
            $release->reason = null;
            $release->save();
        });

        // Send notification to admin
        $admins = User::getByPermission('manage-releases-status');
        Notification::send($admins, new Submission($release));

        // Notify user
        Notification::send(current_user(), new Submitted($release));

        return $release;
    }

    public function adminAcceptRevision(Release $release)
    {
        // Validate Status Transition
        $targetStatus = 'pending';
        if ($release->previous_rejected_status) {
            $targetStatus = $release->previous_rejected_status;
        }
        $this->validateStatusTransition($release, $targetStatus);

        return DB::transaction(function () use ($release, $targetStatus) {

            // Create release history
            ReleaseHistory::create([
                'release_id' => $release->id,
                'action' => 'adminAcceptRevision',
                'message' => 'Revision accepted by admin',
                'type' => 'waiting',
                'user_id' => auth()->user()->id,
            ]);

            // Update release
            $release->status = $targetStatus;
            $release->previous_rejected_status = null;
            $release->delete_by = null;
            $release->reason = null;
            $release->save();

            return $release;
        });
    }

    /**
     * Admin actions
     */
    public function adminReview(Release $release, $aggregator)
    {
        // Validate Status Transition
        $this->validateStatusTransition($release, 'review');

        return DB::transaction(function () use ($release, $aggregator) {

            // Create release history
            ReleaseHistory::create([
                'release_id' => $release->id,
                'action' => 'adminReview',
                'message' => 'Release reviewed by admin',
                'type' => 'waiting',
                'user_id' => auth()->user()->id,
            ]);

            // Update release
            $release->status = 'review';
            $release->aggregator = $aggregator;
            $release->delete_by = null;
            $release->reason = null;
            $release->save();

            return $release;
        });
    }

    public function adminUpload(Release $release)
    {
        // Validate Status Transition
        $this->validateStatusTransition($release, 'uploading');

        return DB::transaction(function () use ($release) {

            // Create release history
            ReleaseHistory::create([
                'release_id' => $release->id,
                'action' => 'adminUpload',
                'message' => 'Release uploaded by admin',
                'type' => 'waiting',
                'user_id' => auth()->user()->id,
            ]);

            // Update release
            $release->status = 'uploading';
            $release->save();

            return $release;
        });
    }

    public function adminApprove(Release $release, $data)
    {
        // Validate Status Transition
        $this->validateStatusTransition($release, 'released');

        $release = DB::transaction(function () use ($release, $data) {

            // Create release history
            ReleaseHistory::create([
                'release_id' => $release->id,
                'action' => 'adminApprove',
                'message' => 'Release approved by admin',
                'type' => 'accepted',
                'user_id' => auth()->user()->id,
            ]);

            // Update release
            $release->status = 'released';
            $release->upc = $data['upc'];
            $release->verified_at = now();
            $release->contract_expired_at = now()->addYears(5);
            $release->contract_auto_renew = true;
            $release->delete_by = null;
            $release->reason = null;
            $release->save();

            // Update tracks ISRC
            foreach ($release->tracks as $track) {
                $track->isrc = $data['isrc'][$track->id];
                $track->save();
            }

            return $release;
        });

        // Notify user
        Notification::send($release->profile->user, new Verified($release));

        return $release;
    }

    public function adminReject(Release $release, $reason)
    {
        $release = DB::transaction(function () use ($release, $reason) {

            // Create release history
            ReleaseHistory::create([
                'release_id' => $release->id,
                'action' => 'adminReject',
                'message' => 'Release rejected by admin: ' . $reason,
                'type' => 'rejected',
                'user_id' => auth()->user()->id,
            ]);

            // Update release
            if ($release->status != 'revision') {
                $release->previous_rejected_status = $release->status;
            }
            $release->status = 'rejected';
            $release->reason = $reason;
            $release->delete_by = now()->addDays(5);
            $release->save();

            return $release;
        });

        // Notify user
        Notification::send($release->profile->user, new Rejected($release));

        return $release;
    }


    // Validate Status Transition
    private function validateStatusTransition(Release $release, string $targetStatus): void
    {
        $allowedTransitions = [
            'draft' => [
                'pending',
            ],

            'revision' => [
                'pending',
                'review',
                'uploading',
                'rejected',
            ],

            'pending' => [
                'review',
                'rejected',
            ],

            'review' => [
                'uploading',
                'rejected',
            ],

            'uploading' => [
                'released',
                'rejected',
            ],

            'rejected' => [
                'revision',
            ],
        ];

        $allowed = $allowedTransitions[$release->status] ?? [];

        if (!in_array($targetStatus, $allowed, true)) {
            throw new InvalidReleaseStatusTransitionException($release->status, $targetStatus);
        }
    }
}
