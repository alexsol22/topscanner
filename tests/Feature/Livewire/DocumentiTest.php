<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Documenti;
use App\Models\Legacy\Documento;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentiTest extends TestCase
{
    public function test_documenti_are_filtered_by_client_operator_type_and_description(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');
        $this->createLegacySchema();

        $staff = User::create([
            'user_type' => 2,
            'nome' => 'Staff',
            'cognome' => 'User',
            'email' => 'staff@example.test',
            'password' => 'secret',
        ]);
        Auth::login($staff);

        $connection = DB::connection('mysql');
        $clientId = $connection->table('tc_clienti')->insertGetId(['ragione_sociale' => 'Cliente Alpha']);
        $otherClientId = $connection->table('tc_clienti')->insertGetId(['ragione_sociale' => 'Cliente Beta']);
        $operatorId = $connection->table('tc_admin')->insertGetId([
            'user_type' => 4,
            'nome' => 'Operatore',
            'cognome' => 'Alpha',
        ]);
        $certificateId = 1;
        $otherCertificateId = 2;
        $connection->table('_certificati')->insert([
            ['id' => $certificateId, 'nome' => 'Sicurezza'],
            ['id' => $otherCertificateId, 'nome' => 'Privacy'],
        ]);

        $this->createDocument($clientId, 'C', $certificateId, 'Certificato cliente alpha');
        $this->createDocument($otherClientId, 'C', $certificateId, 'Certificato cliente beta');
        $this->createDocument($operatorId, 'O', $certificateId, 'Certificato operatore alpha');
        $this->createDocument($clientId, 'C', $otherCertificateId, 'Certificato privacy cliente alpha');
        $this->createDocument($operatorId, 'O', $certificateId, 'Visita medica operatore alpha');

        Livewire::test(Documenti::class)
            ->set('filtroCliente', (string) $clientId)
            ->set('filtroOperatore', (string) $operatorId)
            ->set('filtroTipologia', (string) $certificateId)
            ->set('filtroDescrizione', 'Certificato')
            ->assertSee('Certificato cliente alpha')
            ->assertSee('Certificato operatore alpha')
            ->assertDontSee('Certificato cliente beta')
            ->assertDontSee('Certificato privacy cliente alpha')
            ->assertDontSee('Visita medica operatore alpha');
    }

    public function test_staff_uploads_and_downloads_a_document_from_private_storage(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');
        $this->createLegacySchema();

        $staff = User::create([
            'user_type' => 2,
            'nome' => 'Staff',
            'cognome' => 'User',
            'email' => 'staff@example.test',
            'password' => 'secret',
        ]);
        Auth::login($staff);
        $clientId = DB::connection('mysql')->table('tc_clienti')->insertGetId([
            'ragione_sociale' => 'Cliente Alpha',
        ]);
        Storage::fake('local');

        Livewire::test(Documenti::class)
            ->set('f', [
                'titolo' => 'Contratto',
                'tipo' => 'cliente',
                'cliente_id' => (string) $clientId,
                'user_id' => null,
                'tipologia' => null,
                'scadenza' => null,
                'formazione' => false,
            ])
            ->set('file', UploadedFile::fake()->create('contratto.pdf', 5, 'application/pdf'))
            ->call('salva')
            ->assertHasNoErrors();

        $documento = Documento::query()->firstOrFail();
        $percorso = 'protected/documenti/Cliente_'.$clientId.'/'.$documento->file;
        Storage::disk('local')->assertExists($percorso);
        $this->assertSame(
            true,
            DB::connection('mysql')->table('tc_documenti')->where('id', $documento->id)->exists(),
        );

        $this->get(route('documenti.scarica', ['documento' => $documento->id, 'visualizza' => 1]))
            ->assertOk();
    }

    private function createLegacySchema(): void
    {
        Schema::connection('mysql')->create('tc_admin', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->string('user')->nullable();
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->unsignedInteger('user_type')->nullable();
        });

        Schema::connection('mysql')->create('_tracking', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('id_user')->nullable();
            $table->string('addr')->nullable();
            $table->string('host')->nullable();
            $table->string('agent')->nullable();
            $table->string('pagina')->nullable();
            $table->dateTime('data_view')->nullable();
            $table->text('action')->nullable();
        });

        Schema::connection('mysql')->create('tc_clienti', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('ragione_sociale')->nullable();
            $table->integer('isTest')->nullable();
        });

        Schema::connection('mysql')->create('_certificati', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('nome')->nullable();
        });

        Schema::connection('mysql')->create('tc_documenti', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_tipo');
            $table->string('tipo')->nullable();
            $table->unsignedInteger('tipologia')->nullable();
            $table->text('descrizione');
            $table->string('file');
            $table->string('icona');
            $table->date('scadenza')->nullable();
            $table->string('admin');
            $table->string('formazione');
        });
    }

    private function createDocument(int $ownerId, string $ownerType, int $certificateId, string $description): void
    {
        DB::connection('mysql')->table('tc_documenti')->insert([
            'id_tipo' => $ownerId,
            'tipo' => $ownerType,
            'tipologia' => $certificateId,
            'descrizione' => $description,
            'file' => 'documento.pdf',
            'icona' => 'file-pdf',
            'admin' => 'N',
            'formazione' => 'N',
        ]);
    }
}
