<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Scansione;
use App\Models\Legacy\Postazione;
use App\Models\Legacy\Sede;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ScansioneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');

        Schema::create('tc_admin', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_type')->nullable();
            $table->string('attivo')->nullable();
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
        });
        Schema::create('tc_clienti', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('ragione_sociale')->nullable();
        });
        Schema::create('tc_sedi', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('descrizione')->nullable();
            $table->string('foto')->nullable();
            $table->unsignedInteger('id_cliente')->nullable();
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->integer('distanza')->nullable();
            $table->unsignedInteger('citta_id')->nullable();
            $table->unsignedInteger('provincia_id')->nullable();
        });
        Schema::create('tc_postazioni', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
            $table->string('file')->nullable();
        });
        Schema::create('tc_codici', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('codice')->nullable();
            $table->string('stato')->nullable();
            $table->string('tipo')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
        });
        Schema::create('tc_turni', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->date('data_inizio')->nullable();
            $table->date('data_fine')->nullable();
            $table->string('ora_inizio')->nullable();
            $table->string('ora_fine')->nullable();
            $table->float('ore_lavorate')->nullable();
            $table->string('stato')->nullable();
        });
        Schema::create('tc_gda', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_operatore')->nullable();
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
            $table->unsignedInteger('id_turno')->nullable();
            $table->unsignedInteger('id_postazione')->nullable();
            $table->unsignedInteger('id_codice')->nullable();
            $table->date('data')->nullable();
            $table->string('orario')->nullable();
            $table->string('note_operatore', 255)->nullable();
            $table->float('lat')->nullable();
            $table->float('lng')->nullable();
            $table->float('distanza')->nullable();
            $table->integer('ritardo')->nullable();
            $table->string('tipo')->nullable();
        });
    }

    public function test_pagina_scansione_mostra_il_modulo_per_inviare_una_nota(): void
    {
        $this->preparaTurno();

        Livewire::actingAs(User::findOrFail(1))->test(Scansione::class)
            ->assertSee('Inserisci note')
            ->assertSee('Invia')
            ->assertSee('Nota facoltativa');
    }

    public function test_timbratura_con_nota_la_registra_sulla_presenza_e_la_mostra(): void
    {
        $this->preparaTurno();
        $nota = 'Porta <laterale> aperta';

        Livewire::actingAs(User::findOrFail(1))->test(Scansione::class)
            ->call('timbra', 'ITALPOL*000001', 45.0567356, 7.6693, false, $nota)
            ->assertSee('Inizio turno registrato con successo')
            ->assertSee($nota);

        $this->assertDatabaseHas('tc_gda', [
            'id_turno' => 1,
            'tipo' => 'I',
            'note_operatore' => $nota,
        ]);
    }

    public function test_timbratura_senza_nota_registra_la_presenza(): void
    {
        $this->preparaTurno();

        Livewire::actingAs(User::findOrFail(1))->test(Scansione::class)
            ->call('timbra', 'ITALPOL*000001', 45.0567356, 7.6693, false, '   ')
            ->assertSee('Inizio turno registrato con successo');

        $this->assertNull(DB::table('tc_gda')->value('note_operatore'));
        $this->assertSame(1, DB::table('tc_gda')->count());
    }

    public function test_nota_oltre_255_caratteri_non_registra_la_timbratura(): void
    {
        $this->preparaTurno();

        Livewire::actingAs(User::findOrFail(1))->test(Scansione::class)
            ->call('timbra', 'ITALPOL*000001', 45.0567356, 7.6693, false, str_repeat('a', 256))
            ->assertHasErrors(['nota' => 'max'])
            ->assertSee('La nota può contenere al massimo 255 caratteri.');

        $this->assertSame(0, DB::table('tc_gda')->count());
    }

    public function test_pagina_mostra_la_foto_della_sede_del_turno_attivo(): void
    {
        $this->preparaTurno();
        DB::table('tc_sedi')->where('id', 1)->update([
            'id_cliente' => 1,
            'descrizione' => 'Sede <script>',
            'foto' => 'sede.jpg',
        ]);
        Storage::fake(config('filesystems.files_disk'));
        Storage::disk(config('filesystems.files_disk'))->put(Sede::findOrFail(1)->percorsoFoto(), 'foto-sede');

        Livewire::actingAs(User::findOrFail(1))->test(Scansione::class)
            ->assertSee('scansione/foto', false)
            ->assertSee('Foto di Sede &lt;script&gt;', false)
            ->assertDontSee('Foto di Sede <script>', false);
    }

    public function test_pagina_usa_la_foto_della_postazione_se_la_sede_non_ne_ha(): void
    {
        $this->preparaTurno();
        DB::table('tc_sedi')->where('id', 1)->update(['id_cliente' => 1]);
        DB::table('tc_postazioni')->insert([
            'id' => 4, 'id_cliente' => 1, 'id_sede' => 1, 'file' => 'ingresso.jpg',
        ]);
        Storage::fake(config('filesystems.files_disk'));
        Storage::disk(config('filesystems.files_disk'))->put(Postazione::findOrFail(4)->percorsoFile(), 'foto-postazione');

        Livewire::actingAs(User::findOrFail(1))->test(Scansione::class)
            ->assertSee('scansione/foto', false)
            ->assertSee('Foto di Sede centrale', false);
    }

    public function test_pagina_senza_foto_non_chiede_limmagine(): void
    {
        $this->preparaTurno();

        Livewire::actingAs(User::findOrFail(1))->test(Scansione::class)
            ->assertDontSee('scansione/foto', false);
    }

    public function test_foto_del_turno_attivo_restituisce_il_file_della_sede(): void
    {
        $this->preparaTurno();
        DB::table('tc_sedi')->where('id', 1)->update(['id_cliente' => 1, 'foto' => 'sede.jpg']);
        DB::table('tc_postazioni')->insert([
            'id' => 4, 'id_cliente' => 1, 'id_sede' => 1, 'file' => 'ingresso.jpg',
        ]);
        Storage::fake(config('filesystems.files_disk'));
        Storage::disk(config('filesystems.files_disk'))->put(Sede::findOrFail(1)->percorsoFoto(), 'foto-sede');
        Storage::disk(config('filesystems.files_disk'))->put(Postazione::findOrFail(4)->percorsoFile(), 'foto-postazione');

        $this->actingAs(User::findOrFail(1))
            ->get(route('scansione.foto'))
            ->assertOk()
            ->assertStreamedContent('foto-sede');
    }

    public function test_foto_del_turno_attivo_usa_la_postazione_se_la_sede_non_ha_foto(): void
    {
        $this->preparaTurno();
        DB::table('tc_sedi')->where('id', 1)->update(['id_cliente' => 1, 'foto' => null]);
        DB::table('tc_postazioni')->insert([
            'id' => 4, 'id_cliente' => 1, 'id_sede' => 1, 'file' => 'ingresso.jpg',
        ]);
        Storage::fake(config('filesystems.files_disk'));
        Storage::disk(config('filesystems.files_disk'))->put(Postazione::findOrFail(4)->percorsoFile(), 'foto-postazione');

        $this->actingAs(User::findOrFail(1))
            ->get(route('scansione.foto'))
            ->assertOk()
            ->assertStreamedContent('foto-postazione');
    }

    public function test_foto_del_turno_attivo_non_sostituisce_il_file_mancante_con_un_altra_immagine(): void
    {
        $this->preparaTurno();
        DB::table('tc_sedi')->where('id', 1)->update(['id_cliente' => 1, 'foto' => 'assente.jpg']);
        Storage::fake(config('filesystems.files_disk'));
        Storage::disk(config('filesystems.files_disk'))->put(Sede::findOrFail(1)->cartella().'/Postazione-principale-1-1705509364.jpg', 'foto-altra-sede');

        $this->actingAs(User::findOrFail(1))
            ->get(route('scansione.foto'))
            ->assertNotFound();
    }

    public function test_foto_del_turno_attivo_non_e_disponibile_senza_turno_o_senza_file(): void
    {
        $this->preparaSede();
        DB::table('tc_admin')->insert([
            ['id' => 1, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Mario', 'cognome' => 'Rossi'],
            ['id' => 2, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Anna', 'cognome' => 'Verdi'],
        ]);
        DB::table('tc_turni')->insert([
            'id' => 7, 'id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 2,
            'data_inizio' => '2026-11-11', 'data_fine' => '2026-11-11',
            'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'stato' => 'P',
        ]);

        $this->actingAs(User::findOrFail(1))->get(route('scansione.foto'))->assertNotFound();

        DB::table('tc_turni')->where('id', 7)->update(['id_operatore' => 1]);

        $this->actingAs(User::findOrFail(1))->get(route('scansione.foto'))->assertNotFound();
    }

    public function test_ospite_e_admin_non_scaricano_la_foto_del_turno(): void
    {
        $this->preparaTurno();
        DB::table('tc_sedi')->where('id', 1)->update(['id_cliente' => 1, 'foto' => 'sede.jpg']);

        $this->get(route('scansione.foto'))->assertRedirect(route('login'));

        DB::table('tc_admin')->where('id', 1)->update(['user_type' => 2]);

        $this->actingAs(User::findOrFail(1))->get(route('scansione.foto'))->assertForbidden();
    }

    public function test_pagina_scansione_mostra_la_sostituzione_del_turno(): void
    {
        $this->preparaTurno();

        Livewire::actingAs(User::findOrFail(1))->test(Scansione::class)
            ->assertSee('Sostituisci turno')
            ->assertSee('Scansiona il QR principale della sede per la sostituzione del turno.');
    }

    public function test_sostituzione_assegna_il_primo_turno_programmato_della_sede_e_registra_lingresso(): void
    {
        $this->preparaSede();
        DB::table('tc_admin')->insert([
            ['id' => 1, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Mario', 'cognome' => 'Rossi'],
            ['id' => 2, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Anna', 'cognome' => 'Verdi'],
            ['id' => 3, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Luca', 'cognome' => 'Neri'],
        ]);
        DB::table('tc_turni')->insert([
            [
                'id' => 7, 'id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 2,
                'data_inizio' => '2026-11-10', 'data_fine' => '2026-11-11',
                'ora_inizio' => '22:00', 'ora_fine' => '06:00', 'stato' => 'P',
            ],
            [
                'id' => 8, 'id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 3,
                'data_inizio' => '2026-11-11', 'data_fine' => '2026-11-11',
                'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'stato' => 'P',
            ],
        ]);

        Livewire::actingAs(User::findOrFail(1))->test(Scansione::class)
            ->call('timbra', 'ITALPOL*000001', 45.0567356, 7.6693, true, 'Copertura collega')
            ->assertSee('Inizio turno registrato con successo');

        $this->assertSame(1, (int) DB::table('tc_turni')->where('id', 7)->value('id_operatore'));
        $this->assertSame('I', DB::table('tc_turni')->where('id', 7)->value('stato'));
        $this->assertSame(3, (int) DB::table('tc_turni')->where('id', 8)->value('id_operatore'));
        $this->assertDatabaseHas('tc_gda', [
            'id_turno' => 7,
            'id_operatore' => 1,
            'tipo' => 'I',
            'note_operatore' => 'Copertura collega',
        ]);
    }

    public function test_sostituzione_senza_turno_programmato_oggi_non_registra_la_timbratura(): void
    {
        $this->preparaSede();
        DB::table('tc_admin')->insert([
            ['id' => 1, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Mario', 'cognome' => 'Rossi'],
            ['id' => 2, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Anna', 'cognome' => 'Verdi'],
        ]);
        DB::table('tc_turni')->insert([
            [
                'id' => 7, 'id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 2,
                'data_inizio' => '2026-11-11', 'data_fine' => '2026-11-11',
                'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'stato' => 'I',
            ],
            [
                'id' => 8, 'id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 2,
                'data_inizio' => '2026-11-12', 'data_fine' => '2026-11-12',
                'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'stato' => 'P',
            ],
        ]);

        Livewire::actingAs(User::findOrFail(1))->test(Scansione::class)
            ->call('timbra', 'ITALPOL*000001', 45.0567356, 7.6693, true, null)
            ->assertSee('Turno non trovato');

        $this->assertSame(2, (int) DB::table('tc_turni')->where('id', 7)->value('id_operatore'));
        $this->assertSame(2, (int) DB::table('tc_turni')->where('id', 8)->value('id_operatore'));
        $this->assertSame(0, DB::table('tc_gda')->count());
    }

    public function test_timbratura_normale_non_prende_il_turno_programmato_di_un_altro_operatore(): void
    {
        $this->preparaSede();
        DB::table('tc_admin')->insert([
            ['id' => 1, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Mario', 'cognome' => 'Rossi'],
            ['id' => 2, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Anna', 'cognome' => 'Verdi'],
        ]);
        DB::table('tc_turni')->insert([
            'id' => 7, 'id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 2,
            'data_inizio' => '2026-11-11', 'data_fine' => '2026-11-11',
            'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'stato' => 'P',
        ]);

        Livewire::actingAs(User::findOrFail(1))->test(Scansione::class)
            ->call('timbra', 'ITALPOL*000001', 45.0567356, 7.6693, false, null)
            ->assertSee('Nessun turno attivo per te in questo momento.');

        $this->assertSame(2, (int) DB::table('tc_turni')->where('id', 7)->value('id_operatore'));
        $this->assertSame(0, DB::table('tc_gda')->count());
    }

    private function preparaSede(): void
    {
        $this->travelTo('2026-11-11 10:00:00');

        DB::table('tc_clienti')->insert(['id' => 1, 'ragione_sociale' => 'Cliente']);
        DB::table('tc_sedi')->insert([
            'id' => 1,
            'descrizione' => 'Sede centrale',
            'lat' => '45.0567356',
            'lng' => '7.6693',
            'distanza' => 100,
        ]);
        DB::table('tc_codici')->insert(['id' => 1, 'codice' => 'ITALPOL*000001', 'stato' => 'A', 'tipo' => 'S', 'id_sede' => 1]);
    }

    private function preparaTurno(): void
    {
        $this->travelTo('2026-11-11 10:00:00');

        DB::table('tc_admin')->insert(['id' => 1, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Mario', 'cognome' => 'Rossi']);
        DB::table('tc_clienti')->insert(['id' => 1, 'ragione_sociale' => 'Cliente']);
        DB::table('tc_sedi')->insert([
            'id' => 1,
            'descrizione' => 'Sede centrale',
            'lat' => '45.0567356',
            'lng' => '7.6693',
            'distanza' => 100,
        ]);
        DB::table('tc_codici')->insert(['id' => 1, 'codice' => 'ITALPOL*000001', 'stato' => 'A', 'tipo' => 'S', 'id_sede' => 1]);
        DB::table('tc_turni')->insert([
            'id' => 1,
            'id_cliente' => 1,
            'id_sede' => 1,
            'id_operatore' => 1,
            'data_inizio' => '2026-11-11',
            'data_fine' => '2026-11-11',
            'ora_inizio' => '08:00',
            'ora_fine' => '16:00',
            'stato' => 'P',
        ]);
    }
}
