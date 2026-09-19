<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\User\Profile;
use App\Models\Transaction\Transaction;

use App\Exceptions\Settlement\SubtotalMissmatchException;


use App\Models\Reporting\ReportRow;
use App\Models\Reporting\ReportImport;
use App\Models\Settlement\Settlement;
use App\Models\Settlement\Item;

class SettlementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {

            $settlements = [];

            // Generate settlements data based on transactions
            foreach(Transaction::all() as $transaction) {

                $items = [];

                // Get existing items into new format
                foreach ($transaction->items as $item) {
                    $items[] = [
                        'settlementid'      => null,
                        'profileid'         => $transaction->profileid,
                        'type'              => 'revenue',
                        'relid'             => null,
                        'description'       => 'Royalty ' . date('F Y', strtotime($transaction->year . '-' . $item->month . '-01')),
                        'gross'             => 0,
                        'aggregator'        => 0,
                        'amount'            => $item->amount,
                        'taxed'             => true,
                    ];
                }

                // Create carry over as item
                if($transaction->carryover > 0) {
                    $items[] = [
                        'settlementid'      => null,
                        'profileid'         => $transaction->profileid,
                        'type'              => 'carried_over',
                        'relid'             => null,
                        'description'       => 'Carry over',
                        'gross'             => 0,
                        'aggregator'        => 0,
                        'amount'            => $transaction->carryover,
                        'taxed'             => true,
                    ];
                }

                // Convert to collection
                $items = collect($items);

                // Check if total amount of settlement items is equal to total client rupiah of corresponding row
                $txSubtotal = $transaction->subtotal + $transaction->carryover;
                if (round($items->sum('amount'), 2) != round($txSubtotal, 2)) {

                    // Try to generate a manual settlement
                    $stl = SELF::generateManual($transaction->profile, $transaction, $transaction->year, $transaction->quarter, true, $txSubtotal);
                    $items = collect($stl['items']);

                    if (round($items->sum('amount'), 2) != round($txSubtotal, 2)) {
                        throw new SubtotalMissmatchException($items->sum('amount'), $txSubtotal);
                    }
                    else {
                        $settlements[] = $stl;
                    }
                }
                else {
                    $settlements[] = [
                        'recipient_id'          => $transaction->profileid,
                        'recipient_type'        => 'App\Models\User\Profile',
                        'profileid'             => $transaction->profileid,
                        'type'                  => 'aggregator',
                        'quarter'               => $transaction->quarter,
                        'year'                  => $transaction->year,
                        'gross'                 => $transaction->rupiah_exact,
                        'aggregator'            => $transaction->aggregator_rupiah_exact,
                        'subtotal'              => $items->sum('amount'),
                        'tax'                   => $transaction->tax,
                        'tax2'                  => 0,
                        'total'                 => $transaction->total,
                        'taxrate'               => -2.5,
                        'taxrate2'              => 0,
                        'status'                => $transaction->status == 'completed' ? 'transferred' : $transaction->status,
                        'skip_carryover_check'  => true,
                        'items'                 => $items,
                        'slip'                  => $transaction->slip,
                        'tax_invoice'           => $transaction->tax_invoice
                    ];
                }
            }

            // Create settlements
            foreach($settlements as $settlement) {

                $slip = $items = $tax = null;

                if (isset($settlement['items'])) {
                    $items = $settlement['items'];
                }
                if (isset($settlement['slip'])) {
                    $slip = $settlement['slip'];
                }
                if (isset($settlement['tax_invoice'])) {
                    $tax = $settlement['tax_invoice'];
                }

                unset($settlement['items']);
                unset($settlement['slip']);
                unset($settlement['tax_invoice']);

                // Create settlement
                $stl = Settlement::create($settlement);

                if(count($items) > 0) {
                    foreach($items as $item) {
                        
                        // Create settlement items
                        $stl->items()->create($item);
                    }
                }

                // Reattach slip and tax invoice
                if($slip) {
                    $slip->owner_id = $stl->id;
                    $slip->owner_type = 'App\Models\Settlement\Settlement';
                    $slip->save();
                }
                if($tax) {
                    $tax->owner_id = $stl->id;
                    $tax->owner_type = 'App\Models\Settlement\Settlement';
                    $tax->save();
                }

            }

            // Mark completed report as settled/generated
            $reportImports = ReportImport::where('status','completed')->pluck('id');
            ReportRow::whereIn('report_import_id', $reportImports)->update(['generated' => true]);

        });
    }

    private static function generateManual($profile, $transaction, $year, $quarter, $allow_empty = false, $minimum = 0) {
        
        // Get all revenue within the quarter
        $data = ReportRow::where('profileid', $profile->id)
            ->whereYear('reporting_month', $year)
            ->whereMonth('reporting_month', '<=', $quarter * 3)
            ->whereMonth('reporting_month', '>=', ($quarter - 1) * 3 + 1)
            ->where('status', 'finalized')
            ->where('generated', false)
            ->get();

        // Define the months in this quarter
        $startMonth = ($quarter - 1) * 3 + 1;
        $months = collect(range($startMonth, $startMonth + 2))->map(function ($month) use ($year) {
            return sprintf('%04d-%02d', $year, $month); // e.g. "2025-01"
        });

        // Group report rows by month (actual data)
        $groupedData = $data->groupBy(function ($item) {
            return date('Y-m', strtotime($item->reporting_month));
        });

        // Build settlement items (one for each month in the quarter)
        $newItems = $months->map(function ($monthKey) use ($groupedData, $profile) {
            $rows = $groupedData->get($monthKey, collect());

            return [
                'settlementid'      => null,
                'profileid'         => $profile->id,
                'type'              => 'revenue',
                'relid'             => null,
                'description'       => 'Royalty ' . date('F Y', strtotime($monthKey . '-01')),
                'gross'             => $rows->sum('rupiah_exact'),
                'aggregator'        => $rows->sum('aggregator_rupiah_exact'),
                'amount'            => $rows->sum('client_rupiah_exact'),
                'taxed'             => true,
            ];
        })->values();

        // Check if total amount of settlement items is equal to total client rupiah of corresponding row
        if (round($newItems->sum('amount'), 2) != round($data->sum('client_rupiah_exact'), 2)) {
            throw new SubtotalMissmatchException($data->sum('client_rupiah_exact'), $newItems->sum('amount'));
        }

        $oldItems = collect();
        if($transaction->carryover) {
            $oldItems->push([
                'settlementid'      => null,
                'profileid'         => $profile->id,
                'type'              => 'carried_over',
                'relid'             => null,
                'description'       => 'Carry over',
                'gross'             => 0,
                'aggregator'        => 0,
                'amount'            => $transaction->carryover,
                'taxed'             => true,
            ]);
        }

        $items = $oldItems->merge($newItems);
        
        // Calculate subtotal
        $subtotal = $items->sum('amount');

        // Calculate tax
        $taxrate    = $profile->is_company ? -2.000 : -2.500;
        $taxrate2   = 0.000;
        $tax        = $subtotal * $taxrate / 100;
        $tax2       = $subtotal * $taxrate2 / 100;
        $total      = $subtotal + $tax + $tax2;
        
        // Build settlement
        return [
            'recipient_id'          => $profile->id,
            'recipient_type'        => get_class($profile),
            'profileid'             => $profile->id,
            'type'                  => 'aggregator',
            'quarter'               => $quarter,
            'year'                  => $year,
            'gross'                 => $data->sum('rupiah_exact'),
            'aggregator'            => $data->sum('aggregator_rupiah_exact'),
            'subtotal'              => $subtotal,
            'tax'                   => $tax,
            'tax2'                  => $tax2,
            'total'                 => $total,
            'taxrate'               => $taxrate,
            'taxrate2'              => $taxrate2,
            'status'                => $total < $minimum ? 'carried_over' : 'pending',
            'skip_carryover_check'  => true,
            'items'                 => $items,
        ];
        
    }
}
