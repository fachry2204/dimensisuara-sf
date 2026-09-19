<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBroadcastRequest;
use App\Http\Requests\Admin\UpdateBroadcastRequest;
use App\Models\Broadcast;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BroadcastController extends Controller
{
    /**
     * Display a listing of broadcasts.
     */
    public function index()
    {
        try {
            $broadcastSummary = [
                'total' => Broadcast::count(),
                'sent' => Broadcast::where('status', 'sent')->count(),
                'draft' => Broadcast::where('status', 'draft')->count(),
            ];

            return view('admin.broadcast.index', compact('broadcastSummary'));
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
            $query = Broadcast::with('creator');
            $recordsTotal = $query->count();

            // Searching
            if ($search = $request->input('search.value')) {
                $query->where(function($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%")
                      ->orWhereHas('creator', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Sorting
            $columns = ['subject', 'creator_name', 'recipient_count', 'created_at'];
            $orderColumn = $columns[$request->input('order.0.column', 3)] ?? 'created_at';
            $orderDir = $request->input('order.0.dir', 'desc');

            if ($orderColumn === 'creator_name') {
                $query->join('users', 'broadcasts.created_by', '=', 'users.id')
                      ->orderBy('users.name', $orderDir)
                      ->select('broadcasts.*');
            } else {
                $query->orderBy($orderColumn, $orderDir);
            }

            // Pagination
            $recordsFiltered = $query->count();

            $data = $query
                ->skip($request->input('start'))
                ->take($request->input('length'))
                ->get()
                ->map(function($broadcast) {
                    return [
                        'id' => $broadcast->id,
                        'subject' => $broadcast->subject,
                        'message' => \Str::limit($broadcast->message, 100),
                        'creator_name' => $broadcast->creator->name,
                        'recipient_count' => $broadcast->recipient_count,
                        'excluded_count' => $broadcast->excluded_count,
                        'selection_mode' => $broadcast->selection_mode,
                        'status' => $broadcast->status,
                        'created_at' => $broadcast->created_at->format('Y-m-d H:i:s'),
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
     * Show the form for creating a new broadcast.
     */
    public function create()
    {
        try {
            // Get all users with profiles for the exclusion selector
            $users = User::whereHas('profile')
                ->with('profile')
                ->orderBy('name')
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                    ];
                });

            return view('admin.broadcast.create', compact('users'));
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->route('admin.broadcast.index')->withErrors($th->getMessage());
        }
    }

    /**
     * Store a newly created broadcast in storage.
     */
    public function store(StoreBroadcastRequest $request)
    {
        try {
            $broadcastId = null;

            DB::transaction(function () use ($request, &$broadcastId) {
                $selectedIds = $request->input('excluded_user_ids', []);
                $selectionMode = $request->input('selection_mode', 'exclude');

                // Calculate recipient count based on mode
                $totalUsers = User::whereHas('profile')->count();
                if ($selectionMode === 'include') {
                    $recipientCount = count($selectedIds);
                } else {
                    $recipientCount = $totalUsers - count($selectedIds);
                }

                // Create broadcast
                $broadcast = Broadcast::create([
                    'subject' => $request->subject,
                    'message' => $request->message,
                    'selection_mode' => $selectionMode,
                    'created_by' => Auth::id(),
                    'excluded_user_ids' => $selectedIds,
                    'recipient_count' => $recipientCount,
                ]);

                $broadcastId = $broadcast->id;

                Log::info("Broadcast created: {$broadcast->id} by user " . Auth::id() . " (mode: {$selectionMode})");
            });

            return redirect()->route('admin.broadcast.show', $broadcastId)
                ->with('success', 'Broadcast message created successfully.');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.broadcast.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage())->withInput();
        }
    }

    /**
     * Display the specified broadcast.
     */
    public function show(string $id)
    {
        try {
            $broadcast = Broadcast::with('creator')->findOrFail($id);

            // Get recipient information
            $totalUsers = User::whereHas('profile')->count();
            $excludedCount = $broadcast->excluded_count;

            // Get the selected users (either excluded or included depending on mode)
            $selectedUsers = [];
            if (!empty($broadcast->excluded_user_ids)) {
                $selectedUsers = User::whereIn('id', $broadcast->excluded_user_ids)
                    ->with('profile')
                    ->orderBy('name')
                    ->get()
                    ->map(function($user) {
                        return [
                            'id' => $user->id,
                            'full_name' => $user->full_name,
                            'email' => $user->email,
                        ];
                    });
            }

            return view('admin.broadcast.show', compact('broadcast', 'totalUsers', 'excludedCount', 'selectedUsers'));
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.broadcast.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified broadcast.
     */
    public function edit(string $id)
    {
        try {
            $broadcast = Broadcast::findOrFail($id);

            // Prevent editing if already sent
            if ($broadcast->status !== 'draft') {
                return redirect()->route('admin.broadcast.show', $id)
                    ->withErrors('Cannot edit a broadcast that has already been sent.');
            }

            // Get all users with profiles for the exclusion selector
            $users = User::whereHas('profile')
                ->with('profile')
                ->orderBy('name')
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                    ];
                });

            return view('admin.broadcast.edit', compact('broadcast', 'users'));
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.broadcast.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    /**
     * Update the specified broadcast in storage.
     */
    public function update(UpdateBroadcastRequest $request, string $id)
    {
        try {
            $broadcast = Broadcast::findOrFail($id);

            // Prevent updating if already sent
            if ($broadcast->status !== 'draft') {
                return redirect()->route('admin.broadcast.show', $id)
                    ->withErrors('Cannot update a broadcast that has already been sent.');
            }

            DB::transaction(function () use ($request, $broadcast) {
                $selectedIds = $request->input('excluded_user_ids', []);
                $selectionMode = $request->input('selection_mode', 'exclude');

                // Recalculate recipient count based on mode
                $totalUsers = User::whereHas('profile')->count();
                if ($selectionMode === 'include') {
                    $recipientCount = count($selectedIds);
                } else {
                    $recipientCount = $totalUsers - count($selectedIds);
                }

                $broadcast->update([
                    'subject' => $request->subject,
                    'message' => $request->message,
                    'selection_mode' => $selectionMode,
                    'excluded_user_ids' => $selectedIds,
                    'recipient_count' => $recipientCount,
                ]);

                Log::info("Broadcast updated: {$broadcast->id} by user " . Auth::id() . " (mode: {$selectionMode})");
            });

            return redirect()->route('admin.broadcast.show', $id)
                ->with('success', 'Broadcast has been updated successfully.');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.broadcast.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage())->withInput();
        }
    }

    /**
     * Send the broadcast to recipients.
     */
    public function send(string $id)
    {
        try {
            $broadcast = Broadcast::findOrFail($id);

            // Validate broadcast can be sent
            if ($broadcast->status !== 'draft') {
                return redirect()->back()->withErrors('Broadcast has already been sent or is currently being sent.');
            }

            if ($broadcast->recipient_count === 0) {
                return redirect()->back()->withErrors('No recipients selected for this broadcast.');
            }

            // Get all recipient IDs
            $recipientIds = $broadcast->getRecipientIds();

            if (empty($recipientIds)) {
                $broadcast->update(['status' => 'draft']);
                return redirect()->back()->withErrors('No valid recipients found.');
            }

            // Configuration
            $chunkSize = 50;  // Send to 50 users per chunk
            $delayBetweenChunks = 120; // 2 minutes delay between chunks (in seconds)

            // Split recipients into chunks
            $chunks = array_chunk($recipientIds, $chunkSize);
            $totalChunks = count($chunks);

            // Build jobs for batch dispatch with staggered delays
            $jobs = [];
            foreach ($chunks as $index => $chunkUserIds) {
                $chunkNumber = $index + 1;
                $delay = $index * $delayBetweenChunks; // 0, 120, 240, 360 seconds...

                $jobs[] = (new \App\Jobs\Broadcast\SendBroadcastChunk(
                    $broadcast->id,
                    $chunkUserIds,
                    $chunkNumber,
                    $totalChunks
                ))->delay(now()->addSeconds($delay));
            }

            // Dispatch the batch
            $broadcastId = $broadcast->id;
            $batch = Bus::batch($jobs)
                ->then(function ($batch) use ($broadcastId) {
                    $broadcast = Broadcast::find($broadcastId);
                    if ($broadcast) {
                        $broadcast->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                            'batch_id' => null,
                        ]);
                    }

                    Log::info("Broadcast batch completed", [
                        'broadcast_id' => $broadcastId,
                        'batch_id' => $batch->id,
                        'total_jobs' => $batch->totalJobs,
                        'failed_jobs' => $batch->failedJobs,
                    ]);
                })
                ->catch(function ($batch, $e) use ($broadcastId) {
                    $broadcast = Broadcast::find($broadcastId);
                    if ($broadcast) {
                        $broadcast->update([
                            'status' => 'failed',
                        ]);
                    }

                    Log::error("Broadcast batch failed", [
                        'broadcast_id' => $broadcastId,
                        'batch_id' => $batch->id,
                        'total_jobs' => $batch->totalJobs,
                        'failed_jobs' => $batch->failedJobs,
                        'error' => $e->getMessage(),
                    ]);
                })
                ->name("Broadcast ID {$broadcastId}")
                ->dispatch();

            // Update status and tracking fields immediately
            $broadcast->update([
                'status' => 'pending',
                'batch_id' => $batch->id,
                'total_chunks' => $totalChunks,
                'completed_chunks' => 0,
                'failed_chunks' => 0,
            ]);

            Log::info("Broadcast send initiated: {$id} by user " . Auth::id(), [
                'broadcast_id' => $broadcastId,
                'batch_id' => $batch->id,
                'total_recipients' => count($recipientIds),
                'chunks' => $totalChunks,
                'chunk_size' => $chunkSize,
            ]);

            return redirect()->route('admin.broadcast.show', $id)
                ->with('success', "Broadcast is being sent to {$broadcast->recipient_count} recipients in {$totalChunks} batches.");
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.broadcast.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    /**
     * Remove the specified broadcast from storage.
     */
    public function destroy(string $id)
    {
        try {
            $broadcast = Broadcast::findOrFail($id);
            $broadcast->delete(); // Soft delete

            Log::info("Broadcast deleted: {$id} by user " . Auth::id());

            return redirect()->route('admin.broadcast.index')
                ->with('success', 'Broadcast has been deleted successfully.');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.broadcast.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }
}
