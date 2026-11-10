<?php

namespace App\Livewire;

use App\Models\Legacy\Codice;
use App\Models\User;
use App\Services\QrAziendale;
use App\Services\QrSede;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
#[Title('Generatore QR')]
class GeneratoreQr extends Component
{
    use WithFileUploads;

    public ?int $codiceId = null;

    public string $colore = '#000000';

    public $logo;

    public function mount(): void
    {
        $this->autorizzaStaff();
    }

    public function selezionaCodice(int $codiceId): void
    {
        $this->autorizzaStaff();
        Codice::query()->findOrFail($codiceId);

        $this->codiceId = $codiceId;
        $this->colore = '#000000';
        $this->logo = null;
        $this->resetValidation();
    }

    public function salvaImmagine(QrSede $qr): void
    {
        $this->autorizzaStaff();
        $dati = $this->validate([
            'codiceId' => ['required', 'integer', 'exists:tc_codici,id'],
            'colore' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);
        $codice = Codice::query()->findOrFail($dati['codiceId']);
        $contenutoLogo = $this->logo?->get();

        $qr->generaPersonalizzato($codice->codice, $dati['colore'], $contenutoLogo);

        session()->flash('qr', "Immagine QR aggiornata per {$codice->codice}. Il codice e l'associazione non sono stati modificati.");
    }

    public function render(QrSede $qr, QrAziendale $generatore)
    {
        $this->autorizzaStaff();
        $disco = Storage::disk(config('filesystems.files_disk'));
        $codici = Codice::query()
            ->whereNotNull('codice')
            ->where('codice', '!=', '')
            ->with(['sede', 'postazione'])
            ->orderBy('codice')
            ->get()
            ->map(fn (Codice $codice): array => [
                'modello' => $codice,
                'immaginePresente' => $disco->exists($qr->percorso($codice->codice)),
            ]);

        $selezionato = $this->codiceId ? $codici->firstWhere('modello.id', $this->codiceId) : null;
        $logoDataUri = $this->logo
            ? 'data:'.$this->logo->getMimeType().';base64,'.base64_encode($this->logo->get())
            : null;

        return view('livewire.generatore-qr', [
            'codici' => $codici,
            'selezionato' => $selezionato,
            'svg' => $selezionato
                ? $generatore->svg($selezionato['modello']->codice, '', $logoDataUri, $this->colore)
                : null,
        ]);
    }

    private function autorizzaStaff(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->isStaff(), 403);
    }
}
