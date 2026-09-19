<?php

namespace App\Console\Commands;

use App\Jobs\BackupTrackFiles;
use Illuminate\Console\Command;

class BackupTrackFilesCommand extends Command
{
    protected $signature = 'storage:backup-tracks';

    protected $description = 'Back up eligible track files to the FTP server';

    public function handle(): int
    {
        BackupTrackFiles::dispatchSync();

        $this->info('Track file backup completed.');

        return Command::SUCCESS;
    }
}
