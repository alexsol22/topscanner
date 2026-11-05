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
            'stato' => 'I',
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
            ->assertSee('Ingresso e uscita registrati.');

        $componente->call('chiudiDettaglioTurno')
            ->set('vista', 'calendario')
            ->assertSee('Visualizza timbrature turno delle 08:00 presso Sede Test');
    }
}
