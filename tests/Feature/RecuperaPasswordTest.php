<?php

namespace Tests\Feature;

use App\Livewire\RecuperaPassword;
use App\Mail\NotificaHtml;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class RecuperaPasswordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.name' => 'Omnia Control',
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');

        Schema::create('tc_admin', function (Blueprint $table) {
            $table->id();
            $table->string('user')->nullable();
            $table->string('password')->nullable();
            $table->string('attivo')->default('Y');
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('email')->nullable();
        });

        Schema::create('tc_config', function (Blueprint $table) {
            $table->id();
            $table->string('app')->nullable();
            $table->string('ragione_sociale')->nullable();
            $table->string('colore_primario')->nullable();
            $table->string('societa_indirizzo')->nullable();
            $table->string('societa_cap')->nullable();
            $table->string('societa_comune')->nullable();
            $table->string('societa_provincia')->nullable();
            $table->string('societa_telefono')->nullable();
            $table->string('societa_email')->nullable();
            $table->string('societa_iva')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
        });
        DB::table('tc_config')->insert([
            'app' => 'S 420 ITALPOL', 'ragione_sociale' => 'Top Security Srl', 'colore_primario' => '#289aca',
            'societa_indirizzo' => 'Via Grandi n&deg; 4', 'societa_cap' => '41044', 'societa_comune' => 'Campogalliano',
            'societa_provincia' => 'MO', 'societa_telefono' => '059.3367149', 'societa_email' => 'info@example.test', 'societa_iva' => '0123',
        ]);
    }

    public function test_email_template_has_logo_footer_and_responsive_layout(): void
    {
        $html = NotificaHtml::renderizza('Oggetto prova', '<p>Corpo</p>', ['etichetta' => 'Vai', 'url' => 'https://example.test/x']);

        $this->assertStringContainsString('data:image/png;base64,iVBORw0KGgo', $html);
        $this->assertStringContainsString('Omnia', $html);
        $this->assertStringContainsString('Control', $html);
        $this->assertStringNotContainsString('S 420 ITALPOL', $html);
        $this->assertStringNotContainsString('#289aca', $html);
        $this->assertStringContainsString('#b65d3b', $html);
        $this->assertStringContainsString('#152B38', $html);
        $this->assertStringContainsString('Top Security Srl', $html);
        $this->assertStringContainsString('Via Grandi n° 4 41044 Campogalliano (MO)', $html);
        $this->assertStringContainsString('P.IVA 0123', $html);
        $this->assertStringContainsString('max-width: 620px', $html);
        $this->assertStringContainsString('https://example.test/x', $html);
        $this->assertStringContainsString('<p>Corpo</p>', $html);
    }

    private function utente(string $attivo = 'Y'): void
    {
        DB::table('tc_admin')->insert([
            'user' => 'mario@example.test', 'email' => 'mario@example.test', 'nome' => 'Mario', 'cognome' => 'Rossi',
            'password' => Hash::make('vecchia123'), 'attivo' => $attivo,
        ]);
    }

    public function test_login_page_links_to_password_recovery(): void
    {
        $this->get('/login')->assertOk()->assertSee(route('password.recupero'), false);
        $this->get('/recupera-password')->assertOk();
    }

    public function test_empty_recovery_request_asks_for_the_user(): void
    {
        Livewire::test(RecuperaPassword::class)
            ->set('email', '')
            ->call('recupera')
            ->assertHasErrors(['email' => 'required'])
            ->assertSee('Il campo utente è obbligatorio.');
    }

    public function test_password_recovery_page_shows_omnia_brand(): void
    {
        $this->get('/recupera-password')
            ->assertOk()
            ->assertSee('<title>Recupero password · Omnia Control</title>', false)
            ->assertSee('brand/omnia-control.svg', false)
            ->assertSee('OMNIA', false)
            ->assertSee('CONTROL', false)
            ->assertSee('Recupera password', false);
    }

    public function test_new_password_is_emailed_and_stored_hashed(): void
    {
        Mail::fake();
        $this->utente();

        Livewire::test(RecuperaPassword::class)->set('email', 'mario@example.test')->call('recupera')->assertSet('inviata', true);

        $nuova = null;
        Mail::assertSent(NotificaHtml::class, function (NotificaHtml $mail) use (&$nuova) {
            preg_match('/monospace[^>]*>(\w+)</', $mail->corpoHtml, $m);
            $nuova = $m[1] ?? null;

            return $mail->hasTo('mario@example.test');
        });
        $hash = DB::table('tc_admin')->value('password');
        $this->assertNotNull($nuova);
        $this->assertTrue(Hash::check($nuova, $hash));
        $this->assertFalse(Hash::check('vecchia123', $hash));
    }

    public function test_unknown_or_inactive_user_gets_same_answer_and_no_email(): void
    {
        Mail::fake();
        $this->utente('N');

        Livewire::test(RecuperaPassword::class)->set('email', 'mario@example.test')->call('recupera')->assertSet('inviata', true);
        Livewire::test(RecuperaPassword::class)->set('email', 'nessuno@example.test')->call('recupera')->assertSet('inviata', true);

        Mail::assertNothingSent();
    }

    public function test_requests_are_rate_limited(): void
    {
        Mail::fake();
        $this->utente();
        $componente = Livewire::test(RecuperaPassword::class)->set('email', 'mario@example.test');

        for ($i = 0; $i < 3; $i++) {
            $componente->set('inviata', false)->call('recupera');
        }
        $componente->set('inviata', false)->call('recupera')->assertHasErrors('email')->assertSet('inviata', false);
    }

    public function test_old_password_is_kept_if_email_fails(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $this->utente();
        $prima = DB::table('tc_admin')->value('password');

        Livewire::test(RecuperaPassword::class)->set('email', 'mario@example.test')->call('recupera');

        $this->assertSame($prima, DB::table('tc_admin')->value('password'));
    }
}
