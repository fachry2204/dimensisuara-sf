<?php

namespace App\Http\Controllers\Admin\Aggregator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Http\Controllers\Controller;
use App\Models\Transaction\Transaction;
use App\Models\User\Profile;
use App\Services\TransactionService;

class TransactionController extends Controller
{
    protected $service;

    public function __construct(TransactionService $service)
    {
        $this->service = $service;
    }

    /**
     * View only methods
     */
    public function index()
    {
        $userprofiles   = Profile::all()->sortBy('full_name');
        $years          = Transaction::distinct()->orderByDesc('year')->pluck('year');

        $sums = DB::table('transactions')
            ->selectRaw('
                sum(rupiah_exact) as rupiah_exact,
                sum(aggregator_rupiah_exact) as aggregator_rupiah_exact,
                sum(client_rupiah_exact) as client_rupiah_exact'
            )->first();
        $rupiah_exact               = $sums->rupiah_exact;
        $aggregator_rupiah_exact    = $sums->aggregator_rupiah_exact;
        $client_rupiah_exact        = $sums->client_rupiah_exact;

        return view('admin.aggregator.transaction.index', compact('userprofiles','years','rupiah_exact','aggregator_rupiah_exact','client_rupiah_exact'));
    }

    public function preview(Request $request)
    {
        // Validate
        $request->validate([
            'year'          => 'required|integer',
            'quarter'       => 'required|in:1,2,3,4',
            'allow_empty'   => 'nullable|boolean',
            'minimum'       => 'required|integer',
        ]);

        // Get parameters
        $year           = $request->year;
        $quarter        = $request->quarter;
        $allow_empty    = $request->allow_empty;
        $minimum        = $request->minimum;

        // Generate transactions
        $transactions = $this->service->generate($year, $quarter, $allow_empty, $minimum);

        // Skip if no transaction
        if($transactions->isEmpty()) {
            return redirect()->route('admin.releases.settlement.index')->withErrors('No transaction generated with selected criteria');
        }

        return view('admin.aggregator.transaction.preview', compact('transactions','quarter','year'));
    }

    public function show($id)
    {
        $transaction = Transaction::findOrFail($id);
        return view('admin.aggregator.transaction.show', compact('transaction'));
    }

    /**
     * Action methods
     */
    public function store(Request $request)
    {
        $context = [];

        // Validate
        $request->validate([
            'year'      => 'required|integer',
            'quarter'   => 'required|in:1,2,3,4',
        ]);
        
        try {
            // Get parameters
            $year       = $request->year;
            $quarter    = $request->quarter;

            // Store transaction
            $result = $this->service->finalize($year, $quarter);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id']   = $result->getKey();
                $context['model_type'] = get_class($result);
            }
                
            // Log success
            Log::info('Transaction created.', $context);

            return redirect()->route('admin.releases.settlement.index')->with('success', 'Transaction created.');
        }
        catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack']   = $th->getTraceAsString();

            // Log error
            Log::error('Failed to create transaction.', $context);
            
            return redirect()->route('admin.releases.settlement.index')->withInput()->withErrors($th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'slip'  => 'required|file',
            'tax'   => 'required|file',
        ]);

        $transaction = Transaction::findOrFail($id);
        $context = [];

        try {
            $slip           = $request->file('slip');
            $tax_invoice    = $request->file('tax');

            $result = $this->service->update($transaction, $slip, $tax_invoice);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id']   = $result->getKey();
                $context['model_type'] = get_class($result);
            }
                
            // Log success
            Log::info('Transaction updated.', $context);

            return redirect()->back()->with('success', 'Transaction updated.');
        }
        catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack']   = $th->getTraceAsString();

            // Log error
            Log::error('Failed to update transaction.', $context);
            
            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }
    
    public function destroy(Transaction $transaction)
    {
        // Delete transaction
    }

    public function get_data(Request $request)
    {
        $request->validate([
            'userid'    => ['nullable', 'integer', 'exists:user_profiles,id'],
            'year'      => ['nullable', 'string', 'max:4'],
            'quarter'   => ['nullable', 'integer', 'in:1,2,3,4'],
            'status'    => ['nullable', 'string', 'in:pending,completed,carried_over'],
        ]);
        
        $query = Transaction::query();
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

        $results    = $query->get();

        return response()->json([
            'total'     => number_format($results->sum('total'), 0, ',', '.'),
            'results'   => $results->map(function ($row) {
                return [
                    'id'            => $row->id,
                    'profile_name'  => $row->profile_name,
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
            
            $query = Transaction::query();
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
                    $row->profile_name,
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
                "Content-Disposition" => "attachment; filename=transactions.csv",
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
