<?php

namespace App\Livewire;

use App\Mail\NotificaHtml;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::guest')]
class RecuperaPassword extends Component
{
    #[Validate('required', as: 'email')]
    public string $email = '';

    public bool $inviata = false;

    public function recupera(): void
    {
        $this->validate();
        $chiave = 'recupero|'.Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($chiave, 3)) {
            $this->addError('email', 'Troppe richieste. Riprova tra '.RateLimiter::availableIn($chiave).' secondi.');

            return;
        }

        RateLimiter::hit($chiave, 600);

        $utente = User::where('user', $this->email)->where('attivo', 'Y')->first();

        if ($utente && filter_var($utente->email, FILTER_VALIDATE_EMAIL)) {
            $this->inviaNuovaPassword($utente);
        }

        $this->inviata = true;
    }

    private function inviaNuovaPassword(User $utente): void
    {
        $password = Str::password(10, symbols: false);

        try {
            DB::transaction(function () use ($utente, $password) {
                $utente->update(['password' => $password]);
                Mail::to($utente->email, trim($utente->nome.' '.$utente->cognome) ?: null)
                    ->send(new NotificaHtml('Recupero password', $this->corpoEmail($utente, $password), ['etichetta' => 'Vai al login', 'url' => route('login')]));
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function corpoEmail(User $utente, string $password): string
    {
        return view('emails.recupero-password', [
            'nome' => trim($utente->nome.' '.$utente->cognome),
            'utente' => $utente->user,
            'password' => $password,
        ])->render();
    }

    public function render()
    {
        return view('livewire.recupera-password');
    }
}
