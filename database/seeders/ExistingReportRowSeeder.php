<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Helpers\CountryMatcher;
use App\Models\Reporting\ReportRow;
use Monarobase\CountryList\CountryListFacade as CountryList;

class ExistingReportRowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $matcher    = new CountryMatcher();
        $regions    = ReportRow::distinct()->get(['region']);
        foreach($regions as $region) {

            $newRegion = $region->region;

            // Start checking the region
            if (strlen($region->region) > 2) {

                // Match region using helper
                $code = $matcher->match($region->region);
                if($code) {
                    $newRegion = $code;
                }
            }
            ReportRow::where('region', $region->region)->update(['country' => $newRegion]);
        }
    }
}
