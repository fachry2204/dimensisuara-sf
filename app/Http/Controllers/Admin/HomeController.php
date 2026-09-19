<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Release;
use App\Models\Track;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $catalogPeriod = request()->string('catalog_period')->trim()->toString();
        $catalogPeriod = in_array($catalogPeriod, ['monthly', 'quarterly', 'semiannual', 'yearly'], true)
            ? $catalogPeriod
            : 'monthly';
        // Limited viewers (e.g. Partner) never see pending/review releases.
        $canViewAllReleases = auth()->user()->can('view-releases-all');
        $pendingReleases = $canViewAllReleases
            ? Release::with(['profile', 'thumbnail'])->whereIn('status', ['pending', 'review'])->latest('updated_at')->take(6)->get()
            : collect();
        $countAlbums = Release::where('status', 'released')->where('type', 'album')->count();
        $countSingles = Release::where('status', 'released')->where('type', 'single')->count();
        $pendingCount = $canViewAllReleases ? Release::where('status', 'pending')->count() : 0;
        $reviewCount = $canViewAllReleases ? Release::where('status', 'review')->count() : 0;
        $uploadingCount = $canViewAllReleases ? Release::where('status', 'uploading')->count() : 0;
        $totalTracks = Track::count();
        $publishedTracks = Track::whereHas('releases', fn ($query) => $query->where('status', 'released'))->count();
        $publishedReleases = Release::where('status', 'released')->count();
        $publishedAlbums = Release::where('status', 'released')->where('type', 'album')->count();

        $catalogPeriodMeta = match ($catalogPeriod) {
            'quarterly' => ['count' => 8, 'months' => 3, 'label' => 'Per 3 bulan'],
            'semiannual' => ['count' => 6, 'months' => 6, 'label' => 'Per 6 bulan'],
            'yearly' => ['count' => 5, 'months' => 12, 'label' => 'Per tahun'],
            default => ['count' => 12, 'months' => 1, 'label' => 'Per bulan'],
        };
        $catalogBuckets = collect(range($catalogPeriodMeta['count'] - 1, 0))->mapWithKeys(function ($offset) use ($catalogPeriod, $catalogPeriodMeta) {
            $date = now()->startOfMonth()->subMonths($offset * $catalogPeriodMeta['months']);

            if ($catalogPeriod === 'quarterly') {
                $date = $date->startOfQuarter();
                $key = $date->format('Y').'-Q'.$date->quarter;
                $label = 'Q'.$date->quarter.' '.$date->year;
            } elseif ($catalogPeriod === 'semiannual') {
                $date = $date->month <= 6 ? $date->startOfYear() : $date->month(7)->startOfMonth();
                $half = $date->month <= 6 ? 1 : 2;
                $key = $date->format('Y').'-H'.$half;
                $label = ($half === 1 ? 'Jan–Jun ' : 'Jul–Des ').$date->year;
            } elseif ($catalogPeriod === 'yearly') {
                $date = $date->startOfYear();
                $key = $date->format('Y');
                $label = (string) $date->year;
            } else {
                $key = $date->format('Y-m');
                $label = $date->locale('id')->isoFormat('MMM YY');
            }

            return [$key => ['label' => $label, 'tracks' => 0, 'releases' => 0, 'albums' => 0]];
        });
        Release::where('status', 'released')->withCount('tracks')->get(['id', 'type', 'updated_at'])->each(function ($release) use ($catalogPeriod, $catalogBuckets) {
            $date = $release->updated_at->copy();
            $key = match ($catalogPeriod) {
                'quarterly' => $date->format('Y').'-Q'.$date->quarter,
                'semiannual' => $date->format('Y').'-H'.($date->month <= 6 ? 1 : 2),
                'yearly' => $date->format('Y'),
                default => $date->format('Y-m'),
            };

            if (!$catalogBuckets->has($key)) {
                return;
            }

            $bucket = $catalogBuckets->get($key);
            $bucket['tracks'] += $release->tracks_count;
            $bucket['releases']++;
            $bucket['albums'] += $release->type === 'album' ? 1 : 0;
            $catalogBuckets->put($key, $bucket);
        });
        $catalogLabels = $catalogBuckets->pluck('label')->values();
        $catalogTracks = $catalogBuckets->pluck('tracks')->values();
        $catalogReleases = $catalogBuckets->pluck('releases')->values();
        $catalogAlbums = $catalogBuckets->pluck('albums')->values();
        $catalogTotals = [
            'tracks' => $catalogTracks->sum(),
            'releases' => $catalogReleases->sum(),
            'albums' => $catalogAlbums->sum(),
        ];
        $recentActivity = $canViewAllReleases
            ? Release::with(['profile', 'thumbnail'])->latest('updated_at')->take(6)->get()
            : collect();
        $statisticReleases = Release::with(['profile', 'thumbnail'])
            ->where('status', 'released')
            ->latest('updated_at')
            ->take(10)
            ->get();

        $trendDates = collect(range(6, 0))->map(fn ($offset) => now()->subDays($offset));
        $trendLabels = $trendDates->map(fn ($date) => $date->translatedFormat('d M'))->values();
        $trendSubmitted = $trendDates->map(fn ($date) => Release::whereDate('created_at', $date)->count())->values();
        $trendPublished = $trendDates->map(fn ($date) => Release::where('status', 'released')->whereDate('updated_at', $date)->count())->values();

        return view('admin.index', compact(
            'pendingReleases', 'countAlbums', 'countSingles', 'pendingCount', 'reviewCount', 'uploadingCount', 'totalTracks',
            'publishedTracks', 'publishedReleases', 'publishedAlbums', 'catalogPeriod', 'catalogPeriodMeta', 'catalogLabels', 'catalogTracks', 'catalogReleases', 'catalogAlbums', 'catalogTotals', 'recentActivity', 'statisticReleases', 'trendLabels', 'trendSubmitted', 'trendPublished', 'canViewAllReleases'
        ));
    }

    public function releaseStatistics(Request $request, $id)
    {
        $release = Release::with(['profile', 'thumbnail', 'tracks'])->findOrFail($id);
        $trackIds = $release->tracks->pluck('id');

        $baseReportRows = DB::table('report_rows')
            ->whereIn('trackid', $trackIds)
            ->where('profileid', $release->profileid)
            ->where('status', 'finalized');
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
            ->select(
                'platform',
                DB::raw('SUM(quantity) as streams'),
                DB::raw('SUM(client_rupiah_exact) as earnings'),
                DB::raw('MAX(sales_month) as latest_report_date')
            )
            ->groupBy('platform')
            ->orderByDesc('streams')
            ->get();

        $recentPlatformRows = (clone $reportRows)
            ->select('sales_month', 'platform', DB::raw('SUM(quantity) as streams'))
            ->groupBy('sales_month', 'platform')
            ->orderBy('sales_month')
            ->get()
            ->groupBy('sales_month')
            ->sortKeys()
            ->slice(-12);
        $platformsByMonth = $selectedPeriodGroup === 'monthly'
            ? $recentPlatformRows
            : $recentPlatformRows->flatten(1)
                ->groupBy(function ($row) use ($selectedPeriodGroup) {
                    $date = \Carbon\Carbon::parse($row->sales_month);

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
            $date = \Carbon\Carbon::parse($periodStart);

            return match ($selectedPeriodGroup) {
                'quarterly' => 'Q'.$date->quarter.' '.$date->year,
                'semiannual' => ($date->month <= 6 ? 'Jan–Jun ' : 'Jul–Des ').$date->year,
                default => $date->locale('id')->isoFormat('MMM YY'),
            };
        })->values();
        $topPlatformsByMonth = $platformsByMonth
            ->map(fn ($platforms) => $platforms->sortByDesc('streams')->take(3)->values());
        $topPlatformByMonth = $topPlatformsByMonth
            ->map(fn ($platforms) => $platforms->first())
            ->filter()
            ->values();

        return view('admin.release-statistics', compact(
            'release',
            'totalStreams',
            'totalEarnings',
            'latestReportDate',
            'platformPerformance',
            'topPlatformByMonth',
            'topPlatformsByMonth',
            'platformPeriodLabels',
            'availablePlatforms',
            'selectedPlatform',
            'selectedMonth',
            'selectedQuarter',
            'selectedPeriodGroup'
        ));
    }
}
