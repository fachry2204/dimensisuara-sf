<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;

class FindOrphanedFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:find-orphaned 
                            {--directories=files,covers,transactions,ipls,tracks,thumbnails,settlements,reports,draft : Comma-separated list of directories to scan}
                            {--delete : Actually delete the orphaned files (default is dry-run)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find files in storage that are not referenced in the filestorage table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Scanning for orphaned files...');
        $this->newLine();

        $directories = explode(',', $this->option('directories'));
        $isDryRun = !$this->option('delete');

        // Get all identifiers from database
        $dbIdentifiers = $this->getDatabaseIdentifiers();
        $this->info("📊 Found {$dbIdentifiers->count()} file references in database.");

        // Scan storage directories for actual files
        $storageFiles = $this->getStorageFiles($directories);
        $this->info("📁 Found {$storageFiles->count()} files in storage directories.");
        $this->newLine();

        // Find orphaned files (in storage but not in database)
        $orphanedFiles = $storageFiles->diff($dbIdentifiers);

        if ($orphanedFiles->isEmpty()) {
            $this->info('✅ No orphaned files found. Storage is clean!');
            return Command::SUCCESS;
        }

        // Calculate sizes and prepare report
        $report = $this->generateReport($orphanedFiles);

        // Display results
        $this->displayReport($report, $isDryRun);

        // Delete if not dry-run
        if (!$isDryRun) {
            if ($this->confirm('Are you sure you want to delete these files?')) {
                $this->deleteFiles($orphanedFiles);
                $this->info('🗑️  Files deleted successfully!');
            } else {
                $this->warn('Deletion cancelled.');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Get all file identifiers from the database.
     */
    protected function getDatabaseIdentifiers(): \Illuminate\Support\Collection
    {
        return DB::table('filestorage')
            ->whereNotNull('identifier')
            ->pluck('identifier')
            ->filter()
            ->values();
    }

    /**
     * Get all files from storage directories.
     */
    protected function getStorageFiles(array $directories): \Illuminate\Support\Collection
    {
        $files = collect();
        $basePath = storage_path('app');

        foreach ($directories as $directory) {
            $directory = trim($directory);
            $fullPath = $basePath . DIRECTORY_SEPARATOR . $directory;

            if (!is_dir($fullPath)) {
                $this->warn("⚠️  Directory not found: {$directory}");
                continue;
            }

            $finder = new Finder();
            $finder->files()->in($fullPath);

            foreach ($finder as $file) {
                // Build the identifier path (relative to storage/app)
                $relativePath = $directory . '/' . $file->getFilename();
                $files->push($relativePath);
            }
        }

        return $files;
    }

    /**
     * Generate a report of orphaned files with sizes.
     */
    protected function generateReport(\Illuminate\Support\Collection $orphanedFiles): array
    {
        $basePath = storage_path('app');
        $files = [];
        $totalSize = 0;

        foreach ($orphanedFiles as $identifier) {
            $fullPath = $basePath . DIRECTORY_SEPARATOR . $identifier;

            if (file_exists($fullPath)) {
                $size = filesize($fullPath);
                $totalSize += $size;

                $files[] = [
                    'identifier' => $identifier,
                    'full_path' => $fullPath,
                    'size' => $size,
                    'size_human' => $this->formatBytes($size),
                    'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
                ];
            }
        }

        // Sort by size descending
        usort($files, fn($a, $b) => $b['size'] <=> $a['size']);

        return [
            'files' => $files,
            'total_count' => count($files),
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
        ];
    }

    /**
     * Display the report in console.
     */
    protected function displayReport(array $report, bool $isDryRun): void
    {
        $modeLabel = $isDryRun ? '🔍 DRY RUN MODE' : '⚠️  DELETE MODE';
        $this->warn($modeLabel);
        $this->newLine();

        $this->error("🗂️  Found {$report['total_count']} orphaned files");
        $this->newLine();

        // Display table of files
        $tableData = collect($report['files'])->map(fn($file) => [
            $file['identifier'],
            $file['size_human'],
            $file['modified'],
        ])->toArray();

        $this->table(
            ['File Identifier', 'Size', 'Last Modified'],
            $tableData
        );

        $this->newLine();

        // Summary by directory
        $byDirectory = collect($report['files'])->groupBy(function ($file) {
            return dirname($file['identifier']);
        });

        $this->info('📊 Summary by Directory:');
        $summaryTable = $byDirectory->map(function ($files, $dir) {
            $totalSize = $files->sum('size');
            return [
                $dir,
                $files->count(),
                $this->formatBytes($totalSize),
            ];
        })->values()->toArray();

        $this->table(['Directory', 'File Count', 'Total Size'], $summaryTable);

        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════════');
        $this->info("📦 Total files to be deleted: {$report['total_count']}");
        $this->info("💾 Total space to be freed: {$report['total_size_human']}");
        $this->line('═══════════════════════════════════════════════════════════');
        $this->newLine();

        if ($isDryRun) {
            $this->comment('ℹ️  This is a dry run. No files were deleted.');
            $this->comment('ℹ️  Run with --delete flag to actually remove the files.');
        }
    }

    /**
     * Delete the orphaned files.
     */
    protected function deleteFiles(\Illuminate\Support\Collection $orphanedFiles): void
    {
        $bar = $this->output->createProgressBar($orphanedFiles->count());
        $bar->start();

        foreach ($orphanedFiles as $identifier) {
            Storage::disk('local')->delete($identifier);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
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