<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => config('database.connections.sqlite'),
        ]);
        DB::purge('mysql');

        Schema::create('tc_admin', function (Blueprint $table) {
            $table->id();
            $table->string('user')->nullable();
            $table->string('password')->nullable();
            $table->string('password_old')->nullable();
            $table->string('attivo')->default('Y');
            $table->integer('user_type')->nullable();
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('email')->nullable();
        });

        Schema::create('_tracking', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('id_user')->nullable();
            $table->string('addr')->nullable();
            $table->string('host')->nullable();
            $table->string('agent')->nullable();
            $table->string('pagina')->nullable();
            $table->string('data_view')->nullable();
            $table->string('action')->nullable();
        });
    }

    private function utente(string $password, string $attivo = 'Y'): void
    {
        DB::table('tc_admin')->insert([
            'user' => 'mario',
            'password' => Hash::make($password),
            'password_old' => $password,
            'attivo' => $attivo,
        ]);
    }

    public function test_login_works_with_the_original_password_after_hashing(): void
    {
        $this->utente('2weifo');

        $this->assertTrue(Auth::attempt(['user' => 'mario', 'password' => '2weifo']));
    }

    public function test_login_is_case_sensitive_and_rejects_wrong_password(): void
    {
        $this->utente('2weifo');

        $this->assertFalse(Auth::attempt(['user' => 'mario', 'password' => '2WEIFO']));
        $this->assertFalse(Auth::attempt(['user' => 'mario', 'password' => 'sbagliata']));
    }

    public function test_hash_itself_cannot_be_used_as_password(): void
    {
        $this->utente('2weifo');
        $hash = DB::table('tc_admin')->value('password');

        $this->assertFalse(Auth::attempt(['user' => 'mario', 'password' => $hash]));
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->utente('2weifo', 'N');

        $this->assertFalse(Auth::attempt(['user' => 'mario', 'password' => '2weifo']));
    }

    public function test_new_passwords_are_stored_hashed(): void
    {
        $utente = User::create(['user' => 'luigi', 'password' => 'segreta1', 'user_type' => 4]);

        $this->assertStringStartsWith('$2y$', $utente->getRawOriginal('password'));
        $this->assertTrue(Auth::attempt(['user' => 'luigi', 'password' => 'segreta1']));
    }
}
