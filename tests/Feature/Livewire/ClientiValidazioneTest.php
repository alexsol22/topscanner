<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Clienti;
use App\Mail\NotificaHtml;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ClientiValidazioneTest extends TestCase
{
    public function test_invalid_piva_shows_italian_message_and_stays_on_data_tab(): void
    {
        $this->creaSchema();

        Livewire::actingAs(new User(['user_type' => 2]))->test(Clienti::class)
            ->call('nuovo')
            ->set('tab', 'operatori')
            ->set('f.ragione_sociale', 'Alpha')
            ->set('f.email', 'alpha@example.test')
            ->set('f.piva', '123')
            ->call('salva')
            ->assertHasErrors(['f.piva'])
            ->assertSet('tab', 'dati')
            ->assertSet('modal', true)
            ->assertSee('Il campo partita IVA deve essere di 11 cifre.')
            ->assertDontSee('must be');
    }

    private function creaSchema(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');

        Schema::create('tc_clienti', function (Blueprint $table): void {
            $table->increments('id');
            foreach (['accesso', 'ragione_sociale', 'codice_fiscale', 'piva', 'indirizzo', 'cap', 'nome', 'cognome', 'telefono', 'cellulare', 'fax', 'email', 'pec', 'colore', 'pos'] as $colonna) {
                $table->string($colonna)->nullable();
            }
            $table->integer('citta_id')->nullable();
            $table->integer('provincia_id')->nullable();
            $table->float('lat')->nullable();
            $table->float('lng')->nullable();
            $table->integer('isTest')->nullable();
        });
        foreach (['tc_operatori_preferiti', 'tc_operatori_escludi'] as $tabella) {
            Schema::create($tabella, function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('id_cliente');
                $table->unsignedInteger('id_operatore');
            });
        }
        Schema::create('tc_documenti', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_tipo')->nullable();
            $table->string('tipo')->nullable();
        });
        Schema::create('tc_sedi', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_cliente')->nullable();
        });
        Schema::create('_provincia', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome')->nullable();
            $table->string('sigla')->nullable();
        });
        Schema::create('_comuni', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_provincia')->nullable();
            $table->string('comune')->nullable();
        });
        Schema::create('tc_admin', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_type')->nullable();
            $table->string('attivo')->nullable();
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->unsignedInteger('id_cliente')->nullable();
            $table->string('user')->nullable();
            $table->string('email')->nullable();
            $table->string('cellulare')->nullable();
            $table->string('password')->nullable();
        });
    }

    private function salvaCliente(bool $accesso = true): Testable
    {
        return Livewire::actingAs(new User(['user_type' => 2]))->test(Clienti::class)
            ->call('nuovo')
            ->set('f.ragione_sociale', 'Alpha')
            ->set('f.email', 'alpha@example.test')
            ->set('f.piva', '12345678901')
            ->set('f.accesso', $accesso)
            ->call('salva');
    }

    public function test_enabling_access_creates_account_and_emails_credentials(): void
    {
        $this->creaSchema();
        Mail::fake();

        $this->salvaCliente()->assertHasNoErrors();

        $account = DB::table('tc_admin')->where('user', 'alpha@example.test')->first();
        $this->assertSame(3, (int) $account->user_type);
        $this->assertStringStartsWith('$2y$', $account->password);
        Mail::assertSent(NotificaHtml::class, fn (NotificaHtml $m) => $m->hasTo('alpha@example.test') && str_contains($m->corpoHtml, 'alpha@example.test'));
    }

    public function test_existing_account_is_not_duplicated_nor_re_emailed(): void
    {
        $this->creaSchema();
        Mail::fake();
        $this->salvaCliente();
        $clienteId = DB::table('tc_clienti')->value('id');

        Livewire::actingAs(new User(['user_type' => 2]))->test(Clienti::class)
            ->call('modifica', $clienteId)->set('f.accesso', true)->call('salva');

        $this->assertSame(1, DB::table('tc_admin')->where('id_cliente', $clienteId)->count());
        Mail::assertSentCount(1);
    }

    public function test_access_disabled_creates_no_account(): void
    {
        $this->creaSchema();
        Mail::fake();

        $this->salvaCliente(false);

        $this->assertSame(0, DB::table('tc_admin')->count());
        Mail::assertNothingSent();
    }
}
