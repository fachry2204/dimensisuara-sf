<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;

class LogActivityController extends Controller
{
    private const FILTERS = ['all','today','this_week','attention'];

    private function activeFilter(Request $request): string
    {
        $request->validate(['filter'=>['nullable','string',\Illuminate\Validation\Rule::in(self::FILTERS)]]);
        return $request->input('filter') ?: 'all';
    }

    private function filtered(\Illuminate\Database\Eloquent\Builder $query, string $filter): \Illuminate\Database\Eloquent\Builder
    {
        if ($filter === 'today' || $filter === 'this_week') {
            $start = $filter === 'today' ? now()->startOfDay() : now()->startOfWeek(\Carbon\Carbon::MONDAY);
            $query->where('logs.created_at','>=',$start)->where('logs.created_at','<',now()->addDay()->startOfDay());
        }
        if ($filter === 'attention') $query->whereIn('logs.level',['emergency','alert','critical','error','warning']);
        return $query;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $activeFilter = $this->activeFilter($request);

        $activitySummary = [
            'today' => $this->filtered(LogActivity::query(),'today')->count(),
            'this_week' => $this->filtered(LogActivity::query(),'this_week')->count(),
            'total' => LogActivity::count(),
            'attention' => $this->filtered(LogActivity::query(),'attention')->count(),
        ];

        return view('admin.logactivity.index', compact('activitySummary','activeFilter'));
    }

    public function show($id)
    {
        $log = LogActivity::findOrFail($id);
        try {
            return response()->json([
                'success' => true,
                'data' => array_merge($log->toArray(), ['details'=>app(\App\Services\ActivityLogDetails::class)->present($log)])
            ], 200);

        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function get_data(Request $request)
    {
        $activeFilter = $this->activeFilter($request);
        try {
            // Get records with certain status
            $query = LogActivity::query();
            $recordsTotal = $query->count();
            $this->filtered($query,$activeFilter);

            // Searching
            $query = $query
                ->select(
                    'logs.id as id',
                    'logs.level as level',
                    'logs.message as message',
                    'logs.created_at as created_at',
                    DB::raw('COALESCE(user_admin.name, users.name, "SYSTEM") as user_name'),
                    'logs.userid as user_id',
                    'logs.admin_user_id'
                )
                ->leftJoin('users', 'users.id', '=', 'logs.userid')
                ->leftJoin('user_admin', 'user_admin.id', '=', 'logs.admin_user_id');
            // Searching
            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('logs.level', 'like', "%{$search}%");
                    $q->orWhere('logs.message', 'like', "%{$search}%");
                    $q->orWhere('users.name', 'like', "%{$search}%");
                    $q->orWhere('user_admin.name', 'like', "%{$search}%");
                });
            }

            // Sorting
            $columns = ['logs.level', 'logs.message', 'user_name', 'logs.created_at'];
            $orderColumn = $columns[(int)$request->input('order.0.column',3)] ?? 'logs.created_at';
            $orderDir = strtolower((string)$request->input('order.0.dir','desc')) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($orderColumn, $orderDir)->orderBy('logs.id','desc');

            // Pagination
            $recordsFiltered = $query->count();

            $data = $query
                ->skip(max(0,(int)$request->input('start',0)))
                ->take(max(1,min(100,(int)$request->input('length',10))))
                ->get();

            return response()->json([
                "draw" => intval($request->input('draw')),
                "recordsTotal" => $recordsTotal,
                "recordsFiltered" => $recordsFiltered,
                "data" => $data,
            ], 200);
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }
}
