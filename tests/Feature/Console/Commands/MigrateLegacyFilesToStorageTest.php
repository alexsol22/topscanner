<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MigrateLegacyFilesToStorageTest extends TestCase
{
    public function test_legacy_files_are_copied_with_their_relative_paths_and_sources_are_preserved(): void
    {
        Storage::fake('local');
        $sourceRoot = sys_get_temp_dir().'/legacy-file-migration-'.Str::uuid();
        $documentsSource = $sourceRoot.'/legacy/protected/documenti/Cliente_12/Sede_34';
        $avatarsSource = $sourceRoot.'/legacy/images/avatar';
        File::ensureDirectoryExists($documentsSource);
        File::ensureDirectoryExists($avatarsSource);
        File::put($documentsSource.'/foto.jpg', 'document content');
        File::put($avatarsSource.'/avatar_12.jpeg', 'avatar content');

        try {
            $this->artisan('app:migrate-legacy-files-to-storage', [
                '--documents-from' => $sourceRoot.'/legacy/protected/documenti',
                '--avatars-from' => $avatarsSource,
            ])->assertSuccessful();

            Storage::disk('local')->assertExists('protected/documenti/Cliente_12/Sede_34/foto.jpg');
            Storage::disk('local')->assertExists('images/avatar/avatar_12.jpeg');
            $this->assertFileExists($documentsSource.'/foto.jpg');
            $this->assertFileExists($avatarsSource.'/avatar_12.jpeg');

            $this->artisan('app:migrate-legacy-files-to-storage', [
                '--documents-from' => $sourceRoot.'/legacy/protected/documenti',
                '--avatars-from' => $avatarsSource,
            ])->assertSuccessful();
        } finally {
            File::deleteDirectory($sourceRoot);
        }
    }
}
