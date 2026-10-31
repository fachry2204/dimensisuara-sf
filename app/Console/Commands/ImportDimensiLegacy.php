<?php

namespace App\Console\Commands;

use App\Services\Legacy\DimensiImporter;
use App\Services\Legacy\SqlDumpReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDimensiLegacy extends Command
{
    protected $signature = 'legacy:import-dimensi {dump : Path to the phpMyAdmin SQL dump}
        {--apply : Commit changes; otherwise rolls back the complete trial}
        {--database= : Explicit destination database on the configured MySQL server}';

    protected $description = 'Import legacy Dimensi clients and catalogue without overwriting existing accounts';

    public function handle(): int
    {
        if (!is_file($this->argument('dump'))) {
            $this->error('SQL dump not found.');
            return self::FAILURE;
        }
        if ($database = $this->option('database')) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/D', $database)) {
                $this->error('Invalid destination database name.');
                return self::FAILURE;
            }
            config(['database.connections.legacy_destination' => array_merge(config('database.connections.mysql'), ['database' => $database, 'url' => null])]);
            DB::setDefaultConnection('legacy_destination');
        }
        $this->info('Destination: '.DB::connection()->getDatabaseName());
        $data = (new SqlDumpReader)->read($this->argument('dump'), DimensiImporter::TABLES);
        $connection = DB::connection();
        $lock = $connection->selectOne("SELECT GET_LOCK('dimensi_legacy_import', 0) AS acquired");
        if (!$lock->acquired) {
            $this->error('Another legacy import is running.');
            return self::FAILURE;
        }
        try {
            $connection->beginTransaction();
            $result = (new DimensiImporter)->run($data, hash_file('sha256', $this->argument('dump')));
            foreach ($result as $key => $count) {
                $this->line($key.': '.$count);
            }
            if ($this->option('apply')) {
                $connection->commit();
                $this->info('COMMITTED. Original rows retained encrypted in legacy_import_records.');
            } else {
                $connection->rollBack();
                $this->info('TRIAL ROLLED BACK. No imported rows retained. Auto-increment IDs may advance.');
            }
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            if ($connection->transactionLevel()) {
                $connection->rollBack();
            }
            // Do not print database exceptions containing passwords or personal row values.
            $this->error('Import rolled back: '.get_class($exception).'. '.$exception->getCode());
            return self::FAILURE;
        } finally {
            $connection->select("SELECT RELEASE_LOCK('dimensi_legacy_import')");
        }
    }
}
