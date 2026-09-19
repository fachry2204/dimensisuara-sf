<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Reporting\ReportRow;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\Item;


class ExistingTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $transactions = Transaction::all();
            foreach ($transactions as $transaction) {
                
                // Get all revenue
                $data = ReportRow::where('profileid', $transaction->profileid)
                    ->whereYear('reporting_month', $transaction->year)
                    ->whereMonth('reporting_month', '<=', $transaction->quarter * 3)
                    ->whereMonth('reporting_month', '>=', ($transaction->quarter - 1) * 3 + 1)
                    ->where('status', 'finalized')
                    ->get();
                    
                // Check if there is a mismatch
                if(round($data->sum('client_rupiah_exact')) != round($transaction->subtotal)) {
                    throw new \Exception("Transaction {$transaction->id} subtotal mismatch");
                    
                }
                
                // Add new values
                $transaction->rupiah_exact              = $data->sum('rupiah_exact');
                $transaction->aggregator_rupiah_exact   = $data->sum('aggregator_rupiah_exact');
                $transaction->client_rupiah_exact       = $data->sum('client_rupiah_exact');
                $transaction->save();

                // Fix items
                $items = $data->groupBy('reporting_month')->map(function ($row) {
                    return $row->sum('client_rupiah_exact');
                })->toArray();

                foreach($items as $month => $amount) {
                    $txitem = Item::where('transactionid', $transaction->id)->where('month', (int)date('m', strtotime($month)))->first();
                    if($txitem) {
                        $txitem->amount = $amount;
                        $txitem->save();
                    }
                }
            }
        });
    }
}
