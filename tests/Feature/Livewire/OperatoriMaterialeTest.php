<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Operatori;
use App\Models\Legacy\Documento;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OperatoriMaterialeTest extends TestCase
{
    public function test_rows_are_edited_inline_and_new_row_creates(): void
    {
        $this->creaSchema();
        DB::table('tc_materiali')->insert([
            ['id' => 1, 'id_operatore' => 10, 'id_materiale' => 1, 'descrizione' => 'Giacca', 'taglia' => 'M', 'riconsegna' => '2027-01-31', 'consegnato' => 'N'],
            ['id' => 2, 'id_operatore' => 11, 'id_materiale' => 1, 'descrizione' => null, 'taglia' => 'S', 'riconsegna' => null, 'consegnato' => 'N'],
        ]);

        $componente = Livewire::actingAs(new User(['user_type' => 2]))->test(Operatori::class)
            ->call('modifica', 10)
            ->assertSet('righeMateriali.1.taglia', 'M')
            ->assertSet('righeMateriali.1.riconsegna', '2027-01-31')
            ->set('righeMateriali.1.taglia', 'XL')
            ->set('righeMateriali.1.consegnato', true)
            ->call('salvaMateriale', 1)
            ->assertHasNoErrors();

        $riga = DB::table('tc_materiali')->where('id', 1)->first();
        $this->assertSame('XL', $riga->taglia);
        $this->assertSame('Y', $riga->consegnato);
        $this->assertSame('Giacca', $riga->descrizione);

        $componente->set('materiale', ['id_materiale' => '1', 'taglia' => 'L'])
            ->call('aggiungiMateriale')
            ->assertHasNoErrors()
            ->assertSet('materiale', []);

        $this->assertSame(2, DB::table('tc_materiali')->where('id_operatore', 10)->count());
        $this->assertCount(2, $componente->get('righeMateriali'));

        $componente->set('righeMateriali.1.id_materiale', '')->call('salvaMateriale', 1)->assertHasErrors(['righeMateriali.1.id_materiale']);
        $this->assertSame('1', (string) DB::table('tc_materiali')->where('id', 1)->value('id_materiale'));

        $componente->call('eliminaMateriale', 1);
        $this->assertSame(1, DB::table('tc_materiali')->where('id_operatore', 10)->count());
        $this->assertSame('S', DB::table('tc_materiali')->where('id', 2)->value('taglia'));
    }

    public function test_delete_operator_requires_name_removes_related_data_and_blocks_with_shifts(): void
    {
        $this->creaSchema();
        foreach (['tc_turni', 'tc_gda', 'tc_operatori_preferiti', 'tc_operatori_escludi', 'tc_documenti_view', 'tc_bacheca_view'] as $tabella) {
            Schema::create($tabella, function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('id_operatore')->nullable();
            });
        }
        $disco = config('filesystems.files_disk');
        Storage::fake($disco);
        DB::table('tc_materiali')->insert(['id' => 1, 'id_operatore' => 10, 'id_materiale' => 1, 'taglia' => 'M', 'consegnato' => 'N']);
        DB::table('tc_documenti')->insert(['id' => 1, 'id_tipo' => 10, 'tipo' => 'O', 'formazione' => 'N', 'file' => 'a.pdf']);
        Storage::disk($disco)->put((new Documento(['id_tipo' => 10, 'tipo' => 'O', 'file' => 'a.pdf']))->percorso(), 'x');

        $componente = Livewire::actingAs(new User(['user_type' => 2]))->test(Operatori::class)
            ->call('richiediEliminazione', 10)
            ->set('conferma', 'XXXXX')
            ->call('elimina')
            ->assertHasErrors(['conferma']);
        $this->assertNotNull(DB::table('tc_admin')->find(10));

        DB::table('tc_turni')->insert(['id_operatore' => 10]);
        $componente->set('conferma', 'conferma')->call('elimina')->assertHasErrors(['conferma']);
        $this->assertNotNull(DB::table('tc_admin')->find(10));

        DB::table('tc_turni')->delete();
        $componente->set('conferma', 'conferma')->call('elimina')->assertHasNoErrors();

        $this->assertNull(DB::table('tc_admin')->find(10));
        $this->assertSame(0, DB::table('tc_materiali')->count());
        $this->assertSame(0, DB::table('tc_documenti')->count());
        Storage::disk($disco)->assertMissing((new Documento(['id_tipo' => 10, 'tipo' => 'O', 'file' => 'a.pdf']))->percorso());
    }

    public function test_formazione_rows_are_edited_inline_with_optional_file_replacement(): void
    {
        $this->creaSchema();
        $disco = config('filesystems.files_disk');
        Storage::fake($disco);
        DB::table('tc_documenti')->insert([
            ['id' => 1, 'id_tipo' => 10, 'tipo' => 'O', 'formazione' => 'Y', 'descrizione' => 'Corso base', 'inizio' => '2026-01-10', 'fine' => '2026-01-12', 'file' => 'vecchio.pdf', 'icona' => 'file-pdf'],
            ['id' => 2, 'id_tipo' => 11, 'tipo' => 'O', 'formazione' => 'Y', 'descrizione' => 'Altro operatore', 'inizio' => null, 'fine' => null, 'file' => 'x.pdf', 'icona' => 'file-pdf'],
        ]);
        $documento = new Documento(['id_tipo' => 10, 'tipo' => 'O', 'file' => 'vecchio.pdf']);
        Storage::disk($disco)->put($documento->percorso(), 'contenuto');

        $componente = Livewire::actingAs(new User(['user_type' => 2]))->test(Operatori::class)
            ->call('modifica', 10)
            ->assertSet('righeFormazioni.1.descrizione', 'Corso base')
            ->set('righeFormazioni.1.descrizione', 'Corso avanzato')
            ->call('salvaFormazione', 1)
            ->assertHasNoErrors();

        $this->assertSame('Corso avanzato', DB::table('tc_documenti')->where('id', 1)->value('descrizione'));
        $this->assertSame('vecchio.pdf', DB::table('tc_documenti')->where('id', 1)->value('file'));

        $componente->set('righeFormazioni.1.fine', '2026-01-01')->call('salvaFormazione', 1)->assertHasErrors(['righeFormazioni.1.fine']);

        $componente->set('righeFormazioni.1.fine', '2026-02-01')
            ->set('fileRigheFormazioni.1', UploadedFile::fake()->create('nuovo.pdf', 10, 'application/pdf'))
            ->call('salvaFormazione', 1)
            ->assertHasNoErrors();

        $nuovoFile = DB::table('tc_documenti')->where('id', 1)->value('file');
        $this->assertStringStartsWith('Formazione_', $nuovoFile);
        Storage::disk($disco)->assertMissing($documento->percorso());
        Storage::disk($disco)->assertExists((new Documento(['id_tipo' => 10, 'tipo' => 'O', 'file' => $nuovoFile]))->percorso());

        $componente->set('righeFormazioni.2', ['inizio' => '', 'fine' => '', 'descrizione' => 'Manomessa'])->call('salvaFormazione', 2)->assertNotFound();
        $this->assertSame('Altro operatore', DB::table('tc_documenti')->where('id', 2)->value('descrizione'));
    }

    public function test_overdue_undelivered_material_is_flagged_like_the_reminder_job(): void
    {
        $this->creaSchema();
        DB::table('tc_materiali')->insert([
            ['id' => 1, 'id_operatore' => 10, 'id_materiale' => 1, 'descrizione' => 'Scaduta', 'riconsegna' => today()->subDay()->toDateString(), 'consegnato' => 'N'],
            ['id' => 2, 'id_operatore' => 10, 'id_materiale' => 1, 'descrizione' => 'Vicina', 'riconsegna' => today()->addDays(5)->toDateString(), 'consegnato' => 'N'],
            ['id' => 3, 'id_operatore' => 10, 'id_materiale' => 1, 'descrizione' => 'Lontana', 'riconsegna' => today()->addDays(60)->toDateString(), 'consegnato' => 'N'],
            ['id' => 4, 'id_operatore' => 10, 'id_materiale' => 1, 'descrizione' => 'Resa', 'riconsegna' => today()->subDay()->toDateString(), 'consegnato' => 'Y'],
        ]);

        $html = Livewire::actingAs(new User(['user_type' => 2]))->test(Operatori::class)
            ->call('modifica', 10)->set('tab', 'materiali')->html();

        $this->assertSame(1, substr_count($html, '>Scaduto<'));
        $this->assertSame(1, substr_count($html, '>In scadenza<'));
    }

    private function creaSchema(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');

        Schema::create('_tracking', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('id_user')->nullable();
            $table->string('addr')->nullable();
            $table->string('host')->nullable();
            $table->string('agent')->nullable();
            $table->string('pagina')->nullable();
            $table->dateTime('data_view')->nullable();
            $table->text('action')->nullable();
        });

        Schema::create('tc_admin', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_type')->nullable();
            $table->string('attivo')->nullable();
            foreach (['nome', 'cognome', 'email', 'user', 'cellulare', 'codice', 'colore', 'data_nascita', 'luogo_nascita', 'indirizzo', 'iban', 'password'] as $colonna) {
                $table->string($colonna)->nullable();
            }
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('provincia')->nullable();
            $table->float('costo_orario')->nullable();
        });
        Schema::create('tc_clienti', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('ragione_sociale')->nullable();
        });
        Schema::create('_provincia', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome')->nullable();
            $table->string('sigla')->nullable();
        });
        Schema::create('_materiali', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome')->nullable();
        });
        Schema::create('tc_materiali', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->unsignedInteger('id_materiale')->nullable();
            $table->string('descrizione')->nullable();
            $table->string('taglia')->nullable();
            $table->date('riconsegna')->nullable();
            $table->string('consegnato')->nullable();
        });
        Schema::create('_certificati', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome')->nullable();
        });
        Schema::create('tc_documenti', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_tipo')->nullable();
            $table->string('tipo')->nullable();
            foreach (['formazione', 'descrizione', 'file', 'icona', 'admin'] as $colonna) {
                $table->string($colonna)->nullable();
            }
            $table->date('inizio')->nullable();
            $table->date('fine')->nullable();
            $table->date('scadenza')->nullable();
            $table->unsignedInteger('tipologia')->nullable();
        });

        Schema::create('tc_messaggi', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('giorni')->nullable();
        });
        DB::table('tc_messaggi')->insert(['id' => 5, 'giorni' => 15]);
        DB::table('_materiali')->insert(['id' => 1, 'nome' => 'Giacca']);
        DB::table('tc_admin')->insert([
            ['id' => 10, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Mario', 'cognome' => 'Rossi'],
            ['id' => 11, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Luca', 'cognome' => 'Bianchi'],
        ]);
    }
}
