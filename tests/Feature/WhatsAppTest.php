<?php

namespace Tests\Feature;

use App\Enums\WhatsappStato;
use App\Models\Legacy\SendWhatsapp;
use App\Models\User;
use App\Services\WhatsApp;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppTest extends TestCase
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
            $table->string('user')->nullable();
            $table->string('password')->nullable();
            $table->string('attivo')->default('Y');
            $table->unsignedInteger('user_type')->nullable();
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('email')->nullable();
        });

        Schema::create('tc_send_whatsapp', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('destinatario', 20);
            $table->string('tipo', 20);
            $table->string('template', 100)->nullable();
            $table->string('lingua', 12)->nullable();
            $table->text('testo')->nullable();
            $table->text('parametri')->nullable();
            $table->string('wamid', 191)->nullable()->unique();
            $table->string('stato', 20);
            $table->text('errore')->nullable();
            $table->unsignedInteger('id_operatore')->default(0);
            $table->dateTime('data_invio');
            $table->dateTime('aggiornato_il')->nullable();
        });
    }

    public function test_prova_disattivata_non_chiama_meta_e_non_registra_invii(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        config([
            'whatsapp.enabled' => false,
            'whatsapp.token' => 'token-prova',
            'whatsapp.phone_number_id' => '123456789',
            'whatsapp.destinatario_prova' => '+39 333 1112233',
            'whatsapp.template_prova' => 'hello_world',
        ]);

        $this->artisan('whatsapp:prova')
            ->expectsOutputToContain('WHATSAPP_ENABLED=true')
            ->assertFailed();

        Http::assertNothingSent();
        $this->assertSame(0, DB::table('tc_send_whatsapp')->count());
    }

    public function test_prova_senza_token_indica_la_chiave_mancante(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        config([
            'whatsapp.enabled' => true,
            'whatsapp.token' => null,
            'whatsapp.phone_number_id' => '123456789',
            'whatsapp.destinatario_prova' => '393331112233',
            'whatsapp.template_prova' => 'hello_world',
        ]);

        $this->artisan('whatsapp:prova')
            ->expectsOutputToContain('WHATSAPP_TOKEN')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_prova_invia_il_template_e_registra_lid_meta(): void
    {
        $this->freezeTime();
        $this->preparaInvio();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.PROVA']]]),
        ]);

        $this->artisan('whatsapp:prova', ['numero' => '+39 333 111 2233'])
            ->expectsOutputToContain('WhatsApp inviato a 393331112233. Id Meta: wamid.PROVA')
            ->assertSuccessful();

        $this->assertSame('393331112233', DB::table('tc_send_whatsapp')->value('destinatario'));
        $this->assertSame('template', DB::table('tc_send_whatsapp')->value('tipo'));
        $this->assertSame('hello_world', DB::table('tc_send_whatsapp')->value('template'));
        $this->assertSame('en_US', DB::table('tc_send_whatsapp')->value('lingua'));
        $this->assertSame('inviato', DB::table('tc_send_whatsapp')->value('stato'));
        $this->assertSame('wamid.PROVA', DB::table('tc_send_whatsapp')->value('wamid'));
        Http::assertSent(fn ($richiesta) => $richiesta->url() === 'https://graph.facebook.com/v22.0/123456789/messages'
            && $richiesta->hasHeader('Authorization', 'Bearer token-prova')
            && $richiesta['to'] === '393331112233'
            && $richiesta['type'] === 'template'
            && $richiesta['template']['name'] === 'hello_world'
            && $richiesta['template']['language']['code'] === 'en_US'
            && ! isset($richiesta['template']['components']));
    }

    public function test_prova_con_parametri_li_mette_nel_body_del_template(): void
    {
        $this->preparaInvio();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.PARAM']]]),
        ]);

        $this->artisan('whatsapp:prova', ['--param' => ['Mario', 'Sede Nord']])->assertSuccessful();

        Http::assertSent(fn ($richiesta) => $richiesta['template']['components'][0]['parameters'][0]['text'] === 'Mario'
            && $richiesta['template']['components'][0]['parameters'][1]['text'] === 'Sede Nord');
        $this->assertSame('["Mario","Sede Nord"]', DB::table('tc_send_whatsapp')->value('parametri'));
    }

    public function test_rifiuto_di_meta_segna_il_messaggio_fallito(): void
    {
        $this->preparaInvio();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template non trovato']], 400),
        ]);

        $this->artisan('whatsapp:prova')
            ->expectsOutputToContain('Template non trovato')
            ->assertFailed();

        $this->assertSame('fallito', DB::table('tc_send_whatsapp')->value('stato'));
        $this->assertNull(DB::table('tc_send_whatsapp')->value('wamid'));
    }

    public function test_connessione_assente_segna_il_messaggio_fallito(): void
    {
        $this->preparaInvio();
        Http::fake([
            'graph.facebook.com/*' => Http::failedConnection(),
        ]);

        $this->artisan('whatsapp:prova')->assertFailed();

        $this->assertSame('fallito', DB::table('tc_send_whatsapp')->value('stato'));
        $this->assertNotNull(DB::table('tc_send_whatsapp')->value('errore'));
    }

    public function test_testo_libero_viene_inviato_nel_body(): void
    {
        $this->preparaInvio();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TESTO']]]),
        ]);

        $inviato = app(WhatsApp::class)->inviaTesto('0039 3331112233', 'Promemoria turno');

        $this->assertSame(WhatsappStato::Inviato, $inviato?->stato);
        $this->assertSame('testo', $inviato?->tipo);
        $this->assertSame('Promemoria turno', $inviato?->testo);
        Http::assertSent(fn ($richiesta) => $richiesta['type'] === 'text'
            && $richiesta['text']['body'] === 'Promemoria turno'
            && $richiesta['to'] === '393331112233');
    }

    public function test_verifica_webhook_restituisce_la_challenge(): void
    {
        config(['whatsapp.verify_token' => 'token-verifica']);

        $this->get($this->urlWebhook([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'token-verifica',
            'hub.challenge' => '998877',
        ]))->assertOk()->assertSee('998877');
    }

    public function test_verifica_webhook_con_token_errato_risponde_403(): void
    {
        config(['whatsapp.verify_token' => 'token-verifica']);

        $this->get($this->urlWebhook([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'altro',
            'hub.challenge' => '998877',
        ]))->assertForbidden();
    }

    public function test_verifica_webhook_senza_token_configurato_risponde_403(): void
    {
        config(['whatsapp.verify_token' => null]);

        $this->get($this->urlWebhook([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'token-verifica',
            'hub.challenge' => '998877',
        ]))->assertForbidden();
    }

    public function test_stato_consegnato_aggiorna_il_messaggio(): void
    {
        $this->messaggio('wamid.1');

        $this->postWebhook([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.1',
                            'status' => 'delivered',
                            'timestamp' => '1700000000',
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $this->assertSame('consegnato', DB::table('tc_send_whatsapp')->value('stato'));
        $this->assertSame(1700000000, SendWhatsapp::query()->first()?->aggiornato_il?->getTimestamp());
    }

    public function test_stato_fallito_salva_il_motivo(): void
    {
        $this->messaggio('wamid.1');

        $this->postWebhook([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.1',
                            'status' => 'failed',
                            'timestamp' => '1700000100',
                            'errors' => [['message' => 'Numero non su WhatsApp']],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $this->assertSame('fallito', DB::table('tc_send_whatsapp')->value('stato'));
        $this->assertSame('Numero non su WhatsApp', DB::table('tc_send_whatsapp')->value('errore'));
    }

    public function test_stato_piu_vecchio_non_sovrascrive_un_aggiornamento_successivo(): void
    {
        $this->messaggio('wamid.1', [
            'stato' => 'letto',
            'aggiornato_il' => '2023-11-14 23:30:00',
        ]);

        $this->postWebhook([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.1',
                            'status' => 'delivered',
                            'timestamp' => '1700000000',
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $this->assertSame('letto', DB::table('tc_send_whatsapp')->value('stato'));
    }

    public function test_webhook_con_firma_errata_risponde_403_e_non_cambia_lo_stato(): void
    {
        config(['whatsapp.app_secret' => 'segreto-app']);
        $this->messaggio('wamid.1');

        $this->call('POST', route('whatsapp.webhook'), server: [
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=firma-errata',
            'CONTENT_TYPE' => 'application/json',
        ], content: '{"entry":[]}')
            ->assertForbidden();

        $this->assertSame('inviato', DB::table('tc_send_whatsapp')->value('stato'));
    }

    public function test_webhook_senza_app_secret_risponde_403(): void
    {
        config(['whatsapp.app_secret' => null]);

        $this->call('POST', route('whatsapp.webhook'), server: [
            'CONTENT_TYPE' => 'application/json',
        ], content: '{"entry":[]}')->assertForbidden();
    }

    public function test_webhook_con_json_non_valido_risponde_400(): void
    {
        config(['whatsapp.app_secret' => 'segreto-app']);
        $corpo = '{';

        $this->call('POST', route('whatsapp.webhook'), server: [
            'HTTP_X_HUB_SIGNATURE_256' => $this->firma($corpo),
            'CONTENT_TYPE' => 'application/json',
        ], content: $corpo)->assertBadRequest();
    }

    public function test_staff_vede_il_registro_e_il_testo_viene_escapato(): void
    {
        $staff = User::create([
            'user' => 'staff',
            'password' => 'secret',
            'attivo' => 'Y',
            'user_type' => 2,
            'nome' => 'Alessia',
            'cognome' => 'Verdi',
        ]);
        User::create([
            'user' => 'mario',
            'password' => 'secret',
            'attivo' => 'Y',
            'user_type' => 4,
            'nome' => 'Mario',
            'cognome' => 'Rossi',
        ]);
        SendWhatsapp::create([
            'destinatario' => '393331112233',
            'tipo' => 'template',
            'template' => 'hello_world',
            'lingua' => 'en_US',
            'testo' => '<script>alert(1)</script>',
            'stato' => WhatsappStato::Inviato,
            'id_operatore' => 2,
            'data_invio' => '2026-11-14 12:08:00',
        ]);

        $risposta = $this->actingAs($staff)
            ->get(route('messaggi.canale', ['canale' => 'whatsapp']))
            ->assertOk();

        $risposta->assertSee('WhatsApp inviati')
            ->assertSee('393331112233')
            ->assertSee('Mario Rossi')
            ->assertSee('hello_world')
            ->assertSee('Inviato')
            ->assertSee('<script>alert(1)</script>')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_operatore_non_apre_il_registro_whatsapp(): void
    {
        $operatore = User::create([
            'user' => 'op',
            'password' => 'secret',
            'attivo' => 'Y',
            'user_type' => 4,
            'nome' => 'Mario',
            'cognome' => 'Rossi',
        ]);

        $this->actingAs($operatore)
            ->get(route('messaggi.canale', ['canale' => 'whatsapp']))
            ->assertForbidden();
    }

    public function test_ospite_viene_reindirizzato_al_login(): void
    {
        $this->get(route('messaggi.canale', ['canale' => 'whatsapp']))
            ->assertRedirect(route('login'));
    }

    private function preparaInvio(): void
    {
        Http::preventStrayRequests();
        config([
            'whatsapp.enabled' => true,
            'whatsapp.token' => 'token-prova',
            'whatsapp.phone_number_id' => '123456789',
            'whatsapp.graph_url' => 'https://graph.facebook.com',
            'whatsapp.api_version' => 'v22.0',
            'whatsapp.template_prova' => 'hello_world',
            'whatsapp.lingua_prova' => 'en_US',
            'whatsapp.destinatario_prova' => '393331112233',
            'whatsapp.app_secret' => 'segreto-app',
        ]);
    }

    /** @param  array<string, mixed>  $valori */
    private function messaggio(string $wamid, array $valori = []): void
    {
        SendWhatsapp::create($valori + [
            'destinatario' => '393331112233',
            'tipo' => 'template',
            'template' => 'hello_world',
            'testo' => 'hello_world',
            'wamid' => $wamid,
            'stato' => WhatsappStato::Inviato,
            'id_operatore' => 0,
            'data_invio' => '2026-11-14 12:00:00',
        ]);
    }

    /** @param  array<string, string>  $query */
    private function urlWebhook(array $query): string
    {
        return route('whatsapp.webhook').'?'.http_build_query($query);
    }

    /** @param  array<string, mixed>  $payload */
    private function postWebhook(array $payload)
    {
        config(['whatsapp.app_secret' => 'segreto-app']);
        $corpo = json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->call('POST', route('whatsapp.webhook'), server: [
            'HTTP_X_HUB_SIGNATURE_256' => $this->firma($corpo),
            'CONTENT_TYPE' => 'application/json',
        ], content: $corpo);
    }

    private function firma(string $corpo): string
    {
        return 'sha256='.hash_hmac('sha256', $corpo, 'segreto-app');
    }
}
