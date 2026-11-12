<?php

namespace Tests\Feature;

use App\Livewire\Presenze;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class GdaRegistroTest extends TestCase
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
            $table->string('user')->nullable();
            $table->string('id_cliente')->nullable();
        });
        Schema::create('tc_clienti', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('ragione_sociale')->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('cap')->nullable();
            $table->string('piva')->nullable();
            $table->string('telefono')->nullable();
            $table->unsignedInteger('citta_id')->nullable();
            $table->unsignedInteger('provincia_id')->nullable();
            $table->integer('isTest')->nullable();
        });
        Schema::create('_comuni', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('comune')->nullable();
        });
        Schema::create('_provincia', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('sigla')->nullable();
        });
        Schema::create('tc_sedi', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->string('descrizione')->nullable();
        });
        Schema::create('tc_postazioni', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('titolo')->nullable();
        });
        Schema::create('tc_config', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('app')->nullable();
            $table->string('societa_email')->nullable();
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
            $table->float('ore')->nullable();
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
            $table->date('data')->nullable();
            $table->string('orario')->nullable();
            $table->text('note_operatore')->nullable();
            $table->float('distanza')->nullable();
            $table->integer('ritardo')->nullable();
            $table->string('tipo')->nullable();
        });

        DB::table('tc_admin')->insert([
            ['id' => 1, 'user_type' => 2, 'attivo' => 'Y', 'nome' => 'Admin', 'cognome' => 'Test'],
            ['id' => 2, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Mario', 'cognome' => 'Rossi'],
        ]);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Torino']);
        DB::table('_provincia')->insert(['id' => 1, 'sigla' => 'TO']);
        DB::table('tc_clienti')->insert([
            ['id' => 1, 'ragione_sociale' => 'Cliente Uno', 'indirizzo' => 'Via Po 1', 'cap' => '10100', 'piva' => '12345678901', 'telefono' => '011', 'citta_id' => 1, 'provincia_id' => 1],
            ['id' => 2, 'ragione_sociale' => 'Cliente Due', 'indirizzo' => null, 'cap' => null, 'piva' => null, 'telefono' => null, 'citta_id' => 1, 'provincia_id' => 1],
        ]);
        DB::table('tc_sedi')->insert([['id' => 1, 'id_cliente' => 1, 'descrizione' => 'Sede Uno'], ['id' => 2, 'id_cliente' => 2, 'descrizione' => 'Sede Due']]);
        DB::table('tc_postazioni')->insert(['id' => 1, 'titolo' => 'Cancello Nord']);
        DB::table('tc_config')->insert(['id' => 1, 'app' => 'Omnia', 'societa_email' => 'info@example.com']);

        $turno = fn (int $id, int $cliente, int $sede, string $stato, float $ore, ?float $lavorate) => [
            'id' => $id, 'id_cliente' => $cliente, 'id_sede' => $sede, 'id_operatore' => 2,
            'data_inizio' => '2026-11-03', 'data_fine' => '2026-11-03', 'ora_inizio' => '08:00', 'ora_fine' => '16:00',
            'ore' => $ore, 'ore_lavorate' => $lavorate, 'stato' => $stato,
        ];
        DB::table('tc_turni')->insert([
            $turno(1, 1, 1, 'F', 8, 7.5),
            $turno(2, 1, 1, 'I', 8, null),
            $turno(3, 2, 2, 'F', 6, 6),
        ]);
        DB::table('tc_gda')->insert([
            ['id_operatore' => 2, 'id_cliente' => 1, 'id_sede' => 1, 'id_turno' => 1, 'id_postazione' => null, 'data' => '2026-11-03', 'orario' => '08:05:00', 'tipo' => 'I', 'ritardo' => 5, 'distanza' => 0.02, 'note_operatore' => 'Tutto ok'],
            ['id_operatore' => 2, 'id_cliente' => 1, 'id_sede' => 1, 'id_turno' => 1, 'id_postazione' => 1, 'data' => '2026-11-03', 'orario' => '10:00:00', 'tipo' => 'P', 'ritardo' => 3, 'distanza' => null, 'note_operatore' => null],
            ['id_operatore' => 2, 'id_cliente' => 1, 'id_sede' => 1, 'id_turno' => 1, 'id_postazione' => null, 'data' => '2026-11-03', 'orario' => '15:50:00', 'tipo' => 'F', 'ritardo' => -10, 'distanza' => null, 'note_operatore' => null],
        ]);
    }

    private function admin(): User
    {
        return User::findOrFail(1);
    }

    public function test_senza_filtri_non_mostra_totali_e_l_elenco_contiene_solo_turni_completati(): void
    {
        Livewire::actingAs($this->admin())->test(Presenze::class)
            ->set('da', '2026-11-01')->set('a', '2026-11-30')
            ->assertViewHas('totali', null)
            ->assertViewHas('turniGda', fn ($pagina) => $pagina->pluck('id')->sort()->values()->all() === [1, 3]);
    }

    public function test_totali_contano_solo_turni_completati_con_priorita_cliente_sede_operatore(): void
    {
        Livewire::actingAs($this->admin())->test(Presenze::class)
            ->set('da', '2026-11-01')->set('a', '2026-11-30')
            ->set('filtroCliente', 1)
            ->assertViewHas('totali', ['etichetta' => 'cliente', 'previste' => 8.0, 'lavorate' => 7.5, 'saldo' => -0.5])
            ->set('filtroCliente', null)
            ->set('filtroOperatore', 2)
            ->assertViewHas('totali', ['etichetta' => 'operatore', 'previste' => 14.0, 'lavorate' => 13.5, 'saldo' => -0.5])
            ->set('filtroSede', 2)
            ->assertViewHas('totali', ['etichetta' => 'sede', 'previste' => 6.0, 'lavorate' => 6.0, 'saldo' => 0.0]);
    }

    public function test_dettaglio_passaggi_del_turno(): void
    {
        $componente = Livewire::actingAs($this->admin())->test(Presenze::class)
            ->set('da', '2026-11-01')->set('a', '2026-11-30')
            ->assertDontSee('Passaggi postazioni')
            ->assertSee('Visualizza 1 passaggio')
            ->assertDontSee('Passaggi (')
            ->call('mostraPassaggi', 1)
            ->assertSee('Passaggi postazioni — Sede Uno')
            ->assertSee('3 Min');

        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML($componente->html());
        $radice = (new DOMXPath($documento))->query('//*[@*[local-name()="wire:id"]]')->item(0);
        $dettaglio = (new DOMXPath($documento))->query('.//*[@role="dialog" and starts-with(@aria-label, "Passaggi postazioni")]', $radice);
        $this->assertSame(1, $dettaglio->length);

        $componente->call('chiudiPassaggi')
            ->assertDontSee('Passaggi postazioni');
    }

    public function test_stampa_gda_riporta_intestazione_turni_e_passaggi_del_cliente(): void
    {
        $this->actingAs($this->admin())
            ->get(route('presenze.stampa', ['cliente' => 1, 'da' => '2026-11-01', 'a' => '2026-11-30']))
            ->assertOk()
            ->assertSee('Giornale delle attività (GDA)')
            ->assertSee('Cliente Uno')
            ->assertSee('12345678901')
            ->assertSee('Sede Uno 03-11-2026')
            ->assertSee('Rossi Mario')
            ->assertSee('Postazione principale')
            ->assertSee('Cancello Nord')
            ->assertSee('Tutto ok')
            ->assertSee('7.75')
            ->assertDontSee('Cliente Due');
    }

    public function test_stampa_gda_ricava_il_cliente_dalla_sede_e_richiede_un_criterio(): void
    {
        $this->actingAs($this->admin())->get(route('presenze.stampa', ['sede' => 1]))
            ->assertOk()->assertSee('Cliente Uno');

        $this->actingAs($this->admin())->get(route('presenze.stampa'))->assertStatus(422);
    }
}
