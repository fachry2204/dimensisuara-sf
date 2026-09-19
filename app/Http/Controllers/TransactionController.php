<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Transaction\Transaction;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::where('profileid', current_user()->profile->id)->get();
        return view('customer.transaction.index', compact('transactions'));
    }

    public function show($id)
    {
        try {
            $transaction = Transaction::where('profileid', current_user()->profile->id)->findOrFail($id);
            return view('customer.transaction.show', compact('transaction'));
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('transaction.index')->withErrors('Transaction ID ' . $id . ' not found');
        } 
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }
}
