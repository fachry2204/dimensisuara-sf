<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

use App\Exceptions\Transaction\GeneratedDataNotFoundException;

use App\Models\Reporting\ReportRow;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\Item;
use App\Models\User\Profile;

use App\Services\FileService;

class TransactionService
{
    public static function generate($year, $quarter, $allow_empty = false, $minimum = 0)
    {
        session()->forget('generated_transactions');

        $profiles = Profile::where('status', 'verified')->get();
        $transactions = [];

        foreach($profiles as $profile) {

            // Skip if there is a transaction of the same year and quarter
            $past = Transaction::where('profileid', $profile->id)
                ->where('year', $year)
                ->where('quarter', $quarter)
                ->exists();

            if($past) {
                continue;
            }

            // Get all revenue
            $data = ReportRow::where('profileid', $profile->id)
                ->whereYear('reporting_month', $year)
                ->whereMonth('reporting_month', '<=', $quarter * 3)
                ->whereMonth('reporting_month', '>=', ($quarter - 1) * 3 + 1)
                ->where('status', 'finalized')
                ->get();

            // Get carry over from previous transaction
            $previous = Transaction::where('profileid', $profile->id)
                ->latest()
                ->first();

            // Calculate subtotal
            $subtotal   = $data->sum('client_rupiah_exact');
            $carryover  = $previous?->status == 'carried_over' ? $previous?->carryover + $previous?->subtotal : 0;

            // Skip if unpaid revenue is zero
            if(!$allow_empty && round($subtotal, 0) == 0) {
                continue;
            }

            $tax            = (($subtotal + $carryover) / 2) * 0.05;
            $total          = ($subtotal + $carryover) - $tax;
            $transactions[] = [
                'profile'                   => $profile,
                'rupiah_exact'              => $data->sum('rupiah_exact'),
                'aggregator_rupiah_exact'   => $data->sum('aggregator_rupiah_exact'),
                'client_rupiah_exact'       => $data->sum('client_rupiah_exact'),
                'subtotal'                  => $subtotal,
                'carryover'                 => $carryover,
                'tax'                       => $tax,
                'total'                     => $total,
                'status'                    => $subtotal + $carryover > $minimum ? 'pending' : 'carried_over',
                'items'                     => $data->groupBy('reporting_month')->map(function ($row) {
                    return $row->sum('client_rupiah_exact');
                })->toArray(),
            ];
        }

        $transactions = collect($transactions);
        if($transactions->count() > 0) {
            session(['generated_transactions' => $transactions]);
        }

        return $transactions;
    }

    public static function finalize($year, $quarter)
    {
        $transactions = session('generated_transactions');
        if(!($transactions)) {
            throw new GeneratedDataNotFoundException("Transaction not found");
        }

        DB::transaction(function () use ($transactions, $year, $quarter) {
            foreach($transactions as $transaction) {
                $trx = Transaction::create([
                    'profileid' => $transaction['profile']->id,
                    'quarter'   => $quarter,
                    'year'      => $year,
                    'subtotal'  => $transaction['subtotal'],
                    'carryover' => $transaction['carryover'],
                    'tax'       => $transaction['tax'],
                    'total'     => $transaction['total'],
                    'status'    => $transaction['status'],
                ]);

                if(count($transaction['items']) > 0) {
                    foreach($transaction['items'] as $month => $amount) {
                        $trx->items()->create([
                            'month'     => (int)date('m', strtotime($month)),
                            'amount'    => $amount,
                            'profileid' => $transaction['profile']->id
                        ]);
                    }
                }
            }
        });
        
        session()->forget('generated_transactions');
    }

    public static function update(Transaction $transaction, $slip, $tax_invoice)
    {
        return DB::transaction(function () use ($transaction, $slip, $tax_invoice) {
            FileService::store($slip, $transaction, $transaction->profile, 'slip', 'transactions');
            FileService::store($tax_invoice, $transaction, $transaction->profile, 'tax_invoice', 'transactions');
            $transaction->status = 'completed';
            $transaction->save();

            return $transaction;
        });
    }
}
