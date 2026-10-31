<?php

namespace App\Jobs;

use App\Services\ReleasedFilesDrive;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ArchiveReleasedFile implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    public int $timeout = 900;
    public int $uniqueFor = 3600;
    public int $tries = 1;
    public function __construct(public int $fileId) { $this->onConnection('drive-archive')->onQueue('drive-archive'); }
    public function uniqueId(): string { return (string) $this->fileId; }
    public function handle(ReleasedFilesDrive $service): void { $service->archive($this->fileId); }
}
