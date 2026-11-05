<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SedeForm;
use App\Livewire\Sedi;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SediSuiteTest extends TestCase
{
    private string $disco;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');
        $this->disco = config('filesystems.files_disk');
        Storage::fake($this->disco);
        $this->creaSchema();
    }

    private function utente(int $tipo, int $id = 900, ?int $idCliente = null): User
    {
        DB::table('tc_admin')->insertOrIgnore(['id' => $id, 'user_type' => $tipo, 'attivo' => 'Y', 'nome' => 'Test', 'cognome' => "T$id", 'id_cliente' => $idCliente]);

        return User::find($id);
    }

    private function admin(): User
    {
        return $this->utente(2, 900);
    }

    private function cliente(string $nome = 'Alpha Srl', ?int $isTest = null): int
    {
        return DB::table('tc_clienti')->insertGetId(['ragione_sociale' => $nome, 'isTest' => $isTest]);
    }

    /** @return array{sede: int, postazione: int, codiceSede: int, codicePostazione: int} */
    private function sedeCompleta(int $cliente, string $nome = 'Sede Uno'): array
    {
        $sede = DB::table('tc_sedi')->insertGetId(['id_cliente' => $cliente, 'descrizione' => $nome, 'lat' => 44.1, 'lng' => 10.1]);
        $codiceSede = DB::table('tc_codici')->insertGetId(['codice' => "ITALPOL*S$sede", 'stato' => 'A', 'tipo' => 'S', 'id_sede' => $sede]);
        $postazione = DB::table('tc_postazioni')->insertGetId(['id_cliente' => $cliente, 'id_sede' => $sede, 'titolo' => 'Ingresso']);
        $codicePostazione = DB::table('tc_codici')->insertGetId(['codice' => "ITALPOL*P$postazione", 'stato' => 'A', 'tipo' => 'P', 'id_sede' => $sede, 'id_postazione' => $postazione]);
        DB::table('tc_sedi')->where('id', $sede)->update(['id_codice' => $codiceSede]);
        DB::table('tc_postazioni')->where('id', $postazione)->update(['id_codice' => $codicePostazione]);
        Storage::disk($this->disco)->put("protected/documenti/Cliente_$cliente/Sede_$sede/foto.jpg", 'x');
        Storage::disk($this->disco)->put("protected/documenti/Cliente_$cliente/Sede_$sede/Postazione_$postazione/p.jpg", 'x');

        return compact('sede', 'postazione', 'codiceSede', 'codicePostazione');
    }

    public function test_delete_requires_exact_confirmation_word(): void
    {
        $c = $this->cliente();
        $s = $this->sedeCompleta($c);

        Livewire::actingAs($this->admin())->test(Sedi::class)
            ->call('richiediEliminazione', $s['sede'])->assertSet('eliminaId', $s['sede'])
            ->set('conferma', 'sbagliato')->call('elimina')->assertHasErrors(['conferma']);

        $this->assertSame(1, DB::table('tc_sedi')->count());
        Storage::disk($this->disco)->assertExists("protected/documenti/Cliente_$c/Sede_{$s['sede']}/foto.jpg");
    }

    public function test_delete_cascade_is_complete_isolated_and_cleans_files_and_codes(): void
    {
        $c = $this->cliente();
        $altroCliente = $this->cliente('Beta');
        $a = $this->sedeCompleta($c, 'Da eliminare');
        $b = $this->sedeCompleta($altroCliente, 'Da tenere');
        foreach ([$a['sede'], $b['sede']] as $id) {
            DB::table('tc_turni')->insert(['id_sede' => $id, 'id_cliente' => $c, 'stato' => 'F']);
            DB::table('tc_turni_step')->insert(['id_sede' => $id]);
            DB::table('tc_documenti')->insert(['id_tipo' => $id, 'tipo' => 'S', 'file' => 'd.pdf']);
            DB::table('tc_gda')->insert(['id_sede' => $id]);
        }
        // Il bug del vecchio: l'id della sede usato come id_cliente non deve toccare altri clienti.
        DB::table('tc_postazioni')->insert(['id_cliente' => $a['sede'], 'id_sede' => $b['sede'], 'titolo' => 'Non toccare']);

        Livewire::actingAs($this->admin())->test(Sedi::class)
            ->call('richiediEliminazione', $a['sede'])->set('conferma', ' CONFERMA ')->call('elimina')
            ->assertHasNoErrors()->assertSet('eliminaId', null);

        $this->assertSame(0, DB::table('tc_sedi')->where('id', $a['sede'])->count());
        foreach (['tc_turni', 'tc_turni_step', 'tc_postazioni'] as $t) {
            $this->assertSame(0, DB::table($t)->where('id_sede', $a['sede'])->count(), $t);
            $this->assertGreaterThan(0, DB::table($t)->where('id_sede', $b['sede'])->count(), $t);
        }
        $this->assertSame(0, DB::table('tc_documenti')->where('id_tipo', $a['sede'])->where('tipo', 'S')->count());
        $this->assertSame(1, DB::table('tc_documenti')->where('id_tipo', $b['sede'])->count());
        $this->assertSame(2, DB::table('tc_gda')->count(), 'le presenze restano nello storico');
        Storage::disk($this->disco)->assertMissing("protected/documenti/Cliente_$c/Sede_{$a['sede']}/foto.jpg");
        Storage::disk($this->disco)->assertMissing("protected/documenti/Cliente_$c/Sede_{$a['sede']}/Postazione_{$a['postazione']}/p.jpg");
        Storage::disk($this->disco)->assertExists("protected/documenti/Cliente_$altroCliente/Sede_{$b['sede']}/foto.jpg");

        foreach ([$a['codiceSede'], $a['codicePostazione']] as $codice) {
            $riga = DB::table('tc_codici')->find($codice);
            $this->assertSame('C', $riga->stato);
            $this->assertNull($riga->id_sede);
        }
        $this->assertSame('A', DB::table('tc_codici')->find($b['codiceSede'])->stato);
        $this->assertSame(1, DB::table('_tracking')->where('action', 'like', 'Rimozione sede Da eliminare')->count());
    }

    public function test_delete_postazione_removes_folder_and_frees_code(): void
    {
        $c = $this->cliente();
        $s = $this->sedeCompleta($c);

        Livewire::actingAs($this->admin())->test(Sedi::class)->call('eliminaPostazione', $s['postazione']);

        $this->assertSame(0, DB::table('tc_postazioni')->count());
        Storage::disk($this->disco)->assertMissing("protected/documenti/Cliente_$c/Sede_{$s['sede']}/Postazione_{$s['postazione']}/p.jpg");
        Storage::disk($this->disco)->assertExists("protected/documenti/Cliente_$c/Sede_{$s['sede']}/foto.jpg");
        $this->assertSame('C', DB::table('tc_codici')->find($s['codicePostazione'])->stato);
        $this->assertSame('A', DB::table('tc_codici')->find($s['codiceSede'])->stato);
    }

    public function test_list_shows_turni_counts(): void
    {
        $c = $this->cliente();
        $s = $this->sedeCompleta($c);
        DB::table('tc_turni')->insert([['id_sede' => $s['sede'], 'stato' => 'F'], ['id_sede' => $s['sede'], 'stato' => 'A'], ['id_sede' => $s['sede'], 'stato' => 'F']]);

        Livewire::actingAs($this->admin())->test(Sedi::class)->assertSee('3 turni · 2 conclusi');
    }

    public function test_export_csv_respects_filters_and_roles(): void
    {
        $c = $this->cliente('Alpha');
        $b = $this->cliente('Beta');
        $this->sedeCompleta($c, 'Sede Alpha');
        $this->sedeCompleta($b, 'Sede Beta');

        $r = Livewire::actingAs($this->admin())->test(Sedi::class)->set('filtroCliente', $c)->call('esporta');
        $r->assertFileDownloaded('sedi-'.now()->format('Y-m-d').'.csv');
        $this->assertSame(1, DB::table('_tracking')->where('action', 'Esportazione elenco Sedi')->count());

        Livewire::actingAs($this->utente(3, 901, $c))->test(Sedi::class)->call('esporta')->assertForbidden();
    }

    public function test_roles_and_visibility_on_delete(): void
    {
        $normale = $this->cliente('Reale');
        $test = $this->cliente('Prova', 1);
        $sn = $this->sedeCompleta($normale, 'Sede Reale');
        $st = $this->sedeCompleta($test, 'Sede Prova');

        foreach ([[3, 901, $normale], [4, 902, null]] as [$tipo, $id, $cl]) {
            Livewire::actingAs($this->utente($tipo, $id, $cl))->test(Sedi::class)->call('richiediEliminazione', $sn['sede'])->assertForbidden();
        }
        Livewire::actingAs($this->admin())->test(Sedi::class)->call('richiediEliminazione', $st['sede'])->assertNotFound();
        Livewire::actingAs($this->utente(1, 903))->test(Sedi::class)->call('richiediEliminazione', $sn['sede'])->assertNotFound();

        $this->assertSame(2, DB::table('tc_sedi')->count());
    }

    public function test_edit_cannot_move_sede_to_another_client(): void
    {
        $c = $this->cliente();
        $altro = $this->cliente('Beta');
        $s = $this->sedeCompleta($c);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);

        Livewire::actingAs($this->admin())->test(SedeForm::class, ['sede' => $s['sede']])
            ->set('f.cliente_id', $altro)
            ->set('f.descrizione', 'Rinominata')->set('f.indirizzo', 'Via Roma 1')->set('f.citta', 'Modena')->set('f.provincia', 'MO')
            ->set('f.lat', '44.5')->set('f.lng', '10.9')->set('f.distanza_max', 100)
            ->call('salva')->assertHasNoErrors();

        $riga = DB::table('tc_sedi')->find($s['sede']);
        $this->assertSame($c, (int) $riga->id_cliente);
        $this->assertSame('Rinominata', $riga->descrizione);
        $this->assertSame(1, DB::table('_tracking')->where('action', 'Aggiornamento dati sede Rinominata')->count());
    }

    public function test_save_without_coordinates_warns_but_keeps_old_behaviour(): void
    {
        $c = $this->cliente();
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Modena', 'sigla_provincia' => 'MO', 'id_provincia' => 1]);

        Livewire::actingAs($this->admin())->test(SedeForm::class)
            ->set('f.cliente_id', $c)->set('f.descrizione', 'Nuova')->set('f.indirizzo', 'Via Roma 1')->set('f.citta', 'Modena')->set('f.provincia', 'MO')
            ->call('salva')->assertHasNoErrors();

        $this->assertSame(1, DB::table('tc_sedi')->count());
        $this->assertSame(0.0, (float) DB::table('tc_sedi')->value('lat'));
        $this->assertSame(1, DB::table('_tracking')->where('action', 'Creazione sede Nuova')->count());
        $this->assertStringContainsString('senza coordinate', (string) session('avviso'));
    }

    private function creaSchema(): void
    {
        Schema::create('tc_admin', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_type')->nullable();
            $table->string('attivo')->nullable();
            foreach (['nome', 'cognome', 'email', 'user', 'password'] as $c) {
                $table->string($c)->nullable();
            }
            $table->unsignedInteger('id_cliente')->nullable();
        });
        Schema::create('tc_clienti', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('ragione_sociale')->nullable();
            $table->integer('isTest')->nullable();
        });
        Schema::create('tc_sedi', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_codice')->nullable();
            foreach (['descrizione', 'indirizzo', 'cap', 'telefono', 'email', 'note', 'colore', 'pos', 'foto'] as $c) {
                $table->string($c)->nullable();
            }
            $table->integer('citta_id')->nullable();
            $table->integer('provincia_id')->nullable();
            $table->float('lat')->default(0);
            $table->float('lng')->default(0);
            $table->date('inizio_contratto')->nullable();
            $table->date('fine_contratto')->nullable();
            $table->float('tariffa')->nullable();
            $table->integer('distanza')->default(80);
            foreach (['lun', 'mar', 'mer', 'gio', 'ven', 'sab', 'dom'] as $c) {
                $table->integer($c)->nullable();
            }
        });
        Schema::create('tc_postazioni', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
            $table->unsignedInteger('id_codice')->nullable();
            $table->string('titolo')->nullable();
            $table->text('info')->nullable();
            $table->string('file')->nullable();
            $table->integer('passaggi')->nullable();
            $table->integer('minuti')->nullable();
        });
        Schema::create('tc_codici', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('tipo')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
            $table->unsignedInteger('id_postazione')->nullable();
            $table->string('codice')->nullable();
            $table->string('stato')->nullable();
        });
        Schema::create('tc_turni', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
            $table->string('stato')->nullable();
        });
        Schema::create('tc_turni_step', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_sede')->nullable();
        });
        Schema::create('tc_gda', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_sede')->nullable();
        });
        Schema::create('tc_documenti', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_tipo')->nullable();
            $table->string('tipo')->nullable();
            $table->string('file')->nullable();
        });
        Schema::create('_comuni', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('comune')->nullable();
            $table->string('sigla_provincia')->nullable();
            $table->unsignedInteger('id_provincia')->nullable();
        });
        Schema::create('_tracking', function (Blueprint $table): void {
            $table->integer('id')->primary();
            foreach (['addr', 'host', 'agent', 'pagina', 'data_view', 'action'] as $c) {
                $table->string($c)->nullable();
            }
            $table->unsignedInteger('id_user')->nullable();
        });
    }
}
