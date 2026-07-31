<?php

namespace App\Console\Commands;

use App\Models\PropertyContentImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuditPropertyImages extends Command
{
    protected $signature = 'property-images:audit
                            {--all : Audit every file in storage/app/public/images (read-only)}
                            {--delete : Delete local files that are not referenced in the database}
                            {--force : Skip the confirmation prompt when used with --delete}';

    protected $description = 'Audit local property images and optionally delete orphaned files';

    public function handle(): int
    {
        $imageDirectory = storage_path('app/public/images');

        if (! File::isDirectory($imageDirectory)) {
            $this->warn("Image directory does not exist: {$imageDirectory}");

            return self::SUCCESS;
        }

        $databaseRecords = PropertyContentImage::query()->count();
        $referencedFiles = [];
        $missingFiles = [];

        PropertyContentImage::query()
            ->select(['id', 'url'])
            ->whereNotNull('url')
            ->orderBy('id')
            ->chunkById(500, function ($images) use (&$referencedFiles, &$missingFiles, $imageDirectory): void {
                foreach ($images as $image) {
                    $relativePath = $this->localImagePath($image->getRawOriginal('url'));

                    // Remote URLs (including future S3 URLs) are outside this local audit.
                    if ($relativePath === null) {
                        continue;
                    }

                    $filename = basename($relativePath);
                    $referencedFiles[$filename] = true;

                    if (! File::exists($imageDirectory.DIRECTORY_SEPARATOR.$filename)) {
                        $missingFiles[] = [
                            'id' => $image->id,
                            'path' => $relativePath,
                        ];
                    }
                }
            });

        $auditAllFiles = (bool) $this->option('all');

        // Normal cleanup only covers files created by FetchPropertyContentsJob.
        // --all is deliberately read-only because the shared folder also contains CMS images.
        $localFiles = collect(File::files($imageDirectory));
        if (! $auditAllFiles) {
            $localFiles = $localFiles
                ->filter(fn ($file) => preg_match('/^prop_.+\.webp$/i', $file->getFilename()) === 1)
                ->values();
        }
        $totalBytes = $localFiles->sum(fn ($file) => $file->getSize());
        $orphanFiles = $localFiles
            ->reject(fn ($file) => isset($referencedFiles[$file->getFilename()]))
            ->values();
        $orphanBytes = $orphanFiles->sum(fn ($file) => $file->getSize());

        $this->table(
            ['Check', 'Count / Size'],
            [
                ['Database image records', number_format($databaseRecords)],
                [
                    $auditAllFiles ? 'All files in images folder' : 'Property WebP files (prop_*.webp)',
                    number_format($localFiles->count()),
                ],
                ['Referenced local files', number_format(count($referencedFiles))],
                ['Orphan local files', number_format($orphanFiles->count())],
                ['Missing referenced files', number_format(count($missingFiles))],
                ['Total local storage', $this->formatBytes($totalBytes)],
                ['Orphan storage', $this->formatBytes($orphanBytes)],
            ]
        );

        if ($orphanFiles->isNotEmpty()) {
            $this->newLine();
            $this->info('Sample orphan files:');
            $orphanFiles->take(10)->each(fn ($file) => $this->line('  '.$file->getFilename()));
        }

        if ($missingFiles !== []) {
            $this->newLine();
            $this->warn('Sample database records with missing local files:');
            collect($missingFiles)->take(10)->each(
                fn (array $image) => $this->line("  ID {$image['id']}: {$image['path']}")
            );
        }

        if ($auditAllFiles) {
            $this->newLine();
            $this->comment('--all is audit-only. No files were deleted. Files listed as orphan are only unreferenced by property_content_images and may belong to another module.');

            return self::SUCCESS;
        }

        if (! $this->option('delete')) {
            $this->newLine();
            $this->comment('Dry run only. No files were deleted. Use --delete to remove the listed orphan files.');

            return self::SUCCESS;
        }

        if ($orphanFiles->isEmpty()) {
            $this->info('No orphan files found. Nothing was deleted.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Delete {$orphanFiles->count()} orphan files ({$this->formatBytes($orphanBytes)})?"
        )) {
            $this->comment('Deletion cancelled.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $failed = 0;

        foreach ($orphanFiles as $file) {
            if (File::delete($file->getPathname())) {
                $deleted++;
            } else {
                $failed++;
                $this->error('Could not delete: '.$file->getFilename());
            }
        }

        $this->info("Deleted {$deleted} orphan files.");

        if ($failed > 0) {
            $this->error("Failed to delete {$failed} files.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function localImagePath(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (! str_starts_with($path, 'storage/images/')) {
            return null;
        }

        if (preg_match('/^prop_.+\.webp$/i', basename($path)) !== 1) {
            return null;
        }

        return $path;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return number_format($bytes / (1024 ** $power), 2).' '.$units[$power];
    }
}
