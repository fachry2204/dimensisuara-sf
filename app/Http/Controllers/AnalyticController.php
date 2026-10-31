<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

use App\Models\Track;
use App\Models\Release;


class AnalyticController extends Controller
{
    public function index(Request $request)
    {
        $profileId = current_user()->profile->id;

        // Use the artist's actual report coverage, including the first month.
        $coverage = DB::table('report_aggregate_platforms')
            ->where('profileid', $profileId)
            ->selectRaw('MIN(sales_month) as first_month, MAX(sales_month) as last_month')
            ->first();
        $latestAvailableDate = $coverage->last_month
            ? Carbon::parse($coverage->last_month)->endOfMonth()->format('Y-m-d')
            : null;
        $firstAvailableDate = $coverage->first_month
            ? Carbon::parse($coverage->first_month)->startOfMonth()->format('Y-m-d')
            : null;
        $latestMonth = Carbon::parse($latestAvailableDate ?: Carbon::now()->format('Y-m-d'));

        // Match My Earnings: show all available reports on the initial visit.
        $dateRange = $request->input('date_range', 'all_time');
        if (!in_array($dateRange, ['last_completed_quarter', 'last_6_months', 'last_12_months', 'this_year', 'last_year', 'all_time', 'custom'], true)) {
            $dateRange = 'all_time';
        }
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        // Calculate date ranges based on filter
        switch ($dateRange) {
            case 'last_completed_quarter':
                // Last completed quarter (since data is 1 quarter behind)
                $startDate = Carbon::now()->subMonths(3)->startOfQuarter()->format('Y-m-d');
                $endDate = Carbon::now()->subMonths(3)->endOfQuarter()->format('Y-m-d');
                $comparisonStart = Carbon::now()->subMonths(6)->startOfQuarter()->format('Y-m-d');
                $comparisonEnd = Carbon::now()->subMonths(6)->endOfQuarter()->format('Y-m-d');
                break;
            case 'last_6_months':
                $startDate = $latestMonth->copy()->subMonthsNoOverflow(5)->startOfMonth()->format('Y-m-d');
                $endDate = $latestMonth->copy()->endOfMonth()->format('Y-m-d');
                $comparisonStart = Carbon::parse($startDate)->subMonthsNoOverflow(6)->format('Y-m-d');
                $comparisonEnd = Carbon::parse($startDate)->subDay()->format('Y-m-d');
                break;
            case 'last_12_months':
                $startDate = $latestMonth->copy()->subMonthsNoOverflow(11)->startOfMonth()->format('Y-m-d');
                $endDate = $latestMonth->copy()->endOfMonth()->format('Y-m-d');
                $comparisonStart = Carbon::parse($startDate)->subMonthsNoOverflow(12)->format('Y-m-d');
                $comparisonEnd = Carbon::parse($startDate)->subDay()->format('Y-m-d');
                break;
            case 'this_year':
                // Current year up to last completed quarter
                $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
                $endDate = Carbon::now()->endOfYear()->format('Y-m-d');
                $comparisonStart = Carbon::now()->subYear()->startOfYear()->format('Y-m-d');
                $comparisonEnd = Carbon::now()->subYear()->endOfYear()->format('Y-m-d');
                break;
            case 'last_year':
                $startDate = Carbon::now()->subYear()->startOfYear()->format('Y-m-d');
                $endDate = Carbon::now()->subYear()->endOfYear()->format('Y-m-d');
                $comparisonStart = Carbon::now()->subYears(2)->startOfYear()->format('Y-m-d');
                $comparisonEnd = Carbon::now()->subYears(2)->endOfYear()->format('Y-m-d');
                break;
            case 'all_time':
                $startDate = $firstAvailableDate ?: $latestMonth->copy()->startOfMonth()->format('Y-m-d');
                $endDate = $latestMonth->copy()->endOfMonth()->format('Y-m-d');
                $comparisonStart = null;
                $comparisonEnd = null;
                break;
            case 'custom':
                $startDate = $customStart ?: Carbon::now()->subMonths(12)->format('Y-m-d');
                $endDate = $customEnd ?: $latestMonth->copy()->endOfMonth()->format('Y-m-d');
                $comparisonStart = null;
                $comparisonEnd = null;
                break;
            default:
                // Default to last completed quarter
                $startDate = Carbon::now()->subMonths(3)->startOfQuarter()->format('Y-m-d');
                $endDate = Carbon::now()->subMonths(3)->endOfQuarter()->format('Y-m-d');
                $comparisonStart = Carbon::now()->subMonths(6)->startOfQuarter()->format('Y-m-d');
                $comparisonEnd = Carbon::now()->subMonths(6)->endOfQuarter()->format('Y-m-d');
        }


        $periodPlatformRows = DB::table('report_aggregate_platforms')
            ->where('profileid', $profileId)
            ->whereBetween('sales_month', [$startDate, $endDate]);

        $monthlyRows = (clone $periodPlatformRows)
            ->selectRaw('sales_month, SUM(quantity) as streams, SUM(COALESCE(client_rupiah_exact, 0)) as revenue')
            ->groupBy('sales_month')->orderBy('sales_month')->get();
        $monthsFormatted = $monthlyRows->map(fn ($row) => Carbon::parse($row->sales_month)->locale('id')->isoFormat('MMM YYYY'))->values();
        $streamSeries = $monthlyRows->pluck('streams')->map(fn ($value) => (int) $value)->values();
        $revenueSeries = $monthlyRows->pluck('revenue')->map(fn ($value) => (float) $value)->values();

        $currentPeriod = (int) $monthlyRows->sum('streams');
        $periodRevenue = (float) $monthlyRows->sum('revenue');

        $platformRows = (clone $periodPlatformRows)
            ->selectRaw('platform, SUM(quantity) as streams, SUM(COALESCE(client_rupiah_exact, 0)) as revenue')
            ->groupBy('platform')->orderByDesc('streams')->limit(5)->get();
        $platformCount = (clone $periodPlatformRows)->distinct('platform')->count('platform');

        $countryRows = DB::table('report_aggregate_countries')
            ->where('profileid', $profileId)->whereBetween('sales_month', [$startDate, $endDate])
            ->selectRaw('country, SUM(quantity) as streams')
            ->groupBy('country')->orderByDesc('streams')->limit(5)->get();

        $topTrack = Track::query()
            ->where('tracks.profileid', $profileId)
            ->leftJoin('report_aggregate_tracks as reports', function ($join) use ($profileId, $startDate, $endDate) {
                $join->on('reports.trackid', '=', 'tracks.id')
                    ->where('reports.profileid', $profileId)
                    ->whereBetween('reports.sales_month', [$startDate, $endDate]);
            })
            ->select('tracks.id', 'tracks.title', DB::raw('SUM(COALESCE(reports.quantity, 0)) as streams'))
            ->groupBy('tracks.id', 'tracks.title')
            ->orderByDesc('streams')
            ->first();

        $releases = Release::query()
            ->where('releases.profileid', $profileId)
            ->leftJoin('release_track', 'release_track.release_id', '=', 'releases.id')
            ->leftJoin('report_aggregate_tracks as reports', function ($join) use ($profileId, $startDate, $endDate) {
                $join->on('reports.trackid', '=', 'release_track.track_id')
                    ->where('reports.profileid', $profileId)
                    ->whereBetween('reports.sales_month', [$startDate, $endDate]);
            })
            ->select(
                'releases.id',
                'releases.title',
                'releases.primaryartists',
                'releases.type',
                'releases.releaseon',
                DB::raw('SUM(COALESCE(reports.quantity, 0)) as streams'),
                DB::raw('SUM(COALESCE(reports.client_rupiah_exact, 0)) as revenue')
            )
            ->groupBy('releases.id', 'releases.title', 'releases.primaryartists', 'releases.type', 'releases.releaseon')
            ->orderByDesc('streams')
            ->orderByDesc('releases.id')
            ->paginate(20)
            ->withQueryString();

        $releaseCount = DB::table('report_aggregate_releases')
            ->where('profileid', $profileId)->whereBetween('sales_month', [$startDate, $endDate])
            ->distinct('releaseid')->count('releaseid');

        // Comparison period data for trend calculation
        $previousPeriod = 0;
        $trendPercentage = 0;
        $trendDirection = 'neutral';

        if ($comparisonStart && $comparisonEnd) {
            $previousPeriod = DB::table('report_aggregate_platforms')
                ->where('profileid', $profileId)
                ->where('sales_month', '>=', $comparisonStart)
                ->where('sales_month', '<=', $comparisonEnd)
                ->sum('quantity');

            if ($previousPeriod > 0) {
                $trendPercentage = (($currentPeriod - $previousPeriod) / $previousPeriod) * 100;
                $trendDirection = $trendPercentage > 0 ? 'up' : ($trendPercentage < 0 ? 'down' : 'neutral');
            } elseif ($currentPeriod > 0) {
                $trendPercentage = 100;
                $trendDirection = 'up';
            }
        }

        $trackShare = $currentPeriod > 0 && $topTrack ? round(($topTrack->streams / $currentPeriod) * 100, 1) : null;

        return view('customer.analytic.index', compact(
            'monthsFormatted', 'streamSeries', 'revenueSeries', 'currentPeriod', 'periodRevenue',
            'platformRows', 'platformCount', 'countryRows', 'releases', 'releaseCount', 'trendPercentage', 'trendDirection',
            'dateRange', 'topTrack', 'trackShare', 'startDate', 'endDate', 'latestAvailableDate'
        ));
    }

    public function releaseStatistics(Request $request, $id)
    {
        $profileId = current_user()->profile->id;
        $release = Release::with(['profile', 'thumbnail', 'tracks'])->where('profileid', $profileId)->findOrFail($id);
        $trackIds = $release->tracks->pluck('id');
        $baseReportRows = DB::table('report_rows')->whereIn('trackid', $trackIds)->where('profileid', $profileId)->where('status', 'finalized');
        $availablePlatforms = (clone $baseReportRows)->whereNotNull('platform')->distinct()->orderBy('platform')->pluck('platform');
        $selectedPlatform = $request->string('platform')->trim()->toString();
        $selectedMonth = $request->string('month')->trim()->toString();
        $selectedQuarter = $request->integer('quarter');
        $selectedPeriodGroup = $request->string('period_group')->trim()->toString();
        $selectedPeriodGroup = in_array($selectedPeriodGroup, ['monthly', 'quarterly', 'semiannual'], true)
            ? $selectedPeriodGroup
            : 'monthly';
        $reportRows = (clone $baseReportRows)
            ->when($selectedPlatform, fn ($query) => $query->where('platform', $selectedPlatform))
            ->when(preg_match('/^\d{4}-\d{2}$/', $selectedMonth), function ($query) use ($selectedMonth) {
                [$year, $month] = explode('-', $selectedMonth);
                $query->whereYear('sales_month', $year)->whereMonth('sales_month', $month);
            })
            ->when(in_array($selectedQuarter, [1, 2, 3, 4], true), fn ($query) => $query->whereRaw('QUARTER(sales_month) = ?', [$selectedQuarter]));

        $totalStreams = (clone $reportRows)->sum('quantity');
        $totalEarnings = (clone $reportRows)->sum('client_rupiah_exact');
        $latestReportDate = (clone $reportRows)->max('sales_month');
        $platformPerformance = (clone $reportRows)
            ->select('platform', DB::raw('SUM(quantity) as streams'), DB::raw('SUM(client_rupiah_exact) as earnings'), DB::raw('MAX(sales_month) as latest_report_date'))
            ->groupBy('platform')->orderByDesc('streams')->get();
        $recentPlatformRows = (clone $reportRows)
            ->select('sales_month', 'platform', DB::raw('SUM(quantity) as streams'))
            ->groupBy('sales_month', 'platform')->orderBy('sales_month')->get()
            ->groupBy('sales_month')
            ->sortKeys()
            ->slice(-12);
        $platformsByMonth = $selectedPeriodGroup === 'monthly'
            ? $recentPlatformRows
            : $recentPlatformRows->flatten(1)
                ->groupBy(function ($row) use ($selectedPeriodGroup) {
                    $date = Carbon::parse($row->sales_month);

                    return $selectedPeriodGroup === 'quarterly'
                        ? $date->startOfQuarter()->format('Y-m-d')
                        : ($date->month <= 6 ? $date->startOfYear()->format('Y-m-d') : $date->month(7)->startOfMonth()->format('Y-m-d'));
                })
                ->map(function ($rows, $periodStart) {
                    return $rows->groupBy('platform')->map(function ($platformRows, $platform) use ($periodStart) {
                        return (object) [
                            'sales_month' => $periodStart,
                            'platform' => $platform,
                            'streams' => $platformRows->sum('streams'),
                        ];
                    })->values();
                })
                ->sortKeys();
        $platformPeriodLabels = $platformsByMonth->keys()->map(function ($periodStart) use ($selectedPeriodGroup) {
            $date = Carbon::parse($periodStart);

            return match ($selectedPeriodGroup) {
                'quarterly' => 'Q'.$date->quarter.' '.$date->year,
                'semiannual' => ($date->month <= 6 ? 'Jan–Jun ' : 'Jul–Des ').$date->year,
                default => $date->locale('id')->isoFormat('MMM YY'),
            };
        })->values();
        $topPlatformsByMonth = $platformsByMonth->map(fn ($platforms) => $platforms->sortByDesc('streams')->take(3)->values());
        $topPlatformByMonth = $topPlatformsByMonth->map(fn ($platforms) => $platforms->first())->filter()->values();

        return view('admin.release-statistics', compact('release', 'totalStreams', 'totalEarnings', 'latestReportDate', 'platformPerformance', 'topPlatformByMonth', 'topPlatformsByMonth', 'platformPeriodLabels', 'availablePlatforms', 'selectedPlatform', 'selectedMonth', 'selectedQuarter', 'selectedPeriodGroup') + [
            'backRoute' => route('analytics.index'),
            'backLabel' => 'Analytics & Penghasilan',
            'releaseDetailsRoute' => route('releases.show', $release->id),
        ]);
    }

    public function get_tracks(Request $request)
    {
        try {
            $query          = Track::where('tracks.profileid', current_user()->profile->id);
            $recordsTotal   = $query->get()->count();
            $query = $query->select(
                    'tracks.id as id',
                    'tracks.title as title',
                    'tracks.primaryartists as primaryartists',
                    DB::raw('SUM(viewstbl.quantity) as views')
                )
                ->leftJoin('report_aggregate_tracks as viewstbl', 'viewstbl.trackid', '=', 'tracks.id')
                ->groupBy('tracks.id', 'tracks.title', 'tracks.primaryartists');

            // Searching
            if ($search = $request->input('search.value')) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            }

            // Sorting
            $columns        = ['title', 'views'];
            $orderColumn    = $columns[$request->input('order.0.column')];
            $orderDir       = $request->input('order.0.dir');
            $query->orderBy($orderColumn, $orderDir);

            // Pagination
            $recordsFiltered    = $query->get()->count();

            $data = $query
                ->skip($request->input('start'))
                ->take($request->input('length'))
                ->get();
            
            return response()->json([
                "draw"              => intval($request->input('draw')),
                "recordsTotal"      => $recordsTotal,
                "recordsFiltered"   => $recordsFiltered,
                "data"              => $data,
            ], 200);
        }
        catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false ,
                'errors' => $e->getMessage()
            ], 404);
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);

            return response()->json([
                'success' => false ,
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function get_artists(Request $request)
    {
        try {
            $query = DB::table('report_aggregate_artists')->where('profileid', current_user()->profile->id)
                ->select(
                    'artist_name',
                    DB::raw('SUM(quantity) as views')
                )
                ->groupBy('artist_name');
            $recordsTotal = $query->get()->count();

            // Searching
            if ($search = $request->input('search.value')) {
                $query->where(function($q) use ($search) {
                    $q->where('artist_name', 'like', "%{$search}%");
                });
            }

            // Sorting
            $columns        = ['artist_name', 'views'];
            $orderColumn    = $columns[$request->input('order.0.column')];
            $orderDir       = $request->input('order.0.dir');
            $query->orderBy($orderColumn, $orderDir);

            // Pagination
            $recordsFiltered    = $query->get()->count();

            $data = $query
                ->skip($request->input('start'))
                ->take($request->input('length'))
                ->get();
            
            return response()->json([
                "draw"              => intval($request->input('draw')),
                "recordsTotal"      => $recordsTotal,
                "recordsFiltered"   => $recordsFiltered,
                "data"              => $data,
            ], 200);
        }
        catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false ,
                'errors' => $e->getMessage()
            ], 404);
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);

            return response()->json([
                'success' => false ,
                'errors' => $th->getMessage()
            ], 500);
        }
        
    }
}
