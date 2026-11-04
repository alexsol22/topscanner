<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccessiTest extends TestCase
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

    public function test_staff_can_view_access_records_with_user_details(): void
    {
        $staff = User::create([
            'user' => 'staff',
            'password' => 'secret',
            'attivo' => 'Y',
            'user_type' => 2,
            'nome' => 'Alessia',
            'cognome' => 'Verdi',
            'email' => 'alessia@example.test',
        ]);

        DB::connection('mysql')->table('_tracking')->insert([
            'id' => 1,
            'id_user' => $staff->id,
            'addr' => '192.0.2.10',
            'host' => '',
            'agent' => 'Browser di test',
            'pagina' => '',
            'data_view' => '2026-11-04 18:30:00',
            'action' => 'Accesso utente staff',
        ]);

        $this->actingAs($staff)
            ->get(route('accessi'))
            ->assertOk()
            ->assertSee('Log accessi')
            ->assertSee((string) $staff->id)
            ->assertSee('Alessia')
            ->assertSee('Verdi')
            ->assertSee('Amministratore')
            ->assertSee('staff')
            ->assertSee('alessia@example.test')
            ->assertSee('04/11/2026 18:30')
            ->assertSee('192.0.2.10')
            ->assertSee('Browser di test');
    }

    public function test_guest_is_redirected_to_login_from_access_records(): void
    {
        $this->get(route('accessi'))
            ->assertRedirect(route('login'));
    }

    public function test_non_staff_user_cannot_view_access_records(): void
    {
        $operator = User::create([
            'user' => 'operator',
            'password' => 'secret',
            'attivo' => 'Y',
            'user_type' => 4,
        ]);

        $this->actingAs($operator)
            ->get(route('accessi'))
            ->assertForbidden();
    }
}
