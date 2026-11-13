<?php

namespace Tests\Feature;

use App\Livewire\ModelliMessaggio;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ModelliMessaggioTest extends TestCase
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
        Schema::create('tc_messaggi', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome')->nullable();
            $table->integer('giorni')->nullable();
            $table->integer('ore')->nullable();
            $table->integer('minuti')->nullable();
            $table->string('invia_sms')->nullable();
            $table->string('invia_email')->nullable();
            $table->text('modello_sms')->nullable();
            $table->text('modello_email')->nullable();
            $table->string('oggetto')->nullable();
            $table->string('mittente')->nullable();
            $table->integer('numero_sms')->nullable();
            $table->integer('numero_email')->nullable();
            $table->string('admin_sms')->nullable();
            $table->string('admin_email')->nullable();
        });

        DB::table('tc_admin')->insert(['id' => 1, 'user_type' => 2, 'attivo' => 'Y', 'nome' => 'Admin', 'cognome' => 'Test']);
        DB::table('tc_messaggi')->insert([
            'id' => 2, 'nome' => 'Ritardo inizio', 'giorni' => 0, 'ore' => 0, 'minuti' => 9, 'invia_sms' => 'Y', 'invia_email' => 'Y',
            'modello_sms' => 'sms', 'modello_email' => 'email', 'oggetto' => 'Ritardo', 'mittente' => 'S-420', 'numero_sms' => 1, 'numero_email' => 3,
            'admin_sms' => '', 'admin_email' => '',
        ]);
    }

    public function test_numero_invii_sms_e_email_sono_modificabili_nel_modello(): void
    {
        Livewire::actingAs(User::findOrFail(1))->test(ModelliMessaggio::class)
            ->call('modifica', 2)
            ->assertSet('f.numero_sms', 1)
            ->assertSet('f.numero_email', 3)
            ->set('f.numero_sms', 3)->set('f.numero_email', 2)
            ->call('salva')
            ->assertHasNoErrors();

        $this->assertSame(3, (int) DB::table('tc_messaggi')->where('id', 2)->value('numero_sms'));
        $this->assertSame(2, (int) DB::table('tc_messaggi')->where('id', 2)->value('numero_email'));
        $this->assertSame('N', DB::table('tc_messaggi')->where('id', 2)->value('invia_sms'));
    }

    public function test_messaggi_page_shows_automatic_messages_without_a_channel_toggle(): void
    {
        $response = $this->actingAs(User::findOrFail(1))->get(route('messaggi'));

        $response->assertOk()
            ->assertSee('<title>Messaggi · Omnia Control</title>', false)
            ->assertSee('Messaggi automatici')
            ->assertSee('Vedi layout')
            ->assertDontSee('Invia SMS')
            ->assertDontSee('Canale messaggi');
    }

    public function test_legacy_automatic_messages_url_redirects_to_messaggi(): void
    {
        $response = $this->actingAs(User::findOrFail(1))->get(route('messaggi.modelli'));

        $response->assertRedirectToRoute('messaggi');
    }

    public function test_programmazione_mostra_lemail_che_viene_inviata(): void
    {
        Schema::create('tc_clienti', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('ragione_sociale')->nullable();
        });
        Schema::create('tc_sedi', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->string('descrizione')->nullable();
            $table->string('indirizzo')->nullable();
            $table->integer('lun')->nullable();
        });
        Schema::create('tc_turni', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_sede')->nullable();
            $table->date('data_inizio')->nullable();
            $table->float('ore')->nullable();
        });
        Schema::create('tc_config', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('app')->nullable();
        });
        DB::table('tc_clienti')->insert(['id' => 1, 'ragione_sociale' => 'Cliente Visibile']);
        DB::table('tc_sedi')->insert(['id' => 1, 'id_cliente' => 1, 'descrizione' => 'Sede Nord', 'indirizzo' => 'Via Roma', 'lun' => 4]);
        DB::table('tc_messaggi')->insert([
            'id' => 6, 'nome' => 'Messaggio programmazione settimanale', 'giorni' => 0, 'ore' => 0, 'minuti' => 0,
            'invia_sms' => 'N', 'invia_email' => 'Y', 'modello_sms' => '', 'modello_email' => '<p>Buongiorno</p>',
            'oggetto' => 'Programmazione', 'mittente' => 'ItalPol', 'numero_sms' => 1, 'numero_email' => 1,
            'admin_sms' => '', 'admin_email' => 'segreteria@s-420italpol.it',
        ]);

        Livewire::actingAs(User::findOrFail(1))->test(ModelliMessaggio::class)
            ->call('visualizza', 6)
            ->assertSee('Layout email')
            ->assertSee('Buongiorno')
            ->assertSee('Cliente Visibile')
            ->assertSee('Sede Nord');
    }

    public function test_modifica_mostra_le_variabili_inseribili_nel_testo(): void
    {
        Livewire::actingAs(User::findOrFail(1))->test(ModelliMessaggio::class)
            ->call('modifica', 2)
            ->assertSee('[OPERATORE]')
            ->assertSee('[CERTIFICATO]')
            ->assertSee('[MATERIALE]')
            ->assertDontSee('Testo SMS')
            ->assertDontSee('Mittente SMS');
    }

    public function test_numero_invii_ammette_solo_1_2_3_e_mittente_massimo_11_caratteri(): void
    {
        Livewire::actingAs(User::findOrFail(1))->test(ModelliMessaggio::class)
            ->call('modifica', 2)
            ->set('f.numero_sms', 5)
            ->set('f.mittente', 'MittenteTroppoLungo')
            ->call('salva')
            ->assertHasErrors(['f.numero_sms', 'f.mittente']);

        $this->assertSame(1, (int) DB::table('tc_messaggi')->where('id', 2)->value('numero_sms'));
    }
}
