<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConAccesso;
use App\Services\QrAziendale as Generatore;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
#[Title('Generatore QR')]
class GeneratoreQr extends Component
{
    use ConAccesso, WithFileUploads;

    public string $contenuto = '';

    public string $titolo = 'Omnia Control';

    public string $colore = '#0369a1';

    public $logo;

    public ?int $clienteId = null;

    public ?int $sedeId = null;

    /** Quando è incorporato nella pagina Sedi il QR è legato a una sede e non si può cambiare. */
    public bool $bloccato = false;

    public function mount(?int $sede = null): void
    {
        abort_unless(auth()->user()->isStaff(), 403);

        if ($sede) {
            $s = $this->sediVisibili()->with('codice')->findOrFail($sede);
            $this->bloccato = true;
            $this->clienteId = $s->id_cliente;
            $this->sedeId = $s->id;
            $this->contenuto = $s->codice?->codice ?? '';
            $this->titolo = mb_substr($s->descrizione, 0, 40);
        }
    }

    public function updatedClienteId(): void
    {
        abort_if($this->bloccato, 403);
        $this->sedeId = null;
        $this->contenuto = '';
    }

    public function updatedSedeId(): void
    {
        abort_if($this->bloccato, 403);
        $sede = $this->sedeSelezionata();
        $this->contenuto = $sede?->codice?->codice ?? '';

        if ($sede) {
            $this->titolo = $sede->descrizione;
        }
    }

    public function rimuoviLogo(): void
    {
        $this->logo = null;
    }

    private function sedeSelezionata()
    {
        return $this->sedeId ? $this->sediVisibili()->with('codice')->find($this->sedeId) : null;
    }

    public function render(Generatore $gen)
    {
        $this->validate([
            'titolo' => 'nullable|string|max:40',
            'colore' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $logoUri = $this->logo ? 'data:'.$this->logo->getMimeType().';base64,'.base64_encode($this->logo->get()) : null;

        return view('livewire.generatore-qr', [
            'svg' => $gen->svg($this->contenuto !== '' ? $this->contenuto : ($this->titolo ?: 'Omnia Control'), $this->titolo, $logoUri, $this->colore),
            'clienti' => $this->clientiVisibili()->orderBy('ragione_sociale')->get(['id', 'ragione_sociale']),
            'sedi' => $this->clienteId ? $this->sediVisibili()->where('id_cliente', $this->clienteId)->orderBy('descrizione')->get(['id', 'descrizione']) : collect(),
        ]);
    }
}
