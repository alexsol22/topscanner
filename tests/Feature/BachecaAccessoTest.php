<?php

namespace Tests\Feature;

use App\Livewire\Bacheca;
use App\Livewire\CampanellaNotifiche;
use App\Livewire\ContatoreNotifiche;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class BachecaAccessoTest extends TestCase
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
            $table->string('codice')->nullable();
            $table->string('user')->nullable();
        });
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
        Schema::create('tc_bacheca', function (Blueprint $table): void {
            $table->integer('id')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->string('titolo')->nullable();
            $table->text('testo')->nullable();
            $table->integer('quanti')->nullable();
            $table->date('data_invio')->nullable();
            $table->string('tutti')->nullable();
        });
        Schema::create('tc_bacheca_view', function (Blueprint $table): void {
            $table->integer('id')->nullable();
            $table->unsignedInteger('id_bacheca')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->string('visto')->nullable();
            $table->dateTime('data_vista')->nullable();
        });
        Schema::create('tc_documenti', function (Blueprint $table): void {
            $table->integer('id')->nullable();
            $table->string('descrizione')->nullable();
            $table->string('file')->nullable();
            $table->string('tipo')->nullable();
            $table->unsignedInteger('id_tipo')->nullable();
        });
        Schema::create('tc_documenti_view', function (Blueprint $table): void {
            $table->integer('id')->nullable();
            $table->unsignedInteger('id_documento')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->string('visto')->nullable();
            $table->dateTime('data_vista')->nullable();
        });

        DB::table('tc_admin')->insert([
            ['id' => 1, 'user_type' => 2, 'attivo' => 'Y', 'nome' => 'Admin', 'cognome' => 'Test', 'codice' => null, 'user' => 'admin'],
            ['id' => 2, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Mario', 'cognome' => 'Rossi', 'codice' => 'OP1', 'user' => 'op1'],
            ['id' => 3, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Luca', 'cognome' => 'Bianchi', 'codice' => 'OP2', 'user' => 'op2'],
            ['id' => 4, 'user_type' => 4, 'attivo' => 'Y', 'nome' => 'Gino', 'cognome' => 'Verdi', 'codice' => 'OP3', 'user' => 'op3'],
        ]);
    }

    public function test_modifica_avviso_aggiorna_testo_e_riallinea_i_destinatari(): void
    {
        $admin = User::findOrFail(1);
        $componente = Livewire::actingAs($admin)->test(Bacheca::class)
            ->call('nuovo')
            ->set('f.titolo', 'Titolo')->set('f.testo', 'Testo')->set('f.per_tutti', false)
            ->set('destinatari', ['2', '3'])
            ->call('salva');

        $idAvviso = (int) DB::table('tc_bacheca')->value('id');
        DB::table('tc_bacheca_view')->where('id_operatore', 2)->update(['visto' => 'Y']);

        $componente->call('modifica', $idAvviso)
            ->assertSet('f.titolo', 'Titolo')
            ->assertSet('destinatari', ['2', '3'])
            ->set('f.titolo', 'Nuovo titolo')->set('destinatari', ['2', '4'])
            ->call('salva')
            ->assertSet('modal', false);

        $this->assertSame(1, DB::table('tc_bacheca')->count());
        $this->assertSame('Nuovo titolo', DB::table('tc_bacheca')->value('titolo'));
        $this->assertSame(2, (int) DB::table('tc_bacheca')->value('quanti'));
        $this->assertSame([2, 4], DB::table('tc_bacheca_view')->orderBy('id_operatore')->pluck('id_operatore')->map(fn ($id) => (int) $id)->all());
        $this->assertSame('Y', DB::table('tc_bacheca_view')->where('id_operatore', 2)->value('visto'));
    }

    public function test_elenco_visti_mostra_destinatari_e_data_di_lettura(): void
    {
        DB::table('tc_bacheca')->insert(['id' => 1, 'titolo' => 'A', 'testo' => 'B', 'quanti' => 2, 'data_invio' => '2026-11-01', 'tutti' => 'N']);
        DB::table('tc_bacheca_view')->insert([
            ['id' => 1, 'id_bacheca' => 1, 'id_operatore' => 2, 'visto' => 'Y', 'data_vista' => '2026-11-02 09:30:00'],
            ['id' => 2, 'id_bacheca' => 1, 'id_operatore' => 3, 'visto' => null, 'data_vista' => null],
        ]);

        Livewire::actingAs(User::findOrFail(1))->test(Bacheca::class)
            ->call('mostraVisti', 1)
            ->assertSee('OP1 (Rossi Mario)')
            ->assertSee('Visto il 02-11-2026 09:30')
            ->assertSee('OP2 (Bianchi Luca)');
    }

    public function test_badge_conta_avvisi_di_bacheca_non_letti_solo_per_l_operatore(): void
    {
        DB::table('tc_bacheca')->insert(['id' => 1, 'titolo' => 'A', 'testo' => 'B', 'quanti' => 3, 'data_invio' => '2026-11-01', 'tutti' => 'N']);
        DB::table('tc_bacheca_view')->insert([
            ['id' => 1, 'id_bacheca' => 1, 'id_operatore' => 2, 'visto' => null, 'data_vista' => null],
            ['id' => 2, 'id_bacheca' => 1, 'id_operatore' => 2, 'visto' => 'N', 'data_vista' => null],
            ['id' => 3, 'id_bacheca' => 1, 'id_operatore' => 2, 'visto' => 'Y', 'data_vista' => '2026-11-02 09:00:00'],
            ['id' => 4, 'id_bacheca' => 1, 'id_operatore' => 3, 'visto' => null, 'data_vista' => null],
        ]);

        Livewire::actingAs(User::findOrFail(2))->test(ContatoreNotifiche::class, ['tipo' => 'bacheca'])
            ->assertViewHas('conteggio', 2)
            ->assertSeeHtml('2 da leggere');

        Livewire::actingAs(User::findOrFail(3))->test(ContatoreNotifiche::class, ['tipo' => 'bacheca'])
            ->assertViewHas('conteggio', 1);

        Livewire::actingAs(User::findOrFail(1))->test(ContatoreNotifiche::class, ['tipo' => 'bacheca'])
            ->assertViewHas('conteggio', 0)
            ->assertDontSeeHtml('da leggere');
    }

    public function test_badge_documenti_conta_i_documenti_non_visti_e_scende_dopo_la_lettura(): void
    {
        DB::table('tc_documenti')->insert(['id' => 7, 'descrizione' => 'Contratto', 'file' => 'x.pdf', 'tipo' => 'O', 'id_tipo' => 2]);
        DB::table('tc_documenti_view')->insert(['id' => 1, 'id_documento' => 7, 'id_operatore' => 2, 'visto' => null, 'data_vista' => null]);

        Livewire::actingAs(User::findOrFail(2))->test(ContatoreNotifiche::class, ['tipo' => 'documenti'])
            ->assertViewHas('conteggio', 1);

        DB::table('tc_documenti_view')->update(['visto' => 'Y', 'data_vista' => now()]);

        Livewire::actingAs(User::findOrFail(2))->test(ContatoreNotifiche::class, ['tipo' => 'documenti'])
            ->assertViewHas('conteggio', 0);
    }

    public function test_la_sidebar_dell_operatore_mostra_il_badge_su_bacheca(): void
    {
        DB::table('tc_bacheca')->insert(['id' => 1, 'titolo' => 'A', 'testo' => 'B', 'quanti' => 1, 'data_invio' => '2026-11-01', 'tutti' => 'N']);
        DB::table('tc_bacheca_view')->insert(['id' => 1, 'id_bacheca' => 1, 'id_operatore' => 2, 'visto' => null, 'data_vista' => null]);

        $this->actingAs(User::findOrFail(2))->get(route('bacheca'))
            ->assertOk()
            ->assertSeeLivewire(ContatoreNotifiche::class);
    }

    public function test_campanella_elenca_avvisi_e_documenti_non_letti_e_permette_di_segnarli_letti(): void
    {
        DB::table('tc_bacheca')->insert([
            ['id' => 1, 'titolo' => 'Cambio turno', 'testo' => 'Domani si inizia alle 7', 'quanti' => 2, 'data_invio' => '2026-11-01', 'tutti' => 'N'],
            ['id' => 2, 'titolo' => 'Riunione', 'testo' => 'Venerdì in sede', 'quanti' => 2, 'data_invio' => '2026-11-02', 'tutti' => 'N'],
        ]);
        DB::table('tc_bacheca_view')->insert([
            ['id' => 1, 'id_bacheca' => 1, 'id_operatore' => 2, 'visto' => null, 'data_vista' => null],
            ['id' => 2, 'id_bacheca' => 2, 'id_operatore' => 2, 'visto' => null, 'data_vista' => null],
            ['id' => 3, 'id_bacheca' => 2, 'id_operatore' => 3, 'visto' => null, 'data_vista' => null],
        ]);
        DB::table('tc_documenti')->insert(['id' => 7, 'descrizione' => 'Contratto', 'file' => 'x.pdf', 'tipo' => 'O', 'id_tipo' => 2]);
        DB::table('tc_documenti_view')->insert(['id' => 1, 'id_documento' => 7, 'id_operatore' => 2, 'visto' => null, 'data_vista' => null]);

        $campanella = Livewire::actingAs(User::findOrFail(2))->test(CampanellaNotifiche::class)
            ->assertViewHas('totale', 3)
            ->assertSee('Cambio turno')
            ->assertSee('Domani si inizia alle 7')
            ->assertSee('Riunione')
            ->assertSee('Contratto');

        $campanella->call('segnaLetto', 1)->assertViewHas('totale', 2)->assertDontSee('Cambio turno');
        $this->assertSame('Y', DB::table('tc_bacheca_view')->where('id', 1)->value('visto'));

        // Non si può segnare come letto l'avviso di un altro operatore.
        $campanella->call('segnaLetto', 3);
        $this->assertNull(DB::table('tc_bacheca_view')->where('id', 3)->value('visto'));

        $campanella->call('segnaTuttiLetti')->assertDontSee('Riunione')->assertSee('Contratto')->assertViewHas('totale', 1);
        $this->assertNull(DB::table('tc_bacheca_view')->where('id', 3)->value('visto'));
    }

    public function test_campanella_e_vuota_per_lo_staff_e_presente_nell_header_dell_operatore(): void
    {
        DB::table('tc_bacheca')->insert(['id' => 1, 'titolo' => 'A', 'testo' => 'B', 'quanti' => 1, 'data_invio' => '2026-11-01', 'tutti' => 'N']);
        DB::table('tc_bacheca_view')->insert(['id' => 1, 'id_bacheca' => 1, 'id_operatore' => 2, 'visto' => null, 'data_vista' => null]);

        Livewire::actingAs(User::findOrFail(1))->test(CampanellaNotifiche::class)->assertViewHas('totale', 0);

        $this->actingAs(User::findOrFail(2))->get(route('bacheca'))->assertOk()->assertSeeLivewire(CampanellaNotifiche::class);
        $this->actingAs(User::findOrFail(1))->get(route('bacheca'))->assertOk()->assertDontSeeLivewire(CampanellaNotifiche::class);
    }
}
