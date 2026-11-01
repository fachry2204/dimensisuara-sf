<?php

namespace App\Http\Controllers\Admin\Aggregator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Http\Controllers\Controller;

use App\Http\Requests\Admin\ReleaseVerifyRequest;
use App\Http\Requests\Admin\ReviewReleaseRequest;
use App\Models\Release;
use App\Models\Track;
use App\Models\System\Genre;

use App\Rules\PreReleaseWindow;

use App\Services\Releases\ReleaseService;

class MainController extends Controller
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
        $catalogue = Release::visibleTo(auth()->user());
        $by_type = (clone $catalogue)->selectRaw('type, COUNT(*) as count')->groupBy('type')->pluck('count', 'type');
        $by_type['tracks'] = Track::inReleaseCatalogue(auth()->user())->count();

        $by_status = (clone $catalogue)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');
        $by_status['all'] = (clone $catalogue)->where('status', '!=', 'draft')->count();

        return view('admin.aggregator.index', compact('by_status', 'by_type'));
    }

    public function show($id)
    {
        $release = Release::with(['profile', 'tracks', 'histories.user'])->findOrFail($id);

        // Limited viewers (e.g. Partner) may only open published releases.
        if (!auth()->user()->can('view-releases-all') && $release->status !== 'released') {
            abort(404);
        }

        return view('admin.aggregator.show', compact('release'));
    }

    public function destroy(string $id, Request $request)
    {
        $request->validate(['confirm_delete' => ['required', 'accepted']]);
        $release = Release::visibleTo(auth()->user())->findOrFail($id);
        $title = $release->title;

        try {
            $this->service->destroy($release);
        } catch (\Throwable $exception) {
            Log::error('Release deletion failed.', ['release_id'=>$id, 'admin_user_id'=>auth()->id(), 'exception'=>get_class($exception)]);
            return back()->withErrors('Penghapusan belum selesai. Periksa izin penyimpanan atau koneksi Google Drive, lalu coba hapus kembali.');
        }

        Log::info('Release permanently deleted.', ['release_id'=>$id, 'release_title'=>$title, 'admin_user_id'=>auth()->id()]);
        return redirect()->route('admin.releases.index')->with('success', 'Rilis "'.$title.'" beserta file yang tidak dipakai rilis lain telah dihapus.');
    }

    public function create()
    {
        $release = new Release();
        return view('admin.aggregator.create', compact('release'));
    }

    public function edit($id)
    {
        $release = Release::findOrFail($id);
        return view('admin.aggregator.create', compact('release'));
    }

    public function track($id)
    {
        $track = Track::findOrFail($id);
        if ($track->releases()->count() > 0) {
            return redirect()->route('admin.releases.show', ['id' => $track->releases->last()->id]);
        }

        return redirect()->route('admin.releases.index')->withErrors('Release for track ID ' . $id . ' not found');
    }

    public function exportReleases()
    {
        try {
            $results = Release::visibleTo(auth()->user())->with('profile')->get()->map(function ($row) {
                $primaryArtists = $this->implodeArtists($row->primaryartists);

                return [
                    $row->id,
                    $row->title ?? '-',
                    $primaryArtists,
                    $row->type ?? '-',
                    $row->tracknum ?? 0,
                    $row->status ?? '-',
                    $row->releaseon ?? '-',
                    $row->profile->full_name ?? '-',
                    $row->created_at ?? '-',
                    $row->upc ?? '-',
                    $row->genre_name ?? '-',
                    $row->subgenre_name ?? '-',
                    $row->recordlabel ?? '-',
                    $row->version_name ?? '-',
                    $row->titlelang_name ?? '-',
                ];
            });

            $results = collect([
                [
                    'ID',
                    'Judul',
                    'Artis Utama',
                    'Tipe',
                    'Jumlah Track',
                    'Status',
                    'Tanggal Rilis',
                    'Diunggah Oleh',
                    'Tanggal Dibuat',
                    'UPC',
                    'Genre',
                    'Subgenre',
                    'Label Rekaman',
                    'Versi',
                    'Bahasa Judul',
                ]
            ])->merge($results);

            $headers = [
                "Content-type" => "text/csv",
                "Content-Disposition" => "attachment; filename=releases.csv",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0",
            ];

            $callback = function () use ($results) {
                $handle = fopen('php://output', 'w');

                foreach ($results->toArray() as $row) {
                    $convertedRow = array_map(function ($field) {
                        if (is_numeric($field) && str_contains((string) $field, '.')) {
                            return str_replace('.', ',', $field);
                        }
                        return $field;
                    }, $row);

                    fputcsv($handle, $convertedRow, ';', '"');
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $th) {
            Log::error('Failed to export releases.', [
                'message' => $th->getMessage(),
                'stack' => $th->getTraceAsString()
            ]);
            return redirect()->back()->withErrors('Failed to export releases.');
        }
    }

    public function exportTracks()
    {
        try {
            $results = Track::inReleaseCatalogue(auth()->user())->with(['profile', 'releases' => fn ($query) => $query->visibleTo(auth()->user())])->get()->map(function ($row) {
                $primaryArtists = $this->implodeArtists($row->primaryartists);
                $featuredArtists = $this->implodeArtists($row->featuredartists);

                $duration = $row->duration
                    ? gmdate('i:s', $row->duration)
                    : '-';

                $release = $row->releases->first();
                $releaseTitle = $release ? $release->title : '-';

                return [
                    $row->id,
                    $row->title ?? '-',
                    ($row->releases[0] ?? null) ? $row->releases[0]->title : '-',
                    ($row->releases[0] ?? null) ? $row->releases[0]->status : '-',
                    $primaryArtists,
                    $featuredArtists,
                    $duration,
                    $row->isrc ?? '-',
                    $releaseTitle,
                    $row->profile->full_name ?? '-',
                    $row->created_at ?? '-',
                    $row->genre_name ?? '-',
                    $row->subgenre_name ?? '-',
                    $row->explicit_name ?? '-',
                    $row->titlelang_name ?? '-',
                    $row->composer ?? '-',
                    $row->songwriter ?? '-',
                ];
            });

            $results = collect([
                [
                    'ID',
                    'Judul Track',
                    'Judul Rilis',
                    'Status Rilis',
                    'Artis Utama',
                    'Artis Featured',
                    'Durasi',
                    'ISRC',
                    'Judul Rilis',
                    'Diunggah Oleh',
                    'Tanggal Dibuat',
                    'Genre',
                    'Subgenre',
                    'Explicit',
                    'Bahasa Judul',
                    'Composer',
                    'Songwriter',
                ]
            ])->merge($results);

            $headers = [
                "Content-type" => "text/csv",
                "Content-Disposition" => "attachment; filename=tracks.csv",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0",
            ];

            $callback = function () use ($results) {
                $handle = fopen('php://output', 'w');

                foreach ($results->toArray() as $row) {
                    $convertedRow = array_map(function ($field) {
                        if (is_numeric($field) && str_contains((string) $field, '.')) {
                            return str_replace('.', ',', $field);
                        }
                        return $field;
                    }, $row);

                    fputcsv($handle, $convertedRow, ';', '"');
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $th) {
            Log::error('Failed to export tracks.', [
                'message' => $th->getMessage(),
                'stack' => $th->getTraceAsString()
            ]);
            return redirect()->back()->withErrors('Failed to export tracks.');
        }
    }

    private function implodeArtists($artists)
    {
        // Ensure array
        if (!is_array($artists)) {
            $artists = [];
        }

        $names = array_map(function ($artist) {
            // Case 1: ["Endah Ajeng", "Nara Koe"]
            if (is_string($artist)) {
                return $artist;
            }

            // Case 2: [{"name": "..."}]
            if (is_array($artist)) {
                return $artist['name'] ?? null;
            }

            if (is_object($artist)) {
                return $artist->name ?? null;
            }

            return null;
        }, $artists);

        // Remove null/empty values
        $names = array_filter($names);

        $primaryArtists = $names
            ? implode(', ', $names)
            : '-';

        return $primaryArtists;
    }

    /**
     * Action methods
     */
    public function store(Request $request)
    {
        $release = new Release();
        $context = [];

        // Pre-release date must sit within 1 to 7 days before the planned release date
        $request->validate([
            'tiktok_ytmusic_rerelease_date' => ['bail', 'nullable', 'date', new PreReleaseWindow()],
            'aggregator' => ['nullable', 'string', \Illuminate\Validation\Rule::in(app(\App\Services\SystemSettings::class)->aggregatorNames())],
            'releaseddate' => ['nullable', 'required_if:released,1', 'date'],
            'upc' => ['nullable', 'required_if:released,1', 'string', 'max:255'],
        ]);

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
            $profileid = $request->profileid;
            $status = $request->status;
            $aggregator = $request->aggregator;

            $result = $this->service->store($release, $profileid, $status, $data, $aggregator);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info("Release '{$result->title}' created.", $context);

            return redirect()->route('admin.releases.show', $result->id)->with('success', 'Release created.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['release_title'] = $data['title'] ?? 'Unknown';
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error("Failed to create release '" . ($data['title'] ?? 'Unknown') . "'.", $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $release = Release::findOrFail($id);
        $context = [];

        // Pre-release date must sit within 1 to 7 days before the planned release date
        $request->validate([
            'tiktok_ytmusic_rerelease_date' => ['bail', 'nullable', 'date', new PreReleaseWindow($release->releaseon)],
            'aggregator' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_merge(app(\App\Services\SystemSettings::class)->aggregatorNames(), [$release->aggregator]))],
            'releaseddate' => ['nullable', 'required_if:released,1', 'date'],
            'upc' => ['nullable', 'required_if:released,1', 'string', 'max:255'],
        ]);

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
            $profileid = $request->profileid;
            $status = $request->status;
            $aggregator = $request->aggregator;

            $result = $this->service->store($release, $profileid, $status, $data, $aggregator);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info("Release '{$result->title}' updated.", $context);

            return redirect()->back()->with('success', 'Release updated.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['release_title'] = $release->title;
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error("Failed to update release '{$release->title}'.", $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function accept($id)
    {
        $release = Release::findOrFail($id);
        $context = [];

        try {
            $result = $this->service->adminAcceptRevision($release);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info("Release '{$release->title}' revision accepted.", $context);

            return redirect()->back()->with('success', 'Release revision accepted.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['release_title'] = $release->title;
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error("Failed to accept revision for '{$release->title}'.", $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function review(Request $request, $id)
    {
        $release = Release::findOrFail($id);
        $aggregator = $request->aggregator;
        $context = [];

        try {
            $result = $this->service->adminReview($release, $aggregator);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info("Release '{$release->title}' in review.", $context);

            return redirect()->back()->with('success', 'Release in review.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['release_title'] = $release->title;
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error("Failed to change release '{$release->title}' status to in review.", $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function uploading($id)
    {
        $release = Release::findOrFail($id);
        $context = [];

        try {
            $result = $this->service->adminUpload($release);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info("Release '{$release->title}' uploading.", $context);

            return redirect()->back()->with('success', 'Release uploading.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['release_title'] = $release->title;
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error("Failed to change release '{$release->title}' status to uploading.", $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function verify(ReleaseVerifyRequest $request, $id)
    {
        $release = Release::findOrFail($id);
        $context = [];

        try {
            $data = $request->only([
                'upc',
                'isrc',
            ]);

            $result = $this->service->adminApprove($release, $data);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info("Release '{$release->title}' approved.", $context);

            return redirect()->back()->with('success', 'Release approved.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['release_title'] = $release->title;
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error("Failed to approve release '{$release->title}'.", $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $release = Release::findOrFail($id);
        $context = [];

        try {
            $reason = $request->reason;
            $result = $this->service->adminReject($release, $reason);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info("Release '{$release->title}' rejected.", $context);

            return redirect()->back()->with('success', 'Release rejected.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['release_title'] = $release->title;
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error("Failed to reject release '{$release->title}'.", $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    /**
     * AJAX
     */
    public function get_subgenre(Request $request, $genreid)
    {
        $genre = Genre::findOrFail($genreid);
        $currentSubgenreId = $request->query('current');

        try {
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

    public function get_data(Request $request, $status = null)
    {
        try {
            // Get records with certain status
            $query = Release::visibleTo(auth()->user());
            if (!auth()->user()->can('view-releases-all')) {
                // Limited viewers (e.g. Partner) only ever see published releases.
                $query = $query->where('releases.status', 'released');
            } elseif ($status != 'all') {
                $query = $query->where('releases.status', $status);
            } else {
                $query = $query->where('releases.status', '!=', 'draft');
            }
            $recordsTotal = (clone $query)->count();

            // Searching
            $query = $query
                ->select(
                    'releases.id as id',
                    'releases.title as title',
                    'releases.type as type',
                    'releases.tracknum as tracknum',
                    'releases.releaseon as releaseon',
                    'releases.aggregator as aggregator',
                    'releases.created_at as created_at',
                    'releases.verified_at as verified_at',
                    'releases.contract_expired_at as contract_expired_at',
                    'releases.status as status',
                    DB::raw("CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname) AS fullname")
                )
                ->leftJoin('user_profiles', 'user_profiles.id', '=', 'releases.profileid');
            if (\App\Database\RelationalFields::available('releases')) $query->with('relationalFieldRows');
            else $query->addSelect('releases.primaryartists');

            // Searching
            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $search = strtolower($search);
                    $q->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"]);
                    if (\App\Database\RelationalFields::available('releases')) {
                        $q->orWhereHas('relationalFieldRows', fn ($artists) => $artists->where('field','primaryartists')->where('value_type','string')->whereRaw('LOWER(scalar_value) LIKE ?', ["%{$search}%"]));
                    } else {
                        $q->orWhereRaw('LOWER(primaryartists) LIKE ?', ["%{$search}%"]);
                    }
                    $q->orWhereRaw("LOWER(CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname)) LIKE ?", ["%{$search}%"]);
                });
            }

            // Sorting
            $columns = ['title', 'type', 'status', 'releaseon', 'fullname', 'aggregator', 'created_at'];
            $orderColumn = $columns[$request->input('order.0.column')];
            $orderDir = $request->input('order.0.dir');
            $query->orderBy($orderColumn, $orderDir);

            // Pagination
            $recordsFiltered = (clone $query)->count();

            $data = $query
                ->skip($request->input('start'))
                ->take($request->input('length'))
                ->get()
                ->load('thumbnail');

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Failed to get release data.', [
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
