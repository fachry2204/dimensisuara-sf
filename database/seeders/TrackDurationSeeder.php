<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class TrackDurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dryRun = false;
        if ($this->command) {
            $dryRun = $this->command->confirm('Do you want to perform a dry run (no database changes will be saved)?', true);
        }

        Artisan::call('tracks:update-durations', [
            '--dry-run' => $dryRun
        ], $this->command ? $this->command->getOutput() : null);
    }
}
