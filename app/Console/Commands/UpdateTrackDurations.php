<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Track;
use Illuminate\Support\Facades\File as FileFacade;

class UpdateTrackDurations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracks:update-durations {--dry-run : Only show what would be updated without saving changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan all track full audio files and update their duration using getID3';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE: No database changes will be saved.');
            $this->newLine();
        }

        $total = Track::count();

        if ($total === 0) {
            $this->info('✅ No tracks found in the database.');
            return Command::SUCCESS;
        }

        $this->info("📊 Scanning {$total} tracks...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updatedCount = 0;
        $skippedCount = 0;
        $mismatches = [];

        $getID3 = new \getID3();
        $getID3->setOption(['encoding' => 'UTF-8']);

        Track::with('full_file')->chunk(100, function ($tracks) use ($bar, $getID3, $dryRun, &$updatedCount, &$skippedCount, &$mismatches) {
            foreach ($tracks as $track) {
                $fullFile = $track->full_file;

                if (!$fullFile || !$fullFile->identifier) {
                    $this->newLine();
                    $this->warn("⚠️  Track #{$track->id} ({$track->title}): No full song file record in database. Skipping.");
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                $filepath = storage_path('app' . DIRECTORY_SEPARATOR . $fullFile->identifier);

                if (!FileFacade::exists($filepath)) {
                    $this->newLine();
                    $this->warn("⚠️  Track #{$track->id} ({$track->title}): Audio file does not exist on disk at [{$filepath}]. Skipping.");
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                try {
                    // Read duration via getID3
                    $fileInfo = $getID3->analyze($filepath);

                    if (!isset($fileInfo['playtime_seconds'])) {
                        $this->newLine();
                        $this->error("❌ Track #{$track->id} ({$track->title}): getID3 could not parse duration.");
                        $skippedCount++;
                        $bar->advance();
                        continue;
                    }

                    $newDuration = (int) round($fileInfo['playtime_seconds']);
                    $oldDuration = $track->duration;

                    // Check size and mime mismatches
                    $diskSize = FileFacade::size($filepath);
                    $diskMime = $fileInfo['mime_type'] ?? null;

                    if ($fullFile->size != $diskSize) {
                        $mismatches[] = "[WARNING] Track #{$track->id} ({$track->title}): Size mismatch (DB: " . $this->formatBytes($fullFile->size) . " vs Disk: " . $this->formatBytes($diskSize) . ").";
                    }

                    if ($diskMime && strtolower($fullFile->mime_type) !== strtolower($diskMime)) {
                        $mismatches[] = "[WARNING] Track #{$track->id} ({$track->title}): MIME mismatch (DB: {$fullFile->mime_type} vs Disk: {$diskMime}).";
                    }

                    if ($oldDuration !== $newDuration) {
                        $this->newLine();
                        if ($dryRun) {
                            $this->line("Track #{$track->id} ({$track->title}): Duration would change from {$oldDuration}s to {$newDuration}s.");
                        } else {
                            $track->update(['duration' => $newDuration]);
                            $this->line("Track #{$track->id} ({$track->title}): Updated duration from {$oldDuration}s to {$newDuration}s.");
                        }
                        $updatedCount++;
                    }
                } catch (\Throwable $e) {
                    $this->newLine();
                    $this->error("❌ Track #{$track->id} ({$track->title}): Error parsing file: " . $e->getMessage());
                    $skippedCount++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Scanning completed.");
        $this->info("🔄 Updated / Proposed updates: {$updatedCount}");
        $this->info("⏭️  Skipped: {$skippedCount}");

        if (count($mismatches) > 0) {
            $this->newLine();
            $this->warn("⚠️  Metadata mismatches detected:");
            foreach ($mismatches as $mismatch) {
                $this->line($mismatch);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
