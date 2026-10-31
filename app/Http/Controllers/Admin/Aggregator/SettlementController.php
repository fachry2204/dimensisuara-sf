<?php

namespace App\Http\Controllers\Admin\Aggregator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Http\Controllers\Controller;

use App\Models\Settlement\Settlement;
use App\Models\User\Profile;

use App\Services\SettlementService;

class SettlementController extends Controller
{
    protected $service;

    public function __construct(SettlementService $service)
    {
        $this->service = $service;
    }

    /**
     * View only methods
     */
    public function index()
    {
        $userprofiles   = Profile::all()->sortBy('full_name');
        $years          = Settlement::distinct()->orderByDesc('year')->pluck('year');

        return view('admin.aggregator.settlement.index', compact('userprofiles','years'));
    }

    public function preview(Request $request)
    {
        // Validate
        $request->validate([
            'year'              => 'required|integer',
            'quarter'           => 'required|in:1,2,3,4',
            'allow_empty'       => 'nullable|boolean',
            'minimum'           => 'required|integer',
            'include_carryover' => 'nullable|boolean',
        ]);

        // Get parameters
        $year               = $request->year;
        $quarter            = $request->quarter;
        $allow_empty        = $request->allow_empty;
        $minimum            = $request->minimum;
        $include_carryover  = $request->include_carryover;

        // Generate settlements
        $settlements = $this->service->generateAggregatorSettlements($year, $quarter, $include_carryover, $allow_empty, $minimum);

        // Skip if no settlement
        if($settlements->isEmpty()) {
            return redirect()->route('admin.releases.settlement.index')->withErrors('No settlement generated with selected criteria');
        }

        return view('admin.aggregator.settlement.preview', compact('settlements','quarter','year','include_carryover','allow_empty','minimum'));
    }

    public function show($id)
    {
        $settlement = Settlement::findOrFail($id);
        return view('admin.aggregator.settlement.show', compact('settlement'));
    }

    /**
     * Action methods
     */
    public function store(Request $request)
    {
        $context = [];
        
        try {
            // Store settlement
            $result = $this->service->finalizeAggregatorSettlements();

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id']   = $result->getKey();
                $context['model_type'] = get_class($result);
            }
                
            // Log success
            Log::info('Settlements created.', $context);

            return redirect()->route('admin.releases.settlement.index')->with('success', 'Settlements created.');
        }
        catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack']   = $th->getTraceAsString();

            // Log error
            Log::error('Failed to create settlement.', $context);
            
            return redirect()->route('admin.releases.settlement.index')->withInput()->withErrors($th->getMessage());
        }
    }

    public function complete($id)
    {
        $settlement = Settlement::findOrFail($id);
        $context = [];

        try {
            $result = $this->service->complete($settlement);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id']   = $result->getKey();
                $context['model_type'] = get_class($result);
            }
                
            // Log success
            Log::info('Settlement marked as transferred.', $context);

            return redirect()->back()->with('success', 'Settlement marked as transferred.');
        }
        catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack']   = $th->getTraceAsString();

            // Log error
            Log::error('Failed to mark settlement as transferred.', $context);
            
            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function upload(Request $request, $id)
    {
        $settlement = Settlement::findOrFail($id);
        $request->validate([
            'type' => ['required', 'in:slip,tax'],
            'file' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
        ]);
        $context = [];

        try {
            $type   = $request->type;
            $file   = $request->file('file');

            $result = $this->service->upload($settlement, $type, $file);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id']   = $result->getKey();
                $context['model_type'] = get_class($result);
            }
                
            // Log success
            Log::info('File uploaded for settlement.', $context);

            return redirect()->back()->with('success', 'File uploaded for settlement.');
        }
        catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack']   = $th->getTraceAsString();

            // Log error
            Log::error('Failed to upload file for settlement.', $context);
            
            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }
    
    public function destroy($id)
    {
        $settlement = Settlement::findOrFail($id);

        if ($settlement->status === 'transferred') {
            return redirect()->route('admin.releases.settlement.index')
                ->withErrors('Transferred transactions cannot be deleted.');
        }

        try {
            DB::transaction(function () use ($settlement) {
                $settlement->delete();
            });

            return redirect()->route('admin.releases.settlement.index')
                ->with('success', 'Transaction deleted successfully.');
        }
        catch (\Throwable $th) {
            Log::error('Failed to delete settlement.', [
                'settlement_id' => $settlement->id,
                'message' => $th->getMessage(),
                'stack' => $th->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors('Failed to delete transaction.');
        }
    }

    public function destroyBulk(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:settlements,id'],
        ]);

        try {
            $deleted = 0;

            DB::transaction(function () use ($validated, &$deleted) {
                $settlements = Settlement::whereIn('id', $validated['ids'])
                    ->where('status', '!=', 'transferred')
                    ->get();

                foreach ($settlements as $settlement) {
                    $settlement->delete();
                    $deleted++;
                }
            });

            if ($deleted === 0) {
                return redirect()->route('admin.releases.settlement.index')
                    ->withErrors('No eligible transactions were selected for deletion.');
            }

            return redirect()->route('admin.releases.settlement.index')
                ->with('success', $deleted . ' transaction(s) deleted successfully.');
        }
        catch (\Throwable $th) {
            Log::error('Failed to delete settlements in bulk.', [
                'ids' => $validated['ids'],
                'message' => $th->getMessage(),
                'stack' => $th->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors('Failed to delete selected transactions.');
        }
    }

    public function get_data(Request $request)
    {
        $request->validate([
            'userid'    => ['nullable', 'integer', 'exists:user_profiles,id'],
            'year'      => ['nullable', 'string', 'max:4'],
            'quarter'   => ['nullable', 'integer', 'in:1,2,3,4'],
            'status'    => ['nullable', 'string', 'in:pending,completed,carried_over'],
        ]);
        
        $query = Settlement::with('profile');
        if($request->userid) {
            $query->where('profileid', $request->input('userid'));
        }
        if($request->year) {
            $query->where('year', $request->input('year'));
        }
        if($request->quarter) {
            $query->where('quarter', $request->input('quarter'));
        }
        if($request->status) {
            $query->where('status', $request->input('status'));
        }

        $duplicate_query = clone $query;
        $duplicate_query->whereIn('status', ['pending','transferred']);
        
        $results    = $query->get();
        $totals     = $duplicate_query->get();

        $total_gross        = $totals->sum('gross');
        $total_aggregator   = $totals->sum('aggregator');
        $total_client       = $total_gross - $total_aggregator;

        return response()->json([
            'sum_total'         => number_format($total_gross, 0, ',', '.'),
            'sum_aggregator'    => number_format($total_aggregator, 0, ',', '.'),
            'sum_client'        => number_format($total_client, 0, ',', '.'),
            'results'   => $results->map(function ($row) {
                return [
                    'id'            => $row->id,
                    'profile_name'  => $row->profile->full_name,
                    'year'          => $row->year,
                    'quarter'       => $row->quarter,
                    'status'        => $row->status,
                    'total'         => $row->total,
                    'created_at'    => $row->created_at,
                ];
            }),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        try {
            $request->validate([
                'userid'    => ['nullable', 'integer', 'exists:user_profiles,id'],
                'year'      => ['nullable', 'string', 'max:4'],
                'quarter'   => ['nullable', 'integer', 'in:1,2,3,4'],
                'status'    => ['nullable', 'string', 'in:pending,completed,carried_over'],
            ]);
            
            $query = Settlement::with('profile');
            if($request->userid) {
                $query->where('profileid', $request->input('userid'));
            }
            if($request->year) {
                $query->where('year', $request->input('year'));
            }
            if($request->quarter) {
                $query->where('quarter', $request->input('quarter'));
            }
            if($request->status) {
                $query->where('status', $request->input('status'));
            }


            $results    = $query->get()->map(function ($row) {
                return [
                    $row->profile->full_name,
                    $row->year,
                    $row->quarter,
                    (floatval($row->subtotal) + floatval($row->carryover)),
                    (floatval($row->tax)),
                    (floatval($row->total)),
                    $row->status,
                    $row->created_at,
                ];
            });

            $results    = collect([[
                'Nama',
                'Tahun',
                'Quarter',
                'Subtotal',
                'PPh21',
                'Total',
                'Status',
                'Tanggal Dibuat',
            ]])->merge($results);

            
            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=settlements.csv",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0",
            ];

            $callback = function() use ($results) {
                $handle = fopen('php://output', 'w');

                foreach ($results->toArray() as $row) {
                    $convertedRow = array_map(function ($field) {
                        if (is_numeric($field) && str_contains((string) $field, '.')) {
                            // Replace dot with comma for decimals
                            return str_replace('.', ',', $field);
                        }
                        return $field;
                    }, $row);

                    fputcsv($handle, $convertedRow, ';', '"'); // still use ; as separator
                }

                fclose($handle);
            };
            return response()->stream($callback, 200, $headers);

        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);

        }
    }
}
