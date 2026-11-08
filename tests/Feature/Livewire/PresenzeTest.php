<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Presenze;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class PresenzeTest extends TestCase
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
            $table->string('user')->nullable();
            $table->string('password')->nullable();
            $table->string('attivo')->default('Y');
            $table->unsignedInteger('user_type')->nullable();
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('codice')->nullable();
        });
        Schema::create('tc_clienti', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('ragione_sociale')->nullable();
            $table->integer('isTest')->nullable();
        });
        Schema::create('tc_sedi', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->string('descrizione')->nullable();
        });
        Schema::create('tc_turni', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->date('data_inizio')->nullable();
            $table->string('ora_inizio')->nullable();
            $table->float('ore')->nullable();
            $table->float('ore_lavorate')->nullable();
            $table->string('stato')->nullable();
        });
        Schema::create('tc_gda', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_turno')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
            $table->unsignedInteger('id_postazione')->nullable();
            $table->string('tipo')->nullable();
            $table->date('data')->nullable();
            $table->string('orario')->nullable();
            $table->float('distanza')->nullable();
            $table->integer('ritardo')->nullable();
            $table->text('note_operatore')->nullable();
        });
        Schema::create('tc_postazioni', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('titolo')->nullable();
        });
    }

    public function test_client_hours_has_legacy_filters_and_table_without_switching_views(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2]);

        $response = $this->actingAs($staff)->get(route('presenze.clienti'))->assertOk();

        $response->assertSee('Conteggio orario clienti')
            ->assertSee('Cliente')
            ->assertSee('Sede')
            ->assertSee('Dal')
            ->assertSee('Al')
            ->assertDontSee('role="tablist"', false)
            ->assertDontSee('Giornale delle attività');
    }

    public function test_operator_hours_has_legacy_filters_and_table_without_switching_views(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2]);

        $response = $this->actingAs($staff)->get(route('presenze.operatori'))->assertOk();

        $response->assertSee('Conteggio orario operatori')
            ->assertSee('Codice')
            ->assertSee('Operatore')
            ->assertSee('Dal')
            ->assertSee('Al')
            ->assertDontSee('role="tablist"', false)
            ->assertDontSee('Giornale delle attività');
    }

    public function test_each_hours_section_has_a_distinct_url_and_keeps_its_dropdown_closed(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2]);

        $urls = [
            route('presenze') => 'Giornale delle attività',
            route('presenze.clienti') => 'Conteggio orario clienti',
            route('presenze.operatori') => 'Conteggio orario operatori',
        ];

        $this->assertSame('/presenze', parse_url(route('presenze'), PHP_URL_PATH));
        $this->assertSame('/presenze/clienti', parse_url(route('presenze.clienti'), PHP_URL_PATH));
        $this->assertSame('/presenze/operatori', parse_url(route('presenze.operatori'), PHP_URL_PATH));

        foreach ($urls as $url => $titolo) {
            $this->actingAs($staff)->get($url)->assertOk()
                ->assertSee($titolo)
                ->assertSee('x-data="{ aperto: false }"', false)
                ->assertDontSee('x-data="{ aperto: true }"', false);
        }
    }

    public function test_filter_controls_are_recreated_when_switching_gda_sections(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2]);
        $oggi = today()->toDateString();

        Livewire::actingAs($staff)->test(Presenze::class)
            ->set('da', $oggi.'T00:00')
            ->set('a', $oggi.'T23:59')
            ->assertSee('wire:key="presenze-filtri-gda"', false)
            ->set('vista', 'clienti')
            ->assertSee('wire:key="presenze-filtri-clienti"', false)
            ->set('vista', 'operatori')
            ->assertSee('wire:key="presenze-filtri-operatori"', false);
    }

    public function test_gda_date_and_time_filters_limit_turns_to_the_selected_interval(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2]);
        $cliente = DB::table('tc_clienti')->insertGetId(['ragione_sociale' => 'Cliente Test', 'isTest' => 0]);
        $operatore = DB::table('tc_admin')->insertGetId([
            'user' => 'operatore',
            'password' => 'secret',
            'attivo' => 'Y',
            'user_type' => 4,
            'codice' => 'SIERRA 240',
            'nome' => 'WALID',
            'cognome' => 'EL KAOUACHI',
        ]);
        $sede = DB::table('tc_sedi')->insertGetId(['id_cliente' => $cliente, 'descrizione' => 'Sede Test']);

        foreach (['08:00' => 'Prima', '09:00' => 'Dentro', '10:30' => 'Dopo'] as $ora => $nota) {
            $turno = DB::table('tc_turni')->insertGetId([
                'id_cliente' => $cliente,
                'id_sede' => $sede,
                'id_operatore' => $operatore,
                'data_inizio' => today()->toDateString(),
                'ora_inizio' => $ora,
                'ore' => 1,
                'ore_lavorate' => 1,
                'stato' => 'F',
            ]);
            DB::table('tc_gda')->insert([
                'id_turno' => $turno,
                'id_operatore' => $operatore,
                'id_cliente' => $cliente,
                'id_sede' => $sede,
                'tipo' => 'I',
                'data' => today()->toDateString(),
                'orario' => $ora,
                'note_operatore' => $nota,
            ]);
        }

        Livewire::actingAs($staff)->test(Presenze::class)
            ->assertSet('da', '')
            ->assertSet('a', '')
            ->assertSee('Prima')
            ->assertSee('Dentro')
            ->assertSee('Dopo')
            ->set('da', today()->format('Y-m-d').'T08:30')
            ->set('a', today()->format('Y-m-d').'T10:00')
            ->assertSee('Dentro')
            ->assertDontSee('Prima')
            ->assertDontSee('Dopo');
    }

    public function test_client_and_site_filters_limit_client_hours(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2]);
        $dati = $this->creaTurniFiltrabili();

        $componente = Livewire::actingAs($staff)->test(Presenze::class)
            ->set('vista', 'clienti')
            ->set('da', today()->toDateString())
            ->set('a', today()->toDateString())
            ->set('filtroCliente', $dati['clienteUno'])
            ->set('filtroSede', $dati['sedeUno'])
            ->call('applicaFiltri');

        $this->assertSame([['nome' => 'Cliente Uno', 'previste' => 2.0, 'lavorate' => 2.0]], $componente->viewData('perCliente')->values()->all());
    }

    public function test_operator_filter_limits_operator_hours(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2]);
        $dati = $this->creaTurniFiltrabili();

        $componente = Livewire::actingAs($staff)->test(Presenze::class)
            ->set('vista', 'operatori')
            ->set('da', today()->toDateString())
            ->set('a', today()->toDateString())
            ->set('filtroOperatore', $dati['operatoreDue'])
            ->call('applicaFiltri');

        $this->assertSame([$dati['operatoreDue']], $componente->viewData('perOperatore')->keys()->all());
        $this->assertSame(7.0, $componente->viewData('perOperatore')->get($dati['operatoreDue'])['previste']);
    }

    public function test_gda_filters_limit_turns_by_client_site_operator_and_date_time(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2]);
        $dati = $this->creaTurniFiltrabili();

        $componente = Livewire::actingAs($staff)->test(Presenze::class)
            ->call('applicaFiltri', [
                'filtroCliente' => $dati['clienteUno'],
                'filtroSede' => $dati['sedeUno'],
                'filtroOperatore' => $dati['operatoreUno'],
                'da' => today()->format('Y-m-d').'T08:30',
                'a' => today()->format('Y-m-d').'T10:00',
            ])
            ->assertSet('filtroCliente', $dati['clienteUno'])
            ->assertSet('filtroSede', $dati['sedeUno'])
            ->assertSet('filtroOperatore', $dati['operatoreUno']);

        $componente->assertViewHas('turniGda', fn ($pagina): bool => $pagina->pluck('id')->all() === [$dati['turnoUno']]);
    }

    public function test_gda_has_legacy_title_filters_and_table_without_extra_sections(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2]);
        $cliente = DB::table('tc_clienti')->insertGetId(['ragione_sociale' => 'Cliente Test', 'isTest' => 0]);
        $operatore = DB::table('tc_admin')->insertGetId([
            'user' => 'operatore',
            'password' => 'secret',
            'attivo' => 'Y',
            'user_type' => 4,
            'codice' => 'SIERRA 240',
            'nome' => 'WALID',
            'cognome' => 'EL KAOUACHI',
        ]);
        $sede = DB::table('tc_sedi')->insertGetId(['id_cliente' => $cliente, 'descrizione' => 'Sede Test']);
        $turno = DB::table('tc_turni')->insertGetId([
            'id_cliente' => $cliente,
            'id_sede' => $sede,
            'id_operatore' => $operatore,
            'data_inizio' => today()->toDateString(),
            'ora_inizio' => '08:00',
            'ore' => 8,
            'ore_lavorate' => 8,
            'stato' => 'F',
        ]);
        DB::table('tc_gda')->insert([
            'id_turno' => $turno,
            'id_operatore' => $operatore,
            'id_cliente' => $cliente,
            'id_sede' => $sede,
            'tipo' => 'I',
            'data' => today()->toDateString(),
            'orario' => '08:00',
        ]);

        $response = $this->actingAs($staff)->get(route('presenze'))->assertOk();

        $response->assertSee('<title>Giornale delle attività · Omnia Control</title>', false)
            ->assertSee('Giornale delle attività')
            ->assertSee('Ragione Sociale')
            ->assertSee('Sede')
            ->assertSee('Operattore')
            ->assertSee('Dal')
            ->assertSee('Al')
            ->assertSee('Inizio Turno')
            ->assertSee('Fine Turno')
            ->assertSee('Ore Previste')
            ->assertSee('Ore Lavorate')
            ->assertSee('Note Operatore')
            ->assertSee('Passaggi')
            ->assertSee('Distanza')
            ->assertSee('Stampa')
            ->assertSee('SIERRA 240 (EL KAOUACHI WALID)')
            ->assertDontSee('Registro timbrature')
            ->assertDontSee('Esporta CSV')
            ->assertDontSee('role="tablist"', false);
    }

    /**
     * @return array{clienteUno: int, sedeUno: int, operatoreUno: int, operatoreDue: int, turnoUno: int}
     */
    private function creaTurniFiltrabili(): array
    {
        $clienteUno = DB::table('tc_clienti')->insertGetId(['ragione_sociale' => 'Cliente Uno', 'isTest' => 0]);
        $clienteDue = DB::table('tc_clienti')->insertGetId(['ragione_sociale' => 'Cliente Due', 'isTest' => 0]);
        $operatoreUno = DB::table('tc_admin')->insertGetId([
            'user' => 'operatore-uno', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 4, 'nome' => 'Uno', 'cognome' => 'Operatore',
        ]);
        $operatoreDue = DB::table('tc_admin')->insertGetId([
            'user' => 'operatore-due', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 4, 'nome' => 'Due', 'cognome' => 'Operatore',
        ]);
        $sedeUno = DB::table('tc_sedi')->insertGetId(['id_cliente' => $clienteUno, 'descrizione' => 'Sede Uno']);
        $sedeDue = DB::table('tc_sedi')->insertGetId(['id_cliente' => $clienteUno, 'descrizione' => 'Sede Due']);
        $sedeTre = DB::table('tc_sedi')->insertGetId(['id_cliente' => $clienteDue, 'descrizione' => 'Sede Tre']);

        $turni = [
            [$clienteUno, $sedeUno, $operatoreUno, '09:00', 2.0, 'Turno selezionato'],
            [$clienteUno, $sedeDue, $operatoreDue, '09:30', 3.0, 'Sede diversa'],
            [$clienteDue, $sedeTre, $operatoreDue, '11:00', 4.0, 'Cliente diverso'],
        ];
        $turnoUno = 0;

        foreach ($turni as [$idCliente, $idSede, $idOperatore, $ora, $ore, $nota]) {
            $turno = DB::table('tc_turni')->insertGetId([
                'id_cliente' => $idCliente,
                'id_sede' => $idSede,
                'id_operatore' => $idOperatore,
                'data_inizio' => today()->toDateString(),
                'ora_inizio' => $ora,
                'ore' => $ore,
                'ore_lavorate' => $ore,
                'stato' => 'F',
            ]);
            DB::table('tc_gda')->insert([
                'id_turno' => $turno,
                'id_operatore' => $idOperatore,
                'id_cliente' => $idCliente,
                'id_sede' => $idSede,
                'tipo' => 'I',
                'data' => today()->toDateString(),
                'orario' => $ora,
                'note_operatore' => $nota,
            ]);
            if ($turnoUno === 0) {
                $turnoUno = $turno;
            }
        }

        return [
            'clienteUno' => $clienteUno,
            'sedeUno' => $sedeUno,
            'operatoreUno' => $operatoreUno,
            'operatoreDue' => $operatoreDue,
            'turnoUno' => $turnoUno,
        ];
    }
}
