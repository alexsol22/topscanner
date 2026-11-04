<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConAccesso;
use App\Models\Legacy\Codice;
use App\Models\Legacy\Documento;
use App\Models\Legacy\Postazione;
use App\Models\Legacy\Turno;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Sedi')]
class Sedi extends Component
{
    use ConAccesso, WithPagination;

    public string $cerca = '';

    #[Url(as: 'cliente')]
    public ?int $filtroCliente = null;

    public bool $modalPostazioni = false;

    public ?int $sedeQr = null;

    public ?int $sedePostazioni = null;

    public array $p = ['titolo' => '', 'minuti_passaggio' => ''];

    public function updatingCerca(): void
    {
        $this->resetPage();
    }

    public function elimina(int $id): void
    {
        $this->soloStaff();
        $sede = $this->sediVisibili()->findOrFail($id);

        Documento::where('tipo', 'S')->where('id_tipo', $sede->id)->delete();
        Turno::where('id_sede', $sede->id)->delete();
        Postazione::where('id_sede', $sede->id)->delete();
        $sede->delete();
    }

    public function qr(int $id): void
    {
        $this->soloStaff();
        $this->sedeQr = $this->sediVisibili()->findOrFail($id)->id;
    }

    public function chiudiQr(): void
    {
        $this->sedeQr = null;
    }

    public function postazioni(int $id): void
    {
        $this->sedePostazioni = $this->sediVisibili()->findOrFail($id)->id;
        $this->p = ['titolo' => '', 'minuti_passaggio' => ''];
        $this->resetValidation();
        $this->modalPostazioni = true;
    }

    public function aggiungiPostazione(): void
    {
        $this->soloStaff();
        $this->validate([
            'p.titolo' => 'required|string|max:255',
            'p.minuti_passaggio' => 'nullable|integer|min:1|max:1440',
        ]);
        $sede = $this->sediVisibili()->findOrFail($this->sedePostazioni);

        $post = Postazione::create([
            'id_sede' => $sede->id, 'id_cliente' => $sede->id_cliente,
            'titolo' => $this->p['titolo'], 'minuti' => $this->p['minuti_passaggio'] ?: null,
        ]);
        $post->update(['id_codice' => Codice::nuovo(Codice::POSTAZIONE, $sede->id, $post->id)->id]);
        $this->p = ['titolo' => '', 'minuti_passaggio' => ''];
    }

    public function eliminaPostazione(int $id): void
    {
        $this->soloStaff();
        Postazione::whereIn('id_sede', $this->sediVisibili()->select('tc_sedi.id'))->findOrFail($id)->delete();
    }

    public function render()
    {
        $sedi = $this->sediVisibili()->with(['cliente', 'comune'])->withCount('postazioni')
            ->when($this->filtroCliente, fn ($q) => $q->where('id_cliente', $this->filtroCliente))
            ->when($this->cerca, fn ($q) => $q->where(fn ($w) => $w->where('descrizione', 'like', "%{$this->cerca}%")
                ->orWhereHas('comune', fn ($c) => $c->where('comune', 'like', "%{$this->cerca}%"))))
            ->orderBy('descrizione')->paginate(9);

        return view('livewire.sedi', [
            'sedi' => $sedi,
            'clienti' => $this->clientiVisibili()->orderBy('ragione_sociale')->get(['id', 'ragione_sociale']),
            'sedeCorrente' => $this->sedePostazioni ? $this->sediVisibili()->with('postazioni.codice', 'codice')->find($this->sedePostazioni) : null,
        ]);
    }
}
