<?php

namespace Tests\Feature;

use App\Mail\NotificaHtml;
use App\Services\Sms;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificheSmsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
            'sms.username' => 'utente',
            'sms.password' => 'segreta',
            'sms.delivery_password' => 'callback',
        ]);
        DB::purge('mysql');
        DB::connection('mysql')->getPdo()->sqliteCreateFunction('DATE_FORMAT', fn ($data) => date('d-m-Y', strtotime($data)));

        Carbon::setTestNow('2026-11-06 09:00:00');
        Mail::fake();

        Schema::create('tc_admin', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('email')->nullable();
            $table->string('cellulare')->nullable();
            $table->string('codice')->nullable();
        });
        Schema::create('tc_sedi', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('descrizione')->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('cap')->nullable();
            $table->unsignedInteger('citta_id')->nullable();
            $table->unsignedInteger('provincia_id')->nullable();
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
            $table->unsignedInteger('id_sede')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->date('data_inizio')->nullable();
            $table->date('data_fine')->nullable();
            $table->string('ora_inizio')->nullable();
            $table->string('ora_fine')->nullable();
        });
        Schema::create('tc_gda', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_turno')->nullable();
            $table->string('tipo')->nullable();
        });
        Schema::create('tc_ritardi', function (Blueprint $table): void {
            $table->integer('id')->nullable();
            $table->unsignedInteger('id_turno')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->integer('inizio')->default(0);
            $table->integer('fine')->default(0);
            $table->dateTime('data_ritardo_inizio')->nullable();
            $table->dateTime('data_ritardo_fine')->nullable();
        });
        Schema::create('tc_messaggi', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('giorni')->default(0);
            $table->integer('ore')->default(0);
            $table->integer('minuti')->nullable();
            $table->string('invia_sms')->nullable();
            $table->string('invia_email')->nullable();
            $table->text('modello_sms')->nullable();
            $table->text('modello_email')->nullable();
            $table->string('oggetto')->nullable();
            $table->string('mittente')->nullable();
            $table->integer('numero_sms')->nullable();
            $table->string('admin_sms')->nullable();
            $table->string('admin_email')->nullable();
        });
        Schema::create('tc_config', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('app')->nullable();
        });
        Schema::create('tc_email', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_operatore')->nullable();
            $table->string('oggetto')->nullable();
            $table->text('email')->nullable();
            $table->text('destinatario')->nullable();
            $table->dateTime('data_invio')->nullable();
        });
        Schema::create('tc_send_email', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('email')->nullable();
            $table->string('nome')->nullable();
            $table->text('testo')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->string('sent')->default('N');
            $table->dateTime('data_sent')->nullable();
        });
        Schema::create('tc_send_sms', function (Blueprint $table): void {
            $table->integer('id')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->text('testo')->nullable();
            $table->text('destinatari')->nullable();
            $table->integer('quanti')->nullable();
            $table->string('sender')->nullable();
            $table->dateTime('data_invio')->nullable();
            $table->string('sent')->nullable();
        });
        Schema::create('tc_send_sms_result', function (Blueprint $table): void {
            $table->integer('id')->nullable();
            $table->integer('send_id')->nullable();
            $table->integer('adl_id')->nullable();
            $table->float('credits')->nullable();
            $table->float('costo')->default(0);
            $table->text('dest')->nullable();
            $table->string('response')->nullable();
            $table->string('message')->nullable();
            $table->integer('refer')->nullable();
            $table->string('tipo')->nullable();
            $table->string('numero')->nullable();
        });
        Schema::create('tc_send_sms_delivery', function (Blueprint $table): void {
            $table->integer('id')->nullable();
            $table->integer('send_id')->nullable();
            $table->integer('refer_id')->nullable();
            $table->string('numero')->nullable();
            $table->string('delivery_status')->nullable();
            $table->string('delivery_reason')->nullable();
            $table->dateTime('delivery_data')->nullable();
        });

        DB::table('tc_admin')->insert(['id' => 1, 'nome' => 'Mario', 'cognome' => 'Rossi', 'email' => 'mario@example.com', 'cellulare' => '+39333', 'codice' => 'OP1']);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Torino']);
        DB::table('_provincia')->insert(['id' => 1, 'sigla' => 'TO']);
        DB::table('tc_sedi')->insert(['id' => 1, 'descrizione' => 'Sede Nord', 'indirizzo' => 'Via Roma 1', 'cap' => '10100', 'citta_id' => 1, 'provincia_id' => 1]);
        DB::table('tc_turni')->insert([
            'id' => 1, 'id_sede' => 1, 'id_operatore' => 1,
            'data_inizio' => '2026-11-06', 'data_fine' => '2026-11-06', 'ora_inizio' => '08:00', 'ora_fine' => '16:00',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function messaggio(array $valori): void
    {
        DB::table('tc_messaggi')->insert($valori + [
            'giorni' => 0, 'ore' => 0, 'minuti' => 9, 'invia_sms' => 'Y', 'invia_email' => 'Y',
            'modello_sms' => 'Ritardo [OPERATORE] ore [ORA TURNO] presso [SEDE]', 'modello_email' => 'Ciao [OPERATORE]',
            'oggetto' => 'Ritardo', 'mittente' => 'S-420', 'numero_sms' => 1,
            'admin_sms' => '+39111,+39222', 'admin_email' => 'admin@example.com',
        ]);
    }

    private function fingiProvider(): void
    {
        Http::fake([
            '*/auth' => Http::response(['success' => true, 'data' => ['token' => 'TKN']]),
            '*/send*' => Http::response(['success' => true, 'data' => ['id_send' => 777, 'credits' => 5, 'costo' => 0.04, 'dest' => '1', 'message' => 'Accodato']]),
        ]);
    }

    public function test_con_sms_disattivati_il_ritardo_registra_ed_invia_email_ma_nessuna_chiamata_al_provider(): void
    {
        Http::fake();
        $this->messaggio(['id' => 2]);

        $this->artisan('notifiche:ritardi')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, DB::table('tc_send_sms')->count());
        $this->assertSame(1, (int) DB::table('tc_ritardi')->value('inizio'));
        Mail::assertSent(NotificaHtml::class, 2);
    }

    public function test_ritardo_inizio_invia_sms_a_operatore_e_numeri_admin_con_sms_attivi(): void
    {
        config(['sms.enabled' => true]);
        $this->fingiProvider();
        $this->messaggio(['id' => 2]);

        $this->artisan('notifiche:ritardi')->assertSuccessful();

        $this->assertSame(['+39333', '+39111', '+39222'], DB::table('tc_send_sms')->orderBy('id')->pluck('destinatari')->all());
        $this->assertSame(['Y', 'Y', 'Y'], DB::table('tc_send_sms')->pluck('sent')->all());
        $this->assertSame('Ritardo Rossi Mario ore 08:00 presso Sede Nord', DB::table('tc_send_sms')->value('testo'));
        $this->assertSame('S-420', DB::table('tc_send_sms')->value('sender'));
        $this->assertSame(777, (int) DB::table('tc_send_sms_result')->value('adl_id'));
        Http::assertSent(fn ($richiesta) => str_contains($richiesta->url(), '/send?token=TKN')
            && $richiesta['numbers'] === '+39333'
            && $richiesta['delivery_url'] === route('sms.consegna'));
    }

    public function test_ritardo_non_supera_numero_sms_e_salta_turni_con_timbratura(): void
    {
        config(['sms.enabled' => true]);
        $this->fingiProvider();
        $this->messaggio(['id' => 2]);

        $this->artisan('notifiche:ritardi');
        $this->artisan('notifiche:ritardi');

        $this->assertSame(3, DB::table('tc_send_sms')->count());
        $this->assertSame(1, (int) DB::table('tc_ritardi')->value('inizio'));

        DB::table('tc_ritardi')->delete();
        DB::table('tc_send_sms')->delete();
        DB::table('tc_gda')->insert(['id_turno' => 1, 'tipo' => 'I']);

        $this->artisan('notifiche:ritardi');

        $this->assertSame(0, DB::table('tc_send_sms')->count());
        $this->assertSame(0, DB::table('tc_ritardi')->count());
    }

    public function test_senza_email_operatore_il_ritardo_non_invia_email_ma_invia_sms_come_nel_vecchio_programma(): void
    {
        config(['sms.enabled' => true]);
        $this->fingiProvider();
        DB::table('tc_admin')->where('id', 1)->update(['email' => null]);
        $this->messaggio(['id' => 2]);

        $this->artisan('notifiche:ritardi');

        Mail::assertNothingSent();
        $this->assertSame(3, DB::table('tc_send_sms')->count());
    }

    public function test_avviso_turno_invia_sms_anche_con_email_disattivata(): void
    {
        config(['sms.enabled' => true]);
        $this->fingiProvider();
        DB::table('tc_turni')->update(['ora_inizio' => '11:00']);
        $this->messaggio(['id' => 1, 'ore' => 2, 'invia_email' => 'N', 'minuti' => null, 'modello_sms' => 'Turno [ORA TURNO] [SEDE]']);

        $this->artisan('notifiche:avviso-turni')->assertSuccessful();

        $this->assertSame(['+39333', '+39111', '+39222'], DB::table('tc_send_sms')->orderBy('id')->pluck('destinatari')->all());
        $this->assertSame('Turno 11:00 Sede Nord', DB::table('tc_send_sms')->value('testo'));
        Mail::assertNothingSent();
    }

    public function test_sms_con_errore_del_provider_viene_registrato_come_non_inviato(): void
    {
        config(['sms.enabled' => true]);
        Http::fake([
            '*/auth' => Http::response(['success' => true, 'data' => ['token' => 'TKN']]),
            '*/send*' => Http::response(['success' => false, 'error' => ['code' => 12, 'message' => 'Credito esaurito', 'field' => 'numbers']]),
        ]);

        $inviato = app(Sms::class)->invia('+39333', 'Prova', 'S-420', 1);

        $this->assertFalse($inviato);
        $this->assertSame('N', DB::table('tc_send_sms')->value('sent'));
        $this->assertSame('12 Credito esaurito numbers', DB::table('tc_send_sms_result')->value('response'));
    }

    public function test_callback_consegna_registra_lo_stato_collegandolo_all_sms(): void
    {
        DB::table('tc_send_sms')->insert(['id' => 5, 'destinatari' => '+39333', 'testo' => 'x']);
        DB::table('tc_send_sms_result')->insert(['id' => 1, 'send_id' => 5, 'adl_id' => 777, 'costo' => 0, 'numero' => '+39333']);

        $this->post('/sms/consegna', ['psw' => 'callback', 'id_send' => 777, 'delivery_number' => '+39333', 'delivery_status' => 'D', 'delivery_data' => '2026-11-06 09:01:00'])
            ->assertOk();
        $this->post('/sms/consegna', ['psw' => 'callback', 'id_send' => 777, 'delivery_number' => '+39333', 'delivery_status' => 'F', 'delivery_data' => '2026-11-06 09:02:00'])
            ->assertOk();

        $consegne = DB::table('tc_send_sms_delivery')->orderBy('id')->get();
        $this->assertSame([5, 5], $consegne->pluck('send_id')->all());
        $this->assertSame(['Consegnato', 'Fallito'], $consegne->pluck('delivery_reason')->all());
    }

    public function test_callback_consegna_rifiuta_password_errata_o_non_configurata(): void
    {
        $this->post('/sms/consegna', ['psw' => 'sbagliata', 'id_send' => 1])->assertForbidden();
        $this->post('/sms/consegna', ['id_send' => 1])->assertForbidden();

        config(['sms.delivery_password' => null]);
        $this->post('/sms/consegna', ['psw' => '', 'id_send' => 1])->assertForbidden();

        $this->assertSame(0, DB::table('tc_send_sms_delivery')->count());
    }
}
