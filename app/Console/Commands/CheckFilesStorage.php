<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CheckFilesStorage extends Command
{
    protected $signature = 'app:check-files-storage {--path= : Percorso relativo da verificare (exists)} {--limit=5 : Numero massimo di file da elencare}';

    protected $description = 'Verifica in sola lettura la connessione al disco dei file (nessuna scrittura o cancellazione)';

    public function handle(): int
    {
        $name = config('filesystems.files_disk');
        $config = config("filesystems.disks.{$name}");

        if (! $config) {
            $this->error("Disco '{$name}' non configurato.");

            return self::FAILURE;
        }

        $this->line("Disco: {$name} (driver: {$config['driver']})");

        if (! empty($config['bucket'])) {
            $this->line("Bucket: {$config['bucket']}");
        }

        try {
            $disk = Storage::disk($name);
            $base = config('filesystems.private_paths.documenti');
            $files = array_slice($disk->files($base, true), 0, max(1, (int) $this->option('limit')));

            $this->info('Connessione OK. File in '.$base.': '.count($files).' (campione)');

            foreach ($files as $file) {
                $this->line(" - {$file}");
            }

            if ($path = $this->option('path')) {
                $this->line("exists({$path}): ".($disk->exists($path) ? 'sì' : 'no'));
            }
        } catch (Throwable $e) {
            $this->error('Connessione fallita: '.$e::class.': '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
