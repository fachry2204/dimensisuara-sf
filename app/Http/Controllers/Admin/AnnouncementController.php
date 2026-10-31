<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Models\Announcement;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $today = now()->toDateString();
            $announcementSummary = [
                'total' => Announcement::count(),
                'active' => Announcement::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->count(),
                'scheduled' => Announcement::whereDate('start_date', '>', $today)->count(),
                'expired' => Announcement::whereDate('end_date', '<', $today)->count(),
            ];

            return view('admin.announcement.index', compact('announcementSummary'));
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            return view('admin.announcement.create');
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    /**
     * Get data for DataTables (AJAX)
     */
    public function get_data(Request $request)
    {
        try {
            $query = Announcement::with('creator');
            $recordsTotal = $query->count();

            // Searching
            if ($search = $request->input('search.value')) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('start_date', 'like', "%{$search}%")
                      ->orWhere('end_date', 'like', "%{$search}%")
                      ->orWhereHas('creator', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Sorting
            $columns = ['title', 'start_date', 'end_date', 'creator_name', 'created_at'];
            $orderColumn = $columns[$request->input('order.0.column', 0)] ?? 'created_at';
            $orderDir = $request->input('order.0.dir', 'desc');

            if ($orderColumn === 'creator_name') {
                $query->leftJoin('user_admin', 'announcements.user_id', '=', 'user_admin.id')
                      ->orderBy('user_admin.name', $orderDir)
                      ->select('announcements.*');
            } else {
                $query->orderBy($orderColumn, $orderDir);
            }

            // Pagination
            $recordsFiltered = $query->count();

            $data = $query
                ->skip($request->input('start'))
                ->take($request->input('length'))
                ->get()
                ->map(function($announcement) {
                    return [
                        'id' => $announcement->id,
                        'title' => $announcement->title,
                        'start_date' => $announcement->start_date,
                        'end_date' => $announcement->end_date,
                        'creator_name' => $announcement->creator?->name ?? 'N/A',
                        'created_at' => $announcement->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                "draw" => intval($request->input('draw')),
                "recordsTotal" => $recordsTotal,
                "recordsFiltered" => $recordsFiltered,
                "data" => $data,
            ], 200);
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAnnouncementRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                Announcement::create([
                    'title' => $request->title,
                    'content' => $request->content,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'user_id' => Auth::id(),
                ]);

                Log::info("Announcement created by user " . Auth::id());
            });

            return redirect()->route('admin.announcement.index')
                ->with('success', 'Announcement has been created successfully.');
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage())->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $announcement = Announcement::findOrFail($id);
            return view('admin.announcement.edit', compact('announcement'));
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.announcement.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAnnouncementRequest $request, string $id)
    {
        try {
            $announcement = Announcement::findOrFail($id);

            DB::transaction(function () use ($request, $announcement) {
                $announcement->update([
                    'title' => $request->title,
                    'content' => $request->content,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ]);

                Log::info("Announcement updated: {$announcement->id} by user " . Auth::id());
            });

            return redirect()->route('admin.announcement.index')
                ->with('success', 'Announcement has been updated successfully.');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.announcement.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $announcement = Announcement::findOrFail($id);
            $announcement->delete();

            Log::info("Announcement deleted: {$id} by user " . Auth::id());

            return redirect()->route('admin.announcement.index')
                ->with('success', 'Announcement has been deleted successfully.');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.announcement.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }
}
