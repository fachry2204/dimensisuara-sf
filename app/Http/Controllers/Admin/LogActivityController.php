<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;

class LogActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();

        $activitySummary = [
            'today' => LogActivity::whereDate('created_at', $today)->count(),
            'this_week' => LogActivity::whereDate('created_at', '>=', $weekStart)->count(),
            'total' => LogActivity::count(),
            'attention' => LogActivity::whereIn('level', ['emergency', 'alert', 'critical', 'error', 'warning'])->count(),
        ];

        return view('admin.logactivity.index', compact('activitySummary'));
    }

    public function show($id)
    {
        $log = LogActivity::find($id);
        try {
            return response()->json([
                'success' => true,
                'data' => $log->toArray()
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
        try {
            // Get records with certain status
            $query = LogActivity::query();
            $recordsTotal = $query->count();

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
                    $q->where('level', 'like', "%{$search}%");
                    $q->orWhere('message', 'like', "%{$search}%");
                    $q->orWhere('users.name', 'like', "%{$search}%");
                    $q->orWhere('user_admin.name', 'like', "%{$search}%");
                });
            }

            // Sorting
            $columns = ['level', 'message', 'user_name', 'created_at'];
            $orderColumn = $columns[$request->input('order.0.column')];
            $orderDir = $request->input('order.0.dir');
            $query->orderBy($orderColumn, $orderDir);

            // Pagination
            $recordsFiltered = $query->count();

            $data = $query
                ->skip($request->input('start'))
                ->take($request->input('length'))
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
