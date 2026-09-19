<?php

namespace App\Services;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

use App\Helpers\CountryMatcher;
use App\Models\Release;
use App\Models\Track;
use App\Models\User\Profile;

use App\Models\Reporting\ReportImport;
use App\Models\Reporting\ReportRow;

use App\Services\FileService;
use App\Jobs\Reporting\ProcessAggregate;
use App\Jobs\Reporting\ProcessCalculationPerBatch;
use App\Jobs\Reporting\ProcessDetectionPerBatch;

class ReportService
{
    /**
     * Import report excel
     */
    public static function import($file, $source, $sheetname = null)
    {
        DB::transaction(function () use ($file, $source, $sheetname) {

            if($source == 'believe') {
                $excel = SELF::validateExcelFileBelieve($file, $sheetname);
            }
            else if ($source == 'soundon') {
                $excel = SELF::validateExcelFileSoundon($file, $sheetname);
            }
            else if ($source == 'lokamusik') {
                $excel = SELF::validateExcelFileLokamusik($file, $sheetname);
            }
            else {
                throw new \Exception("Invalid source");
            }

            // Validate excel file and get attributes and content
            $highestRow     = $excel['highestRow'];
            $highestColumn  = $excel['highestColumn'];
            $map            = $excel['map'];
            $sheet          = $excel['sheet'];
            
            // Get content
            if($source == 'believe') {
                $content = SELF::getExcelFileDataBelieve($sheet, $highestRow, $highestColumn, $map);
            }
            else if ($source == 'soundon') {
                $content = SELF::getExcelFileDataSoundon($sheet, $highestRow, $highestColumn, $map);
            }
            else if ($source == 'lokamusik') {
                $content = SELF::getExcelFileDataLokamusik($sheet, $highestRow, $highestColumn, $map);
            }
            else {
                throw new \Exception("Invalid source");
            }
    
            // Create report import
            $timestamp      = Carbon::now()->format('Ymd-His_');
            $report_import  = ReportImport::create([
                'userid'            => auth()->user()->id,
                'source'            => $source,
                'original_filename' => $timestamp . $file->getClientOriginalName(),
                'sheet'             => $sheetname,
                'status'            => 'pending_import',
                'total_rows'        => count($content),
            ]);
    
            // Create report rows
            foreach($content as $row) {
                $report_import->rows()->create($row);
            }
            
            // Store report file
            FileService::store($file, $report_import, null, null, 'reports');

            // Prepare jobs for batch
            $jobs = [];
            ReportRow::where('report_import_id', $report_import->id)
                ->chunk(500, function ($chunk) use (&$jobs) {
                    $jobs[] = new ProcessDetectionPerBatch($chunk->pluck('id')->toArray());
                });

            // Dispatch the batch
            $batch = Bus::batch($jobs)
                ->then(function ($batch) use ($report_import) {
                    $report_import->update([
                        'status'    => 'pending_review',
                        'batch_id'  => null
                    ]);

                    $batch->delete();

                    Log::info("Report import ID {$report_import->id}, Batch {$batch->id} completed");
                })
                ->catch(function ($batch, $e) use ($report_import) {
                    $report_import->update(['status' => 'failed']);
                    Log::error("Report import ID {$report_import->id}, Batch {$batch->id} failed: {$e->getMessage()}");
                })
                ->name("Report import ID {$report_import->id}")
                ->dispatch();

            // Save batch_id to the report for tracking
            $report_import->update(['batch_id' => $batch->id]);
        });

        return true;
    }

    /**
     * Get track from ISRC
     */
    public static function detectPerBatch(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            $matcher = new CountryMatcher();

            // Calculate aggregator and client share
            foreach($rows as $row) {

                // Get profile from ISRC
                $track = Track::where('isrc', $row->isrc)->first();
    
                if($track) {
                    $row->trackid   = $track->id;
                    $row->profileid = $track->profile->id;
                    $row->status    = 'finalized';
                }
                else {
                    $row->status    = 'incomplete';
                }

                // Set country
                $row->country = $matcher->match($row->region) ?? $row->region;   
                $row->save();
            }
        });
    }

    /**
     * Calculate rupiah revenue, and aggregator and client share
     */
    public static function calculatePerBatch(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            // Calculate aggregator and client share
            foreach($rows as $row) {

                // Getting report import/batch
                $report = $row->batch;
                if(!$report->idr_rate) {
                    throw new \Exception("IDR rate is not set for this batch");
                }

                // Get IDR value using rate
                $row->idr_rate                  = $report->idr_rate;
                $row->rupiah_exact              = (float) ($row->idr_rate * $row->net_revenue);
                $row->rupiah                    = round($row->rupiah_exact);
                $row->save();

                // Process calculation if profile, aggregator rate, and IDR rate is set
                if($row->profile && $row->profile->aggregator_rate && $report->idr_rate) {

                    // Calculate aggregator and client share
                    $row->rate                      = $row->profile->aggregator_rate;
                    $row->client_rupiah_exact       = $row->rupiah_exact * ($row->rate / 100);
                    $row->aggregator_rupiah_exact   = $row->rupiah_exact - $row->client_rupiah_exact;
                    $row->client_rupiah             = round($row->client_rupiah_exact);
                    $row->aggregator_rupiah         = round($row->aggregator_rupiah_exact);
                    $row->save();
                }
            }
        });
    }

    /**
     * Finalize report, allow report to be included in aggregate
     */
    public static function finalize(ReportImport $report)
    {
        $calculated = DB::transaction(function () use ($report) {

            // Process calculation if IDR rate is set
            if($report->idr_rate) {
                $report->status = 'pending_calculation';
                $report->save();

                $jobs = [];
                ReportRow::where('report_import_id', $report->id)
                    ->chunk(500, function ($chunk) use (&$jobs) {
                        $jobs[] = new ProcessCalculationPerBatch($chunk->pluck('id')->toArray());
                    });

                // Dispatch the batch
                $batch = Bus::batch($jobs)
                    ->then(function ($batch) use ($report) {
                        $report->update([
                            'status'    => 'completed',
                            'batch_id'  => null
                        ]);

                        $batch->delete();
                        ProcessAggregate::dispatch();
                        Log::info("Report calculation ID {$report->id}, Batch {$batch->id} completed");
                    })
                    ->catch(function ($batch, $e) use ($report) {
                        $report->update(['status' => 'failed']);
                        Log::error("Report calculation ID {$report->id}, Batch {$batch->id} failed: {$e->getMessage()}");
                    })
                    ->name("Report calculation ID {$report->id}")
                    ->dispatch();

                // Save batch_id to the report for tracking
                $report->update(['batch_id' => $batch->id]);
                return true;
            }

            if($report->status == 'pending_rate') {
                throw new \Exception("Report is already finalized. Please provide IDR rate to calculate revenue.");
            }
            
            // Update report status
            $report->status = 'pending_rate';
            $report->save();

            return false;
        });

        if(!$calculated) {
            // Run aggregate now
            ProcessAggregate::dispatch();
        }

        return $calculated;
    }

    /**
     * Recheck incomplete report rows
     */
    public static function recheck()
    {
        $count = DB::transaction(function () {
            $count = 0;
    
            // Get all incomplete rows
            $rows = ReportRow::where('status', 'incomplete')->get();
    
            foreach($rows as $row) {
                // Get profile from ISRC
                $track = Track::where('isrc', $row->isrc)->first();
    
                // Calculate aggregator and client share
                if($track) {
                    $row->trackid   = $track->id;
                    $row->profileid = $track->profile->id;
                    $row->status = 'finalized';

                    if($row->batch->idr_rate && $track->profile->aggregator_rate) {
                        $row->rate                      = $track->profile->aggregator_rate;
                        $row->client_rupiah_exact       = $row->rupiah_exact * ($row->rate / 100);
                        $row->aggregator_rupiah_exact   = $row->rupiah_exact - $row->client_rupiah_exact;
                        $row->client_rupiah             = round($row->client_rupiah_exact);
                        $row->aggregator_rupiah         = round($row->aggregator_rupiah_exact);
                    }
                    $row->save();
                    $count++;
                }
            }
            return $count;
        });

        if($count > 0) {
            // Run aggregate now
            ProcessAggregate::dispatch();
        }

        return $count;
    }

    public static function aggregate()
    {
        DB::transaction(function () {

            // Multi dimensional aggregate - Process in chunks to avoid memory issues
            ReportRow::baseAggregateQuery()
                ->orderBy('profileid')
                ->orderBy('trackid')
                ->chunk(1000, function ($rows) {
                $batchData = [];

                foreach($rows as $row) {
                    $batchData[] = [
                        'profileid'                 => $row->profileid,
                        'trackid'                   => $row->trackid,
                        'platform'                  => $row->platform,
                        'country'                   => $row->country,
                        'sales_type'                => $row->sales_type ?? 'Stream_Failback',
                        'reporting_month'           => $row->reporting_month,
                        'sales_month'               => $row->sales_month,
                        'artist_name'               => $row->artist_name,
                        'quantity'                  => $row->quantity,
                        'rupiah_exact'              => $row->rupiah_exact,
                        'aggregator_rupiah_exact'   => $row->aggregator_rupiah_exact,
                        'client_rupiah_exact'       => $row->client_rupiah_exact
                    ];
                }

                // Batch upsert - much more efficient than individual updateOrInsert
                if (!empty($batchData)) {
                    DB::table('report_aggregate_multi')->upsert(
                        $batchData,
                        ['profileid', 'trackid', 'platform', 'country', 'sales_type', 'reporting_month', 'sales_month', 'artist_name'],
                        ['quantity', 'rupiah_exact', 'aggregator_rupiah_exact', 'client_rupiah_exact']
                    );
                }
            });

            $aggregates = [
                'tracks'    => 'trackid',
                'platforms' => 'platform',
                'countries' => 'country',
                'artists'   => 'artist_name',
                'sales'     => 'sales_type',
                'monthly'   => ''
            ];

            foreach($aggregates as $table => $column) {
                // Build query based on table type
                if($table == 'monthly') {
                    $query = DB::table('report_aggregate_multi')
                        ->select(
                            'profileid',
                            'reporting_month',
                            'sales_month',
                            DB::raw('SUM(quantity) as quantity'),
                            DB::raw('SUM(rupiah_exact) as rupiah_exact'),
                            DB::raw('SUM(aggregator_rupiah_exact) as aggregator_rupiah_exact'),
                            DB::raw('SUM(client_rupiah_exact) as client_rupiah_exact'),
                        )
                        ->groupBy('profileid', 'reporting_month', 'sales_month')
                        ->orderBy('profileid')
                        ->orderBy('reporting_month');
                }
                else {
                    $query = DB::table('report_aggregate_multi')
                        ->select(
                            'profileid',
                            $column,
                            'reporting_month',
                            'sales_month',
                            DB::raw('SUM(quantity) as quantity'),
                            DB::raw('SUM(rupiah_exact) as rupiah_exact'),
                            DB::raw('SUM(aggregator_rupiah_exact) as aggregator_rupiah_exact'),
                            DB::raw('SUM(client_rupiah_exact) as client_rupiah_exact'),
                        )
                        ->groupBy('profileid', $column, 'reporting_month', 'sales_month')
                        ->orderBy('profileid')
                        ->orderBy($column);
                }

                // Process in chunks and batch upsert for performance
                $query->chunk(1000, function ($aggregate) use ($table, $column) {
                    $batchData = [];

                    foreach($aggregate as $row) {
                        $rowData = [
                            'profileid'                 => $row->profileid,
                            'reporting_month'           => $row->reporting_month,
                            'sales_month'               => $row->sales_month,
                            'quantity'                  => $row->quantity,
                            'rupiah_exact'              => $row->rupiah_exact,
                            'aggregator_rupiah_exact'   => $row->aggregator_rupiah_exact,
                            'client_rupiah_exact'       => $row->client_rupiah_exact,
                        ];

                        // Add dimension column if not monthly aggregate
                        if($table != 'monthly') {
                            $rowData[$column] = $row->$column;
                        }

                        $batchData[] = $rowData;
                    }

                    // Batch upsert - much more efficient than individual updateOrInsert
                    if (!empty($batchData)) {
                        $uniqueKeys = $table == 'monthly'
                            ? ['profileid', 'reporting_month', 'sales_month']
                            : ['profileid', $column, 'reporting_month', 'sales_month'];

                        DB::table('report_aggregate_'.$table)->upsert(
                            $batchData,
                            $uniqueKeys,
                            ['quantity', 'rupiah_exact', 'aggregator_rupiah_exact', 'client_rupiah_exact']
                        );
                    }
                });
            }

        });
    
    }

    /**
     * Private functions
     */
    private static function validateExcelFileBelieve($file, $sheetname = null)
    {
        $spreadsheet    = IOFactory::load($file);
        $sheet          = $sheetname ? $spreadsheet->getSheetByName($sheetname) : $spreadsheet->getSheet(0);

        if(!$sheet) {
            throw new \Exception("Sheet not found");
        }

        $highestRow     = $sheet->getHighestRow();
        $highestColumn  = $sheet->getHighestColumn();
        $map = [];
        
        // Detect row header
        $map_row = [
            'Reporting month'               => 'reporting_month',
            'Sales Month'                   => 'sales_month',
            'Platform'                      => 'platform',
            'Country / Region'              => 'region',
            'Label Name'                    => 'label_name',
            'Artist Name'                   => 'artist_name',
            'Release title'                 => 'release_title',
            'Track title'                   => 'track_title',
            'UPC'                           => 'upc',
            'ISRC'                          => 'isrc',
            'Release Catalog nb'            => 'release_catalog',
            'Streaming Subscription Type'   => 'subscription_type',
            'Release type'                  => 'release_type',
            'Sales Type'                    => 'sales_type',
            'Quantity'                      => 'quantity',
            'Client Payment Currency'       => 'currency_code',
            'Unit Price'                    => 'unit_price',        //
            'Mechanical Fee'                => 'mechanical_fee',    //
            'Gross Revenue'                 => 'gross_revenue',     //
            'Client share rate'             => 'client_share_rate', //
            'Net Revenue'                   => 'net_revenue',
        ];

        for ($col = 'A'; $col <= $highestColumn; ++$col) {
            $cell   = $sheet->getCell($col . '1');
            $value  = $cell->getFormattedValue();

            if(is_null($value)) {
                continue;
            }

            // Map row header
            if(isset($map_row[$value])) {
                $map[$col] = $map_row[$value];
            }
        }

        // Make sure required columns are present
        $mustPresent = ['reporting_month','sales_month','platform','region','artist_name','release_title','track_title','upc','isrc','quantity','net_revenue'];
        $missing = array_filter($mustPresent, fn($value) => !in_array($value, $map));
        if (!empty($missing)) {
            throw new \Exception('Missing column in excel: ' . implode(', ', array_map(function($value) use ($map_row) {
                return array_search($value, $map_row);
            }, $missing)));
        }

        return [
            'sheet'         => $sheet,
            'map'           => $map,
            'highestRow'    => $highestRow,
            'highestColumn' => $highestColumn
        ];
    }

    private static function getExcelFileDataBelieve($sheet, $highestRow, $highestColumn, $map)
    {
        // Loop through each column in the row
        $content = [];
        for ($row = 2; $row <= $highestRow; ++$row) {
            $rowData = [];
            for ($col = 'A'; $col <= $highestColumn; ++$col) {

                // Skip if column not mapped
                if(!isset($map[$col])) {
                    continue;
                }

                $cell   = $sheet->getCell($col . $row);
                $value  = $cell->getValue();

                // Check critical values
                if(in_array($map[$col], ['reporting_month', 'sales_month', 'platform', 'region', 'upc', 'isrc', 'quantity', 'net_revenue'])) {
                    if ($value === null || $value === '') {
                        throw new \Exception("Empty value for column $map[$col] at row $row");
                    }
                }
                
                // Check first and second column
                if($map[$col] == 'reporting_month' || $map[$col] == 'sales_month') {
                    if(is_null($value)) {
                        continue 2;
                    }

                    // Detect and parse date if string
                    if(is_string($value)) {
                        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $value)) {
                            $value = Carbon::createFromFormat('Y/m/d', $value)->format('Y-m-d');
                        }
                        else {
                            throw new \Exception("Invalid date format: $value at row $row");
                        }
                    }
                    // Parse date if numeric
                    else {
                        $value = (new \DateTime())->setTimestamp(Date::excelToTimestamp($value))->format('Y-m-d');
                    }
                }

                // Check quantity column
                elseif($map[$col] == 'quantity') {

                    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                        throw new \Exception("Invalid quantity: $value at row $row");
                    }

                    $value = (int) $value;
                }
                
                // Check UPC column
                elseif(in_array($map[$col],['platform','region','label_name','artist_name','release_title','track_title','upc','isrc'])) {
                    $value = (string) $value;
                }

                elseif(in_array($map[$col],['rupiah_exact','client_share_rate','net_revenue'])) {
                    $value = $cell->getCalculatedValue();

                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', '', $value);
                        $value = str_replace(',', '.', $value);
                        $value = floatval($value);
                    }
                }

                // Remove science notation
                if (is_numeric($value) && stripos((string) $value, 'E') !== false) {
                    $value = rtrim(rtrim(sprintf('%.15f', $value), '0'), '.');
                }

                // Remove leading and trailing spaces
                $rowData[$map[$col]] = is_string($value) ? trim($value) : $value;
            }
            $rowData['status'] = 'pending';
            $content[] = $rowData;
        }

        return $content;
    }



    private static function validateExcelFileSoundon($file, $sheetname = null)
    {
        $spreadsheet    = IOFactory::load($file);
        $sheet          = $sheetname ? $spreadsheet->getSheetByName($sheetname) : $spreadsheet->getSheet(0);

        if(!$sheet) {
            throw new \Exception("Sheet not found");
        }

        $highestRow     = $sheet->getHighestRow();
        $highestColumn  = $sheet->getHighestColumn();
        $map = [];
        
        // Detect row header
        $map_row = [
            'Sales Period'      => 'sales_month',
            'Reporting Period'  => 'reporting_month',
            'Track Title'       => 'track_title',
            'ISRC'              => 'isrc',
            'Track Artists'     => 'artist_name',
            'UPC Code'          => 'upc',
            'Album Title'       => 'release_title',
            'Store Name'        => 'platform',
            'Sales Region'      => 'region',
            'Units of Sold'     => 'quantity',
            'Final Royalty'     => 'net_revenue',
        ];

        for ($col = 'A'; $col <= $highestColumn; ++$col) {
            $cell   = $sheet->getCell($col . '1');
            $value  = $cell->getFormattedValue();

            if(is_null($value)) {
                continue;
            }

            // Map row header
            if(isset($map_row[$value])) {
                $map[$col] = $map_row[$value];
            }
        }

        // Make sure required columns are present
        $mustPresent = ['reporting_month','sales_month','platform','region','artist_name','release_title','track_title','upc','isrc','quantity','net_revenue'];
        $missing = array_filter($mustPresent, fn($value) => !in_array($value, $map));
        if (!empty($missing)) {
            throw new \Exception('Missing column in excel: ' . implode(', ', array_map(function($value) use ($map_row) {
                return array_search($value, $map_row);
            }, $missing)));
        }

        return [
            'sheet'         => $sheet,
            'map'           => $map,
            'highestRow'    => $highestRow,
            'highestColumn' => $highestColumn
        ];
    }

    private static function getExcelFileDataSoundon($sheet, $highestRow, $highestColumn, $map)
    {
        // Loop through each column in the row
        $content = [];
        for ($row = 2; $row <= $highestRow; ++$row) {
            $rowData = [];
            for ($col = 'A'; $col <= $highestColumn; ++$col) {

                // Skip if column not mapped
                if(!isset($map[$col])) {
                    continue;
                }

                $cell   = $sheet->getCell($col . $row);
                $value  = $cell->getValue();

                // Check critical values
                if(in_array($map[$col], ['reporting_month', 'sales_month', 'platform', 'region', 'upc', 'isrc', 'quantity', 'net_revenue'])) {
                    if ($value === null || $value === '') {
                        throw new \Exception("Empty value for column $map[$col] at row $row");
                    }
                }
                
                // Check first and second column
                if($map[$col] == 'reporting_month' || $map[$col] == 'sales_month') {
                    if(is_null($value)) {
                        continue 2;
                    }

                    // Detect and parse date if string
                    if(is_string($value)) {
                        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
                            $value = Carbon::createFromFormat('Y-m', $value)->format('Y-m-1');
                        }
                        else if (preg_match('/^\d{4}-\d{2}-\d{2}~\d{4}-\d{2}-\d{2}$/', $value)) {
                            $value = Carbon::createFromFormat('Y-m-d', explode('~', $value)[0])->format('Y-m-1');
                        }
                        else {
                            throw new \Exception("Invalid date format: $value at row $row");
                        }
                    }
                    // Parse date if numeric
                    else {
                        $value = (new \DateTime())->setTimestamp(Date::excelToTimestamp($value))->format('Y-m-d');
                    }
                }

                // Check quantity column
                elseif($map[$col] == 'quantity') {

                    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                        throw new \Exception("Invalid quantity: $value at row $row");
                    }

                    $value = (int) $value;
                }
                
                // Check UPC column
                elseif(in_array($map[$col],['platform','region','label_name','artist_name','release_title','track_title','upc','isrc'])) {
                    $value = (string) $value;
                }

                elseif(in_array($map[$col],['rupiah_exact','client_share_rate','net_revenue'])) {
                    $value = $cell->getCalculatedValue();

                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', '', $value);
                        $value = str_replace(',', '.', $value);
                        $value = floatval($value);
                    }
                }

                // Remove science notation
                if (is_numeric($value) && stripos((string) $value, 'E') !== false) {
                    $value = rtrim(rtrim(sprintf('%.15f', $value), '0'), '.');
                }

                // Remove leading and trailing spaces
                $rowData[$map[$col]] = is_string($value) ? trim($value) : $value;
            }
            $rowData['label_name'] = '';
            $rowData['status'] = 'pending';
            $content[] = $rowData;
        }

        return $content;
    }

    private static function validateExcelFileLokamusik($file, $sheetname = null)
    {
        $spreadsheet    = IOFactory::load($file);
        $sheet          = $sheetname ? $spreadsheet->getSheetByName($sheetname) : $spreadsheet->getSheet(0);

        if(!$sheet) {
            throw new \Exception("Sheet not found");
        }

        $highestRow     = $sheet->getHighestRow();
        $highestColumn  = $sheet->getHighestColumn();
        $map = [];
        
        // Detect row header
        $map_row = [
            'sales_period'      => 'sales_month',
            'period'            => 'reporting_month',
            'title'             => 'track_title',
            'isrc'              => 'isrc',
            'artist'            => 'artist_name',
            'label_name'        => 'label_name',
            'upc'               => 'upc',
            'platform'          => 'platform',
            'country'           => 'region',
            'sales_type'        => 'sales_type',
            'sales_unit'        => 'quantity',
            'client_net_share'  => 'net_revenue',
        ];

        for ($col = 'A'; $col <= $highestColumn; ++$col) {
            $cell   = $sheet->getCell($col . '1');
            $value  = $cell->getFormattedValue();

            if(is_null($value)) {
                continue;
            }

            // Map row header
            if(isset($map_row[$value])) {
                $map[$col] = $map_row[$value];
            }
        }

        // Make sure required columns are present
        $mustPresent = ['reporting_month','sales_month','platform','region','artist_name','track_title','upc','isrc','quantity','net_revenue','sales_type'];
        $missing = array_filter($mustPresent, fn($value) => !in_array($value, $map));
        if (!empty($missing)) {
            throw new \Exception('Missing column in excel: ' . implode(', ', array_map(function($value) use ($map_row) {
                return array_search($value, $map_row);
            }, $missing)));
        }

        return [
            'sheet'         => $sheet,
            'map'           => $map,
            'highestRow'    => $highestRow,
            'highestColumn' => $highestColumn
        ];
    }

    private static function getExcelFileDataLokamusik($sheet, $highestRow, $highestColumn, $map)
    {
        // Loop through each column in the row
        $content = [];
        for ($row = 2; $row <= $highestRow; ++$row) {
            $rowData = [];
            for ($col = 'A'; $col <= $highestColumn; ++$col) {

                // Skip if column not mapped
                if(!isset($map[$col])) {
                    continue;
                }

                $cell   = $sheet->getCell($col . $row);
                $value  = $cell->getValue();

                // Check critical values
                if(in_array($map[$col], ['reporting_month', 'sales_month', 'platform', 'region', 'upc', 'isrc', 'quantity', 'net_revenue'])) {
                    if ($value === null || $value === '') {
                        throw new \Exception("Empty value for column $map[$col] at row $row");
                    }
                }
                
                // Check first and second column
                if($map[$col] == 'reporting_month' || $map[$col] == 'sales_month') {
                    if(is_null($value)) {
                        continue 2;
                    }

                    // Detect and parse date if string
                    if(is_string($value)) {
                        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
                            $value = Carbon::createFromFormat('Y-m', $value)->format('Y-m-1');
                        }
                        else if (preg_match('/^\d{4}-\d{2}-\d{2}~\d{4}-\d{2}-\d{2}$/', $value)) {
                            $value = Carbon::createFromFormat('Y-m-d', explode('~', $value)[0])->format('Y-m-1');
                        }
                        else {
                            throw new \Exception("Invalid date format: $value at row $row");
                        }
                    }
                    // Parse date if numeric
                    else {
                        $value = (new \DateTime())->setTimestamp(Date::excelToTimestamp($value))->format('Y-m-d');
                    }
                }

                // Check quantity column
                elseif($map[$col] == 'quantity') {

                    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                        throw new \Exception("Invalid quantity: $value at row $row");
                    }

                    $value = (int) $value;
                }
                
                // Check UPC column
                elseif(in_array($map[$col],['platform','region','label_name','artist_name','release_title','track_title','upc','isrc'])) {
                    $value = (string) $value;
                }

                elseif(in_array($map[$col],['rupiah_exact','client_share_rate','net_revenue'])) {
                    $value = $cell->getCalculatedValue();

                    if (is_string($value)) {
                        $value = preg_replace('/\s+/', '', $value);
                        $value = str_replace(',', '.', $value);
                        $value = floatval($value);
                    }
                }

                // Remove science notation
                if (is_numeric($value) && stripos((string) $value, 'E') !== false) {
                    $value = rtrim(rtrim(sprintf('%.15f', $value), '0'), '.');
                }

                // Remove leading and trailing spaces
                $rowData[$map[$col]] = is_string($value) ? trim($value) : $value;
            }
            $rowData['release_title'] = '';
            $rowData['status'] = 'pending';
            $content[] = $rowData;
        }

        return $content;
    }

}
