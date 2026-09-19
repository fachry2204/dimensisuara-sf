<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

use App\Models\Contract;

class ContractMigrateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {

            // Delete deleted contracts
            DB::table('contracts')->whereNotNull('deleted_at')->delete();

            // Temporarily set contract number to other than 1-5
            foreach (Contract::where('number', '<=', '5')->get() as $contract) {
                $contract->number = random_int(101, 999);
                $contract->save();
            }

            // Get specific contracts
            $userids = [56,63,62,59,71];
            foreach ($userids as $index => $userid) {
                
                $contracts = Contract::select('contracts.*')
                    ->join('user_profiles', 'contracts.profileid', 'user_profiles.id')
                    ->join('users', 'user_profiles.userid', 'users.id')
                    ->where('users.id', $userid)
                    ->get();

                if($contracts->count() != 1) {
                    throw new \Exception("User ID " . $userid . " has " . $contracts->count() . " contracts");       
                }

                $contract = $contracts->first();
                $contract->type     = $contract->type == 'PKS-DMSV' ? 'aggregator' : $contract->type;
                $contract->year     = $contract->profile->created_at->year;
                $contract->month    = $contract->profile->created_at->month;
                $contract->number   = $index + 1;
                $contract->sort     = $contract->year . '-' . str_pad($contract->number, 3, '0', STR_PAD_LEFT);
                $contract->save();
            }

            $numbering = [
                'aggregator' => [
                    '2024' => [],
                    '2025' => [1,2,3,4,5],
                ]
            ];
            
            // Get all contracts
            $contracts = Contract::all();
            foreach ($contracts as $contract) {
                if(in_array($contract->profile->user->id, $userids) ) continue;

                $contract->type     = $contract->type == 'PKS-DMSV' ? 'aggregator' : $contract->type;

                $number = (end($numbering[$contract->type][$contract->profile->created_at->year]) ?? 0) + 1;
                $numbering[$contract->type][$contract->profile->created_at->year][] = $number;

                $contract->year     = $contract->profile->created_at->year;
                $contract->month    = $contract->profile->created_at->month;
                $contract->number   = $number;
                $contract->sort     = $contract->year . '-' . str_pad($contract->number, 3, '0', STR_PAD_LEFT);
                $contract->save();

            }

        });
    }
}
