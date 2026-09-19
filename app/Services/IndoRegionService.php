<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IndoRegionService
{
    public static function populate()
    {
        // Reset all tables
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('_provinces')->truncate();
        DB::table('_cities')->truncate();
        DB::table('_districts')->truncate();
        DB::table('_villages')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        DB::transaction(function () {

            // Define indexes
            $i1 = $i2 = $i3 = $i4 = 1;
            
            // Populate provinces
            $provinces = SELF::getData('/provinces.json');
            foreach ($provinces['data'] as $province) {
                DB::table('_provinces')->insert([
                    'id'            => $i1,
                    'code'          => $province['code'],
                    'name'          => $province['name'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                // Populate cities
                $cities = SELF::getData('/regencies/' . $province['code'] . '.json');
                foreach ($cities['data'] as $city) {
                    DB::table('_cities')->insert([
                        'id'            => $i2,
                        'provinceid'    => $i1,
                        'code'          => $city['code'],
                        'name'          => $city['name'],
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    // Populate districts
                    $districts = SELF::getData('/districts/' . $city['code'] . '.json');
                    foreach ($districts['data'] as $district) {
                        DB::table('_districts')->insert([
                            'id'            => $i3,
                            'cityid'        => $i2,
                            'code'          => $district['code'],
                            'name'          => $district['name'],
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);

                        // Populate villages
                        $villages = SELF::getData('/villages/' . $district['code'] . '.json');
                        foreach ($villages['data'] as $village) {
                            DB::table('_villages')->insert([
                                'id'            => $i4,
                                'districtid'    => $i3,
                                'code'          => $village['code'],
                                'name'          => $village['name'],
                                'created_at'    => now(),
                                'updated_at'    => now(),
                            ]);

                            $i4++;
                        }

                        $i3++;
                    }

                    $i2++;
                }

                $i1++;
            }
        });
    }

    private static function getData($url)
    {
        echo "CURL \n";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL             => env('WILAYAH_ID_URL') . $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => "",
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => "GET",
        ));
        $response   = curl_exec($curl);
        $err        = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new \Error("cURL Error: " . $err);
        }

        return json_decode($response, true);
    }
}
