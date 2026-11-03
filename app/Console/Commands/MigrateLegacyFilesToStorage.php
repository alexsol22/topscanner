<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

#[Signature('app:migrate-legacy-files-to-storage
    {--documents-from= : Directory containing protected/documenti}
    {--avatars-from= : Directory containing images/avatar}
    {--dry-run : List the files without copying them}')]
#[Description('Copy Yii files to the configured private Laravel storage disk, preserving their paths')]
class MigrateLegacyFilesToStorage extends Command
{
    public function handle(): int
    {
        $trees = [
            [
                'source' => $this->option('documents-from') ?: base_path('s420/protected/documenti'),
                'destination' => trim(config('filesystems.private_paths.documenti'), '/'),
            ],
            [
                'source' => $this->option('avatars-from') ?: base_path('s420/images/avatar'),
                'destination' => trim(config('filesystems.private_paths.avatars'), '/'),
            ],
        ];
        $dryRun = (bool) $this->option('dry-run');
        $totals = ['copied' => 0, 'unchanged' => 0];

        foreach ($trees as $tree) {
            $counts = $this->copyTree($tree['source'], $tree['destination'], $dryRun);
            $totals['copied'] += $counts['copied'];
            $totals['unchanged'] += $counts['unchanged'];
        }

        $this->info(($dryRun ? 'Would copy ' : 'Copied ').$totals['copied'].' files; '.$totals['unchanged'].' already match.');

        return self::SUCCESS;
    }

    /**
     * @return array{copied: int, unchanged: int}
     */
    private function copyTree(string $source, string $destination, bool $dryRun): array
    {
        $sourcePath = $source;
        $source = realpath($sourcePath) ?: '';
        if ($source === '' || ! is_dir($source)) {
            throw new RuntimeException("Source directory does not exist: {$sourcePath}");
        }

        $counts = ['copied' => 0, 'unchanged' => 0];
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        );
        $disk = Storage::disk();

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isLink()) {
                throw new RuntimeException("Symbolic links are not supported: {$file->getPathname()}");
            }

            if (! $file->isFile()) {
                continue;
            }

            $relativePath = substr($file->getPathname(), \strlen($source) + 1);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            $target = $destination.'/'.$relativePath;

            if ($disk->exists($target)) {
                if ($this->sourceHash($file->getPathname()) !== $this->storageHash($target)) {
                    throw new RuntimeException("Destination file differs from source: {$target}");
                }

                $counts['unchanged']++;

                continue;
            }

            if (! $dryRun) {
                $stream = fopen($file->getPathname(), 'rb');
                if ($stream === false) {
                    throw new RuntimeException("Unable to read source file: {$file->getPathname()}");
                }

                try {
                    if (! $disk->put($target, $stream)) {
                        throw new RuntimeException("Unable to write destination file: {$target}");
                    }
                } finally {
                    fclose($stream);
                }
            }

            $counts['copied']++;
        }

        $this->line("{$source}: {$counts['copied']} to copy, {$counts['unchanged']} already matching.");

        return $counts;
    }

    private function sourceHash(string $path): string
    {
        $hash = hash_file('sha256', $path);
        if ($hash === false) {
            throw new RuntimeException("Unable to hash source file: {$path}");
        }

        return $hash;
    }

    private function storageHash(string $path): string
    {
        $stream = Storage::disk()->readStream($path);
        if ($stream === false) {
            throw new RuntimeException("Unable to read destination file: {$path}");
        }

        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);

            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
