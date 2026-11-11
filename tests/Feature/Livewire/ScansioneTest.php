<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Scansione;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->integer('distanza')->nullable();
            $table->unsignedInteger('citta_id')->nullable();
            $table->unsignedInteger('provincia_id')->nullable();
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
