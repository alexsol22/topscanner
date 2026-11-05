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

    public function test_staff_sidebar_links_every_section_without_disabled_entries(): void
    {
        $staff = User::create(['user' => 'staff', 'password' => 'secret', 'attivo' => 'Y', 'user_type' => 2, 'nome' => 'Alessia', 'cognome' => 'Verdi']);

        $risposta = $this->actingAs($staff)->get(route('accessi'))->assertOk()->assertDontSee('Disponibile prossimamente');

        foreach (['dashboard', 'amministratori', 'accessi', 'clienti', 'operatori', 'sedi', 'turni', 'presenze', 'bacheca', 'messaggi', 'messaggi.modelli', 'documenti'] as $rotta) {
            $risposta->assertSee('href="'.route($rotta).'"', false);
        }
    }
}
