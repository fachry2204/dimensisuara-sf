<?php

use App\Jobs\BackupTrackFiles;
use App\Jobs\ClearFileCache;
use App\Jobs\ClearTemporaryFiles;
use App\Jobs\ClearUnusedReleases;
use App\Jobs\Reporting\ReportingRecheck;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new ClearUnusedReleases)->daily();
Schedule::command('drive:archive-released')->everyFiveMinutes()->withoutOverlapping();
Schedule::job(new BackupTrackFiles)->daily()->withoutOverlapping();
Schedule::job(new ClearTemporaryFiles)->hourly();
Schedule::job(new ClearFileCache)->hourly();
Schedule::job(new ReportingRecheck)->hourly();

Schedule::command('queue:work --stop-when-empty')->everyMinute();
