<?php

namespace App\Http\Controllers\Admin\Aggregator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Http\Requests\Admin\UploadReportRequest;

use App\Jobs\Reporting\ProcessAggregate;
use App\Models\User\Profile;
use App\Models\Release;
use App\Models\Track;
use App\Models\Reporting\ReportImport;
use App\Models\Reporting\ReportRow;
use App\Models\Reporting\ReportAggregate;

use App\Services\ReportService;

class ReportingController extends Controller
{
    public function index()
    {
        $userprofiles   = Profile::all()->sortBy('full_name');
        $years          = ReportRow::where('status', 'finalized')->distinct()->orderByDesc('year')->pluck(DB::raw('YEAR(reporting_month) as year'));
        $platforms      = ReportRow::where('status', 'finalized')->distinct()->orderBy('platform')->pluck('platform');
        $regions        = ReportRow::where('status', 'finalized')->distinct()->orderBy('region')->pluck('region');
        $artists        = ReportRow::where('status', 'finalized')->distinct()->orderBy('artist_name')->pluck('artist_name');

        $batches = ReportImport::all();
        $reportingSummary = [
            'total_batches' => $batches->count(),
            'total_rows' => $batches->sum('total_rows'),
            'completed' => $batches->where('status', 'completed')->count(),
            'needs_attention' => $batches->whereNotIn('status', ['completed'])->count(),
        ];

        return view('admin.aggregator.reporting.index', compact(
            'userprofiles', 'artists', 'years', 'platforms', 'regions', 'batches', 'reportingSummary'
        ));
    }

    public function upload(UploadReportRequest $request)
    {
        try {
            $file   = $request->file('report');
            $sheet  = $request->input('sheet') ?? null;
            $source = $request->input('source');
    
            ReportService::import($file, $source, $sheet);

            return redirect()->back()->with('success', 'Report imported successfully');
        }
        catch (\Throwable $th) {

            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function show($id)
    {
        // Check if report exists
        $report = ReportImport::findOrFail($id);

        // Get grouped report
        $reps = ReportRow::selectRaw('MIN(id) as id, upc, MIN(profileid) as profileid, MIN(trackid) as trackid, isrc, artist_name, track_title, release_title, SUM(rupiah_exact) as rupiah_exact, MIN(status) as status')
            ->where('report_import_id', $id)
            ->groupBy('upc', 'isrc', 'track_title', 'release_title','artist_name')
            ->orderBy('artist_name', 'asc')
            ->orderBy('release_title', 'asc')
            ->get();

        // Get summary
        $totals = $report->rows->reduce(function ($carry, $row) {
            $carry['total_revenue']         += $row->rupiah_exact;
            $carry['total_views']           += $row->quantity;
            return $carry;
        }, ['total_revenue' => 0, 'total_views' => 0]);
        extract($totals);

        return view('admin.aggregator.reporting.show', compact('report','reps','total_revenue','total_views'));
    }

    public function update($id, Request $request)
    {
        try {
            $report = ReportImport::findOrFail($id);
            $final  = ReportService::finalize($report);

            if(!$final) {
                return redirect()->back()->with('success', 'Report finalized. Please wait for aggregate to be calculated');
            }

            return redirect()->route('admin.releases.reporting.index')->with('success', 'Report finalized and will be calculated');

        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function rate($id, Request $request)
    {
        $request->validate([
            'idr_rate' => 'nullable',
        ]);

        try {
            $report = ReportImport::findOrFail($id);
            $rate   = $request->input('idr_rate');

            $report->idr_rate = $rate;
            $report->save();

            return redirect()->back()->with('success', 'Report rate set');
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            // Find report
            $report = ReportImport::findOrFail($id);

            DB::transaction(function () use ($report) {

                // Delete file
                $filename   = $report->original_filename;
                $path       = 'app' . DIRECTORY_SEPARATOR . 'reports';
                $filepath   = storage_path($path . DIRECTORY_SEPARATOR . $filename);
                FileFacade::delete($filepath);

                // Delete report
                $report->delete();

                // Run aggregate
                ProcessAggregate::dispatch();
            });
            
            return redirect()->back()->with('success', 'Report deleted. Aggregate data will be recalculated.');

        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function recheck()
    {
        try {
            $count = ReportService::recheck();
            return redirect()->back()->with('success', 'Report recheck success: ' . $count . ' rows updated');
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function get_data(Request $request)
    {
        $request->validate([
            'profileid'    => ['nullable', 'integer', 'exists:user_profiles,id'],
            'artist'    => ['nullable', 'string', 'max:255'],
            'upc'       => ['nullable', 'string', 'max:50'],
            'isrc'      => ['nullable', 'string', 'max:50'],
            'platform'  => ['nullable', 'string', 'max:100'],
            'region'    => ['nullable', 'string', 'max:100'],
            'period'    => ['nullable', 'string', 'max:255'],
            'quarter'   => ['nullable', 'integer', 'in:1,2,3,4'],
        ]);
        
        $query = ReportRow::query()
            ->select('*', DB::raw('Month(reporting_month) as month'))
            ->leftJoin('report_imports', 'report_imports.id', '=', 'report_rows.report_import_id')
            ->whereIn('report_imports.status', ['pending_rate','completed']);

        if($request->profileid) {
            $query->where('profileid', $request->input('profileid'));
        }

        if($request->artist) {
            $query->where('artist_name', 'like', "%{$request->artist}%");
        }
        
        $query->whereYear('reporting_month', (int) $request->input('period'));

        if($request->quarter) {
            $query->whereMonth('reporting_month', '>=', $request->input('quarter') * 3 - 2);
            $query->whereMonth('reporting_month', '<=', $request->input('quarter') * 3);
        }

        if($request->upc) {
            $query->where('upc', $request->input('upc'));
        }
        if($request->isrc) {
            $query->where('isrc', $request->input('isrc'));
        }
        if($request->platform) {
            $query->where('platform', $request->input('platform'));
        }
        if($request->region) {
            $query->where('region', $request->input('region'));
        }

        $results    = $query->get();

        return response()->json([
            'sum_total'         => number_format($results->sum('rupiah_exact'), 0, ',', '.'),
            'sum_client'        => number_format($results->sum('client_rupiah_exact'), 0, ',', '.'),
            'sum_aggregator'    => number_format($results->sum('aggregator_rupiah_exact'), 0, ',', '.'),
            'results'           => $results->map(function ($row) {
                return [
                    'id'                => $row->id,
                    'quarter'           => ceil($row->month / 3),
                    'month'             => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$row->month - 1],
                    'track_title'       => $row->track_title,
                    'artist_name'       => $row->artist_name,
                    'upc'               => $row->upc,
                    'isrc'              => $row->isrc,
                    'period'            => $row->reporting_month,
                    'platform'          => $row->platform,
                    'region'            => $row->region,
                    'views'             => $row->quantity,
                    'rupiah'            => $row->rupiah,
                    'client_rupiah'     => $row->client_rupiah,
                    'aggregator_rupiah' => $row->aggregator_rupiah
                ];
            }),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        try {
            $request->validate([
                'profileid'    => ['nullable', 'integer', 'exists:user_profiles,id'],
                'artist'    => ['nullable', 'string', 'max:255'],
                'upc'       => ['nullable', 'string', 'max:50'],
                'isrc'      => ['nullable', 'string', 'max:50'],
                'platform'  => ['nullable', 'string', 'max:100'],
                'region'    => ['nullable', 'string', 'max:100'],
                'period'    => ['nullable', 'string', 'max:255'],
                'quarter'   => ['nullable', 'integer', 'in:1,2,3,4'],
            ]);

            $query = ReportRow::query()
                ->select('report_rows.*', DB::raw('Month(reporting_month) as month'), 'user_profiles.firstname', 'user_profiles.lastname')
                ->leftJoin('report_imports', 'report_imports.id', '=', 'report_rows.report_import_id')
                ->leftJoin('user_profiles', 'user_profiles.id', '=', 'report_rows.profileid')
                ->whereIn('report_imports.status', ['pending_rate','completed']);

            if($request->profileid) {
                $query->where('profileid', $request->input('profileid'));
            }

            if($request->artist) {
                $query->where('artist_name', 'like', "%{$request->artist}%");
            }

            $query->whereYear('reporting_month', (int) $request->input('period'));

            if($request->quarter) {
                $query->whereMonth('reporting_month', '>=', $request->input('quarter') * 3 - 2);
                $query->whereMonth('reporting_month', '<=', $request->input('quarter') * 3);
            }

            if($request->upc) {
                $query->where('upc', $request->input('upc'));
            }
            if($request->isrc) {
                $query->where('isrc', $request->input('isrc'));
            }
            if($request->platform) {
                $query->where('platform', $request->input('platform'));
            }
            if($request->region) {
                $query->where('region', $request->input('region'));
            }

            $results = $query->get()->map(function ($row) {
                $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                $userName = ($row->firstname ?? '') . ' ' . ($row->lastname ?? '');

                return [
                    $row->id,
                    trim($userName) ?: '-',
                    $row->track_title ?? '-',
                    $row->artist_name ?? '-',
                    $row->release_title ?? '-',
                    $row->upc ?? '-',
                    $row->isrc ?? '-',
                    $row->reporting_month ?? '-',
                    ceil($row->month / 3),
                    $months[$row->month - 1] ?? '-',
                    $row->platform ?? '-',
                    $row->region ?? '-',
                    $row->quantity ?? 0,
                    $row->rupiah_exact ?? 0,
                    $row->client_rupiah_exact ?? 0,
                    $row->aggregator_rupiah_exact ?? 0,
                ];
            });

            $results = collect([[
                'ID',
                'User',
                'Judul Track',
                'Nama Artis',
                'Judul Rilis',
                'UPC',
                'ISRC',
                'Periode',
                'Quarter',
                'Bulan',
                'Platform',
                'Region',
                'Jumlah Views',
                'Total Rupiah',
                'Rupiah Client',
                'Rupiah Aggregator',
            ]])->merge($results);

            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=reporting.csv",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0",
            ];

            $callback = function() use ($results) {
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
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors('Failed to export reporting data.');
        }
    }

    public function get_release(Request $request)
    {
        $search = $request->input('q');

        $releases = Release::query()
            ->when($search, function ($query, $search) {
                return $query->where('title', 'like', "%{$search}%");
            })
            ->select('id', 'title')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $releases->map(function ($release) {
                return [
                    'id' => $release->id,
                    'text' => $release->title,
                ];
            }),
        ]);
    }

    public function get_track(Request $request)
    {
        $search = $request->input('q');

        $tracks = Track::query()
            ->when($search, function ($query, $search) {
                return $query->where('title', 'like', "%{$search}%");
            })
            ->select('id', 'title')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $tracks->map(function ($track) {
                return [
                    'id' => $track->id,
                    'text' => $track->title,
                ];
            }),
        ]);
    }
    public function get_profile(Request $request)
    {
        $search = $request->input('q');

        $users = Profile::query()
            ->when($search, function ($query, $search) {
                return $query->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%");
            })
            ->select('id', 'firstname', 'lastname')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'text' => $user->firstname . ' ' . $user->lastname,
                ];
            }),
        ]);
    }
}
