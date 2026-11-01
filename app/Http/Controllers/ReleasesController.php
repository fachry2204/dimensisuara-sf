<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use App\Exceptions\Aggregator\ValidationErrorException;
use App\Exceptions\Aggregator\InvalidFileFormatException;

use App\Models\System\Genre;
use App\Models\Release;
use App\Models\Track;

use App\Services\Releases\ReleaseService;

class ReleasesController extends Controller
{
    protected $service;

    public function __construct(ReleaseService $service)
    {
        $this->service = $service;
    }

    /**
     * View only methods
     */
    public function index()
    {
        $releases = Release::where('profileid', current_user()->profile->id)->whereNotIn('status', ['draft'])->get();
        $albums = Release::where('profileid', current_user()->profile->id)->whereIn('status', ['pending', 'released'])->where('type', 'album')->get();
        $singles = Release::where('profileid', current_user()->profile->id)->whereIn('status', ['pending', 'released'])->where('type', 'single')->get();
        $rejected = Release::where('profileid', current_user()->profile->id)->where('status', 'rejected')->get();
        $draft = Release::where('profileid', current_user()->profile->id)->where('status', 'draft')->get();

        return view('customer.releases.index', compact('releases', 'albums', 'singles', 'rejected', 'draft'));
    }

    public function show($id)
    {
        // Get release
        $release = Release::where('profileid', current_user()->profile->id)
            ->with(['tracks', 'histories.user'])
            ->findOrFail($id);

        // Allow show except for draft
        if (in_array($release->status, ['draft'])) {
            return redirect()->route('releases.edit', ['id' => $id]);
        }

        // Return view
        return view('customer.releases.show', compact('release', 'id'));
    }

    public function create($type)
    {
        // Check type input
        if (!in_array($type, ['album', 'single'])) {
            abort(404);
        }

        // Create new release
        $release = new Release();
        $release->type = $type;

        // Return view
        return view('customer.releases.create', compact('release'));
    }

    public function edit($id)
    {
        // Get release
        $release = Release::where('profileid', current_user()->profile->id)->findOrFail($id);

        // Allow edit only for draft and rejected
        if (!in_array($release->status, ['draft', 'rejected'])) {
            return redirect()->route('releases.show', ['id' => $id]);
        }

        // Return view
        return view('customer.releases.create', compact('release'));
    }

    /**
     * Action methods
     */
    public function destroy($id)
    {
        // Allow delete only for draft and rejected
        $release = Release::where('profileid', current_user()->profile->id)
            ->whereIn('status', ['draft', 'rejected'])
            ->findOrFail($id);

        $context = [];

        // Capture title before deletion
        $releaseTitle = $release->title;

        try {
            $result = $this->service->destroy($release);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info("Release '{$releaseTitle}' deleted.", $context);

            return redirect()->back()->with('success', 'Release deleted.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['release_title'] = $releaseTitle;
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error("Failed to delete release '{$releaseTitle}'.", $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function store(Request $request)
    {
        $release = new Release();
        $context = [];

        // Extract data before try block for error logging
        $data = $request->only([
            'title',
            'titlelang',
            'recordlabel',
            'version',
            'ipl',
            'genre',
            'subgenre',
            'released',
            'releaseddate',
            'releaseon',
            'upc',
            'primaryartists',
            'type',
            'tracks',
            'tracks_json',
            'cover',
            'tiktok_ytmusic_rerelease_date',
        ]);

        try {
            $profileid = current_user()->profile->id;
            $status = 'draft';

            // Never trust the editable field to enforce the account's label entitlement.
            $data['recordlabel'] = current_user()->profile->recordLabelFor($data['recordlabel'] ?? null);
            $result = $this->service->store($release, $profileid, $status, $data);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info("Release '{$result->title}' created.", $context);

            // Return
            return response()->json([
                'success' => true,
                'id' => $result->id,
                'tracks' => $result->tracks->pluck('id')->all(),
                'ipl_url' => $result->ipl_url,
                'ipl_name' => $result->ipl ? $result->ipl->original_name : null,
            ], 200);
        } catch (InvalidFileFormatException $e) {

            // Return validation errors
            return response()->json([
                'part' => 'release',
                'errors' => [
                    'cover' => $e->getMessage()
                ]
            ], 422);
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['release_title'] = $data['title'] ?? 'Unknown';
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error("Failed to create release '" . ($data['title'] ?? 'Unknown') . "'.", $context);

            return response()->json([
                'success' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function update($id, Request $request)
    {
        $release = Release::where('profileid', current_user()->profile->id)
            ->whereIn('status', ['draft', 'rejected'])
            ->findOrFail($id);

        $context = [];

        try {
            $profileid = current_user()->profile->id;
            $status = $release->status;
            $data = $request->only([
                'title',
                'titlelang',
                'recordlabel',
                'version',
                'ipl',
                'genre',
                'subgenre',
                'released',
                'releaseddate',
                'releaseon',
                'upc',
                'primaryartists',
                'type',
                'tracks',
                'tracks_json',
                'cover',
                'tiktok_ytmusic_rerelease_date',
            ]);

            // Never trust the editable field to enforce the account's label entitlement.
            $data['recordlabel'] = current_user()->profile->recordLabelFor($data['recordlabel'] ?? null);
            $result = $this->service->store($release, $profileid, $status, $data);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info("Release '{$result->title}' updated.", $context);

            return response()->json([
                'success' => true,
                'id' => $result->id,
                'tracks' => $result->tracks->pluck('id')->all(),
                'ipl_url' => $result->ipl_url,
                'ipl_name' => $result->ipl ? $result->ipl->original_name : null,
            ], 200);
        } catch (InvalidFileFormatException $e) {

            // Return validation errors
            return response()->json([
                'part' => 'release',
                'errors' => [
                    'cover' => $e->getMessage()
                ]
            ], 422);
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['release_title'] = $release->title;
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error("Failed to update release '{$release->title}'.", $context);

            return response()->json([
                'success' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function submit($id)
    {
        // Get release
        $release = Release::where('profileid', current_user()->profile->id)
            ->whereIn('status', ['draft', 'rejected'])
            ->with('tracks')
            ->findOrFail($id);

        $context = [];

        try {
            $result = $this->service->submit($release);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info("Release '{$release->title}' submitted.", $context);

            return response()->json([
                'success' => true,
            ], 200);

        } catch (ValidationErrorException $e) {

            // Return validation errors
            return response()->json([
                'part' => $e->getPart(),
                'position' => $e->getPosition(),
                'errors' => $e->getData()
            ], 422);
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['release_title'] = $release->title;
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error("Failed to submit release '{$release->title}'.", $context);

            return response()->json([
                'success' => false,
                'errors' => $th->getMessage(),
                'stack' => $th->getTraceAsString()
            ], 500);
        }
    }

    public function get_subgenre(Request $request, $genreid)
    {
        try {
            $genre = Genre::findOrFail($genreid);
            $currentSubgenreId = $request->query('current');

            $subgenres = $genre->subgenres()
                ->where(function ($query) use ($currentSubgenreId) {
                    $query->where('is_active', true);
                    if ($currentSubgenreId) {
                        $query->orWhere('id', $currentSubgenreId);
                    }
                })
                ->get()
                ->sortBy('name')
                ->values();

            return response()->json([
                'success' => true,
                'subgenres' => $subgenres
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Genre not found.', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'errors' => $e->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            Log::error('Failed to get subgenre.', [
                'message' => $th->getMessage(),
                'stack' => $th->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function get_tracks($id)
    {
        try {
            $release = Release::where('profileid', current_user()->profile->id)->findOrFail($id);

            $tracks = $release->tracks->map(function ($track) {
                $primaryartists = $track->primaryartists ?? [];
                $temp = [];
                if (is_array($primaryartists)) {
                    foreach ($primaryartists as $primaryartist) {
                        if (is_a($primaryartist, 'stdClass')) {
                            $temp[] = $primaryartist->name;
                        } else if (is_array($primaryartist)) {
                            if (isset($primaryartist['name'])) {
                                $temp[] = $primaryartist['name'];
                            }
                        } else if (is_string($primaryartist)) {
                            $temp[] = $primaryartist;
                        }
                    }
                    $performer = implode(', ', $temp);
                }

                $duration = (int) $track->duration;
                $duration_minute = floor($duration / 60);
                $duration_second = $duration % 60;

                return [
                    'id' => $track->id,
                    'title' => $track->title,
                    'performer' => $performer,
                    'genre' => $track->genre,
                    'language' => $track->titlelang,
                    'isrc' => $track->isrc,
                    'iswc' => $track->iswc,
                    'duration_minute' => $duration_minute,
                    'duration_second' => $duration_second,
                    'songwriters' => $track->songwriters
                ];
            });

            return response()->json([
                'success' => true,
                'tracks' => $tracks
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Release not found for fetching tracks.', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'errors' => $e->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            Log::error('Failed to get tracks for release.', [
                'message' => $th->getMessage(),
                'stack' => $th->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function track_chunk_store(Request $request)
    {
        $chunk = $request->file('chunk');
        $index = $request->input('index');
        $filename = $request->input('filename');

        try {
            // Store chunks using service
            $this->service->trackChunkStore($chunk, $index, $filename);

            return response()->json([
                'success' => true,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Failed to store track chunk.', [
                'message' => $th->getMessage(),
                'stack' => $th->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function track_chunk_merge(Request $request)
    {
        // Define directories
        $filename = $request->input('filename');
        $total_chunks = $request->input('totalchunks');
        $hash = $request->input('hash');
        $type = $request->input('type');

        try {
            // Merge chunks using service
            $this->service->trackChunkMerge($filename, $total_chunks, $hash, $type);

            // Response
            return response()->json([
                'success' => true,
                'name' => $filename,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Failed to merge track chunks.', [
                'message' => $th->getMessage(),
                'stack' => $th->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }
}
