<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
#[Title('Il mio profilo')]
class Profilo extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $cognome = '';

    public string $email = '';

    public string $cellulare = '';

    public $foto = null;

    public string $password_attuale = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = $this->utente();
        $this->name = $user->name;
        $this->cognome = $user->cognome ?? '';
        $this->email = $user->email;
        $this->cellulare = $user->cellulare ?? '';
    }

    public function salvaProfilo(): void
    {
        $user = $this->utente();
        $dati = $this->validate([
            'name' => 'required|string|max:30',
            'cognome' => 'required|string|max:30',
            'email' => ['required', 'email', 'max:40', Rule::unique('tc_admin', 'email')->ignore($user->id)],
            'cellulare' => 'nullable|string|max:20',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $aggiornamenti = [
            'name' => $dati['name'],
            'cognome' => $dati['cognome'],
            'email' => $dati['email'],
            'cellulare' => $dati['cellulare'] ?: null,
        ];

        if ($this->foto) {
            $this->eliminaFotoEsistente($user);
            $nome = 'avatar_'.$user->id.'.'.strtolower($this->foto->getClientOriginalExtension());
            Storage::disk(config('filesystems.files_disk'))->putFileAs(config('filesystems.private_paths.avatars'), $this->foto, $nome);
            $aggiornamenti['foto_path'] = $nome;
            $this->foto = null;
        }

        $user->update($aggiornamenti);
        session()->flash('profilo', 'Profilo aggiornato.');
    }

    public function rimuoviFoto(): void
    {
        $user = $this->utente();
        $this->eliminaFotoEsistente($user);
        $user->update(['foto_path' => null]);
        $this->foto = null;
        session()->flash('profilo', 'Foto rimossa.');
    }

    public function cambiaPassword(): void
    {
        $this->validate([
            'password_attuale' => 'required',
            'password' => 'required|string|min:6|max:40|confirmed|different:password_attuale',
        ], [], ['password_attuale' => 'password attuale', 'password' => 'nuova password']);

        if (! Hash::check($this->password_attuale, (string) $this->utente()->getRawOriginal('password'))) {
            $this->addError('password_attuale', 'La password attuale non è corretta.');

            return;
        }

        $this->utente()->update(['password' => $this->password]);
        $this->reset('password_attuale', 'password', 'password_confirmation');
        session()->flash('password', 'Password aggiornata.');
    }

    private function utente(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function eliminaFotoEsistente(User $user): void
    {
        if ($user->foto_path) {
            Storage::disk(config('filesystems.files_disk'))->delete(trim(config('filesystems.private_paths.avatars'), '/').'/'.basename($user->foto_path));
        }
    }

    public function render()
    {
        return view('livewire.profilo', ['utente' => $this->utente()]);
    }
}
