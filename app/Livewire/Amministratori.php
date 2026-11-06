<?php

namespace App\Livewire;

use App\Enums\Ruolo;
use App\Models\Legacy\Tracking;
use App\Models\User;
use App\Services\Comunicazioni;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Amministratori')]
class Amministratori extends Component
{
    use WithPagination;

    public string $cerca = '';

    public bool $modal = false;

    public ?int $userId = null;

    public array $f = [];

    public function mount(): void
    {
        Tracking::registra('Visualizzazione elenco utenti ');
    }

    public function updatingCerca(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'f.name' => 'required|string|max:30',
            'f.cognome' => 'required|string|max:30',
            'f.email' => ['required', 'email', 'max:40', 'max:255', Rule::unique('tc_admin', 'email')->ignore($this->userId)],
            'f.cellulare' => 'required|regex:/^\s*[+-]?\d+\s*$/|max:20',
            'f.password' => [$this->userId ? 'nullable' : 'required', 'string', 'min:8', 'max:40'],
        ];
    }

    public function nuovo(): void
    {
        $this->soloStaff();
        $this->userId = null;
        $this->f = ['name' => '', 'cognome' => '', 'email' => '', 'cellulare' => '', 'password' => ''];
        $this->resetValidation();
        $this->modal = true;
    }

    public function modifica(int $id): void
    {
        $this->soloStaff();
        $user = $this->amministratori()->findOrFail($id);
        $this->userId = $user->id;
        $this->f = [
            'name' => $user->name,
            'cognome' => $user->cognome ?? '',
            'email' => $user->email,
            'cellulare' => $user->cellulare ?? '',
            'password' => '',
        ];
        $this->resetValidation();
        $this->modal = true;
    }

    public function salva(): void
    {
        $this->soloStaff();
        $this->validate();

        $dati = [
            'name' => $this->f['name'],
            'cognome' => $this->f['cognome'],
            'email' => $this->f['email'],
            'cellulare' => $this->f['cellulare'] ?: null,
            'ruolo' => Ruolo::Admin->value,
        ];

        if ($this->userId) {
            $user = $this->amministratori()->findOrFail($this->userId);
            if ($this->f['password'] !== '') {
                $dati['password'] = $this->f['password'];
            }
            $user->update($dati);
            Tracking::registra('Modifica operatore '.$user->user);
        } else {
            $user = User::create($dati + ['password' => $this->f['password'], 'attivo' => true]);
            Tracking::registra('Nuovo utente '.$user->user);

            // Come Yii (sendEmailAccessi): alla creazione l'utente riceve le credenziali provvisorie.
            app(Comunicazioni::class)->inviaCredenziali($user->nome_completo, (string) $user->user, (string) $user->email, $this->f['password']);
        }

        $this->modal = false;
    }

    public function attivaDisattiva(int $id): void
    {
        $this->soloStaff();
        $user = $this->amministratori()->findOrFail($id);
        $user->update(['attivo' => ! $user->attivo]);
        Tracking::registra(($user->attivo ? 'Abilitazione ' : 'Disabilitazione ').' elenco utenti '.$user->user);
    }

    private function soloStaff(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->attivo && in_array($user->ruolo, [Ruolo::SuperAdmin, Ruolo::Admin], true), 403);
    }

    private function amministratori(): Builder
    {
        return User::query()->where('user_type', 2);
    }

    public function render()
    {
        $this->soloStaff();

        $amministratori = $this->amministratori()
            ->when($this->cerca, fn (Builder $query) => $query->where(fn (Builder $where) => $where
                ->where('nome', 'like', "%{$this->cerca}%")
                ->orWhere('cognome', 'like', "%{$this->cerca}%")
                ->orWhere('email', 'like', "%{$this->cerca}%")))
            ->orderBy('cognome')
            ->orderBy('nome')
            ->paginate(10);

        return view('livewire.amministratori', compact('amministratori'));
    }
}
