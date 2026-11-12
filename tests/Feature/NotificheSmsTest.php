<?php

namespace Tests\Feature;

use App\Mail\NotificaHtml;
use App\Services\ProvaNotifiche;
use App\Services\Sms;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Monolog\Handler\NullHandler;
use Tests\TestCase;

class NotificheSmsTest extends TestCase
{
    /** @var list<MessageLogged> */
    private array $diario = [];

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
        Schema::create('tc_clienti', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('ragione_sociale')->nullable();
        });
        Schema::create('tc_turni', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_sede')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->unsignedInteger('id_cliente')->nullable();
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
        DB::table('tc_clienti')->insert(['id' => 1, 'ragione_sociale' => 'Prova']);
        DB::table('_comuni')->insert(['id' => 1, 'comune' => 'Torino']);
        DB::table('_provincia')->insert(['id' => 1, 'sigla' => 'TO']);
        DB::table('tc_sedi')->insert(['id' => 1, 'descrizione' => 'Sede Nord', 'indirizzo' => 'Via Roma 1', 'cap' => '10100', 'citta_id' => 1, 'provincia_id' => 1]);
        DB::table('tc_turni')->insert([
            'id' => 1, 'id_sede' => 1, 'id_operatore' => 1, 'id_cliente' => 1,
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

    public function test_prova_avvisi_invia_solo_i_turni_dell_operatore_senza_sms_o_registrazioni(): void
    {
        config(['sms.enabled' => true]);
        Http::fake();
        DB::table('tc_admin')->where('id', 1)->update(['email' => ProvaNotifiche::EMAIL]);
        DB::table('tc_admin')->insert(['id' => 2, 'nome' => 'Altro', 'email' => 'altro@example.com', 'cellulare' => '+39444']);
        DB::table('tc_turni')->where('id', 1)->update(['ora_inizio' => '11:00']);
        DB::table('tc_clienti')->insert(['id' => 2, 'ragione_sociale' => 'Reale']);
        DB::table('tc_sedi')->insert(['id' => 2, 'descrizione' => 'Sede Reale']);
        DB::table('tc_turni')->insert([
            ['id' => 2, 'id_operatore' => 2, 'id_cliente' => 1, 'id_sede' => 1, 'data_inizio' => '2026-11-06', 'ora_inizio' => '11:00'],
            ['id' => 3, 'id_operatore' => 1, 'id_cliente' => 2, 'id_sede' => 2, 'data_inizio' => '2026-11-06', 'ora_inizio' => '11:00'],
        ]);
        $this->messaggio(['id' => 1, 'ore' => 2, 'modello_email' => 'Ciao [OPERATORE] [SEDE]']);

        $this->artisan('notifiche:avviso-turni', ['--prova-sicura' => true])->assertSuccessful();

        Mail::assertSent(NotificaHtml::class, 1);
        Mail::assertSent(NotificaHtml::class, fn (NotificaHtml $mail) => $mail->hasTo(ProvaNotifiche::EMAIL)
            && str_contains($mail->corpoHtml, 'Rossi Mario')
            && str_contains($mail->corpoHtml, 'Sede Nord')
            && ! str_contains($mail->corpoHtml, 'Altro'));
        Mail::assertNotSent(NotificaHtml::class, fn (NotificaHtml $mail) => str_contains($mail->corpoHtml, 'Sede Reale'));
        Http::assertNothingSent();
        $this->assertSame(0, DB::table('tc_email')->count());
        $this->assertSame(0, DB::table('tc_send_sms')->count());
    }

    public function test_prova_ritardi_segna_ritardo_e_coda_senza_sms_ne_altri_destinatari(): void
    {
        config(['sms.enabled' => true]);
        Http::fake();
        DB::table('tc_admin')->where('id', 1)->update(['email' => ProvaNotifiche::EMAIL]);
        DB::table('tc_admin')->insert(['id' => 2, 'nome' => 'Altro', 'email' => 'altro@example.com', 'cellulare' => '+39444']);
        DB::table('tc_clienti')->insert(['id' => 2, 'ragione_sociale' => 'Reale']);
        DB::table('tc_sedi')->insert(['id' => 2, 'descrizione' => 'Sede Reale']);
        DB::table('tc_turni')->insert([
            ['id' => 2, 'id_operatore' => 2, 'id_cliente' => 1, 'id_sede' => 1, 'data_inizio' => '2026-11-06', 'ora_inizio' => '08:00'],
            ['id' => 3, 'id_operatore' => 1, 'id_cliente' => 2, 'id_sede' => 2, 'data_inizio' => '2026-11-06', 'ora_inizio' => '08:00'],
        ]);
        DB::table('tc_send_email')->insert([
            ['id' => 1, 'email' => ProvaNotifiche::EMAIL, 'id_operatore' => 1, 'testo' => 'Coda prova', 'sent' => 'N'],
            ['id' => 2, 'email' => 'altro@example.com', 'id_operatore' => 2, 'testo' => 'Coda altra', 'sent' => 'N'],
            ['id' => 3, 'email' => 'altro@example.com', 'id_operatore' => 1, 'testo' => 'Destinatario diverso', 'sent' => 'N'],
        ]);
        $this->messaggio(['id' => 2, 'modello_email' => 'Ciao [OPERATORE] [SEDE]']);

        $this->artisan('notifiche:ritardi', ['--prova-sicura' => true])->assertSuccessful();
        $this->artisan('notifiche:ritardi', ['--prova-sicura' => true])->assertSuccessful();

        Mail::assertSent(NotificaHtml::class, 2);
        Mail::assertSent(NotificaHtml::class, fn (NotificaHtml $mail) => $mail->hasTo(ProvaNotifiche::EMAIL) && $mail->corpoHtml === 'Coda prova');
        Mail::assertSent(NotificaHtml::class, fn (NotificaHtml $mail) => $mail->hasTo(ProvaNotifiche::EMAIL)
            && str_contains($mail->corpoHtml, 'Rossi Mario')
            && str_contains($mail->corpoHtml, 'Sede Nord'));
        Mail::assertNotSent(NotificaHtml::class, fn (NotificaHtml $mail) => str_contains($mail->corpoHtml, 'Sede Reale'));
        Http::assertNothingSent();
        $this->assertSame(1, (int) DB::table('tc_ritardi')->where('id_turno', 1)->value('inizio'));
        $this->assertSame(1, DB::table('tc_ritardi')->count());
        $this->assertSame(0, DB::table('tc_email')->count());
        $this->assertSame('Y', DB::table('tc_send_email')->where('id', 1)->value('sent'));
        $this->assertSame(2, DB::table('tc_send_email')->where('sent', 'N')->count());
    }

    public function test_prova_scadenze_include_solo_documenti_e_materiali_dell_operatore(): void
    {
        DB::connection('mysql')->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parti) => implode('', $parti));
        DB::table('tc_admin')->where('id', 1)->update(['email' => ProvaNotifiche::EMAIL]);
        DB::table('tc_admin')->insert(['id' => 2, 'nome' => 'Altro', 'email' => 'altro@example.com']);
        Schema::create('_certificati', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome');
        });
        Schema::create('_materiali', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome');
        });
        Schema::create('tc_documenti', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('id_tipo');
            $table->integer('tipologia');
            $table->string('tipo');
            $table->string('descrizione');
            $table->date('scadenza');
        });
        Schema::create('tc_materiali', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('id_operatore');
            $table->integer('id_materiale');
            $table->string('descrizione');
            $table->string('taglia');
            $table->date('riconsegna');
            $table->string('consegnato');
        });
        DB::table('_certificati')->insert(['id' => 1, 'nome' => 'Certificato']);
        DB::table('_materiali')->insert(['id' => 1, 'nome' => 'Materiale']);
        DB::table('tc_documenti')->insert([
            ['id_tipo' => 1, 'tipologia' => 1, 'tipo' => 'O', 'descrizione' => 'proprio', 'scadenza' => '2026-11-06'],
            ['id_tipo' => 2, 'tipologia' => 1, 'tipo' => 'O', 'descrizione' => 'altrui', 'scadenza' => '2026-11-06'],
        ]);
        DB::table('tc_materiali')->insert([
            ['id_operatore' => 1, 'id_materiale' => 1, 'descrizione' => 'proprio', 'taglia' => 'M', 'riconsegna' => '2026-11-06', 'consegnato' => 'N'],
            ['id_operatore' => 2, 'id_materiale' => 1, 'descrizione' => 'altrui', 'taglia' => 'M', 'riconsegna' => '2026-11-06', 'consegnato' => 'N'],
        ]);
        $this->messaggio(['id' => 4, 'modello_email' => '[CERTIFICATO]']);
        $this->messaggio(['id' => 5, 'modello_email' => '[MATERIALE]']);

        $this->artisan('notifiche:scadenze', ['--prova-sicura' => true])->assertSuccessful();

        Mail::assertSent(NotificaHtml::class, 2);
        Mail::assertSent(NotificaHtml::class, fn (NotificaHtml $mail) => $mail->hasTo(ProvaNotifiche::EMAIL) && $mail->corpoHtml === 'Certificato proprio');
        Mail::assertSent(NotificaHtml::class, fn (NotificaHtml $mail) => $mail->hasTo(ProvaNotifiche::EMAIL) && $mail->corpoHtml === 'Materiale proprio Taglia M');
        $this->assertSame(0, DB::table('tc_email')->count());
    }

    public function test_prova_programmazione_mostra_solo_i_turni_dell_operatore(): void
    {
        DB::table('tc_admin')->where('id', 1)->update(['email' => ProvaNotifiche::EMAIL]);
        DB::table('tc_clienti')->insert(['id' => 2, 'ragione_sociale' => 'Reale']);
        DB::table('tc_turni')->insert([
            ['id' => 2, 'id_operatore' => 2, 'id_cliente' => 1, 'data_inizio' => '2026-11-06', 'ora_inizio' => '12:34', 'ora_fine' => '13:34'],
            ['id' => 3, 'id_operatore' => 1, 'id_cliente' => 2, 'data_inizio' => '2026-11-06', 'ora_inizio' => '15:45', 'ora_fine' => '16:45'],
        ]);

        $this->artisan('notifiche:programmazione', ['--prova-sicura' => true])->assertSuccessful();

        Mail::assertSent(NotificaHtml::class, 1);
        Mail::assertSent(NotificaHtml::class, fn (NotificaHtml $mail) => $mail->hasTo(ProvaNotifiche::EMAIL)
            && str_contains($mail->corpoHtml, '08:00 - 16:00')
            && ! str_contains($mail->corpoHtml, '12:34')
            && ! str_contains($mail->corpoHtml, '15:45'));
        $this->assertSame(0, DB::table('tc_email')->count());
    }

    public function test_prova_non_parte_se_l_operatore_non_e_univoco(): void
    {
        DB::table('tc_admin')->insert([
            ['id' => 2, 'email' => ProvaNotifiche::EMAIL],
            ['id' => 3, 'email' => ProvaNotifiche::EMAIL],
        ]);

        try {
            $this->artisan('notifiche:avviso-turni', ['--prova-sicura' => true])->run();
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('deve esistere esattamente un operatore', $exception->getMessage());
            Mail::assertNothingSent();
            $this->assertSame(0, DB::table('tc_email')->count());

            return;
        }

        $this->fail('La prova deve interrompersi quando l’operatore non è univoco.');
    }

    public function test_prova_non_parte_se_il_cliente_non_e_univoco(): void
    {
        DB::table('tc_admin')->where('id', 1)->update(['email' => ProvaNotifiche::EMAIL]);
        DB::table('tc_clienti')->insert(['id' => 2, 'ragione_sociale' => 'prova']);

        try {
            $this->artisan('notifiche:avviso-turni', ['--prova-sicura' => true])->run();
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('esattamente un cliente', $exception->getMessage());
            Mail::assertNothingSent();

            return;
        }

        $this->fail('La prova deve interrompersi quando il cliente non è univoco.');
    }

    public function test_con_prova_di_default_lavviso_senza_flag_arriva_solo_all_operatore_autorizzato(): void
    {
        config(['notifiche.prova' => true, 'sms.enabled' => true]);
        Http::fake();
        DB::table('tc_admin')->where('id', 1)->update(['email' => ProvaNotifiche::EMAIL]);
        DB::table('tc_admin')->insert(['id' => 2, 'nome' => 'Altro', 'email' => 'altro@example.com', 'cellulare' => '+39444']);
        DB::table('tc_turni')->where('id', 1)->update(['ora_inizio' => '11:00']);
        DB::table('tc_turni')->insert(['id' => 2, 'id_operatore' => 2, 'id_cliente' => 1, 'data_inizio' => '2026-11-06', 'ora_inizio' => '11:00']);
        $this->messaggio(['id' => 1, 'ore' => 2]);

        $this->artisan('notifiche:avviso-turni')->assertSuccessful();

        Mail::assertSent(NotificaHtml::class, 1);
        Mail::assertSent(NotificaHtml::class, fn (NotificaHtml $mail) => $mail->hasTo(ProvaNotifiche::EMAIL));
        Mail::assertNotSent(NotificaHtml::class, fn (NotificaHtml $mail) => $mail->hasTo('altro@example.com'));
        Http::assertNothingSent();
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

    public function test_ritardi_a_mezzanotte_restano_nel_diario_senza_elaborare(): void
    {
        Carbon::setTestNow('2026-11-06 00:10:00');
        $this->messaggio(['id' => 2]);
        $this->ascoltaDiario();

        $this->artisan('notifiche:ritardi')
            ->expectsOutputToContain('Ritardi saltati: a mezzanotte non si elabora nulla')
            ->assertSuccessful();

        $this->assertSame('warning', $this->voceDiario('Ritardi saltati: a mezzanotte non si elabora nulla')->level);
        $this->assertSame(['comando' => 'notifiche:ritardi'], $this->voceDiario('Ritardi saltati: a mezzanotte non si elabora nulla')->context);
        $this->assertSame(0, DB::table('tc_ritardi')->count());
    }

    public function test_ritardi_registrano_coda_turni_notificati_e_turni_saltati(): void
    {
        $this->messaggio(['id' => 2]);
        $this->messaggio(['id' => 3]);
        DB::table('tc_turni')->insert([
            ['id' => 2, 'id_sede' => 1, 'id_operatore' => 1, 'id_cliente' => 1, 'data_inizio' => '2026-11-06', 'data_fine' => '2026-11-06', 'ora_inizio' => '08:00', 'ora_fine' => '16:00'],
            ['id' => 3, 'id_sede' => 1, 'id_operatore' => 1, 'id_cliente' => 1, 'data_inizio' => '2026-11-06', 'data_fine' => '2026-11-06', 'ora_inizio' => '08:00', 'ora_fine' => '16:00'],
        ]);
        DB::table('tc_gda')->insert(['id_turno' => 2, 'tipo' => 'I']);
        DB::table('tc_ritardi')->insert(['id_turno' => 3, 'id_operatore' => 1, 'inizio' => 1, 'fine' => 0]);
        DB::table('tc_send_email')->insert([
            'id' => 9, 'email' => 'mario@example.com', 'nome' => 'Rossi Mario', 'id_operatore' => 1, 'testo' => 'Coda', 'sent' => 'N',
        ]);
        $this->ascoltaDiario();

        $this->artisan('notifiche:ritardi')->expectsOutputToContain('Ritardo notificato')->assertSuccessful();

        $this->assertSame([
            'comando' => 'notifiche:ritardi',
            'id' => 9,
            'operatore' => 1,
            'email' => 'mario@example.com',
            'inviate' => 1,
        ], $this->voceDiario('Comunicazione in coda inviata')->context);
        $this->assertSame([
            'comando' => 'notifiche:ritardi',
            'tipo' => 'I',
            'giorno' => '2026-11-06',
            'ora_limite' => '08:51',
            'turni' => 3,
        ], $this->voceDiario('Controllo ritardi', ['tipo' => 'I'])->context);
        $this->assertSame([
            'comando' => 'notifiche:ritardi',
            'turno' => 1,
            'operatore' => 1,
            'tipo' => 'I',
            'sede' => 'Sede Nord',
            'giorno' => '06-11-2026',
            'orario' => '08:00',
            'sms_inviati' => 0,
            'sms_falliti' => 0,
            'motivo_sms' => 'invio SMS disattivato',
            'email_inviate' => 2,
            'email' => 'mario@example.com',
            'motivo_email' => null,
        ], $this->voceDiario('Ritardo notificato')->context);
        $this->assertSame('warning', $this->voceDiario('Ritardo saltato', ['turno' => 2])->level);
        $this->assertSame('timbratura già presente', $this->voceDiario('Ritardo saltato', ['turno' => 2])->context['motivo']);
        $this->assertSame('limite di avvisi raggiunto', $this->voceDiario('Ritardo saltato', ['turno' => 3])->context['motivo']);
        $this->assertSame(1, $this->voceDiario('Ritardo saltato', ['turno' => 3])->context['avvisi']);
        $this->assertSame([
            'comando' => 'notifiche:ritardi',
            'prova' => false,
            'operatore' => null,
            'cliente' => null,
            'coda' => 1,
            'inizio_notificati' => 1,
            'inizio_saltati' => 2,
            'fine_notificati' => 0,
            'fine_saltati' => 0,
        ], $this->voceDiario('Ritardi completati')->context);
    }

    public function test_avviso_turni_registra_sms_email_e_modello_assente(): void
    {
        $this->ascoltaDiario();

        $this->artisan('notifiche:avviso-turni')
            ->expectsOutputToContain('Modello avviso turni assente')
            ->assertSuccessful();

        $this->assertSame('warning', $this->voceDiario('Modello avviso turni assente')->level);

        config(['sms.enabled' => true]);
        $this->fingiProvider();
        DB::table('tc_turni')->update(['ora_inizio' => '11:00']);
        $this->messaggio(['id' => 1, 'ore' => 2]);
        $this->diario = [];

        $this->artisan('notifiche:avviso-turni')->assertSuccessful();

        $this->assertSame([
            'comando' => 'notifiche:avviso-turni',
            'turno' => 1,
            'operatore' => 1,
            'sede' => 'Sede Nord',
            'giorno' => '06-11-2026',
            'orario' => '11:00',
            'sms_inviati' => 3,
            'sms_falliti' => 0,
            'motivo_sms' => null,
            'email_inviate' => 1,
            'email' => 'mario@example.com',
            'motivo_email' => null,
        ], $this->voceDiario('Avviso turno elaborato')->context);
        $this->assertSame([
            'comando' => 'notifiche:avviso-turni',
            'prova' => false,
            'operatore' => null,
            'cliente' => null,
            'giorno' => '2026-11-06',
            'ora' => '11:00',
            'turni' => 1,
            'email_inviate' => 1,
        ], $this->voceDiario('Avvisi turno completati')->context);
    }

    public function test_scadenze_registrano_modello_assente_e_righe_inviate(): void
    {
        $this->ascoltaDiario();

        $this->artisan('notifiche:scadenze')->assertSuccessful();

        $this->assertSame('modello assente', $this->voceDiario('Riepilogo scadenze saltato', ['tipo' => 'certificati'])->context['motivo']);
        $this->assertSame('modello assente', $this->voceDiario('Riepilogo scadenze saltato', ['tipo' => 'materiali'])->context['motivo']);

        DB::connection('mysql')->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parti) => implode('', $parti));
        Schema::create('_certificati', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome');
        });
        Schema::create('tc_documenti', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('id_tipo');
            $table->integer('tipologia');
            $table->string('tipo');
            $table->string('descrizione');
            $table->date('scadenza');
        });
        DB::table('_certificati')->insert(['id' => 1, 'nome' => 'Certificato']);
        DB::table('tc_documenti')->insert([
            'id_tipo' => 1, 'tipologia' => 1, 'tipo' => 'O', 'descrizione' => 'proprio', 'scadenza' => '2026-11-06',
        ]);
        $this->messaggio(['id' => 4, 'modello_email' => '[CERTIFICATO]']);
        $this->diario = [];

        $this->artisan('notifiche:scadenze')->assertSuccessful();

        $this->assertSame([
            'comando' => 'notifiche:scadenze',
            'tipo' => 'certificati',
            'limite' => '2026-11-06',
            'righe' => 1,
            'email_inviate' => 1,
            'destinatari' => 'admin@example.com',
        ], $this->voceDiario('Riepilogo scadenze inviato')->context);
        $this->assertSame(0, $this->voceDiario('Scadenze completate')->context['materiali']);
        $this->assertSame(1, $this->voceDiario('Scadenze completate')->context['certificati']);
    }

    public function test_programmazione_di_prova_registra_ogni_turno(): void
    {
        DB::table('tc_admin')->where('id', 1)->update(['email' => ProvaNotifiche::EMAIL]);
        $this->ascoltaDiario();

        $this->artisan('notifiche:programmazione', ['--prova-sicura' => true])->assertSuccessful();

        $this->assertSame([
            'comando' => 'notifiche:programmazione',
            'turno' => 1,
            'operatore' => 1,
            'cliente' => 1,
            'giorno' => '2026-11-06',
            'ora_inizio' => '08:00',
            'ora_fine' => '16:00',
        ], $this->voceDiario('Turno di prova in programmazione')->context);
        $this->assertSame([
            'comando' => 'notifiche:programmazione',
            'operatore' => 1,
            'cliente' => 1,
            'turni' => 1,
            'email' => ProvaNotifiche::EMAIL,
        ], $this->voceDiario('Programmazione di prova inviata')->context);
    }

    public function test_programmazione_reale_registra_clienti_e_destinatari(): void
    {
        Schema::table('tc_turni', function (Blueprint $table): void {
            $table->float('ore')->nullable();
        });
        Schema::table('tc_sedi', function (Blueprint $table): void {
            $table->unsignedInteger('id_cliente')->nullable();
        });
        Schema::table('tc_admin', function (Blueprint $table): void {
            $table->integer('user_type')->nullable();
        });
        DB::table('tc_sedi')->where('id', 1)->update(['id_cliente' => 1]);
        DB::table('tc_admin')->where('id', 1)->update(['user_type' => 2]);
        $this->ascoltaDiario();

        $this->artisan('notifiche:programmazione')->assertSuccessful();

        $this->assertSame([
            'comando' => 'notifiche:programmazione',
            'cliente' => 1,
            'ragione_sociale' => 'Prova',
            'sedi' => 1,
        ], $this->voceDiario('Programmazione cliente inclusa')->context);
        $this->assertSame([
            'comando' => 'notifiche:programmazione',
            'destinatari' => 1,
            'email_inviate' => 1,
            'indirizzi' => 'mario@example.com',
            'dal' => '2026-11-06',
            'al' => '2026-11-12',
        ], $this->voceDiario('Programmazione inviata')->context);
    }

    private function ascoltaDiario(): void
    {
        config(['logging.channels.cron' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ]]);
        Log::forgetChannel('cron');
        $this->diario = [];

        Log::listen(function (MessageLogged $messaggio): void {
            if (isset($messaggio->context['comando'])) {
                $this->diario[] = $messaggio;
            }
        });
    }

    /** @param  array<string, mixed>  $contesto */
    private function voceDiario(string $messaggio, array $contesto = []): MessageLogged
    {
        foreach ($this->diario as $voce) {
            if ($voce->message !== $messaggio) {
                continue;
            }

            foreach ($contesto as $chiave => $valore) {
                if (($voce->context[$chiave] ?? null) !== $valore) {
                    continue 2;
                }
            }

            return $voce;
        }

        $this->fail('Voce di diario assente: '.$messaggio);
    }
}
