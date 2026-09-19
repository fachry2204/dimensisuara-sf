<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpFoundation\Response;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use App\Helpers\Helpers;

use Carbon\Carbon;

use App\Models\Release;
use App\Models\Track;
use App\Models\Reporting\ReportImport;
use App\Models\Reporting\ReportRow;
use App\Models\Announcement;
use App\Models\Settlement\Settlement;

use App\Services\ContractService;
use App\Services\ReportService;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Counts
        $total      = DB::table('report_aggregate_monthly')->where('profileid', current_user()->profile->id)->sum('client_rupiah_exact');
        $tracks     = Track::where('profileid', current_user()->profile->id)->count();
        $releases   = Release::where('profileid', current_user()->profile->id)->where('status', 'released')->count();

        // Stats
        $views      = DB::table('report_aggregate_monthly')
            ->select(
                DB::raw('YEAR(sales_month) as year'),
                DB::raw('MONTH(sales_month) as month'),
                DB::raw('SUM(quantity) as views'),
            )
            ->where('profileid', current_user()->profile->id)
            ->groupBy('sales_month')
            ->orderBy('sales_month', 'desc')
            ->limit(12)
            ->get()
            ->sortBy(function ($row) {
                return $row->year . '-' . str_pad($row->month, 2, '0', STR_PAD_LEFT);
            })
            ->values()
            ->map(function ($row) {
                return [
                    'x' => Carbon::parse($row->year . '-' . $row->month . '-01')->locale('id_ID')->isoFormat('MMMM YYYY'),
                    'y' => round($row->views),
                ];
            });

        $revenue    = DB::table('report_aggregate_monthly')
            ->select(
                DB::raw('YEAR(reporting_month) as year'),
                DB::raw('MONTH(reporting_month) as month'),
                DB::raw('SUM(client_rupiah_exact) as client_rupiah_exact'),
            )
            ->where('profileid', current_user()->profile->id)
            ->groupBy('reporting_month')
            ->orderBy('reporting_month', 'desc')
            ->limit(12)
            ->get()
            ->sortBy(function ($row) {
                return $row->year . '-' . str_pad($row->month, 2, '0', STR_PAD_LEFT);
            })
            ->values()
            ->map(function ($row) {
                return [
                    'x' => Carbon::parse($row->year . '-' . $row->month . '-01')->locale('id_ID')->isoFormat('MMMM YYYY'),
                    'y' => round($row->client_rupiah_exact),
                ];
            });

        
        $mostplayed = DB::table('report_aggregate_tracks')->where('profileid', current_user()->profile->id)
            ->selectRaw('trackid, SUM(quantity) as quantity, SUM(client_rupiah_exact) as client_revenue')
            ->groupBy('trackid')
            ->orderBy('quantity', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $track = Track::where('id', $row->trackid)->where('profileid', current_user()->profile->id)->first();
                if($track) {
                    return [
                        'track' => $track,
                        'total_views' => $row->quantity,
                        'total_revenue' => $row->client_revenue,
                    ];
                }
            });

        $recentReleases = Release::where('profileid', current_user()->profile->id)
            ->whereNotIn('status', ['draft'])
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get();

        // Get active announcements (where current date is between start_date and end_date)
        $announcements = Announcement::whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('customer.index', compact('total', 'tracks', 'releases', 'views', 'revenue', 'mostplayed', 'recentReleases', 'announcements'));
    }
    
    public function pending()
    {
        $dest = null;

        // If user is admin or verified customer, redirect here
        if(Gate::check('customer.verified')) {
            $dest = redirect()->route('home');
        }
        elseif (Gate::check('admin')) {
            $dest = redirect()->route('admin.home');
        }

        // If user is not admin or verified customer but is a customer, redirect here
        elseif(Gate::check('customer')) {
            $dest = view('customer.pending');
        }

        if($dest) {
            return $dest;
        }

        // Logout unknown user
        Auth::logout();
        return redirect()->route('login');

    }
    
    public function earnings()
    {
        $profileid = current_user()->profile->id;

        // Data is delayed by 1 quarter, calculate the last completed quarter
        $lastCompletedQuarter = Carbon::now()->subMonths(3);
        $latestAvailableDate = Carbon::now()->subMonths(3)->endOfQuarter()->format('Y-m-d');

        // Last completed quarter revenue (not current quarter, as data is delayed)
        $revenue_quarter = DB::table('report_aggregate_monthly')->selectRaw('SUM(client_rupiah_exact) as sum')
            ->where('profileid', $profileid)
            ->whereRaw('QUARTER(reporting_month) = ?', [$lastCompletedQuarter->quarter])
            ->whereRaw('YEAR(reporting_month) = ?', [$lastCompletedQuarter->year])
            ->value('sum') ?? 0;

        // This year revenue (up to last completed quarter)
        $revenue_year = DB::table('report_aggregate_monthly')->selectRaw('SUM(client_rupiah_exact) as sum')
            ->where('profileid', $profileid)
            ->whereYear('reporting_month', Carbon::now()->year)
            ->where('reporting_month', '<=', $latestAvailableDate)
            ->value('sum') ?? 0;

        // Total revenue (all available data)
        $revenue = DB::table('report_aggregate_monthly')->selectRaw('SUM(client_rupiah_exact) as sum')
            ->where('profileid', $profileid)
            ->where('reporting_month', '<=', $latestAvailableDate)
            ->value('sum') ?? 0;

        // Last available month (end of last completed quarter minus 1 month)
        $lastAvailableMonth = Carbon::now()->subMonths(4)->endOfMonth();
        $revenue_last_month = DB::table('report_aggregate_monthly')->selectRaw('SUM(client_rupiah_exact) as sum')
            ->where('profileid', $profileid)
            ->whereYear('reporting_month', $lastAvailableMonth->year)
            ->whereMonth('reporting_month', $lastAvailableMonth->month)
            ->value('sum') ?? 0;

        // Get all report rows based on current user ISRCs (only finalized up to last quarter)
        $report_rows = ReportRow::where('status', 'finalized')
            ->where('profileid', $profileid)
            ->where('reporting_month', '<=', $latestAvailableDate)
            ->selectRaw('YEAR(reporting_month) as reporting_month, platform, region, artist_name')
            ->distinct()
            ->orderBy('reporting_month', 'desc')
            ->orderBy('platform')
            ->orderBy('region')
            ->get();

        // Get unique years, platforms, regions, and artists for options
        $years      = $report_rows->pluck('reporting_month')->unique()->sort()->reverse()->values();
        $platforms  = $report_rows->pluck('platform')->unique()->sort()->values();
        $regions    = $report_rows->pluck('region')->unique()->sort()->values();
        $artists    = $report_rows->pluck('artist_name')->unique()->filter()->sort()->values();

        // Get earnings by month for chart (last 12 months of available data)
        $earningsByMonth = DB::table('report_aggregate_monthly')
            ->selectRaw('DATE_FORMAT(reporting_month, "%Y-%m") as month, SUM(client_rupiah_exact) as total')
            ->where('profileid', $profileid)
            ->where('reporting_month', '>=', Carbon::now()->subMonths(15)->startOfMonth())
            ->where('reporting_month', '<=', $latestAvailableDate)
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $monthsChart = $earningsByMonth->pluck('month')->map(function($month) {
            return Carbon::parse($month)->locale('en_US')->isoFormat('MMM YYYY');
        });
        $earningsData = $earningsByMonth->pluck('total');

        // Top earning platform
        $topPlatform = ReportRow::where('profileid', $profileid)
            ->where('status', 'finalized')
            ->selectRaw('platform, SUM(client_rupiah_exact) as total_earnings')
            ->groupBy('platform')
            ->orderBy('total_earnings', 'desc')
            ->first();

        // Top earning region
        $topRegion = ReportRow::where('profileid', $profileid)
            ->where('status', 'finalized')
            ->selectRaw('region, SUM(client_rupiah_exact) as total_earnings')
            ->groupBy('region')
            ->orderBy('total_earnings', 'desc')
            ->first();

        // Platform distribution for chart
        $platformDistribution = ReportRow::where('profileid', $profileid)
            ->where('status', 'finalized')
            ->selectRaw('platform, SUM(client_rupiah_exact) as total_earnings')
            ->groupBy('platform')
            ->orderBy('total_earnings', 'desc')
            ->limit(5)
            ->get();

        $latestReportDate = DB::table('report_aggregate_monthly')->where('profileid', $profileid)->max('reporting_month');
        $latestReportDate = $latestReportDate ? Carbon::parse($latestReportDate)->endOfMonth() : null;
        $periodStart = $latestReportDate ? $latestReportDate->copy()->subMonths(11)->startOfMonth() : null;

        $monthly = DB::table('report_aggregate_monthly')->where('profileid', $profileid)
            ->when($periodStart, fn ($query) => $query->whereBetween('reporting_month', [$periodStart, $latestReportDate]))
            ->selectRaw('reporting_month, SUM(client_rupiah_exact) as earnings')
            ->groupBy('reporting_month')->orderBy('reporting_month')->get();
        $monthsChart = $monthly->map(fn ($row) => Carbon::parse($row->reporting_month)->locale('id')->isoFormat('MMM YYYY'))->values();
        $earningsData = $monthly->pluck('earnings')->map(fn ($value) => (float) $value)->values();
        $periodEarnings = (float) $monthly->sum('earnings');
        $totalEarnings = (float) DB::table('report_aggregate_monthly')->where('profileid', $profileid)->sum('client_rupiah_exact');
        $previousPeriodEarnings = $periodStart
            ? (float) DB::table('report_aggregate_monthly')->where('profileid', $profileid)->whereBetween('reporting_month', [$periodStart->copy()->subMonths(12), $periodStart->copy()->subMonth()->endOfMonth()])->sum('client_rupiah_exact')
            : 0;
        $trend = $previousPeriodEarnings > 0 ? (($periodEarnings - $previousPeriodEarnings) / $previousPeriodEarnings) * 100 : null;

        $platformDistribution = ReportRow::where('profileid', $profileid)->where('status', 'finalized')
            ->when($periodStart, fn ($query) => $query->whereBetween('reporting_month', [$periodStart, $latestReportDate]))
            ->selectRaw('platform, SUM(client_rupiah_exact) as total_earnings')
            ->groupBy('platform')->orderByDesc('total_earnings')->limit(6)->get();
        $recentRows = ReportRow::where('profileid', $profileid)->where('status', 'finalized')
            ->when($latestReportDate, fn ($query) => $query->where('reporting_month', '<=', $latestReportDate))
            ->selectRaw('reporting_month, platform, release_title, track_title, SUM(quantity) as streams, SUM(client_rupiah_exact) as earnings')
            ->groupBy('reporting_month', 'platform', 'release_title', 'track_title')
            ->orderByDesc('reporting_month')->orderByDesc('earnings')->limit(4)->get();
        // Settlement dates may be empty until a payment is completed. Use the
        // settlement period as the canonical ordering so the dashboard never
        // displays an arbitrary older record as the latest payment.
        $lastSettlement = Settlement::where('profileid', $profileid)
            ->where('status', 'transferred')
            ->orderByDesc('year')->orderByDesc('quarter')->orderByDesc('id')
            ->first();
        // A carried-over amount is not a payment in progress; it is retained
        // for a later settlement. Only pending means it awaits transfer.
        $pendingSettlement = Settlement::where('profileid', $profileid)
            ->where('status', 'pending')
            ->orderByDesc('year')->orderByDesc('quarter')->orderByDesc('id')
            ->first();
        $paidTotal = (float) Settlement::where('profileid', $profileid)->where('status', 'transferred')->sum('total');
        $estimatedBalance = max(0, $totalEarnings - $paidTotal);
        $reportFilterOptions = ReportRow::where('profileid', $profileid)
            ->where('status', 'finalized')
            ->selectRaw('YEAR(reporting_month) as year, platform, region, release_title')
            ->get();
        $reportYears = $reportFilterOptions->pluck('year')->filter()->unique()->sortDesc()->values();
        $reportPlatforms = $reportFilterOptions->pluck('platform')->filter()->unique()->sort()->values();
        $reportRegions = $reportFilterOptions->pluck('region')->filter()->unique()->sort()->values()
            ->map(fn ($region) => ['code' => $region, 'name' => $this->countryName($region)]);
        $reportReleases = $reportFilterOptions->pluck('release_title')->filter()->unique()->sort()->values();

        return view('customer.earnings', compact(
            'latestReportDate', 'monthsChart', 'earningsData', 'periodEarnings', 'totalEarnings', 'trend',
            'platformDistribution', 'recentRows', 'lastSettlement', 'pendingSettlement', 'estimatedBalance',
            'reportYears', 'reportPlatforms', 'reportRegions', 'reportReleases'
        ));
    }

    public function get_earnings_data(Request $request)
    {
        $profileid = current_user()->profile->id;

        // Validate request
        $request->validate([
            'upc'       => ['nullable', 'string', 'max:50'],
            'isrc'      => ['nullable', 'string', 'max:50'],
            'platform'  => ['nullable', 'string', 'max:50'],
            'region'    => ['nullable', 'string', 'max:50'],
            'artist'    => ['nullable', 'string', 'max:255'],
            'release'   => ['nullable', 'string', 'max:255'],
            'search'    => ['nullable', 'string', 'max:255'],
            'period'    => ['nullable', 'integer', 'digits:4'],
            'quarter'   => ['nullable', 'integer', 'in:1,2,3,4'],
            'page'      => ['nullable', 'integer', 'min:1'],
        ]);

        $query = ReportRow::query()
            ->where('profileid', $profileid)
            ->where('status', 'finalized');

        if ($request->filled('period')) {
            $query->whereYear('reporting_month', (int) $request->input('period'));
        }

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
        if($request->artist) {
            $query->where('artist_name', $request->input('artist'));
        }
        if($request->release) {
            $query->where('release_title', $request->input('release'));
        }
        if($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('track_title', 'like', "%{$search}%")
                    ->orWhere('release_title', 'like', "%{$search}%")
                    ->orWhere('platform', 'like', "%{$search}%");
            });
        }

        $summary = (clone $query)->selectRaw('COUNT(*) as report_count, COALESCE(SUM(client_rupiah_exact), 0) as total_earnings, COALESCE(SUM(quantity), 0) as total_streams')->first();
        $platformDistribution = (clone $query)->selectRaw('platform, COALESCE(SUM(client_rupiah_exact), 0) as earnings')
            ->groupBy('platform')->orderByDesc('earnings')->limit(5)->get();
        $results = $query->select('*', DB::raw('MONTH(reporting_month) as month'))
            ->orderByDesc('reporting_month')->orderByDesc('client_rupiah_exact')
            ->paginate(20)->withQueryString();

        return response()->json([
            'summary' => [
                'report_count' => (int) $summary->report_count,
                'total_earnings' => (float) $summary->total_earnings,
                'total_streams' => (int) $summary->total_streams,
            ],
            'platforms' => $platformDistribution,
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'total' => $results->total(),
            ],
            'results' => $results->getCollection()->map(function ($row) {
                return [
                    'id'            => $row->id,
                    'quarter'       => ceil($row->month / 3),
                    'month'         => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$row->month - 1],
                    'track_title'   => $row->track_title,
                    'release_title' => $row->release_title,
                    'upc'           => $row->upc,
                    'isrc'          => $row->isrc,
                    'period'        => $row->reporting_month,
                    'sales_month'   => $row->sales_month,
                    'platform'      => $row->platform,
                    'region'        => $row->region,
                    'region_name'   => $this->countryName($row->region),
                    'artist_name'   => $row->artist_name,
                    'views_raw'     => $row->quantity ?? 0,
                    'views'         => number_format($row->quantity, 0, ',', '.'),
                    'rupiah_raw'    => (float) ($row->client_rupiah_exact ?? 0),
                    'rupiah'        => number_format($row->client_rupiah_exact ?? 0, 0, ',', '.'),
                ];
            }),
        ]);
    }

    public function export_earnings(Request $request): Response
    {
        try {
            $profileid = current_user()->profile->id;

            // Validate request
            $request->validate([
                'upc'       => ['nullable', 'string', 'max:50'],
                'isrc'      => ['nullable', 'string', 'max:50'],
                'platform'  => ['nullable', 'string', 'max:50'],
                'region'    => ['nullable', 'string', 'max:50'],
                'artist'    => ['nullable', 'string', 'max:255'],
                'release'   => ['nullable', 'string', 'max:255'],
                'period'    => ['nullable', 'integer', 'digits:4'],
                'quarter'   => ['nullable', 'integer', 'in:1,2,3,4'],
            ]);

            $query = ReportRow::query()
                ->select('*', DB::raw('Month(reporting_month) as month'))
                ->where('profileid', $profileid)
                ->where('status', 'finalized');

            if ($request->filled('period')) {
                $query->whereYear('reporting_month', (int) $request->input('period'));
            }

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
            if($request->artist) {
                $query->where('artist_name', $request->input('artist'));
            }
            if($request->release) {
                $query->where('release_title', $request->input('release'));
            }

            $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = [
                'ID',
                'Judul Track',
                'Nama Artis',
                'Judul Rilis',
                'UPC',
                'ISRC',
                'Periode Sales',
                'Periode Report',
                'Quarter',
                'Bulan',
                'Platform',
                'Region',
                'Jumlah Views',
                'Pendapatan (Rp)',
            ];

            foreach ($headers as $column => $header) {
                $sheet->setCellValueExplicit([$column + 1, 1], $header, DataType::TYPE_STRING);
            }

            $rowNumber = 2;
            foreach ($query->get() as $row) {
                $sheet->setCellValue([1, $rowNumber], $row->id);
                $sheet->setCellValueExplicit([2, $rowNumber], $row->track_title ?? '-', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit([3, $rowNumber], $row->artist_name ?? '-', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit([4, $rowNumber], $row->release_title ?? '-', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit([5, $rowNumber], $row->upc ?? '-', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit([6, $rowNumber], $row->isrc ?? '-', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit([7, $rowNumber], $row->sales_month ?? '-', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit([8, $rowNumber], $row->reporting_month ?? '-', DataType::TYPE_STRING);
                $sheet->setCellValue([9, $rowNumber], ceil($row->month / 3));
                $sheet->setCellValueExplicit([10, $rowNumber], $months[$row->month - 1] ?? '-', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit([11, $rowNumber], $row->platform ?? '-', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit([12, $rowNumber], $row->region ?? '-', DataType::TYPE_STRING);
                $sheet->setCellValue([13, $rowNumber], (int) ($row->quantity ?? 0));
                $sheet->setCellValue([14, $rowNumber], (float) ($row->client_rupiah_exact ?? 0));

                $rowNumber++;
            }

            $filename = 'earnings.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Pragma' => 'no-cache',
            ]);
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors('Failed to export earnings data.');
        }
    }

    private function countryName(?string $region): string
    {
        $region = strtoupper(trim((string) $region));

        if (preg_match('/^[A-Z]{2}$/', $region) && class_exists(\Locale::class)) {
            $name = \Locale::getDisplayRegion('und_'.$region, 'id_ID');
            if ($name && strtoupper($name) !== $region) {
                return $name;
            }
        }

        return $region ?: 'Tidak diketahui';
    }

}
