<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use App\Models\Contract;
use App\Models\Publishing\Songwriter;

use App\Services\ContractService;

class PublishingQuerySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            foreach (Contract::all() as $contract) {
                $contract->docnum = ContractService::prettyDocNum($contract->type, $contract->number, $contract->month, $contract->year);
                $contract->save();
            }

            foreach (Songwriter::all() as $songwriter) {
                $songwriter->firstname  = Crypt::decryptString($songwriter->firstname)  ? Crypt::decryptString($songwriter->firstname)  : $songwriter->firstname;
                $songwriter->lastname   = $songwriter->lastname && Crypt::decryptString($songwriter->lastname)   ? Crypt::decryptString($songwriter->lastname)   : $songwriter->lastname;
                $songwriter->save();
            }
            
        });
    }
}
