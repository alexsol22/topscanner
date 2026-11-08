<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SidebarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');

        Schema::connection('mysql')->create('tc_admin', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('user')->nullable();
            $table->string('password')->nullable();
            $table->string('attivo')->default('Y');
            $table->unsignedInteger('user_type')->nullable();
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('email')->nullable();
        });

        Schema::connection('mysql')->create('_tracking', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('id_user')->nullable();
            $table->string('addr')->nullable();
            $table->string('host')->nullable();
            $table->string('agent')->nullable();
            $table->string('pagina')->nullable();
            $table->dateTime('data_view')->nullable();
            $table->text('action')->nullable();
        });
    }

    public function test_staff_sidebar_matches_legacy_sections_with_operatori_label(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2, 'nome' => 'Alessia', 'cognome' => 'Verdi']);

        $risposta = $this->actingAs($staff)->get(route('accessi'))->assertOk();

        $risposta->assertSee('href="'.url('/').'"', false);

        foreach (['clienti', 'operatori', 'turni', 'documenti', 'bacheca', 'messaggi', 'amministratori'] as $rotta) {
            $risposta->assertSee('href="'.route($rotta).'"', false);
        }

        foreach ([
            route('presenze'),
            route('presenze.clienti'),
            route('presenze.operatori'),
            route('messaggi.canale', ['canale' => 'sms']),
            route('messaggi.canale', ['canale' => 'email']),
        ] as $url) {
            $risposta->assertSee('href="'.$url.'"', false);
        }

        $risposta->assertSee('Operatori')
            ->assertSee('aria-label="Navigazione principale"', false)
            ->assertSee('aria-controls="navigazione-principale"', false)
            ->assertDontSee('id="ricerca-pagine"', false)
            ->assertSee('Bacheca')
            ->assertSee('Agenda Pos')
            ->assertSee('Orari clienti')
            ->assertSee('Orari operatori')
            ->assertSee('SMS inviati')
            ->assertSee('Email inviate')
            ->assertSee('WhatsApp inviati')
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('Utenti')
            ->assertDontSee('Data Base');
        $risposta->assertDontSee('href="'.route('accessi').'"', false);
        $risposta->assertDontSee('href="'.route('messaggi.modelli').'"', false);
        $risposta->assertDontSee('href="'.route('sedi').'"', false);
    }

    public function test_staff_home_redirects_to_clienti(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2, 'nome' => 'Alessia', 'cognome' => 'Verdi']);

        $this->actingAs($staff)->get('/')->assertRedirectToRoute('clienti');
    }

    public function test_dashboard_route_is_removed(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2, 'nome' => 'Alessia', 'cognome' => 'Verdi']);

        $this->actingAs($staff)->get('/dashboard')->assertNotFound();
    }
}
