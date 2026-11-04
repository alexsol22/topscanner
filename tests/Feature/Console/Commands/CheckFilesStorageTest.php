<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckFilesStorageTest extends TestCase
{
    public function test_reports_connection_without_writing(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('protected/documenti/a.pdf', 'x');

        $this->artisan('app:check-files-storage', ['--path' => 'protected/documenti/a.pdf'])
            ->expectsOutputToContain('Connessione OK')
            ->expectsOutputToContain('exists(protected/documenti/a.pdf): sì')
            ->assertSuccessful();

        Storage::disk('local')->assertExists('protected/documenti/a.pdf');
    }
}
