<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

use App\Exceptions\Settlement\InvalidStatusException;
use App\Exceptions\Settlement\InvalidSettlementDocTypeException;
use App\Exceptions\Settlement\SubtotalMissmatchException;
use App\Exceptions\Settlement\SessionDataNotFoundException;
use App\Models\Reporting\ReportRow;
use App\Models\Settlement\Settlement;
use App\Models\Settlement\Item;
use App\Models\User\Profile;
use App\Notifications\Customer\Settlement\Completed;

use App\Services\FileService;

class SettlementService
{
    /**
     * Settlement generation
     */
    public function generateAggregatorSettlements($year, $quarter, $include_carryover = false, $allow_empty = false, $minimum = 0)
    {
        session()->forget('generated_aggregator_settlements');

        $profiles = Profile::where('status', 'verified')->get();
        $settlements = [];

        foreach($profiles as $profile) {

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

                return collect([
                    'settlementid'      => null,
                    'profileid'         => $profile->id,
                    'type'              => 'revenue',
                    'relid'             => null,
                    'description'       => 'Royalty ' . date('F Y', strtotime($monthKey . '-01')),
                    'gross'             => $rows->sum('rupiah_exact'),
                    'aggregator'        => $rows->sum('aggregator_rupiah_exact'),
                    'amount'            => $rows->sum('client_rupiah_exact'),
                    'taxed'             => true,
                    'report_row_ids'    => $rows->pluck('id')->values(),
                ]);
            })->values();

            // Check if total amount of settlement items is equal to total client rupiah of corresponding row
            if (round($newItems->sum('amount'), 2) != round($data->sum('client_rupiah_exact'), 2)) {
                throw new SubtotalMissmatchException($data->sum('client_rupiah_exact'), $newItems->sum('amount'));
            }

            $oldItems           = collect();
            $carried_over_ids   = collect();
            
            // Include carryover
            if($include_carryover) {

                // Get carry over from previous settlements
                $carried_overs = Settlement::with('items')
                    ->where('profileid', $profile->id)
                    ->where('type', 'aggregator')
                    ->where('status', 'carried_over')
                    ->where('skip_carryover_check', false)
                    ->where(function ($q) use ($year, $quarter) {
                        $q->where('year', '<', $year)
                        ->orWhere(function ($q2) use ($year, $quarter) {
                            $q2->where('year', $year)
                                ->where('quarter', '<', $quarter);
                        });
                    })
                    ->orderBy('id', 'asc')
                    ->get();
    
                // Add carry over items
                $carried_over_ids = $carried_overs->pluck('id')->values();
                
                foreach ($carried_overs as $carried_over) {
                    foreach ($carried_over->items as $carried_over_item) {
                        
                        if(in_array($carried_over_item->type,['revenue','carried_over'])) {
                            $carried_over_item->type = 'carried_over';
                            $oldItems->push($carried_over_item);
                        }
                    }
                }
            }

            $items = $oldItems->merge($newItems);
          
            // Calculate subtotal
            $subtotal = $items->sum('amount');

            // Skip if unpaid revenue is zero
            if(!$allow_empty && round($subtotal, 0) == 0) {
                continue;
            }

            // Calculate tax
            $taxrate    = $profile->is_company ? -2.000 : -2.500;
            $taxrate2   = 0.000;
            $tax        = $subtotal * $taxrate / 100;
            $tax2       = $subtotal * $taxrate2 / 100;
            $total      = $subtotal + $tax + $tax2;
            
            // Build settlement
            $settlements[] = [
                'recipient'             => $profile,
                'recipient_id'          => $profile->id,
                'recipient_type'        => get_class($profile),
                'profileid'             => $profile->id,
                'type'                  => 'aggregator',
                'quarter'               => $quarter,
                'year'                  => $year,
                'gross'                 => $data->sum('rupiah_exact'),
                'aggregator'            => $data->sum('aggregator_rupiah_exact'),
                'revenue'               => $items->where('type', 'revenue')->sum('amount'),
                'carryover'             => $items->where('type', 'carried_over')->sum('amount'),
                'subtotal'              => $subtotal,
                'tax'                   => $tax,
                'tax2'                  => $tax2,
                'total'                 => $total,
                'taxrate'               => $taxrate,
                'taxrate2'              => $taxrate2,
                'status'                => $total < $minimum ? 'carried_over' : 'pending',
                'skip_carryover_check'  => $total < $minimum ? false : true,
                'items'                 => $items,
                'carried_over_ids'      => $carried_over_ids
            ];
        }

        $settlements = collect($settlements)->sortBy('recipient.full_name');
        if($settlements->count() > 0) {
            session(['generated_aggregator_settlements' => $settlements]);
        }

        return $settlements;
    }

    public function finalizeAggregatorSettlements()
    {
        $settlements = session('generated_aggregator_settlements');
        if(!($settlements)) {
            throw new SessionDataNotFoundException();
        }

        DB::transaction(function () use ($settlements) {
            
            $rowIds = [];
            foreach($settlements as $settlement) {

                // Create settlement
                $stl = Settlement::create($settlement);

                if(count($settlement['items']) > 0) {
                    foreach($settlement['items'] as $item) {

                        // Pool report row ids
                        if(isset($item['report_row_ids'])) {
                            $rowIds = array_merge($rowIds, $item['report_row_ids']->toArray());
                            unset($item['report_row_ids']);
                        }
                        
                        // Create settlement items
                        $stl->items()->create($item->toArray());
                    }
                }

                // Update carried over settlements
                if(count($settlement['carried_over_ids']) > 0) {
                    Settlement::whereIn('id', $settlement['carried_over_ids'])
                        ->update([
                            'skip_carryover_check' => true
                        ]);
                }
            }

            // Update report rows
            ReportRow::whereIn('id', $rowIds)->update(['generated' => true]);
        });

        session()->forget('generated_aggregator_settlements');
    }

    /**
     * Settlement editing
     */
    public function update(Settlement $settlement, $data)
    {
        $settlement->update($data);
    }

    /**
     * Settlement status
     */
    public function complete(Settlement $settlement)
    {
        $updated = DB::transaction(function () use ($settlement) {
            
            if(!in_array($settlement->status, ['pending'])) {
                throw new InvalidStatusException($settlement->status, ['pending']);
            }
    
            $settlement->status = 'transferred';
            $settlement->save();
    
            return $settlement;
        });

        // Send email
        $user = $settlement->profile->user;
        if($user) {
            $user->notify(new Completed($settlement));
        }


        return $updated;
    }

    public function cancel(Settlement $settlement)
    {
        return DB::transaction(function () use ($settlement) {
            
            if(!in_array($settlement->status, ['pending'])) {
                throw new InvalidStatusException($settlement->status, ['pending']);
            }
    
            $settlement->status = 'cancelled';
            $settlement->save();
    
            return $settlement;
        });
    }

    /**
     * Settlement files update
     */
    public function upload($settlement, $type, $file)
    {
        if(!in_array($type, ['tax', 'slip'])) {
            throw new InvalidSettlementDocTypeException($type, ['tax', 'slip']);
        }
        FileService::store($file, $settlement, $settlement->profile, $type, 'settlements');

        return $settlement;
    }

}
