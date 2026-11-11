<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Turni;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class TurniTest extends TestCase
{
    private const ORA_INGRESSO = '08:07';

    private const ORA_USCITA = '16:03';

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
            $table->string('email')->nullable();
            $table->string('user')->nullable();
            $table->string('id_cliente')->nullable();
        });
        Schema::create('tc_clienti', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('ragione_sociale')->nullable();
            $table->integer('isTest')->nullable();
        });
        Schema::create('tc_sedi', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('citta_id')->nullable();
            $table->string('descrizione')->nullable();
        });
        Schema::create('_comuni', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('comune')->nullable();
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
            $table->text('note_operatore')->nullable();
        });
        Schema::create('tc_gda', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_turno')->nullable();
            $table->string('tipo')->nullable();
            $table->date('data')->nullable();
            $table->string('orario')->nullable();
        });
    }

    public function test_operatore_vede_il_collegamento_per_sostituire_il_turno(): void
    {
        DB::table('tc_admin')->insert([
            'id' => 1, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Mario', 'cognome' => 'Rossi',
        ]);

        Livewire::actingAs(User::findOrFail(1))->test(Turni::class)
            ->assertSee('Sostituisci turno')
            ->assertSee('sostituzione=1', false);
    }

    public function test_staff_non_vede_il_collegamento_per_sostituire_il_turno(): void
    {
        DB::table('tc_admin')->insert([
            'id' => 1, 'user_type' => 2, 'attivo' => 'Y', 'nome' => 'Admin', 'cognome' => 'Test',
        ]);

        Livewire::actingAs(User::findOrFail(1))->test(Turni::class)
            ->assertDontSee('Sostituisci turno');
    }

    public function test_calendar_status_filter_can_be_selected_and_cleared(): void
    {
        DB::table('tc_admin')->insert([
            'id' => 1, 'user_type' => 2, 'attivo' => 'Y', 'nome' => 'Admin', 'cognome' => 'Test',
        ]);

        $componente = Livewire::actingAs(User::findOrFail(1))->test(Turni::class);

        $componente
            ->assertSee('wire:click="$set(\'filtroStato\', \'completato\')"', false)
            ->assertSee('class="fixed z-[60]', false)
            ->assertSee('id="suggerimenti-sedi"', false)
            ->assertSee('id="suggerimenti-operatori"', false)
            ->set('filtroStato', 'completato')
            ->assertSet('filtroStato', 'completato')
            ->assertSee('wire:click="$set(\'filtroStato\', \'\')"', false)
            ->assertSee('aria-label="Azzera filtro stato"', false)
            ->set('filtroStato', '')
            ->assertSet('filtroStato', '')
            ->assertSee('wire:click="$set(\'filtroStato\', \'completato\')"', false);
    }

    public function test_agenda_page_has_a_single_reactive_title(): void
    {
        DB::table('tc_admin')->insert([
            'id' => 1, 'user_type' => 2, 'attivo' => 'Y', 'nome' => 'Admin', 'cognome' => 'Test',
        ]);

        $response = $this->actingAs(User::findOrFail(1))->get(route('turni'));

        $response->assertOk()->assertSee('Agenda Pos');
        $this->assertSame(1, substr_count($response->getContent(), '<h1 '));
    }

    public function test_agenda_title_and_total_follow_selected_filters_and_list_dates(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 7)->setTime(12, 0));
        DB::table('tc_admin')->insert([
            ['id' => 1, 'user_type' => 2, 'attivo' => 'Y', 'nome' => 'Admin', 'cognome' => 'Test'],
            ['id' => 2, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Mario', 'cognome' => 'Rossi'],
            ['id' => 3, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Anna', 'cognome' => 'Verdi'],
        ]);
        DB::table('tc_clienti')->insert([
            ['id' => 1, 'ragione_sociale' => 'AREA 1', 'isTest' => 0],
            ['id' => 2, 'ragione_sociale' => 'AREA 2', 'isTest' => 0],
        ]);
        DB::table('tc_sedi')->insert([
            ['id' => 1, 'id_cliente' => 1, 'descrizione' => 'Sede Nord'],
            ['id' => 2, 'id_cliente' => 1, 'descrizione' => 'Sede Sud'],
            ['id' => 3, 'id_cliente' => 2, 'descrizione' => 'Sede Altra'],
        ]);
        DB::table('tc_turni')->insert([
            ['id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 2, 'data_inizio' => '2026-11-07', 'data_fine' => '2026-11-07', 'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'ore' => 8.25, 'stato' => 'F'],
            ['id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 2, 'data_inizio' => '2026-10-01', 'data_fine' => '2026-10-01', 'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'ore' => 4, 'stato' => 'F'],
            ['id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 2, 'data_inizio' => '2026-11-07', 'data_fine' => '2026-11-07', 'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'ore' => 1.5, 'stato' => 'I'],
            ['id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 3, 'data_inizio' => '2026-11-07', 'data_fine' => '2026-11-07', 'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'ore' => 2, 'stato' => 'I'],
            ['id_cliente' => 1, 'id_sede' => 2, 'id_operatore' => 2, 'data_inizio' => '2026-11-07', 'data_fine' => '2026-11-07', 'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'ore' => 3, 'stato' => 'F'],
            ['id_cliente' => 2, 'id_sede' => 3, 'id_operatore' => 2, 'data_inizio' => '2026-11-07', 'data_fine' => '2026-11-07', 'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'ore' => 20, 'stato' => 'F'],
        ]);

        $componente = Livewire::actingAs(User::findOrFail(1))->test(Turni::class)
            ->assertSee('Agenda Pos')
            ->assertDontSee('Totale ore:')
            ->set('filtroCliente', 1)
            ->assertSee('AREA 1')
            ->assertSee('18.75')
            ->assertSee('(tutti i periodi)')
            ->set('filtroSede', 1)
            ->assertSee('Sede Nord')
            ->assertSee('15.75')
            ->set('filtroOperatore', 2)
            ->assertSee('Mario Rossi')
            ->assertSee('13.75')
            ->set('filtroStato', 'completato')
            ->assertSee('12.25')
            ->set('vista', 'lista')
            ->set('da', '2026-11-07')
            ->set('a', '2026-11-07')
            ->assertSee('8.25')
            ->assertSee('(intervallo selezionato)');

        $componente->set('filtroCliente', null)
            ->set('filtroSede', null)
            ->set('filtroOperatore', null)
            ->set('filtroStato', '')
            ->set('da', '')
            ->set('a', '')
            ->assertDontSee('Totale ore:');
    }

    public function test_la_lista_mostra_prima_la_data_piu_recente(): void
    {
        DB::table('tc_admin')->insert([
            'id' => 1, 'user_type' => 2, 'attivo' => 'Y', 'nome' => 'Admin', 'cognome' => 'Test',
        ]);
        DB::table('tc_clienti')->insert(['id' => 1, 'ragione_sociale' => 'Cliente', 'isTest' => 0]);
        DB::table('tc_sedi')->insert(['id' => 1, 'id_cliente' => 1, 'descrizione' => 'Sede']);
        DB::table('tc_turni')->insert([
            ['id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 1, 'data_inizio' => '2026-11-09', 'data_fine' => '2026-11-09', 'ora_inizio' => '08:00', 'ora_fine' => '12:00', 'ore' => 4, 'stato' => 'F'],
            ['id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 1, 'data_inizio' => '2026-11-11', 'data_fine' => '2026-11-11', 'ora_inizio' => '14:00', 'ora_fine' => '18:00', 'ore' => 4, 'stato' => 'P'],
            ['id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 1, 'data_inizio' => '2026-11-11', 'data_fine' => '2026-11-11', 'ora_inizio' => '08:00', 'ora_fine' => '12:00', 'ore' => 4, 'stato' => 'P'],
        ]);

        $html = Livewire::actingAs(User::findOrFail(1))->test(Turni::class)
            ->set('vista', 'lista')
            ->html();

        $mattina = strpos($html, '11/11/2026 08:00');
        $pomeriggio = strpos($html, '11/11/2026 14:00');
        $precedente = strpos($html, '09/11/2026 08:00');

        $this->assertNotFalse($mattina);
        $this->assertLessThan($pomeriggio, $mattina);
        $this->assertLessThan($precedente, $pomeriggio);
    }

    public function test_turno_shows_progress_for_registered_start_and_end(): void
    {
        DB::table('tc_admin')->insert([
            ['id' => 1, 'user_type' => 2, 'attivo' => 'Y', 'nome' => 'Admin', 'cognome' => 'Test'],
            ['id' => 2, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Operatore', 'cognome' => 'Test'],
        ]);
        $cliente = DB::table('tc_clienti')->insertGetId(['ragione_sociale' => 'Cliente Test', 'isTest' => 0]);
        $comune = DB::table('_comuni')->insertGetId(['comune' => 'Milano']);
        $sede = DB::table('tc_sedi')->insertGetId([
            'id_cliente' => $cliente,
            'citta_id' => $comune,
            'descrizione' => 'Sede Test',
        ]);
        $data = today()->toDateString();
        $turno = DB::table('tc_turni')->insertGetId([
            'id_cliente' => $cliente,
            'id_sede' => $sede,
            'id_operatore' => 2,
            'data_inizio' => $data,
            'data_fine' => $data,
            'ora_inizio' => '08:00',
            'ora_fine' => '16:00',
            'ore' => 8,
            'stato' => 'P',
        ]);
        $componente = Livewire::actingAs(User::findOrFail(1))->test(Turni::class)
            ->set('vista', 'lista')
            ->set('da', $data)
            ->set('a', $data)
            ->assertSee('Visualizza timbrature')
            ->assertDontSee('role="progressbar"', false);

        $componente->call('mostraTurno', $turno)
            ->assertSee('aria-valuenow="0"', false)
            ->assertSee('0%')
            ->assertSee('Nessuna timbratura registrata.');

        DB::table('tc_gda')->insert([
            'id_turno' => $turno,
            'tipo' => 'I',
            'data' => $data,
            'orario' => self::ORA_INGRESSO,
        ]);

        $componente->call('mostraTurno', $turno)
            ->assertSee('aria-valuenow="1"', false)
            ->assertSee('50%')
            ->assertSee(self::ORA_INGRESSO)
            ->assertSee('In attesa')
            ->assertSee('Ingresso registrato, uscita in attesa.');

        DB::table('tc_gda')->insert([
            'id_turno' => $turno,
            'tipo' => 'F',
            'data' => $data,
            'orario' => self::ORA_USCITA,
        ]);

        $componente->call('mostraTurno', $turno)
            ->assertSee('aria-valuenow="2"', false)
            ->assertSee('100%')
            ->assertSee(self::ORA_INGRESSO)
            ->assertSee(self::ORA_USCITA)
            ->assertSee('Timbrature del turno')
            ->assertSee('Ingresso e uscita registrati.')
            ->assertSee('Completato');

        $componente->call('chiudiDettaglioTurno')
            ->set('vista', 'calendario')
            ->assertSee('Visualizza timbrature turno delle 08:00 presso Sede Test');
    }

    public function test_con_sede_selezionata_mostra_ore_programmate_contro_richieste(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 9)->setTime(12, 0));
        $this->aggiungiOreRichieste();
        DB::table('tc_admin')->insert([
            'id' => 1, 'user_type' => 2, 'attivo' => 'Y', 'nome' => 'Admin', 'cognome' => 'Test',
        ]);
        DB::table('tc_clienti')->insert([
            ['id' => 1, 'ragione_sociale' => 'AREA 1', 'isTest' => 0],
            ['id' => 2, 'ragione_sociale' => 'AREA 2', 'isTest' => 0],
        ]);
        DB::table('tc_sedi')->insert([
            ['id' => 1, 'id_cliente' => 1, 'descrizione' => 'Sede Nord', 'lun' => 8, 'mar' => 0, 'mer' => 4, 'gio' => 4, 'ven' => 0, 'sab' => 0, 'dom' => 0],
            ['id' => 2, 'id_cliente' => 2, 'descrizione' => 'Sede Altra', 'lun' => 8, 'mar' => 0, 'mer' => 0, 'gio' => 0, 'ven' => 0, 'sab' => 0, 'dom' => 0],
        ]);
        DB::table('tc_turni')->insert([
            ['id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 1, 'data_inizio' => '2026-11-09', 'data_fine' => '2026-11-09', 'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'ore' => 8, 'stato' => 'F'],
            ['id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 1, 'data_inizio' => '2026-11-10', 'data_fine' => '2026-11-10', 'ora_inizio' => '08:00', 'ora_fine' => '13:00', 'ore' => 5, 'stato' => 'F'],
            ['id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 1, 'data_inizio' => '2026-11-11', 'data_fine' => '2026-11-11', 'ora_inizio' => '08:00', 'ora_fine' => '10:00', 'ore' => 2, 'stato' => 'F'],
            ['id_cliente' => 1, 'id_sede' => 1, 'id_operatore' => 1, 'data_inizio' => '2026-11-12', 'data_fine' => '2026-11-12', 'ora_inizio' => '08:00', 'ora_fine' => '14:00', 'ore' => 6, 'stato' => 'F'],
            ['id_cliente' => 2, 'id_sede' => 2, 'id_operatore' => 1, 'data_inizio' => '2026-11-09', 'data_fine' => '2026-11-09', 'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'ore' => 20, 'stato' => 'F'],
        ]);

        $componente = Livewire::actingAs(User::findOrFail(1))->test(Turni::class)
            ->assertDontSee('Copertura settimanale')
            ->set('filtroSede', 1)
            ->assertSee('Copertura settimanale')
            ->assertSee('Lunedì 9 novembre: 8 su 8 ore richieste, in linea')
            ->assertSee('Martedì 10 novembre: non richiesto')
            ->assertSee('Mercoledì 11 novembre: 2 su 4 ore richieste, mancano 2 h')
            ->assertSee('Giovedì 12 novembre: 6 su 4 ore richieste, 2 h in più')
            ->assertSee('bg-sky-50/80', false)
            ->assertDontSee('20 su 8')
            ->set('filtroStato', 'non_eseguito')
            ->assertSee('Lunedì 9 novembre: 8 su 8 ore richieste, in linea');

        $componente->call('cambiaSettimanaCopertura', 1)
            ->assertSee('Lunedì 16 novembre: 0 su 8 ore richieste, mancano 8 h')
            ->assertDontSee('Lunedì 9 novembre: 8 su 8 ore richieste, in linea');
    }

    public function test_la_copertura_di_una_sede_non_visibile_resta_nascosta(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 9)->setTime(12, 0));
        $this->aggiungiOreRichieste();
        DB::table('tc_admin')->insert([
            'id' => 1, 'user_type' => 3, 'attivo' => 'Y', 'nome' => 'Cliente', 'cognome' => 'Uno', 'id_cliente' => '1',
        ]);
        DB::table('tc_clienti')->insert([
            ['id' => 1, 'ragione_sociale' => 'AREA 1', 'isTest' => 0],
            ['id' => 2, 'ragione_sociale' => 'AREA 2', 'isTest' => 0],
        ]);
        DB::table('tc_sedi')->insert([
            ['id' => 2, 'id_cliente' => 2, 'descrizione' => 'Sede Altra', 'lun' => 8, 'mar' => 0, 'mer' => 0, 'gio' => 0, 'ven' => 0, 'sab' => 0, 'dom' => 0],
        ]);
        DB::table('tc_turni')->insert([
            'id_cliente' => 2, 'id_sede' => 2, 'id_operatore' => 1, 'data_inizio' => '2026-11-09', 'data_fine' => '2026-11-09', 'ora_inizio' => '08:00', 'ora_fine' => '16:00', 'ore' => 20, 'stato' => 'F',
        ]);

        Livewire::actingAs(User::findOrFail(1))->test(Turni::class)
            ->set('filtroSede', 2)
            ->assertDontSee('Copertura settimanale')
            ->assertDontSee('20 su 8')
            ->assertDontSee('Sede Altra');
    }

    private function aggiungiOreRichieste(): void
    {
        Schema::table('tc_sedi', function (Blueprint $table): void {
            foreach (['lun', 'mar', 'mer', 'gio', 'ven', 'sab', 'dom'] as $giorno) {
                $table->float($giorno)->nullable();
            }
        });
    }
}
