<?php

namespace Tests\Feature;

use App\Mail\NotificaHtml;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SegnaInviiNotificheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');
        DB::connection('mysql')->getPdo()->sqliteCreateFunction('DATE_FORMAT', fn ($data) => date('d-m-Y', strtotime((string) $data)));
        DB::connection('mysql')->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parti) => implode('', $parti));

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
            $table->unsignedInteger('id_operatore')->nullable();
            $table->unsignedInteger('id_sede')->nullable();
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
        Schema::create('tc_send_email', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('email')->nullable();
            $table->string('nome')->nullable();
            $table->text('testo')->nullable();
            $table->unsignedInteger('id_operatore')->nullable();
            $table->string('sent')->default('N');
            $table->dateTime('data_sent')->nullable();
        });
        Schema::create('tc_messaggi', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('giorni')->default(0);
            $table->integer('minuti')->nullable();
            $table->string('invia_sms')->nullable();
            $table->string('invia_email')->nullable();
            $table->text('modello_email')->nullable();
            $table->string('oggetto')->nullable();
            $table->string('mittente')->nullable();
            $table->integer('numero_sms')->nullable();
            $table->string('admin_email')->nullable();
            $table->date('segnato_fino_al')->nullable();
        });
        Schema::create('tc_email', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_operatore')->nullable();
            $table->string('oggetto')->nullable();
            $table->text('email')->nullable();
            $table->text('destinatario')->nullable();
            $table->dateTime('data_invio')->nullable();
        });
        Schema::create('tc_config', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('app')->nullable();
        });

        DB::table('tc_admin')->insert(['id' => 1, 'nome' => 'Mario', 'cognome' => 'Rossi', 'email' => 'mario@example.com']);
        DB::table('tc_sedi')->insert(['id' => 1, 'descrizione' => 'Sede Nord']);
        DB::table('tc_turni')->insert([
            ['id' => 1, 'id_operatore' => 1, 'id_sede' => 1, 'data_inizio' => '2026-11-06', 'data_fine' => '2026-11-06', 'ora_inizio' => '08:00', 'ora_fine' => '12:00'],
            ['id' => 2, 'id_operatore' => 1, 'id_sede' => 1, 'data_inizio' => '2026-11-06', 'data_fine' => '2026-11-06', 'ora_inizio' => '18:00', 'ora_fine' => '22:00'],
            ['id' => 3, 'id_operatore' => 1, 'id_sede' => 1, 'data_inizio' => '2026-11-06', 'data_fine' => '2026-11-06', 'ora_inizio' => '08:00', 'ora_fine' => '12:00'],
            ['id' => 4, 'id_operatore' => 1, 'id_sede' => 1, 'data_inizio' => '2026-11-05', 'data_fine' => '2026-11-06', 'ora_inizio' => '22:00', 'ora_fine' => '08:00'],
        ]);
        DB::table('tc_gda')->insert(['id' => 1, 'id_turno' => 3, 'tipo' => 'I']);
        DB::table('tc_ritardi')->insert(['id' => 10, 'id_turno' => 1, 'id_operatore' => 1, 'inizio' => 0, 'fine' => 0]);
        DB::table('tc_send_email')->insert(['id' => 1, 'email' => 'mario@example.com', 'nome' => 'Mario', 'testo' => 'Coda', 'id_operatore' => 1, 'sent' => 'N']);
        DB::table('tc_messaggi')->insert([
            ['id' => 2, 'giorni' => 0, 'minuti' => 2, 'invia_sms' => 'N', 'invia_email' => 'Y', 'modello_email' => 'Ciao', 'oggetto' => 'Ritardo', 'mittente' => 'S-420', 'numero_sms' => 1, 'admin_email' => 'admin@example.com'],
            ['id' => 3, 'giorni' => 0, 'minuti' => 30, 'invia_sms' => 'N', 'invia_email' => 'N', 'modello_email' => 'Fine', 'oggetto' => 'Fine', 'mittente' => 'S-420', 'numero_sms' => 1, 'admin_email' => ''],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_segna_i_ritardi_gia_maturati_e_la_coda_senza_inviare(): void
    {
        $this->artisan('notifiche:segna-inviati')->assertSuccessful();
        $this->artisan('notifiche:ritardi')->assertSuccessful();

        $this->assertSame(1, (int) DB::table('tc_ritardi')->where('id_turno', 1)->value('inizio'));
        $this->assertSame(1, DB::table('tc_ritardi')->where('id_turno', 1)->count());
        $this->assertNull(DB::table('tc_ritardi')->where('id_turno', 2)->value('id'));
        $this->assertNull(DB::table('tc_ritardi')->where('id_turno', 3)->value('id'));
        $this->assertSame(1, (int) DB::table('tc_ritardi')->where('id_turno', 4)->value('fine'));
        $this->assertSame('Y', DB::table('tc_send_email')->where('id', 1)->value('sent'));
        Mail::assertNothingSent();
    }

    public function test_segna_le_scadenze_gia_nel_periodo_e_il_cron_invia_solo_le_nuove(): void
    {
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
        DB::table('_materiali')->insert(['id' => 1, 'nome' => 'Divisa']);
        DB::table('tc_documenti')->insert([
            ['id' => 1, 'id_tipo' => 1, 'tipologia' => 1, 'tipo' => 'O', 'descrizione' => 'vecchio', 'scadenza' => '2026-11-06'],
            ['id' => 2, 'id_tipo' => 1, 'tipologia' => 1, 'tipo' => 'O', 'descrizione' => 'nuovo', 'scadenza' => '2026-11-22'],
        ]);
        DB::table('tc_materiali')->insert([
            'id' => 1, 'id_operatore' => 1, 'id_materiale' => 1, 'descrizione' => 'vecchio', 'taglia' => 'M', 'riconsegna' => '2026-11-06', 'consegnato' => 'N',
        ]);
        DB::table('tc_messaggi')->insert([
            ['id' => 4, 'giorni' => 15, 'minuti' => 0, 'invia_sms' => 'N', 'invia_email' => 'Y', 'modello_email' => '[CERTIFICATO]', 'oggetto' => 'Scadenza', 'mittente' => 'S-420', 'numero_sms' => 1, 'admin_email' => 'admin@example.com'],
            ['id' => 5, 'giorni' => 15, 'minuti' => 0, 'invia_sms' => 'N', 'invia_email' => 'Y', 'modello_email' => '[MATERIALE]', 'oggetto' => 'Materiale', 'mittente' => 'S-420', 'numero_sms' => 1, 'admin_email' => 'admin@example.com'],
        ]);

        $this->artisan('notifiche:segna-inviati')->assertSuccessful();
        $this->artisan('notifiche:scadenze')->assertSuccessful();

        $this->assertSame('2026-11-21', Carbon::parse(DB::table('tc_messaggi')->where('id', 4)->value('segnato_fino_al'))->toDateString());
        $this->assertSame('2026-11-21', Carbon::parse(DB::table('tc_messaggi')->where('id', 5)->value('segnato_fino_al'))->toDateString());
        Mail::assertNothingSent();

        Carbon::setTestNow('2026-11-07 09:00:00');
        $this->artisan('notifiche:scadenze')->assertSuccessful();

        Mail::assertSent(NotificaHtml::class, 1);
        Mail::assertSent(NotificaHtml::class, fn (NotificaHtml $mail) => $mail->hasTo('admin@example.com')
            && str_contains($mail->corpoHtml, 'Certificato nuovo')
            && ! str_contains($mail->corpoHtml, 'vecchio'));
    }
}
