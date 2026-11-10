<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CollaudoManualeAccessTest extends TestCase
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
    }

    public function test_named_email_can_view_the_manual_checklist(): void
    {
        $user = $this->createUser('alessiosolari22@gmail.com');

        $this->actingAs($user)
            ->get(route('collaudo-manuale'))
            ->assertOk()
            ->assertSee('Checklist collaudo manuale')
            ->assertSee('COMMS-08');
    }

    public function test_other_staff_user_is_forbidden_from_the_manual_checklist(): void
    {
        $user = $this->createUser('admin@example.test', 2);

        $this->actingAs($user)
            ->get(route('collaudo-manuale'))
            ->assertForbidden();
    }

    public function test_email_with_different_case_is_forbidden_from_the_manual_checklist(): void
    {
        $user = $this->createUser('AlessioSolari22@gmail.com');

        $this->actingAs($user)
            ->get(route('collaudo-manuale'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_the_manual_checklist(): void
    {
        $this->get(route('collaudo-manuale'))
            ->assertRedirect(route('login'));
    }

    private function createUser(string $email, int $userType = 4): User
    {
        return User::create([
            'user' => $email,
            'password' => 'secret',
            'attivo' => 'Y',
            'user_type' => $userType,
            'email' => $email,
        ]);
    }
}
