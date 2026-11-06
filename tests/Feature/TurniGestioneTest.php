<?php

namespace Tests\Feature;

use App\Livewire\Turni;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class TurniGestioneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');
        DB::connection('mysql')->getPdo()->sqliteCreateFunction('DATE_FORMAT', fn ($data) => date('d-m-Y', strtotime($data)));

        Carbon::setTestNow('2026-11-06 12:00:00');

        Schema::create('tc_admin', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_type')->nullable();
            $table->string('attivo')->nullable();
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('email')->nullable();
            $table->string('cellulare')->nullable();
            $table->string('codice')->nullable();
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
            $table->unsignedInteger('provincia_id')->nullable();
            $table->unsignedInteger('id_codice')->nullable();
            $table->string('descrizione')->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('cap')->nullable();
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
        });
        Schema::create('_comuni', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('comune')->nullable();
        });
        Schema::create('_provincia', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('sigla')->nullable();
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
        Schema::create('tc_send_email', function (Blueprint $table): void {
            $table->integer('id')->nullable();
            $table->integer('code')->nullable();
            $table->string('email')->nullable();
            $table->string('nome')->nullable();
            $table->text('testo')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->string('sent')->default('N');
            $table->dateTime('data_sent')->nullable();
        });

        DB::table('tc_admin')->insert([
            ['id' => 1, 'user_type' => 2, 'attivo' => 'Y', 'nome' => 'Admin', 'cognome' => 'Test', 'email' => null, 'cellulare' => null, 'codice' => null],
            ['id' => 2, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Mario', 'cognome' => 'Rossi', 'email' => 'mario@example.com', 'cellulare' => null, 'codice' => 'OP1'],
            ['id' => 3, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Luca', 'cognome' => 'Bianchi', 'email' => 'luca@example.com', 'cellulare' => null, 'codice' => 'OP2'],
        ]);
        DB::table('tc_clienti')->insert([['id' => 1, 'ragione_sociale' => 'Cliente Uno'], ['id' => 2, 'ragione_sociale' => 'Cliente Due']]);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Torino']);
        DB::table('_provincia')->insert(['id' => 1, 'sigla' => 'TO']);
        DB::table('tc_sedi')->insert([
            ['id' => 1, 'id_cliente' => 1, 'citta_id' => 1, 'provincia_id' => 1, 'id_codice' => null, 'descrizione' => 'Sede Uno', 'indirizzo' => 'Via Roma 1', 'cap' => '10100', 'lat' => '45.0', 'lng' => '7.6'],
            ['id' => 2, 'id_cliente' => 2, 'citta_id' => 1, 'provincia_id' => 1, 'id_codice' => null, 'descrizione' => 'Sede Due', 'indirizzo' => 'Via Po 2', 'cap' => '10100', 'lat' => '45.0', 'lng' => '7.6'],
        ]);

        $turno = fn (int $id, int $cliente, int $sede, int $operatore, string $inizio, string $fine, string $stato, float $ore) => [
            'id' => $id, 'id_cliente' => $cliente, 'id_sede' => $sede, 'id_operatore' => $operatore,
            'data_inizio' => substr($inizio, 0, 10), 'ora_inizio' => substr($inizio, 11),
            'data_fine' => substr($fine, 0, 10), 'ora_fine' => substr($fine, 11),
            'ore' => $ore, 'ore_lavorate' => null, 'stato' => $stato, 'note_operatore' => null,
        ];
        DB::table('tc_turni')->insert([
            $turno(1, 1, 1, 2, '2026-11-06 08:00', '2026-11-06 10:00', 'P', 2),   // non timbrato? fine passata -> non eseguito
            $turno(2, 1, 1, 2, '2026-11-06 11:00', '2026-11-06 13:00', 'P', 2),   // iniziato da un'ora, non timbrato
            $turno(3, 1, 1, 3, '2026-11-06 14:00', '2026-11-06 18:00', 'P', 4),   // programmato
            $turno(4, 2, 2, 3, '2026-11-06 06:00', '2026-11-06 09:00', 'I', 3),   // iniziato non completato
            $turno(5, 2, 2, 3, '2026-11-06 11:00', '2026-11-06 15:00', 'I', 4),   // in corso
            $turno(6, 2, 2, 3, '2026-11-05 08:00', '2026-11-05 16:00', 'F', 8),   // completato
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function lista()
    {
        return Livewire::actingAs(User::findOrFail(1))->test(Turni::class)
            ->set('vista', 'lista')->set('da', '2026-11-01')->set('a', '2026-11-30');
    }

    private function idsLista($componente): array
    {
        return $componente->viewData('turni')->pluck('id')->sort()->values()->all();
    }

    public function test_filtro_stato_replica_la_legenda_di_yii(): void
    {
        $atteso = [
            'programmato' => [3], 'non_timbrato' => [2], 'non_eseguito' => [1],
            'in_corso' => [5], 'non_completato' => [4], 'completato' => [6],
        ];

        foreach ($atteso as $stato => $ids) {
            $this->assertSame($ids, $this->idsLista($this->lista()->set('filtroStato', $stato)), "stato {$stato}");
        }

        $this->assertSame([1, 2, 3, 4, 5, 6], $this->idsLista($this->lista()));
    }

    public function test_filtro_cliente_e_totali_con_priorita_cliente_sede_operatore(): void
    {
        $this->assertNull($this->lista()->viewData('totali'));

        $this->assertSame([4, 5, 6], $this->idsLista($this->lista()->set('filtroCliente', 2)));
        $this->assertSame(['etichetta' => 'cliente', 'ore' => 15.0], $this->lista()->set('filtroCliente', 2)->viewData('totali'));
        $this->assertSame(['etichetta' => 'cliente', 'ore' => 15.0], $this->lista()->set('filtroCliente', 2)->set('filtroOperatore', 2)->viewData('totali'));
        $this->assertSame(['etichetta' => 'operatore', 'ore' => 4.0], $this->lista()->set('filtroOperatore', 2)->viewData('totali'));
    }

    public function test_apri_e_chiudi_richiedono_nota_data_e_ora_e_registrano_la_timbratura_manuale(): void
    {
        $componente = Livewire::actingAs(User::findOrFail(1))->test(Turni::class)
            ->call('apriChiudi', 2)
            ->assertSet('ac.stato', 'I')
            ->assertSet('ac.data', '2026-11-06')
            ->assertSet('ac.ora', '11:00')
            ->set('ac.note', '')
            ->call('confermaApriChiudi')
            ->assertHasErrors(['ac.note' => 'required']);

        $componente->set('ac.note', 'Iniziato dal responsabile')->set('ac.ora', '11:10')->call('confermaApriChiudi')->assertHasNoErrors();

        $turno = DB::table('tc_turni')->find(2);
        $this->assertSame('I', $turno->stato);
        $this->assertSame('Iniziato dal responsabile', $turno->note_operatore);
        $gda = DB::table('tc_gda')->where('id_turno', 2)->first();
        $this->assertSame('I', $gda->tipo);
        $this->assertSame('11:10', $gda->orario);
        $this->assertSame(10, (int) $gda->ritardo);
        $this->assertSame(1, (int) $gda->id_operatore);

        Livewire::actingAs(User::findOrFail(1))->test(Turni::class)
            ->call('apriChiudi', 2)
            ->assertSet('ac.stato', 'F')
            ->assertSet('ac.ora', '13:00')
            ->set('ac.note', 'Chiuso')->call('confermaApriChiudi');

        $this->assertSame('F', DB::table('tc_turni')->where('id', 2)->value('stato'));
        $this->assertSame(1.75, (float) DB::table('tc_turni')->where('id', 2)->value('ore_lavorate'));
    }

    public function test_invia_turni_accoda_una_email_per_operatore_con_i_suoi_turni(): void
    {
        Livewire::actingAs(User::findOrFail(1))->test(Turni::class)
            ->set('da', '2026-11-06')->set('a', '2026-11-06')
            ->call('nuovoInvio')
            ->set('invio.cliente', 1)
            ->call('inviaTurni')
            ->assertSet('esitoInvio', 'Email turno inviate correttamente. Totale operatori coinvolti 2');

        $coda = DB::table('tc_send_email')->orderBy('id_operatore')->get();
        $this->assertSame(['mario@example.com', 'luca@example.com'], $coda->pluck('email')->all());
        $this->assertSame(['N', 'N'], $coda->pluck('sent')->all());
        $this->assertStringContainsString('Ciao Rossi Mario', $coda[0]->testo);
        $this->assertStringContainsString('06-11-2026 Alle 08:00 presso la sede Sede Uno al seguente indirizzo Via Roma 1 Torino (TO)', $coda[0]->testo);
        $this->assertStringContainsString('Alle 11:00', $coda[0]->testo);
        $this->assertStringNotContainsString('Alle 14:00', $coda[0]->testo);
        $this->assertStringContainsString('Alle 14:00', $coda[1]->testo);
        $this->assertSame($coda[0]->code, $coda[1]->code);
    }

    public function test_invia_turni_senza_turni_corrispondenti_non_accoda_nulla(): void
    {
        Livewire::actingAs(User::findOrFail(1))->test(Turni::class)
            ->call('nuovoInvio')
            ->set('invio.inizio', '2027-01-01')->set('invio.fine', '2027-01-31')
            ->call('inviaTurni')
            ->assertSet('esitoInvio', 'Non sono stati trovati turni a cui inviare comunicazioni per sede, operatore, data indicate.');

        $this->assertSame(0, DB::table('tc_send_email')->count());
    }
}
