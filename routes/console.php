<?php

use App\Jobs\BackupTrackFiles;
use App\Jobs\ClearFileCache;
use App\Jobs\ClearTemporaryFiles;
use App\Jobs\ClearUnusedReleases;
use App\Jobs\Reporting\ReportingRecheck;
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => app(\App\Services\NotificationHealth::class)->record('scheduler'))->name('notification-scheduler-heartbeat')->everyMinute();
// The notification connection remains asynchronous even when QUEUE_CONNECTION=sync.
Schedule::command('queue:work status-notifications --queue=default --stop-when-empty --tries=1 --timeout=60')->everyMinute()->withoutOverlapping(10)->runInBackground();

Schedule::job(new ClearUnusedReleases)->daily();
Schedule::command('drive:archive-released')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('drive:restore-thumbnails')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('queue:work drive-archive --queue=drive-archive --stop-when-empty --max-time=50 --timeout=900 --tries=1')
    ->everyMinute()->withoutOverlapping(30)->runInBackground()
    ->when(fn () => (bool) app(\App\Services\SystemSettings::class)->get('drive_enabled'));
Schedule::job(new BackupTrackFiles)->daily()->withoutOverlapping();
Schedule::job(new ClearTemporaryFiles)->hourly();
Schedule::job(new ClearFileCache)->hourly();
Schedule::job(new ReportingRecheck)->hourly();

Schedule::command('queue:work --stop-when-empty')->everyMinute();
