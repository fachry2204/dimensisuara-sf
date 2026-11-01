<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminAnalytics
{
    private ?bool $hasReleaseId = null;
    private const METRICS = 'SUM(COALESCE(quantity,0)) as streams, SUM(COALESCE(rupiah_exact,0)) as revenue, SUM(COALESCE(client_rupiah_exact,0)) as user_revenue, SUM(COALESCE(aggregator_rupiah_exact,0)) as system_revenue';

    public function filters(Request $request): array
    {
        $input = $request->validate([
            'start'=>['nullable','date_format:Y-m','required_with:end'],
            'end'=>['nullable','date_format:Y-m','required_with:start','after_or_equal:start'],
            'profile'=>['nullable','integer','exists:user_profiles,id'],
            'platform'=>['nullable','string','max:100'], 'country'=>['nullable','string','max:100'],
            'compare'=>['nullable','in:previous,year,none'],
            'tab'=>['nullable','in:overview,releases,artists,platforms,countries,geography,quality'],
            'q'=>['nullable','string','max:150'], 'sort'=>['nullable','in:streams,revenue,user_revenue,system_revenue,title'],
        ]);
        // Preserve older bookmarks for the former combined tab.
        if (($input['tab'] ?? null) === 'geography') $input['tab'] = 'platforms';
        $coverage = $this->published()->selectRaw('MIN(sales_month) as first_month, MAX(sales_month) as last_month')->first();
        $end = isset($input['end']) ? CarbonImmutable::createFromFormat('!Y-m',$input['end'])->endOfMonth() : CarbonImmutable::parse($coverage->last_month ?: now())->endOfMonth();
        $start = isset($input['start']) ? CarbonImmutable::createFromFormat('!Y-m',$input['start'])->startOfMonth() : $end->subMonthsNoOverflow(11)->startOfMonth();
        // A finite month range keeps chart payloads bounded, including empty periods.
        if ($start->diffInMonths($end->startOfMonth()) > 119) {
            throw \Illuminate\Validation\ValidationException::withMessages(['start'=>'Pilih rentang maksimal 10 tahun.']);
        }
        $months = (int) $start->diffInMonths($end->startOfMonth()) + 1;
        $compare = $input['compare'] ?? 'previous';
        $previousStart = $compare === 'year' ? $start->subYear() : $start->subMonthsNoOverflow($months);
        $previousEnd = $compare === 'year' ? $end->subYear()->endOfMonth() : $start->subDay();
        return array_merge(['profile'=>null,'platform'=>null,'country'=>null,'q'=>'','sort'=>'streams','tab'=>'overview'], $input, [
            'start'=>$start->format('Y-m'), 'end'=>$end->format('Y-m'), 'from'=>$start->toDateString(), 'to'=>$end->toDateString(),
            'previous_from'=>$previousStart->toDateString(),'previous_to'=>$previousEnd->toDateString(), 'compare'=>$compare,
            'q'=>trim((string)($input['q']??'')),
            'coverage'=>$coverage->last_month ? CarbonImmutable::parse($coverage->last_month)->format('m/Y') : null,
        ]);
    }

    private function raw(array $f, bool $previous = false): Builder
    {
        $query = DB::table('report_rows as rr')->join('report_imports as ri','ri.id','=','rr.report_import_id')
            ->whereBetween('rr.sales_month',[$f[$previous?'previous_from':'from'],$f[$previous?'previous_to':'to']]);
        foreach (['profile'=>'profileid','platform'=>'platform','country'=>'country'] as $filter=>$column) {
            if (filled($f[$filter])) $query->where('rr.'.$column,$f[$filter]);
        }
        return $query;
    }

    private function published(): Builder
    {
        return DB::table('report_rows as rr')->join('report_imports as ri','ri.id','=','rr.report_import_id')
            ->where('ri.status','completed')->where('rr.status','finalized')->whereNotNull('rr.profileid');
    }

    private function activity(array $f, bool $previous = false): Builder
    {
        return DB::query()->fromSub($this->raw($f,$previous)->where('ri.status','completed')->where('rr.status','finalized')->whereNotNull('rr.profileid')->select('rr.*'),'activity');
    }

    public function rows(array $f, bool $previous = false): Builder
    {
        // Group lookup candidates before joining: a track on two releases never doubles revenue.
        // An explicit release or a unique owner+UPC wins; ambiguous candidates remain unassigned.
        $byUpc = DB::table('releases')->select('profileid','upc')->selectRaw('MIN(id) as id')
            ->whereNotNull('upc')->where('upc','!=','')->groupBy('profileid','upc')->havingRaw('COUNT(*) = 1');
        $byTrack = DB::table('release_track as rt')->join('releases as r','r.id','=','rt.release_id')
            ->select('rt.track_id','r.profileid')->selectRaw('MIN(r.id) as id')
            ->groupBy('rt.track_id','r.profileid')->havingRaw('COUNT(DISTINCT r.id) = 1');
        $query = $this->raw($f,$previous)->where('ri.status','completed')->where('rr.status','finalized')->whereNotNull('rr.profileid');
        $releaseCandidates = ['by_upc.id','by_track.id'];
        // Some installed reporting schemas have only trackid/profileid, without releaseid.
        if ($this->hasReleaseId ??= Schema::hasColumn('report_rows','releaseid')) {
            $query->leftJoin('releases as direct',fn ($j) => $j->on('direct.id','=','rr.releaseid')->on('direct.profileid','=','rr.profileid'));
            array_unshift($releaseCandidates,'direct.id');
        }
        $query->leftJoinSub($byUpc,'by_upc',fn ($j) => $j->on('by_upc.profileid','=','rr.profileid')->on('by_upc.upc','=','rr.upc'))
            ->leftJoinSub($byTrack,'by_track',fn ($j) => $j->on('by_track.profileid','=','rr.profileid')->on('by_track.track_id','=','rr.trackid'))
            ->select('rr.*')->selectRaw('COALESCE('.implode(',',$releaseCandidates).') as resolved_release');
        return DB::query()->fromSub($query,'activity');
    }

    public function releaseQuery(array $f): Builder
    {
        $totals = $this->rows($f)->whereNotNull('resolved_release')->select('resolved_release')->selectRaw(self::METRICS)->groupBy('resolved_release');
        $query = DB::table('releases as r')->leftJoinSub($totals,'totals','r.id','=','totals.resolved_release')
            ->leftJoin('user_profiles as p','p.id','=','r.profileid')
            ->select('r.id','r.title','r.upc','r.profileid','r.status','p.userid','p.firstname','p.lastname')
            ->selectRaw('COALESCE(totals.streams,0) as streams, COALESCE(totals.revenue,0) as revenue, COALESCE(totals.user_revenue,0) as user_revenue, COALESCE(totals.system_revenue,0) as system_revenue');
        if ($f['compare'] !== 'none') {
            $prior = $this->rows($f,true)->whereNotNull('resolved_release')->select('resolved_release')->selectRaw(self::METRICS)->groupBy('resolved_release');
            $query->leftJoinSub($prior,'prior','r.id','=','prior.resolved_release')
                ->selectRaw('COALESCE(prior.streams,0) as previous_streams, COALESCE(prior.revenue,0) as previous_revenue');
        }
        if (filled($f['profile'])) $query->where('r.profileid',$f['profile']);
        if ($f['q'] !== '') {
            $search = '%'.$f['q'].'%';
            $query->where(fn ($q) => $q->where('r.title','like',$search)->orWhere('r.upc','like',$search)
                ->orWhereExists(fn ($tracks) => $tracks->selectRaw('1')->from('release_track as rt')->join('tracks as t','t.id','=','rt.track_id')
                    ->whereColumn('rt.release_id','r.id')->where('t.isrc','like',$search)));
        }
        return $query;
    }

    public function sortedReleases(array $f): Builder
    {
        return $this->releaseQuery($f)->orderBy($f['sort'],$f['sort']==='title'?'asc':'desc')->orderBy('r.id');
    }

    private function monthly(Builder $query): \Illuminate\Support\Collection
    {
        return $query->selectRaw('SUBSTR(sales_month,1,7) as month, '.self::METRICS)->groupByRaw('SUBSTR(sales_month,1,7)')->orderBy('month')->get()->keyBy('month');
    }

    public function dashboard(array $f): array
    {
        $rows = $this->activity($f);
        $totals = (clone $rows)->selectRaw(self::METRICS)->first();
        $previous = $f['compare']==='none' ? null : $this->activity($f,true)->selectRaw(self::METRICS)->first();
        $currentMonths = $this->monthly(clone $rows);
        $previousMonths = $f['compare']==='none' ? collect() : $this->monthly($this->activity($f,true));
        $series = [];
        $date = CarbonImmutable::parse($f['from']);
        $priorDate = CarbonImmutable::parse($f['previous_from']);
        while ($date->toDateString() <= $f['to']) {
            $current = $currentMonths->get($date->format('Y-m'));
            $prior = $previousMonths->get($priorDate->format('Y-m'));
            $series[] = ['month'=>$date->locale('id')->isoFormat('MMM YYYY'), 'streams'=>(float)($current->streams??0), 'revenue'=>(float)($current->revenue??0),
                'previous_streams'=>(float)($prior->streams??0), 'previous_revenue'=>(float)($prior->revenue??0)];
            $date=$date->addMonth(); $priorDate=$priorDate->addMonth();
        }
        $platforms = (clone $rows)->select('platform')->selectRaw(self::METRICS)->groupBy('platform')->orderByDesc('revenue')->get();
        $countries = (clone $rows)->select('country')->selectRaw(self::METRICS)->groupBy('country')->orderByDesc('streams')->get();
        $users = (clone $rows)->select('profileid')->selectRaw(self::METRICS)->groupBy('profileid');
        $userQuery = DB::query()->fromSub($users,'u')->leftJoin('user_profiles as p','p.id','=','u.profileid')
            ->select('u.*','p.userid','p.firstname','p.lastname')->orderByDesc('revenue');
        $artists = (clone $rows)->select('artist_name')->selectRaw(self::METRICS)->groupBy('artist_name')->orderByDesc('streams');
        $tracks = (clone $rows)->select('trackid','isrc','track_title','artist_name')->selectRaw(self::METRICS)
            ->groupBy('trackid','isrc','track_title','artist_name')->orderByDesc('streams');
        $statuses = $this->raw($f)->select('ri.status')->selectRaw('COUNT(DISTINCT ri.id) as batches')->groupBy('ri.status')->pluck('batches','status');
        $quality = [
            'completed'=>(int)($statuses['completed']??0), 'waiting'=>(int)($statuses['pending_calculation']??0),
            'incomplete'=>$this->raw($f)->where('rr.status','incomplete')->distinct()->count('rr.isrc'),
            'unassigned'=>$this->rows($f)->whereNull('resolved_release')->count(),
            'difference'=>(float)$totals->revenue-(float)$totals->user_revenue-(float)$totals->system_revenue,
        ];
        $catalogueFilters=$f; $catalogueFilters['q']=''; $catalogueFilters['sort']='streams';
        $topReleases=$this->sortedReleases($catalogueFilters)->where('totals.streams','>',0)->limit(5)->get();
        $releases=$this->sortedReleases($f)->paginate(15,['*'],'release_page')->withQueryString();
        $options = [
            'profiles'=>DB::table('user_profiles')->select('id','firstname','lastname')->orderBy('firstname')->get(),
            'platforms'=>DB::table('report_rows')->whereNotNull('platform')->distinct()->orderBy('platform')->pluck('platform'),
            'countries'=>DB::table('report_rows')->whereNotNull('country')->distinct()->orderBy('country')->pluck('country'),
        ];
        return compact('totals','previous','series','platforms','countries','quality','releases','topReleases','options','statuses') + [
            'users'=>$userQuery->paginate(15,['*'],'user_page')->withQueryString(),
            'artists'=>$artists->paginate(15,['*'],'artist_page')->withQueryString(),
            'tracks'=>$tracks->paginate(15,['*'],'track_page')->withQueryString(),
            'activeUsers'=>(clone $rows)->distinct()->count('profileid'),
            'activeTracks'=>(clone $rows)->distinct()->count('trackid'),
        ];
    }
}
