<?php

namespace Tests\Feature;

use App\Models\Legacy\Turno;
use App\Models\User;
use App\Services\Timbratura;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TimbraturaTest extends TestCase
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
        });
        Schema::create('tc_sedi', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->integer('distanza')->nullable();
        });
        Schema::create('tc_codici', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('codice')->nullable();
            $table->string('stato')->nullable();
            $table->string('tipo')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
        });
        Schema::create('tc_postazioni', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_codice')->nullable();
            $table->string('titolo')->nullable();
            $table->integer('minuti')->nullable();
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
            $table->text('note_operatore')->nullable();
            $table->float('lat')->nullable();
            $table->float('lng')->nullable();
            $table->float('distanza')->nullable();
            $table->integer('ritardo')->nullable();
            $table->string('tipo')->nullable();
        });

        DB::table('tc_admin')->insert(['id' => 1, 'user_type' => 4, 'attivo' => 'Y']);
        DB::table('tc_sedi')->insert(['id' => 1, 'lat' => '45.0567356', 'lng' => '7.6693', 'distanza' => 100]);
        DB::table('tc_codici')->insert(['id' => 1, 'codice' => 'ITALPOL*000001', 'stato' => 'A', 'tipo' => 'S', 'id_sede' => 1]);
        DB::table('tc_turni')->insert([
            'id' => 1,
            'id_cliente' => 1,
            'id_sede' => 1,
            'id_operatore' => 1,
            'data_inizio' => today()->toDateString(),
            'data_fine' => today()->toDateString(),
            'ora_inizio' => '08:00',
            'ora_fine' => '16:00',
            'stato' => 'P',
        ]);
    }

    public function test_scansione_sede_senza_coordinate_viene_rifiutata_come_nel_vecchio_programma(): void
    {
        $esito = (new Timbratura)->scansionaTurno(Turno::findOrFail(1), User::findOrFail(1), 'ITALPOL*000001', null, null);

        $this->assertFalse($esito['ok']);
        $this->assertStringContainsString('superiore a 100 metri', $esito['message']);
        $this->assertSame(0, DB::table('tc_gda')->count());
        $this->assertSame('P', DB::table('tc_turni')->value('stato'));
    }

    public function test_scansione_sede_in_posizione_registra_inizio_e_poi_fine(): void
    {
        $timbratura = new Timbratura;
        $turno = Turno::findOrFail(1);
        $operatore = User::findOrFail(1);

        $inizio = $timbratura->scansionaTurno($turno, $operatore, 'ITALPOL*000001', 45.0567356, 7.6693);
        $fine = $timbratura->scansionaTurno($turno->fresh(), $operatore, 'ITALPOL*000001', 45.0567356, 7.6693);

        $this->assertTrue($inizio['ok']);
        $this->assertSame('I', $inizio['tipo']);
        $this->assertTrue($fine['ok']);
        $this->assertSame('F', $fine['tipo']);
        $this->assertSame('F', DB::table('tc_turni')->value('stato'));
    }
}
