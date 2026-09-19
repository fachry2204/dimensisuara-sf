<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Settlement\Settlement;

class SettlementController extends Controller
{
    public function index()
    {
        $settlements = Settlement::where('profileid', current_user()->profile->id)
            ->orderByDesc('year')->orderByDesc('quarter')->orderByDesc('id')->get();
        $transferredTotal = (float) $settlements->where('status', 'transferred')->sum('total');
        $pendingTotal = (float) $settlements->where('status', 'pending')->sum('total');
        $carriedTotal = (float) $settlements->where('status', 'carried_over')->sum('total');
        $currentSettlement = $settlements->firstWhere('status', 'pending')
            ?? $settlements->firstWhere('status', 'carried_over')
            ?? $settlements->firstWhere('status', 'transferred');

        return view('customer.settlement.index', compact(
            'settlements', 'transferredTotal', 'pendingTotal', 'carriedTotal', 'currentSettlement'
        ));
    }

    public function show($id)
    {
        try {
            $settlement = Settlement::with(['items', 'profile', 'slip', 'tax_invoice'])
                ->where('profileid', current_user()->profile->id)->findOrFail($id);
            return view('customer.settlement.show', compact('settlement'));
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('settlements.index')->withErrors('Transaction ID ' . $id . ' not found');
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }
}
