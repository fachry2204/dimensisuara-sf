<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use App\Services\AdminAnalytics;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request, AdminAnalytics $analytics)
    {
        $filters = $analytics->filters($request);
        $data = $analytics->dashboard($filters);
        $ids = $data['releases']->pluck('id')->merge($data['topReleases']->pluck('id'))->unique();
        $covers = Release::with(['thumbnail','cover'])->whereIn('id',$ids)->get(['id'])->mapWithKeys(
            fn ($release) => [$release->id => $release->thumbnail?->url ?: $release->cover?->url ?: asset('build/images/album-placeholder.webp')]
        );
        $chartData = ['series'=>$data['series'], 'platforms'=>$data['platforms']->map(fn ($p) => ['name'=>$p->platform ?: 'Tidak diketahui','revenue'=>(float)$p->revenue])->values(), 'compare'=>$filters['compare']];
        return view('admin.analytics.index', $data + compact('filters','covers','chartData'));
    }

    public function export(Request $request, AdminAnalytics $analytics)
    {
        $filters = $analytics->filters($request);
        return response()->streamDownload(function () use ($analytics,$filters) {
            $out = fopen('php://output','w');
            fwrite($out,"\xEF\xBB\xBF");
            $write = static fn (array $values) => fputcsv($out,$values,',','"','');
            $write(['Periode penjualan',$filters['start'],$filters['end']]);
            $write(['Sumber','Laporan completed, baris finalized dengan akun; stream/unit = quantity laporan']);
            $write(['ID Rilis','Rilis','UPC','User','Stream / Unit','Pendapatan IDR','Bagian User IDR','Bagian Sistem IDR']);
            foreach ($analytics->sortedReleases($filters)->cursor() as $row) {
                $safe = static fn ($value) => preg_match('/^[\s]*[=+@-]/u',(string)$value) ? "'".$value : (string)$value;
                $write([$row->id,$safe($row->title),"'".$row->upc,$safe(trim($row->firstname.' '.$row->lastname)),
                    $row->streams,$row->revenue,$row->user_revenue,$row->system_revenue]);
            }
            fclose($out);
        },'analitik-rilis-'.$filters['start'].'-'.$filters['end'].'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
